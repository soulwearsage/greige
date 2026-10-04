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
// 1回のループで収集する記事の合計上限
var NC_MAX_TOTAL = 100;
// collectNewsLooped() が繰り返す回数
var NC_COLLECT_LOOPS = 5;
// 画像が取れなかった記事は保存しない（ボードに NO IMAGE を出さないため）
var NC_REQUIRE_IMAGE = true;
// キーワード未一致の記事も埋め草として収集するか
var NC_INCLUDE_UNMATCHED = false;
// 1実行の時間上限（GASの6分制限に対する安全マージン）
var NC_TIME_BUDGET_MS = 290 * 1000;

// ボットUAだと弾くサイトがあるのでブラウザのUAを使う
var NC_UA = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 ' +
            '(KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

// ─────────────────────────────────────────────────────────────
// メニュー
// ─────────────────────────────────────────────────────────────

function onOpenNewsCollector() {
  SpreadsheetApp.getActiveSpreadsheet().addMenu('ニュース収集', [
    { name: '▶ ニュースを収集（画像付き）',     functionName: 'collectNews' },
    { name: '▶ 画像URLだけ補完（既存記事）',   functionName: 'ncFillMissingImages' },
    { name: '▶ Google News行を削除（お掃除）', functionName: 'ncClearGoogleNewsRows' },
    { name: '▶ 接続診断',                      functionName: 'ncDiagnose' },
  ]);
}

// ─────────────────────────────────────────────────────────────
// エントリーポイント
// ─────────────────────────────────────────────────────────────

function collectNewsDryRun() { ncCollect(true); }
function collectNews()       { ncCollect(false); }

/** 両サイト新着を一括取得しキーワード照合（1ボタンで完了） */
function collectNewsLooped() {
  for (var i = 0; i < NC_COLLECT_LOOPS; i++) {
    Logger.log('=== ループ ' + (i + 1) + ' / ' + NC_COLLECT_LOOPS + ' ===');
    ncCollect(false);
  }
  Logger.log('全ループ完了');
}

