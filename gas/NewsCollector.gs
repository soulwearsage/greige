/**
 * GREIGE MAGAZINE - ニュース収集
 *
 * NEWS_KEYWORDS スプレッドシートからキーワードを読み込み、
 * prtimes.jp と atpress.ne.jp を検索して NEWS_POOL に保存する。
 *
 * ■ Script Properties
 *   NEWS_KEYWORDS_ID : NEWS_KEYWORDSスプレッドシートID
 *   NEWS_POOL_ID     : NEWS_POOLスプレッドシートID
 *
 * ■ 使い方
 *   「▶ ニュースを収集」を実行（1回あたり最大30件×2サイト）
 *   毎日自動実行する場合はトリガーで collectNews() を設定
 */

var NC_KEYWORDS_ID = '1H5WtyDPXzxdnzj7RCnt0EggipQmIyCZfn58MIK3gFJE';
var NC_POOL_ID     = '11e6LwzXi-5_1GyLz35zRV4e0pUPbq63fdB1NwJtWXMI';
var NC_POOL_TAB    = 'NEWS_POOL';

// 1回の実行でキーワードあたり取得する最大記事数
var NC_MAX_PER_KEYWORD = 5;
// 1回の実行で処理するキーワード数の上限（タイムアウト対策）
var NC_MAX_KEYWORDS = 20;

// ─────────────────────────────────────────────────────────────
// メニュー
// ─────────────────────────────────────────────────────────────

function onOpenNewsCollector() {
  SpreadsheetApp.getActiveSpreadsheet().addMenu('ニュース収集', [
    { name: '▶ ニュースを収集', functionName: 'collectNews' },
    { name: '▶ ドライラン（取得のみ・保存しない）', functionName: 'collectNewsDryRun' },
  ]);
}

// ─────────────────────────────────────────────────────────────
// エントリーポイント
// ─────────────────────────────────────────────────────────────

function collectNewsDryRun() { ncCollect(true); }
function collectNews()       { ncCollect(false); }

function ncCollect(dryRun) {
  var props   = PropertiesService.getScriptProperties();
  var kwId    = props.getProperty('NEWS_KEYWORDS_ID') || NC_KEYWORDS_ID;
  var poolId  = props.getProperty('NEWS_POOL_ID')     || NC_POOL_ID;
  var poolTab = props.getProperty('NEWS_POOL_TAB')    || NC_POOL_TAB;

  var keywords = ncLoadKeywords(kwId);
  if (!keywords.length) {
    Logger.log('有効なキーワードがありません');
    return;
  }

  var pool       = ncLoadPool(poolId, poolTab);
  var existUrls  = pool.existUrls;
  var sheet      = pool.sheet;
  var header     = pool.header;

  var saved  = 0;
  var tried  = 0;
  var errors = [];

  for (var i = 0; i < keywords.length && tried < NC_MAX_KEYWORDS; i++) {
    var kw = keywords[i];
    tried++;

    var articles = [];
    try {
      var pr = ncSearchPrTimes(kw.keyword);
      articles = articles.concat(pr);
    } catch (e) {
      errors.push('PRTimes[' + kw.keyword + ']: ' + e.message);
    }
    try {
      var at = ncSearchAtPress(kw.keyword);
      articles = articles.concat(at);
    } catch (e) {
      errors.push('AtPress[' + kw.keyword + ']: ' + e.message);
    }

    for (var j = 0; j < articles.length; j++) {
      var art = articles[j];
      if (!art.source_url || existUrls[art.source_url]) continue;

      art.category        = kw.category;
      art.matched_keyword = kw.keyword;
      art.keywords        = kw.keyword;
      art.collected_date  = Utilities.formatDate(new Date(), 'Asia/Tokyo', 'yyyy-MM-dd');
      art.status          = 'NEW';
      art.created_at      = Utilities.formatDate(new Date(), 'Asia/Tokyo', 'yyyy-MM-dd HH:mm:ss');
      art.updated_at      = art.created_at;
      art.news_id         = ncGenId();

      existUrls[art.source_url] = true;

      if (!dryRun) {
        ncAppendRow(sheet, header, art);
      }
      saved++;
      Logger.log((dryRun ? '[DRY] ' : '') + art.title + ' / ' + art.source_url);
    }

    Utilities.sleep(500);
  }

  var msg = (dryRun ? '【ドライラン】' : '【収集完了】') + '\n' +
    'キーワード処理数: ' + tried + '\n' +
    '新規記事: ' + saved + '\n' +
    (errors.length ? 'エラー(' + errors.length + '):\n' + errors.slice(0, 5).join('\n') : '');
  Logger.log(msg);
  try { SpreadsheetApp.getUi().alert(msg); } catch(e) {}
}

