/**
 * GREIGE MAGAZINE - News Board API（キュレーション画面のバックエンド）
 *
 * search_edit.php の News Board タブから呼ばれる Web App アクション。
 *
 * ■ 既存の Code.gs への組み込み方
 *   doGet / doPost のルーターに、以下の2行を追加してください。
 *
 *     // doGet(e) の中
 *     var newsGet = newsBoardHandleGet(e.parameter.action, e.parameter);
 *     if (newsGet !== null) return newsBoardJson(newsGet);
 *
 *     // doPost(e) の中（data = JSON.parse(e.postData.contents) のあと）
 *     var newsPost = newsBoardHandlePost(data.action, data);
 *     if (newsPost !== null) return newsBoardJson(newsPost);
 *
 * ■ Script Properties
 *   SPREADSHEET_ID            : スプレッドシートID（省略時は下の定数）
 *   NEWS_POOL_TAB             : NEWS_POOLのタブ名（省略時 NEWS_POOL）
 *   NEWS_IMAGE_ROOT_FOLDER_ID : 画像を保存するドライブのルートフォルダID（必須）
 */

var NB_DEFAULT_SPREADSHEET_ID = '1C4ljnBvsJmGzQe6aaWbdIUj4mXYjl7Mk3bPhdQfJQWw';
var NB_DEFAULT_POOL_TAB       = 'NEWS_POOL';

// 1記事あたりドライブに保存できる画像の上限
var NB_MAX_SAVE_IMAGES = 20;
// プレスリリースから拾う画像の上限
var NB_MAX_SCRAPE_IMAGES = 30;
// 画像として小さすぎるものを弾くための最小バイト数（アイコン除去）
var NB_MIN_IMAGE_BYTES = 8000;

// ─────────────────────────────────────────────────────────────
// ルーター
// ─────────────────────────────────────────────────────────────

/** @return {Object|null} News Board の担当でなければ null */
function newsBoardHandleGet(action, params) {
  if (action === 'newsDates') return nbListDates();
  if (action === 'newsList')  return nbListNews(params.date);
  return null;
}

/** @return {Object|null} News Board の担当でなければ null */
function newsBoardHandlePost(action, data) {
  if (action === 'updateNewsStatus') return nbUpdateStatus(data.news_id, data.status);
  if (action === 'fetchNewsImages')  return nbFetchImages(data.source_url);
  if (action === 'saveNewsImages')   return nbSaveImages(data);
  return null;
}

function newsBoardJson(obj) {
  return ContentService
    .createTextOutput(JSON.stringify(obj))
    .setMimeType(ContentService.MimeType.JSON);
}

// ─────────────────────────────────────────────────────────────
// シートユーティリティ
// ─────────────────────────────────────────────────────────────

function nbProps() {
  var p = PropertiesService.getScriptProperties();
  return {
    sheetId:      p.getProperty('SPREADSHEET_ID')            || NB_DEFAULT_SPREADSHEET_ID,
    poolTab:      p.getProperty('NEWS_POOL_TAB')             || NB_DEFAULT_POOL_TAB,
    imageRootId:  p.getProperty('NEWS_IMAGE_ROOT_FOLDER_ID') || '',
  };
}

function nbSheet() {
  var props = nbProps();
  var sheet = SpreadsheetApp.openById(props.sheetId).getSheetByName(props.poolTab);
  if (!sheet) throw new Error('タブが見つかりません: "' + props.poolTab + '"');
  return sheet;
}

/** シート全体を {header, rows, hmap} で返す */
function nbRead() {
  var sheet   = nbSheet();
  var lastRow = sheet.getLastRow();
  var lastCol = sheet.getLastColumn();
  if (lastRow < 2) return { sheet: sheet, header: [], rows: [], hmap: {} };

  var values = sheet.getRange(1, 1, lastRow, lastCol).getValues();
  var header = values[0];
  var hmap   = {};
  header.forEach(function(h, i) {
    var key = String(h == null ? '' : h).trim();
    if (key) hmap[key] = i;
  });
  return { sheet: sheet, header: header, rows: values.slice(1), hmap: hmap };
}

function nbCell(row, hmap, name) {
  if (hmap[name] === undefined) return '';
  var v = row[hmap[name]];
  if (v instanceof Date) return Utilities.formatDate(v, 'Asia/Tokyo', 'yyyy-MM-dd');
  return String(v == null ? '' : v).trim();
}

/** 1行を News Board のカード用オブジェクトに変換 */
function nbRowToItem(row, hmap) {
  return {
    news_id:        nbCell(row, hmap, 'news_id'),
    title:          nbCell(row, hmap, 'title'),
    source_url:     nbCell(row, hmap, 'source_url'),
    source_name:    nbCell(row, hmap, 'source_name'),
    news_date:      nbCell(row, hmap, 'news_date'),
    collected_date: nbCell(row, hmap, 'collected_date'),
    category:       nbCell(row, hmap, 'category'),
    keywords:       nbCell(row, hmap, 'keywords'),
    summary:        nbCell(row, hmap, 'summary'),
    ai_reason:      nbCell(row, hmap, 'ai_reason'),
    image_url:      nbCell(row, hmap, 'image_url'),
    status:         nbCell(row, hmap, 'status') || 'NEW',
    selected_date:  nbCell(row, hmap, 'selected_date'),
    image_saved:    nbCell(row, hmap, 'image_saved'),
  };
}