function ncCollect(dryRun) {
  var t0 = new Date().getTime();

  var props   = PropertiesService.getScriptProperties();
  var kwId    = props.getProperty('NEWS_KEYWORDS_ID') || NC_KEYWORDS_ID;
  var poolId  = props.getProperty('NEWS_POOL_ID')     || NC_POOL_ID;
  var poolTab = props.getProperty('NEWS_POOL_TAB')    || NC_POOL_TAB;

  var keywords  = ncLoadKeywords(kwId);
  var pool      = ncLoadPool(poolId, poolTab);
  var existUrls = pool.existUrls;
  var sheet     = pool.sheet;
  var header    = pool.header;

  // ① 両サイト新着を一括取得してRSS照合用プールを作る
  NC_PR_FEED_CACHE = null;
  var prFeed = ncLoadPrTimesFeed();
  var apFeed = ncLoadAtPressFeed();
  Logger.log('PR TIMES RSS: ' + prFeed.length + '件 / AT PRESS: ' + apFeed.length + '件');

  // ② RSS記事をキーワード照合（CULTURE・TOPICなど英語でも一致しやすいもの）
  var queue    = [];
  var seenUrls = {};

  var combined = prFeed.concat(apFeed);
  for (var j = 0; j < combined.length; j++) {
    var art = combined[j];
    if (!art.source_url || seenUrls[art.source_url]) continue;
    var hay = (art.title + ' ' + art.summary).toLowerCase();
    for (var k = 0; k < keywords.length; k++) {
      var kwText = String(keywords[k].keyword || '').toLowerCase();
      if (kwText && hay.indexOf(kwText) !== -1) {
        art._kw = keywords[k];
        seenUrls[art.source_url] = true;
        queue.push(art);
        break;
      }
    }
  }
  Logger.log('RSS照合ヒット: ' + queue.length + '件');

  var saved = 0, noImage = 0, dup = 0, timeUp = false;
  var today = Utilities.formatDate(new Date(), 'Asia/Tokyo', 'yyyy-MM-dd');
  var now   = Utilities.formatDate(new Date(), 'Asia/Tokyo', 'yyyy-MM-dd HH:mm:ss');

  for (var q = 0; q < queue.length && saved < NC_MAX_TOTAL; q++) {
    if (new Date().getTime() - t0 > NC_TIME_BUDGET_MS) { timeUp = true; break; }

    var art = queue[q];
    if (!art.source_url || existUrls[art.source_url]) { dup++; continue; }

    var img = '';
    if (!dryRun) {
      img = ncResolveArticle(art.source_url).image;
      if (!img && NC_REQUIRE_IMAGE) {
        noImage++;
        Logger.log('\u00d7 \u753b\u50cf\u306a\u3057\u3067\u9664\u5916: ' + art.title.slice(0, 40));
        continue;
      }
    }

    var kwRef = art._kw;
    var row = {
      news_id:         ncGenId(),
      title:           art.title,
      summary:         art.summary,
      source_url:      art.source_url,
      source_name:     art.source_name,
      news_date:       art.news_date,
      image_url:       img,
      category:        kwRef ? kwRef.category : 'TREND',
      matched_keyword: kwRef ? kwRef.keyword  : '',
      keywords:        kwRef ? kwRef.keyword  : '',
      collected_date:  today,
      status:          'NEW',
      created_at:      now,
      updated_at:      now,
    };

    existUrls[art.source_url] = true;
    if (!dryRun) ncAppendRow(sheet, header, row);
    saved++;
    Logger.log('\u2713 ' + art.title.slice(0, 40));
  }

  var msg = (dryRun ? '\u300c\u30c9\u30e9\u30a4\u30e9\u30f3\u300d' : '\u300c\u53ce\u96c6\u5b8c\u4e86\u300d') + '\n' +
    '\u65b0\u898f\u4fdd\u5b58: ' + saved + '\u4ef6\uff08\u3059\u3079\u3066\u753b\u50cf\u4ed8\u304d\uff09\n' +
    '\u753b\u50cf\u306a\u3057\u3067\u9664\u5916: ' + noImage + '\u4ef6\n' +
    '\u91cd\u8907\u30b9\u30ad\u30c3\u30d7: ' + dup + '\u4ef6' +
    (timeUp ? '\n\u203b\u6642\u9593\u5207\u308c\u3067\u9014\u4e2d\u7d42\u4e86\u3002\u3082\u3046\u4e00\u5ea6\u5b9f\u884c\u3059\u308b\u3068\u7d9a\u304d\u3092\u53d6\u5f97\u3057\u307e\u3059\u3002' : '');
  Logger.log(msg);
  try { SpreadsheetApp.getUi().alert(msg); } catch (e) {}
}

// ─────────────────────────────────────────────────────────────
// NEWS_KEYWORDS 読み込み
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