// ─────────────────────────────────────────────────────────────
// キーワード読み込み
// ─────────────────────────────────────────────────────────────

function ncLoadKeywords(ssId) {
  var ss       = SpreadsheetApp.openById(ssId);
  var tabs     = ['BRAND', 'PERSON', 'CULTURE', 'TOPIC'];
  var keywords = [];

  tabs.forEach(function(tabName) {
    var sheet = ss.getSheetByName(tabName);
    if (!sheet) return;

    var lastRow = sheet.getLastRow();
    if (lastRow < 2) return;

    var values = sheet.getRange(1, 1, lastRow, sheet.getLastColumn()).getValues();
    var header = values[0];
    var hmap   = {};
    header.forEach(function(h, i) { hmap[String(h).trim()] = i; });

    for (var r = 1; r < values.length; r++) {
      var row     = values[r];
      var keyword = String(row[hmap['keyword'] || 0] || '').trim();
      var enabled = row[hmap['enabled'] !== undefined ? hmap['enabled'] : -1];
      var category= String(row[hmap['category'] !== undefined ? hmap['category'] : -1] || '').trim();

      if (!keyword) continue;
      if (enabled === false || String(enabled).toUpperCase() === 'FALSE') continue;

      keywords.push({ keyword: keyword, category: category, type: tabName });
    }
  });

  return keywords;
}

// ─────────────────────────────────────────────────────────────
// NEWS_POOL 読み込み
// ─────────────────────────────────────────────────────────────

function ncLoadPool(ssId, tabName) {
  var ss    = SpreadsheetApp.openById(ssId);
  var sheet = ss.getSheetByName(tabName);
  if (!sheet) throw new Error('NEWS_POOLタブが見つかりません');

  var existUrls = {};
  var lastRow   = sheet.getLastRow();

  var header = [];
  if (lastRow >= 1) {
    var lastCol = sheet.getLastColumn();
    if (lastCol >= 1) {
      header = sheet.getRange(1, 1, 1, lastCol).getValues()[0].map(function(v) { return String(v).trim(); });
    }
  }

  if (lastRow >= 2 && header.length > 0) {
    var urlIdx = header.indexOf('source_url');
    if (urlIdx >= 0) {
      var urls = sheet.getRange(2, urlIdx + 1, lastRow - 1, 1).getValues();
      urls.forEach(function(row) {
        var u = String(row[0]).trim();
        if (u) existUrls[u] = true;
      });
    }
  }

  return { sheet: sheet, header: header, existUrls: existUrls };
}

// ─────────────────────────────────────────────────────────────
// 行追加
// ─────────────────────────────────────────────────────────────

function ncAppendRow(sheet, header, art) {
  var row = header.map(function(col) { return art[col] || ''; });
  sheet.appendRow(row);
}

// ─────────────────────────────────────────────────────────────
// prtimes.jp 検索（RSSキャッシュ方式）
// 1実行でRSSを1回だけ取得し、キーワードでフィルタリングする
// ─────────────────────────────────────────────────────────────

// 実行中のRSSキャッシュ（同一実行内で使い回す）
var NC_PRTIMES_RSS_CACHE = null;

function ncGetPrTimesRSS() {
  if (NC_PRTIMES_RSS_CACHE !== null) return NC_PRTIMES_RSS_CACHE;
  NC_PRTIMES_RSS_CACHE = ncFetchRSSArticles('https://prtimes.jp/rss/', 'PR TIMES');
  return NC_PRTIMES_RSS_CACHE;
}

function ncSearchPrTimes(keyword) {
  var all = ncGetPrTimesRSS();
  return ncFilterArticlesByKeyword(all, keyword);
}