// ─────────────────────────────────────────────────────────────
// 一覧
// ─────────────────────────────────────────────────────────────

/** 収集日の一覧（新しい順） */
function nbListDates() {
  var d = nbRead();
  if (!d.rows.length) return [];

  var set = {};
  d.rows.forEach(function(row) {
    var date = nbCell(row, d.hmap, 'collected_date');
    if (date) set[date] = true;
  });
  return Object.keys(set).sort().reverse();
}

/** 指定した収集日のニュース一覧 */
function nbListNews(date) {
  var d = nbRead();
  if (!d.rows.length) return [];

  return d.rows
    .filter(function(row) {
      if (!nbCell(row, d.hmap, 'news_id')) return false;
      if (!date) return true;
      return nbCell(row, d.hmap, 'collected_date') === date;
    })
    .map(function(row) { return nbRowToItem(row, d.hmap); });
}

// ─────────────────────────────────────────────────────────────
// ステータス更新
// ─────────────────────────────────────────────────────────────

function nbUpdateStatus(newsId, status) {
  if (!newsId) return { ok: false, error: 'news_id が空です' };

  var d = nbRead();
  if (d.hmap['status'] === undefined) {
    return { ok: false, error: 'NEWS_POOL に status 列がありません' };
  }

  for (var i = 0; i < d.rows.length; i++) {
    if (nbCell(d.rows[i], d.hmap, 'news_id') !== String(newsId)) continue;

    var sheetRow = i + 2;
    d.sheet.getRange(sheetRow, d.hmap['status'] + 1).setValue(status);

    if (status === 'SELECTED' && d.hmap['selected_date'] !== undefined) {
      var today = Utilities.formatDate(new Date(), 'Asia/Tokyo', 'yyyy-MM-dd');
      d.sheet.getRange(sheetRow, d.hmap['selected_date'] + 1).setValue(today);
    }
    return { ok: true, news_id: newsId, status: status };
  }
  return { ok: false, error: '該当する news_id が見つかりません: ' + newsId };
}

// ─────────────────────────────────────────────────────────────
// プレスリリースから画像URLを抽出
// ─────────────────────────────────────────────────────────────