var NC_POOL_SCHEMA = [
  'news_id', 'title', 'summary', 'source_url', 'source_name',
  'news_date', 'collected_date', 'category', 'keywords', 'matched_keyword',
  'image_url', 'ai_score', 'ai_reason', 'status', 'selected_date',
  'selected_by', 'notes', 'image_saved', 'image_folder_url',
  'news_master_id', 'created_at', 'updated_at',
];

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

  // ヘッダーが空なら自動でスキーマを書き込む
  if (header.filter(function(h) { return h; }).length === 0) {
    sheet.getRange(1, 1, 1, NC_POOL_SCHEMA.length).setValues([NC_POOL_SCHEMA]);
    header = NC_POOL_SCHEMA.slice();
    Logger.log('NEWS_POOL ヘッダーを初期化しました');
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
// prtimes.jp 検索
//
// 経路A（優先）: prtimes.jp 公式RSS。実URLが直接取れるので og:image が確実に取れる。
//                1回の実行で1度だけ取得してキャッシュし、キーワードで絞り込む。
// 経路B（代替）: Google News RSS。CBMi URLは暗号化されていて復号できないため、
//                記事ページを1回開いて実URLを取り出す。
// ─────────────────────────────────────────────────────────────

// 1実行内で使い回す公式RSSのキャッシュ（null=未取得, []=取得失敗）
var NC_PR_FEED_CACHE = null;

/** prtimes.jp 公式RSSを1度だけ取得して記事配列にする */
function ncLoadPrTimesFeed() {
  if (NC_PR_FEED_CACHE !== null) return NC_PR_FEED_CACHE;

  var feeds = ['https://prtimes.jp/topics/11.rdf', 'https://prtimes.jp/topics/46.rdf', 'https://prtimes.jp/index.rdf', 'https://prtimes.jp/rss/index.rdf'];
  for (var f = 0; f < feeds.length; f++) {
    var xml = ncFetch(feeds[f]);
    if (!xml) { Logger.log('公式RSS NG(取得失敗): ' + feeds[f]); continue; }
    if (xml.indexOf('<item') === -1) {
      Logger.log('公式RSS NG(item無し): ' + feeds[f] + ' / 先頭: ' + xml.slice(0, 200));
      continue;
    }

    var items = [];
    // <items><rdf:Seq> を拾わないよう、item の直後は空白か > に限定する
    var re = /<item(?:\s[^>]*)?>([\s\S]*?)<\/item>/gi;
    var m;
    while ((m = re.exec(xml)) !== null) {
      var b = m[1];
      var link = ncTagText(b, 'link');
      if (!link || link.indexOf('prtimes.jp') === -1) continue;

      items.push({
        title:       ncTagText(b, 'title'),
        source_url:  link,
        source_name: 'PR TIMES',
        news_date:   ncParsePubDate(ncTagText(b, 'dc:date') || ncTagText(b, 'pubDate')),
        summary:     ncStripTags(ncTagText(b, 'description')).slice(0, 300),
        image_url:   '',
      });
    }
    if (items.length) {
      Logger.log('公式RSS取得: ' + items.length + '件 (' + feeds[f] + ')');
      NC_PR_FEED_CACHE = items;
      return items;
    }
  }

  Logger.log('公式RSS使用不可 → Google News にフォールバック');
  NC_PR_FEED_CACHE = [];
  return NC_PR_FEED_CACHE;
}

/** CDATA対応でタグの中身を取り出す */
function ncLoadAtPressFeed() {
  var url  = 'https://www.atpress.ne.jp/?sort=date';
  var html = ncFetch(url);
  if (!html) { Logger.log('AT PRESS 一覧取得失敗'); return []; }

  var articles = [];
  var seen     = {};

  // /news/数字 のリンクを全て抽出（AT PRESSの記事URL形式）
  var re = /href="(\/news\/(\d+)[^"]*)"/gi;
  var m;
  while ((m = re.exec(html)) !== null) {
    var path   = m[1].split('?')[0].split('#')[0];
    var fullUrl = 'https://www.atpress.ne.jp' + path;
    if (seen[fullUrl]) continue;
    seen[fullUrl] = true;

    // リンク周辺のブロックからタイトルと日付を抽出
    var pos   = m.index;
    var block = html.slice(Math.max(0, pos - 600), pos + 600);

    // タイトル
    var titleM = block.match(/class="[^"]*(?:title|heading|name)[^"]*"[^>]*>([\s\S]*?)<\/(?:h[1-6]|p|span|a|div)/i);
    var title  = titleM ? ncStripTags(titleM[1]).trim() : '';
    if (!title) {
      var aM = block.match(/<a[^>]+href="[^"]*news\/\d+[^"]*"[^>]*>([\s\S]*?)<\/a>/i);
      title  = aM ? ncStripTags(aM[1]).trim() : '';
    }
    if (!title || title.length < 3) continue;

    // 日付
    var dateM = block.match(/(\d{4}[-\/\.]\d{2}[-\/\.]\d{2})/);
    var newsDate = dateM ? dateM[1].replace(/[\/\.]/g, '-') : '';

    articles.push({
      title:       title,
      source_url:  fullUrl,
      source_name: 'AT PRESS',
      news_date:   newsDate,
      summary:     '',
      image_url:   '',
    });
  }

  Logger.log('AT PRESS 一覧取得: ' + articles.length + '件');
  return articles;
}

function ncTagText(block, tag) {
  var re = new RegExp('<' + tag + '[^>]*>(?:<!\\[CDATA\\[)?([\\s\\S]*?)(?:\\]\\]>)?<\\/' + tag + '>', 'i');
  var m  = block.match(re);
  return m ? m[1].trim() : '';
}

function ncSearchPrTimes(keyword) {
  // 経路A: PR TIMES 検索ページをキーワードで直接検索（実URLが得られる）
  var results = ncSearchPrTimesPage(keyword);
  if (results.length) return results;

  // 経路B: 公式RSSをキーワードで絞り込む（補助）
  var feed = ncLoadPrTimesFeed();
  if (feed.length) {
    var hit = [];
    var kw  = keyword.toLowerCase();
    for (var i = 0; i < feed.length && hit.length < NC_MAX_PER_KEYWORD; i++) {
      var a = feed[i];
      if ((a.title + ' ' + a.summary).toLowerCase().indexOf(kw) !== -1) hit.push(a);
    }
    if (hit.length) return hit;
  }

  return [];
}

