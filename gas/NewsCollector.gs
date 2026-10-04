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
// prtimes.jp 検索（Google News RSS 経由）
// prtimes.jp はSPAのため直接スクレイピング不可。
// Google News RSS で site:prtimes.jp キーワード を検索する。
// ─────────────────────────────────────────────────────────────

function ncSearchPrTimes(keyword) {
  var url = 'https://news.google.com/rss/search?q=' +
            encodeURIComponent('site:prtimes.jp ' + keyword) +
            '&hl=ja&gl=JP&ceid=JP:ja';
  return ncFetchGNewsRSS(url, 'PR TIMES');
}

/**
 * Google News RSS を取得して記事配列に変換。
 * CDATA を正しく扱うためテキストパースを使用する。
 * description 内の <a href> から元記事URLを抽出する。
 */
function ncFetchGNewsRSS(rssUrl, defaultSource) {
  var res;
  try {
    res = UrlFetchApp.fetch(rssUrl, {
      muteHttpExceptions: true,
      followRedirects: true,
      headers: { 'User-Agent': 'Mozilla/5.0 (compatible; GreigeBot/1.0)' },
    });
  } catch(e) {
    Logger.log('GNews RSS fetch error: ' + e.message);
    return [];
  }
  if (res.getResponseCode() !== 200) {
    Logger.log('GNews RSS HTTP ' + res.getResponseCode() + ': ' + rssUrl);
    return [];
  }

  var text     = res.getContentText();
  var articles = [];

  // <item>...</item> を正規表現で抽出（CDATA対応）
  var itemRe = /<item>([\s\S]*?)<\/item>/gi;
  var im;
  while ((im = itemRe.exec(text)) !== null && articles.length < NC_MAX_PER_KEYWORD) {
    var block = im[1];

    // <title>: CDATA or plain text
    var titleM = block.match(/<title[^>]*>(?:<!\[CDATA\[)?([\s\S]*?)(?:\]\]>)?<\/title>/i);
    var title  = titleM ? ncStripTags(titleM[1]).trim() : '';
    if (!title) continue;

    // <description>: CDATA の中に <a href="元記事URL"> が入っている
    var descM = block.match(/<description[^>]*>(?:<!\[CDATA\[)?([\s\S]*?)(?:\]\]>)?<\/description>/i);
    var desc  = descM ? descM[1] : '';

    // <link>: Google News URL（CBMi...をbase64デコードして元URLを復元）
    var linkM  = block.match(/<link>([\s\S]*?)<\/link>/i);
    var gnLink = linkM ? linkM[1].trim() : '';

    var link = gnLink;
    if (!link) continue;

    // <pubDate>
    var dateM   = block.match(/<pubDate>([\s\S]*?)<\/pubDate>/i);
    var pubDate = dateM ? dateM[1].trim() : '';

    // <source>: 配信元名
    var srcM       = block.match(/<source[^>]*>([\s\S]*?)<\/source>/i);
    var sourceName = srcM ? ncStripTags(srcM[1]).trim() : defaultSource;
    if (!sourceName) sourceName = defaultSource;

    articles.push({
      title:       title,
      source_url:  link,
      source_name: sourceName,
      news_date:   ncParsePubDate(pubDate),
      summary:     ncStripTags(desc.replace(/&lt;/g,'<').replace(/&gt;/g,'>').replace(/&amp;/g,'&')).slice(0, 300),
      image_url:   '',
    });
  }

  return articles;
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
  // 取得失敗 or 空なら Google News RSS にフォールバック
  if (!html) {
    var rssUrl = 'https://news.google.com/rss/search?q=' +
                 encodeURIComponent('site:atpress.ne.jp ' + keyword) +
                 '&hl=ja&gl=JP&ceid=JP:ja';
    return ncFetchGNewsRSS(rssUrl, 'AT PRESS');
  }

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

  // フォールバック1: /releases/数字 リンクを直接抽出
  if (articles.length === 0) {
    articles = ncParseAtPressSimple(html, seen);
  }

  // フォールバック2: それでも0件なら Google News RSS
  if (articles.length === 0) {
    var rssUrl = 'https://news.google.com/rss/search?q=' +
                 encodeURIComponent('site:atpress.ne.jp ' + keyword) +
                 '&hl=ja&gl=JP&ceid=JP:ja';
    articles = ncFetchGNewsRSS(rssUrl, 'AT PRESS');
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
 * Google News RSS 経由の prtimes.jp 検索テスト。
 * GASエディタで実行 → 件数と記事タイトルが出れば成功。
 */
function ncDebugPrTimes() {
  var keyword = 'ファッション';
  var articles = ncSearchPrTimes(keyword);
  Logger.log('prtimes.jp「' + keyword + '」→ ' + articles.length + '件');
  articles.forEach(function(a) {
    Logger.log(a.news_date + ' | ' + a.title.slice(0, 60) + '\n  ' + a.source_url);
  });
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