function nbFetchImages(sourceUrl) {
  if (!sourceUrl) return { ok: false, error: '記事URLが空です' };

  var html;
  try {
    var res = UrlFetchApp.fetch(sourceUrl, {
      muteHttpExceptions: true,
      followRedirects: true,
      headers: { 'User-Agent': 'Mozilla/5.0 (compatible; GreigeBot/1.0)' },
    });
    if (res.getResponseCode() !== 200) {
      return { ok: false, error: 'HTTP ' + res.getResponseCode() };
    }
    html = res.getContentText();
  } catch (e) {
    return { ok: false, error: '取得失敗: ' + e.message };
  }

  var urls  = [];
  var seen  = {};

  function push(raw) {
    if (!raw) return;
    var url = nbAbsoluteUrl(raw.trim(), sourceUrl);
    if (!url || seen[url]) return;
    if (!/^https?:\/\//i.test(url)) return;
    if (!/\.(jpe?g|png|webp)(\?|$)/i.test(url)) return;
    if (/sprite|icon|logo|avatar|blank|spacer|1x1|pixel/i.test(url)) return;
    seen[url] = true;
    urls.push(url);
  }

  // og:image を最優先（プレスリリースのメイン画像）
  var ogRe = /<meta[^>]+property=["']og:image["'][^>]+content=["']([^"']+)["']/gi;
  var m;
  while ((m = ogRe.exec(html)) !== null) push(m[1]);

  // 本文中の <img>。lazy load 対応で data-src 系も見る
  var imgRe = /<img[^>]+>/gi;
  while ((m = imgRe.exec(html)) !== null && urls.length < NB_MAX_SCRAPE_IMAGES) {
    var tag = m[0];
    var src = nbAttr(tag, 'data-original') || nbAttr(tag, 'data-lazy-src') ||
              nbAttr(tag, 'data-src')      || nbAttr(tag, 'src');
    push(src);
  }

  return { ok: true, images: urls.slice(0, NB_MAX_SCRAPE_IMAGES) };
}

function nbAttr(tag, name) {
  var re = new RegExp(name + '=["\']([^"\']+)["\']', 'i');
  var m  = tag.match(re);
  return m ? m[1] : '';
}

/** 相対URLを絶対URLに直す */
function nbAbsoluteUrl(url, baseUrl) {
  if (/^https?:\/\//i.test(url)) return url;
  if (url.indexOf('//') === 0) return 'https:' + url;

  var m = baseUrl.match(/^(https?:\/\/[^\/]+)(\/.*)?$/i);
  if (!m) return '';
  var origin = m[1];

  if (url.indexOf('/') === 0) return origin + url;

  var path = (m[2] || '/').replace(/\/[^\/]*$/, '/');
  return origin + path + url;
}

// ─────────────────────────────────────────────────────────────
// 選んだ画像をドライブに保存
//   保存先: <ルート>/ニュース/YYYY年MM月/<記事タイトル>/
// ─────────────────────────────────────────────────────────────

function nbSaveImages(data) {
  var props = nbProps();
  if (!props.imageRootId) {
    return { ok: false, error: 'Script Properties に NEWS_IMAGE_ROOT_FOLDER_ID を設定してください' };
  }

  var urls = String(data.image_urls || '')
    .split('\n')
    .map(function(s) { return s.trim(); })
    .filter(Boolean)
    .slice(0, NB_MAX_SAVE_IMAGES);

  if (!urls.length) return { ok: false, error: '画像が選択されていません' };

  var folder;
  try {
    folder = nbResolveFolder(props.imageRootId, data.news_date, data.title, data.news_id);
  } catch (e) {
    return { ok: false, error: 'フォルダ作成に失敗: ' + e.message };
  }

  var saved  = 0;
  var errors = [];

  urls.forEach(function(url, i) {
    try {
      var res = UrlFetchApp.fetch(url, {
        muteHttpExceptions: true,
        followRedirects: true,
        headers: { 'User-Agent': 'Mozilla/5.0 (compatible; GreigeBot/1.0)' },
      });
      if (res.getResponseCode() !== 200) {
        errors.push('HTTP ' + res.getResponseCode() + ': ' + url);
        return;
      }
      var blob = res.getBlob();
      if (blob.getBytes().length < NB_MIN_IMAGE_BYTES) return; // アイコン等は捨てる

      blob.setName(nbFileName(data.news_id, i, url));
      folder.createFile(blob);
      saved++;
    } catch (e) {
      errors.push(e.message + ': ' + url);
    }
    Utilities.sleep(200);
  });

  nbMarkImageSaved(data.news_id, saved, folder.getUrl());

  return {
    ok: true,
    saved: saved,
    folder_url: folder.getUrl(),
    errors: errors,
  };
}

/** <ルート>/ニュース/YYYY年MM月/<タイトル> を掘る */
function nbResolveFolder(rootId, newsDate, title, newsId) {
  var root  = DriveApp.getFolderById(rootId);
  var news  = nbChildFolder(root, 'ニュース');

  var d  = nbParseDate(newsDate) || new Date();
  var ym = Utilities.formatDate(d, 'Asia/Tokyo', 'yyyy年MM月');
  var ymFolder = nbChildFolder(news, ym);

  var name = nbSafeName(title) || String(newsId || 'untitled');
  return nbChildFolder(ymFolder, name);
}

/** 同名フォルダがあれば再利用、なければ作る */
function nbChildFolder(parent, name) {
  var it = parent.getFoldersByName(name);
  return it.hasNext() ? it.next() : parent.createFolder(name);
}

function nbParseDate(s) {
  if (!s) return null;
  var m = String(s).match(/(\d{4})\D+(\d{1,2})\D+(\d{1,2})/);
  if (!m) return null;
  return new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]));
}

/** ドライブのフォルダ名に使えない文字を落として短縮 */
function nbSafeName(s) {
  return String(s == null ? '' : s)
    .replace(/[\\\/:*?"<>|]/g, '')
    .replace(/\s+/g, ' ')
    .trim()
    .slice(0, 60);
}

function nbFileName(newsId, index, url) {
  var m   = String(url).match(/\.(jpe?g|png|webp)(\?|$)/i);
  var ext = m ? m[1].toLowerCase() : 'jpg';
  var n   = String(index + 1);
  if (n.length < 2) n = '0' + n;
  return (newsId || 'news') + '_' + n + '.' + ext;
}

/** 保存結果を NEWS_POOL に書き戻す（列が無ければ黙って飛ばす） */
function nbMarkImageSaved(newsId, saved, folderUrl) {
  if (!newsId) return;
  try {
    var d = nbRead();
    for (var i = 0; i < d.rows.length; i++) {
      if (nbCell(d.rows[i], d.hmap, 'news_id') !== String(newsId)) continue;
      var sheetRow = i + 2;
      if (d.hmap['image_saved'] !== undefined) {
        d.sheet.getRange(sheetRow, d.hmap['image_saved'] + 1).setValue(saved);
      }
      if (d.hmap['image_folder_url'] !== undefined) {
        d.sheet.getRange(sheetRow, d.hmap['image_folder_url'] + 1).setValue(folderUrl);
      }
      return;
    }
  } catch (e) {
    // 書き戻しの失敗で保存自体を失敗にはしない
  }
}