/**
 * PR TIMES 検索ページを直接スクレイピングしてキーワード検索する。
 * 実記事URLが得られるので og:image も確実に取れる。
 */
function ncSearchPrTimesPage(keyword) {
  var url = 'https://prtimes.jp/main/action.php?run=html&page=searchkey&search_word=' +
            encodeURIComponent(keyword);
  var html = ncFetch(url);
  if (!html) {
    Logger.log('PR TIMES検索ページ取得失敗: ' + keyword);
    return [];
  }

  var articles = [];
  var seen = {};

  // 記事URLパターン: /main/html/rd/p/数字.数字.html
  var linkRe = /href="(\/main\/html\/rd\/p\/\d+\.\d+\.html)"/gi;
  var m;
  while ((m = linkRe.exec(html)) !== null && articles.length < NC_MAX_PER_KEYWORD) {
    var path    = m[1];
    var fullUrl = 'https://prtimes.jp' + path;
    if (seen[fullUrl]) continue;
    seen[fullUrl] = true;

    // URLの前後からタイトルを探す
    var pos   = m.index;
    var block = html.slice(Math.max(0, pos - 500), pos + 500);
    var titleM = block.match(/class="[^"]*title[^"]*"[^>]*>([\s\S]*?)<\/(?:h[1-6]|p|a|span|div)/i);
    var title  = titleM ? ncStripTags(titleM[1]).trim() : '';
    if (!title) {
      // aタグのテキストを使う
      var aM = block.match(/<a[^>]+href="[^"]*rd\/p\/[^"]*"[^>]*>([\s\S]*?)<\/a>/i);
      title  = aM ? ncStripTags(aM[1]).trim() : '';
    }
    if (!title || title.length < 3) continue;

    var dateM = block.match(/(\d{4}[-\/]\d{2}[-\/]\d{2})/);
    var newsDate = dateM ? dateM[1].replace(/\//g, '-') : '';

    articles.push({
      title:       title,
      source_url:  fullUrl,
      source_name: 'PR TIMES',
      news_date:   newsDate,
      summary:     '',
      image_url:   '',
    });
  }

  Logger.log('PR TIMES検索「' + keyword + '」→ ' + articles.length + '件');
  return articles;
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

    var descHtml = desc.replace(/&lt;/g,'<').replace(/&gt;/g,'>').replace(/&amp;/g,'&');

    // Google NewsのCBMi URLをデコードして実際の記事URLを取得
    var realUrl = ncDecodeGNewsUrl(gnLink) || gnLink;

    articles.push({
      title:       title,
      source_url:  realUrl,
      source_name: sourceName,
      news_date:   ncParsePubDate(pubDate),
      summary:     ncStripTags(descHtml).slice(0, 300),
      image_url:   '',
    });
  }

  return articles;
}

/**
 * Google News RSS の CBMi... URL から実際の記事URLをデコードする。
 * base64url デコードしてバイト列から http(s):// を探す。
 */