/** RSS/Atom フィードを取得して記事配列に変換 */
function ncFetchRSSArticles(rssUrl, defaultSource) {
  var res;
  try {
    res = UrlFetchApp.fetch(rssUrl, {
      muteHttpExceptions: true,
      followRedirects: true,
      headers: { 'User-Agent': 'Mozilla/5.0 (compatible; GreigeBot/1.0)' },
    });
  } catch(e) {
    Logger.log('RSS fetch error: ' + rssUrl + ' / ' + e.message);
    return [];
  }
  if (res.getResponseCode() !== 200) {
    Logger.log('RSS HTTP ' + res.getResponseCode() + ': ' + rssUrl);
    return [];
  }

  var articles = [];
  try {
    var doc     = XmlService.parse(res.getContentText());
    var root    = doc.getRootElement();
    var ns      = root.getNamespace();
    // RSS 2.0: <rss><channel><item>
    var channel = root.getChild('channel');
    var items   = channel ? channel.getChildren('item') : [];
    // Atom: <feed><entry>
    if (items.length === 0) items = root.getChildren('entry', ns);

    items.forEach(function(item) {
      var title   = (item.getChildText('title')   || item.getChildText('title', ns)   || '').trim();
      var link    = (item.getChildText('link')     || '').trim();
      // Atom の <link href="...">
      if (!link) {
        var linkEl = item.getChild('link', ns);
        if (linkEl) link = (linkEl.getAttribute('href') || linkEl).toString().trim();
      }
      var desc    = (item.getChildText('description') || item.getChildText('summary', ns) || '').trim();
      var pubDate = (item.getChildText('pubDate')  || item.getChildText('updated', ns)  || '').trim();

      title = ncStripTags(title);
      if (!title || !link) return;

      articles.push({
        title:       title,
        source_url:  link,
        source_name: defaultSource,
        news_date:   ncParsePubDate(pubDate),
        summary:     ncStripTags(desc).slice(0, 300),
        image_url:   '',
      });
    });
  } catch(e) {
    Logger.log('RSS parse error: ' + rssUrl + ' / ' + e.message);
  }
  return articles;
}

/** キーワードでフィルタリング */
function ncFilterArticlesByKeyword(articles, keyword) {
  var kw = keyword.toLowerCase();
  var results = [];
  for (var i = 0; i < articles.length && results.length < NC_MAX_PER_KEYWORD; i++) {
    var art = articles[i];
    var text = (art.title + ' ' + art.summary).toLowerCase();
    if (text.indexOf(kw) !== -1) results.push(art);
  }
  return results;
}

function ncParsePubDate(pubDate) {
  if (!pubDate) return '';
  try {
    var d = new Date(pubDate);
    if (isNaN(d.getTime())) return '';
    return Utilities.formatDate(d, 'Asia/Tokyo', 'yyyy-MM-dd');
  } catch(e) { return ''; }
}

// ─────────────────────────────────────────────────────────────
// atpress.ne.jp 検索
// ─────────────────────────────────────────────────────────────

function ncSearchAtPress(keyword) {
  var url = 'https://www.atpress.ne.jp/search?keyword=' +
            encodeURIComponent(keyword) + '&sort=date';
  var html = ncFetch(url);
  if (!html) return [];

  var articles = [];
  var seen     = {};

  // パターン1: li.p-result__item
  var blockRe = /<li[^>]*class="[^"]*(?:result|item|release)[^"]*"[^>]*>([\s\S]*?)<\/li>/gi;
  var m;
  while ((m = blockRe.exec(html)) !== null && articles.length < NC_MAX_PER_KEYWORD) {
    var item = ncExtractAtPressItem(m[1], seen);
    if (item) { articles.push(item); seen[item.source_url] = true; }
  }

  // パターン2: article ブロック
  if (articles.length === 0) {
    var artRe = /<article[^>]*>([\s\S]*?)<\/article>/gi;
    while ((m = artRe.exec(html)) !== null && articles.length < NC_MAX_PER_KEYWORD) {
      var item = ncExtractAtPressItem(m[1], seen);
      if (item) { articles.push(item); seen[item.source_url] = true; }
    }
  }

  // フォールバック: /releases/数字 リンクを直接抽出
  if (articles.length === 0) {
    articles = ncParseAtPressSimple(html, seen);
  }

  return articles;
}

function ncExtractAtPressItem(block, seen) {
  var linkRe = /<a[^>]+href="(\/releases\/\d+)"[^>]*>([\s\S]*?)<\/a>/i;
  var lm = block.match(linkRe);
  if (!lm) return null;

  var relUrl  = lm[1];
  var fullUrl = 'https://www.atpress.ne.jp' + relUrl;
  if (seen && seen[fullUrl]) return null;

  // タイトル: リンクテキスト or class="*title*"
  var title = ncStripTags(lm[2]).trim();
  if (title.length < 5) {
    var tm = block.match(/class="[^"]*title[^"]*"[^>]*>([\s\S]*?)<\/(?:h[1-6]|p|span|div)/i);
    if (tm) title = ncStripTags(tm[1]).trim();
  }
  if (!title || title.length < 5) return null;

  var datm     = block.match(/<time[^>]+datetime="([^"]+)"/i);
  var newsDate = datm ? datm[1].slice(0, 10) : '';

  var compRe    = /class="[^"]*(?:company|corp|name)[^"]*"[^>]*>([\s\S]*?)<\/(?:span|div|p|a)/i;
  var cm        = block.match(compRe);
  var sourceName= cm ? ncStripTags(cm[1]).trim() : 'AT PRESS';
  if (!sourceName || sourceName.length < 2) sourceName = 'AT PRESS';

  var sumRe  = /class="[^"]*(?:summary|lead|body|text)[^"]*"[^>]*>([\s\S]*?)<\/(?:p|div|span)/i;
  var sm     = block.match(sumRe);
  var summary= sm ? ncStripTags(sm[1]).trim() : '';

  var imgRe  = /<img[^>]+src="(https?:[^"]+(?:\.jpe?g|\.png|\.webp)[^"]*)"[^>]*/i;
  var im     = block.match(imgRe);
  var imgUrl = im ? im[1] : '';

  return {
    title:       title,
    source_url:  fullUrl,
    source_name: sourceName,
    news_date:   newsDate,
    summary:     summary,
    image_url:   imgUrl,
  };
}