function ncDecodeGNewsUrl(gnUrl) {
  var m = gnUrl.match(/\/articles\/(CBMi[^?&#\s]+)/);
  if (!m) return '';
  try {
    var bytes = Utilities.base64DecodeWebSafe(m[1]);
    for (var i = 0; i < bytes.length - 4; i++) {
      // 'h'=104 't'=116 't'=116 'p'=112
      if (bytes[i] === 104 && bytes[i+1] === 116 && bytes[i+2] === 116 && bytes[i+3] === 112) {
        var chars = [];
        for (var j = i; j < bytes.length; j++) {
          if (bytes[j] < 0x20) break;
          chars.push(String.fromCharCode(bytes[j]));
        }
        return chars.join('');
      }
    }
    return '';
  } catch(e) {
    return '';
  }
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
      deadline: 15,
      headers: {
        'User-Agent': NC_UA,
        'Accept': 'application/rss+xml, application/xml, text/xml, text/html, */*',
        'Accept-Language': 'ja,en;q=0.8',
      },
    });
    var code = res.getResponseCode();
    if (code !== 200) {
      Logger.log('fetch HTTP ' + code + ': ' + url);
      return null;
    }
    return res.getContentText('UTF-8');
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
// 画像バックフィル
//   image_url が空の記事を元URLから og:image で埋める。
//   1回の実行で最大 NC_IMAGE_FILL_MAX 件処理（タイムアウト対策）。
// ─────────────────────────────────────────────────────────────

var NC_IMAGE_FILL_MAX = NC_MAX_TOTAL;

function ncFillMissingImages() {
  var props   = PropertiesService.getScriptProperties();
  var poolId  = props.getProperty('NEWS_POOL_ID')  || NC_POOL_ID;
  var poolTab = props.getProperty('NEWS_POOL_TAB') || NC_POOL_TAB;

  var pool = ncLoadPool(poolId, poolTab);
  var sheet = pool.sheet;
  var header = pool.header;

  var imgIdx = header.indexOf('image_url');
  var urlIdx = header.indexOf('source_url');
  if (imgIdx < 0 || urlIdx < 0) {
    Logger.log('image_url / source_url 列が見つかりません');
    return;
  }

  var lastRow = sheet.getLastRow();
  if (lastRow < 2) return;

  var data    = sheet.getRange(2, 1, lastRow - 1, header.length).getValues();
  var updated = 0;

  for (var i = 0; i < data.length && updated < NC_IMAGE_FILL_MAX; i++) {
    var row      = data[i];
    var existing = String(row[imgIdx] || '').trim();
    if (existing) continue;

    var sourceUrl = String(row[urlIdx] || '').trim();
    if (!sourceUrl) continue;

    var ogImage = ncFetchOgImage(sourceUrl);
    if (ogImage) {
      sheet.getRange(i + 2, imgIdx + 1).setValue(ogImage);
      updated++;
      Logger.log('OK: ' + ogImage.slice(0, 80));
    } else {
      Logger.log('画像なし: ' + sourceUrl.slice(0, 80));
    }
    Utilities.sleep(200);
  }

  Logger.log('画像URL更新: ' + updated + '件');
}

function ncGetHtml(url) {
  try {
    var res = UrlFetchApp.fetch(url, {
      muteHttpExceptions: true,
      followRedirects: true,
      headers: { 'User-Agent': NC_UA },
      deadline: 8,
    });
    if (res.getResponseCode() !== 200) return '';
    return res.getContentText('UTF-8');
  } catch(e) {
    return '';
  }
}

/** Google側のロゴ/サムネ or サイト共通OG画像は記事固有の画像ではないので弾く */
function ncIsGenericImage(url) {
  if (!url) return true;
  // Googleの画像
  if (/googleusercontent\.com|gstatic\.com|ggpht\.com|google\.[a-z.]+\//i.test(url)) return true;
  // PR TIMESのデフォルトOG画像
  if (/prtimes\.jp\/common\//i.test(url)) return true;
  // atpressのデフォルトOG画像
  if (/atpress\.ne\.jp\/common\//i.test(url)) return true;
  return false;
}

// 後方互換
function ncIsGoogleImage(url) { return ncIsGenericImage(url); }

/** HTMLエンティティを戻す。&amp; のまま保存すると画像が表示されない */
function ncDecodeEntities(s) {
  if (!s) return '';
  return String(s)
    .replace(/&amp;/gi,  '&')
    .replace(/&quot;/gi, '"')
    .replace(/&#0?39;/g, "'")
    .replace(/&apos;/gi, "'")
    .replace(/&lt;/gi,   '<')
    .replace(/&gt;/gi,   '>');
}

function ncPickOgImage(html) {
  if (!html) return '';
  var m = html.match(/<meta[^>]+property=["']og:image["'][^>]+content=["']([^"']+)["']/i);
  if (!m) m = html.match(/<meta[^>]+content=["']([^"']+)["'][^>]+property=["']og:image["']/i);
  var img = ncDecodeEntities(m ? m[1] : '');
  if (ncIsGenericImage(img)) return '';
  return img;
}

/**
 * 記事URLを実URLに解決し、og:image も取得する。
 * Google News URL の場合は記事ページHTMLから実URLを拾い直す。
 * @return {{url:string, image:string}}
 */
function ncResolveArticle(url) {
  var html = ncGetHtml(url);
  var img  = ncPickOgImage(html);
  if (img) return { url: url, image: img };

  // 画像が取れない＝Google Newsの中継ページの可能性。実URLを探す。
  if (html) {
    var hits = html.match(/https?:\/\/(?:www\.)?(?:prtimes\.jp|atpress\.ne\.jp)\/[^"'\s<\\]+/i);
    if (hits && hits[0] !== url) {
      var real = hits[0].replace(/&amp;/g, '&');
      var h2   = ncGetHtml(real);
      var i2   = ncPickOgImage(h2);
      if (i2) return { url: real, image: i2 };
      return { url: real, image: '' };
    }
  }
  return { url: url, image: '' };
}

/**
 * 既存行に入ってしまった Google のロゴ画像URLを空に戻す。
 * 記事そのものは消さない。実行後に「画像URLだけ補完」で取り直せる。
 */
function ncClearGoogleImages() {
  var props  = PropertiesService.getScriptProperties();
  var pool   = ncLoadPool(props.getProperty('NEWS_POOL_ID')  || NC_POOL_ID,
                          props.getProperty('NEWS_POOL_TAB') || NC_POOL_TAB);
  var imgIdx = pool.header.indexOf('image_url');
  if (imgIdx < 0) { Logger.log('image_url 列がありません'); return; }

  var lastRow = pool.sheet.getLastRow();
  if (lastRow < 2) return;

  var rng     = pool.sheet.getRange(2, imgIdx + 1, lastRow - 1, 1);
  var vals    = rng.getValues();
  var cleared = 0;
  for (var i = 0; i < vals.length; i++) {
    if (vals[i][0] && ncIsGoogleImage(String(vals[i][0]))) { vals[i][0] = ''; cleared++; }
  }
  rng.setValues(vals);

  var msg = 'Google画像を消去: ' + cleared + '件';
  Logger.log(msg);
  try { SpreadsheetApp.getUi().alert(msg); } catch(e) {}
}

/** 後方互換: og:image だけ欲しいとき */
function ncFetchOgImage(url) {
  return ncResolveArticle(url).image;
}

/**
 * Google News の CBMi URL が入った行を全削除する。
 * CBMi URL は暗号化されていて実記事URLに復号できないため、
 * これらの行からは og:image を永久に取得できない。
 * 1行ずつ deleteRow するとタイムアウトするので残す行だけ一括で書き戻す。
 */
function ncClearGoogleNewsRows() {
  var props  = PropertiesService.getScriptProperties();
  var pool   = ncLoadPool(props.getProperty('NEWS_POOL_ID')  || NC_POOL_ID,
                          props.getProperty('NEWS_POOL_TAB') || NC_POOL_TAB);
  var sheet  = pool.sheet;
  var header = pool.header;
  var urlIdx = header.indexOf('source_url');
  if (urlIdx < 0) { Logger.log('source_url 列がありません'); return; }

  var lastRow = sheet.getLastRow();
  if (lastRow < 2) { Logger.log('データ行がありません'); return; }

  var data = sheet.getRange(2, 1, lastRow - 1, header.length).getValues();
  var keep = [];
  for (var i = 0; i < data.length; i++) {
    if (String(data[i][urlIdx]).indexOf('news.google.com') === -1) keep.push(data[i]);
  }
  var deleted = data.length - keep.length;

  sheet.getRange(2, 1, data.length, header.length).clearContent();
  if (keep.length) sheet.getRange(2, 1, keep.length, header.length).setValues(keep);

  var msg = 'Google News行を削除: ' + deleted + '件 / 残り: ' + keep.length + '件';
  Logger.log(msg);
  try { SpreadsheetApp.getUi().alert(msg); } catch (e) {}
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
    Logger.log(a.news_date + ' | ' + a.title.slice(0, 60) + '\n  URL: ' + a.source_url + '\n  IMG: ' + (a.image_url || '（なし）'));
  });
}

/** RSSのdescription生テキストを確認する */
function ncDebugRssDescription() {
  var url = 'https://news.google.com/rss/search?q=' +
            encodeURIComponent('site:prtimes.jp ファッション') +
            '&hl=ja&gl=JP&ceid=JP:ja';
  var res = UrlFetchApp.fetch(url, { muteHttpExceptions: true, followRedirects: true });
  var text = res.getContentText();
  // 最初の<item>だけ取り出す
  var m = text.match(/<item>([\s\S]*?)<\/item>/i);
  if (!m) { Logger.log('itemなし'); return; }
  Logger.log('=== 最初のitem ===');
  Logger.log(m[1].slice(0, 2000));
}

/**
 * ★これを1回実行してログを全部コピペして渡してください★
 * 画像が取れない原因を特定するための診断。3つの経路を同時に試す。
 */
function ncDiagnose() {
  // ── テスト1: prtimes.jp の公式RSSが使えるか ──
  Logger.log('========== TEST 1: prtimes.jp 公式RSS ==========');
  try {
    var r1 = UrlFetchApp.fetch('https://prtimes.jp/index.rdf', {
      muteHttpExceptions: true, followRedirects: true, deadline: 20,
      headers: { 'User-Agent': 'Mozilla/5.0 (compatible; GreigeBot/1.0)' },
    });
    Logger.log('HTTP: ' + r1.getResponseCode());
    if (r1.getResponseCode() === 200) {
      var x = r1.getContentText('UTF-8');
      Logger.log('サイズ: ' + x.length);
      var it = x.match(/<item[\s\S]*?<\/item>/i);
      Logger.log('--- 最初のitem ---');
      Logger.log(it ? it[0].slice(0, 1200) : '(itemなし) 先頭800字:\n' + x.slice(0, 800));
    }
  } catch (e) { Logger.log('例外: ' + e.message); }

  // ── テスト2: Google News の記事URLを開くと何が返るか ──
  Logger.log('========== TEST 2: Google News 記事URL ==========');
  try {
    var rss = UrlFetchApp.fetch(
      'https://news.google.com/rss/search?q=' + encodeURIComponent('site:prtimes.jp ファッション') +
      '&hl=ja&gl=JP&ceid=JP:ja',
      { muteHttpExceptions: true, followRedirects: true, deadline: 20 }
    ).getContentText();
    var lm = rss.match(/<link>(https:\/\/news\.google\.com\/rss\/articles\/[^<]+)<\/link>/i);
    if (!lm) { Logger.log('記事linkが取れません'); }
    else {
      var gn = lm[1];
      Logger.log('GNews URL: ' + gn.slice(0, 120));
      Logger.log('デコード結果: [' + (ncDecodeGNewsUrl(gn) || '★失敗★') + ']');

      var r2 = UrlFetchApp.fetch(gn, {
        muteHttpExceptions: true, followRedirects: true, deadline: 20,
        headers: { 'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36' },
      });
      Logger.log('HTTP: ' + r2.getResponseCode());
      var h = r2.getContentText('UTF-8');
      Logger.log('返却サイズ: ' + h.length);
      // HTML内に実記事URLが埋まっているか
      var hits = h.match(/https?:\/\/(?:www\.)?prtimes\.jp[^"'\s<\\]*/g);
      Logger.log('prtimes.jp URLの出現: ' + (hits ? hits.length + '件' : '0件'));
      if (hits) {
        for (var i = 0; i < Math.min(5, hits.length); i++) Logger.log('  ' + hits[i]);
      } else {
        Logger.log('--- HTML先頭1000字 ---');
        Logger.log(h.slice(0, 1000));
      }
    }
  } catch (e) { Logger.log('例外: ' + e.message); }

  // ── テスト3: 公式RSSの実記事から og:image が取れるか ──
  Logger.log('========== TEST 3: prtimes.jp og:image ==========');
  try {
    NC_PR_FEED_CACHE = null;              // キャッシュを捨てて取り直す
    var feed = ncLoadPrTimesFeed();
    Logger.log('公式RSSの記事数: ' + feed.length);
    if (!feed.length) {
      Logger.log('★公式RSSから記事が取れていません★');
    } else {
      for (var k = 0; k < Math.min(3, feed.length); k++) {
        var a = feed[k];
        var r = ncResolveArticle(a.source_url);
        Logger.log((k + 1) + '. ' + a.title.slice(0, 40));
        Logger.log('   URL: ' + a.source_url);
        Logger.log('   IMG: ' + (r.image || '★取れず★'));
      }
    }
  } catch (e) { Logger.log('例外: ' + e.message); }

  Logger.log('========== 診断おわり ==========');
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