function ncParseAtPressSimple(html, seen) {
  var articles = [];
  var re = /<a[^>]+href="(\/releases\/\d+)"[^>]*>([\s\S]*?)<\/a>/gi;
  var m;
  while ((m = re.exec(html)) !== null && articles.length < NC_MAX_PER_KEYWORD) {
    var relUrl  = m[1];
    var fullUrl = 'https://www.atpress.ne.jp' + relUrl;
    if (seen && seen[fullUrl]) continue;
    var title = ncStripTags(m[2]).trim();
    if (title.length < 5) continue;
    articles.push({
      title:       title,
      source_url:  fullUrl,
      source_name: 'AT PRESS',
      news_date:   '',
      summary:     '',
      image_url:   '',
    });
  }
  return articles;
}

// ─────────────────────────────────────────────────────────────
// ユーティリティ
// ─────────────────────────────────────────────────────────────

function ncFetch(url) {
  try {
    var res = UrlFetchApp.fetch(url, {
      muteHttpExceptions: true,
      followRedirects: true,
      headers: { 'User-Agent': 'Mozilla/5.0 (compatible; GreigeBot/1.0)' },
    });
    if (res.getResponseCode() !== 200) return null;
    return res.getContentText();
  } catch (e) {
    Logger.log('fetch error: ' + url + ' / ' + e.message);
    return null;
  }
}

function ncStripTags(s) {
  return String(s || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
}

function ncGenId() {
  return 'NW' + new Date().getTime() + Math.floor(Math.random() * 1000);
}

// ─────────────────────────────────────────────────────────────
// デバッグ用：実際のHTMLを確認する
// ─────────────────────────────────────────────────────────────

/**
 * prtimes.jp RSSフィードの動作確認。
 * GASエディタで実行 → STATUS: 200 と記事タイトル一覧が出れば成功。
 */
function ncDebugPrTimes() {
  var rssUrl = 'https://prtimes.jp/rss/';
  try {
    var res = UrlFetchApp.fetch(rssUrl, {
      muteHttpExceptions: true,
      followRedirects: true,
      headers: { 'User-Agent': 'Mozilla/5.0 (compatible; GreigeBot/1.0)' },
    });
    Logger.log('STATUS: ' + res.getResponseCode() + '  URL: ' + rssUrl);
    if (res.getResponseCode() === 200) {
      var articles = ncFetchRSSArticles(rssUrl, 'PR TIMES');
      Logger.log('取得件数: ' + articles.length);
      articles.slice(0, 5).forEach(function(a) {
        Logger.log(a.news_date + ' | ' + a.title.slice(0, 60));
      });
      // キーワードフィルタのテスト
      var kw = 'ファッション';
      var filtered = ncFilterArticlesByKeyword(articles, kw);
      Logger.log('「' + kw + '」でフィルタ → ' + filtered.length + '件');
    } else {
      Logger.log('本文先頭500文字:');
      Logger.log(res.getContentText().slice(0, 500));
    }
  } catch(e) {
    Logger.log('ERROR: ' + e.message);
  }
}

function ncDebugAtPress() {
  var url = 'https://www.atpress.ne.jp/search?keyword=' +
            encodeURIComponent('Adidas') + '&sort=date';
  var html = ncFetch(url);
  if (!html) { Logger.log('取得失敗'); return; }
  Logger.log('=== URL ===');
  Logger.log(url);
  Logger.log('=== HTML 先頭3000文字 ===');
  Logger.log(html.slice(0, 3000));
  Logger.log('=== releases を含む行 ===');
  var lines = html.split('\n');
  lines.forEach(function(line) {
    if (line.indexOf('/releases/') !== -1 || line.indexOf('release') !== -1) {
      Logger.log(line.trim().slice(0, 200));
    }
  });
}
