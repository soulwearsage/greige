/**
 * GREIGE MAGAZINE - ニュース投稿 [1/3] 設定と検証
 * ※ NewsContent / NewsApi / NewsRun の3ファイルで一組。
 *   どれか欠けると動きません。
 *
 * ■ Script Properties
 *   WP_USER          : WordPressユーザー名
 *   WP_APP_PASS      : WordPressアプリケーションパスワード（スペース含む形式可）
 *   SPREADSHEET_ID   : スプレッドシートID（省略時 NEWS_DEFAULT_SPREADSHEET_ID）
 *   NEWS_TAB_NAME    : タブ名（省略時 NEWS_MASTER）
 */

var NEWS_WP_SITE = 'https://greige.online';

var NEWS_DEFAULT_SPREADSHEET_ID = '1C4ljnBvsJmGzQe6aaWbdIUj4mXYjl7Mk3bPhdQfJQWw';
var NEWS_DEFAULT_TAB_NAME       = 'NEWS_MASTER';

// ニュースは WordPress 標準の post タイプに投稿する
var NEWS_POST_TYPE = 'post';
var NEWS_REST_BASE = 'posts';

// カテゴリー名 → WordPress カテゴリーID のマッピング（サイトに合わせて変更）
var NEWS_CATEGORY_MAP = {
  'FASHION':    13,
  'LIFE STYLE': 16,
  'LIFESTYLE':  16,
  'WELLBEING':  18,
  'BEAUTY':     19,
  'INTERIOR':   17,
  'TREND':      21,
  'NEWS':       22,
};

// 投稿に最低限必要な列
var NEWS_NEEDED_COLUMNS = (
  'title,article_body,lead,slug,review_status,disclosure_type,disclosure_included,' +
  'wp_post_id,wp_url,wp_post_type,wp_status,featured_image_id,wp_category,wp_tags,' +
  'published_at,wp_last_synced_at,wp_error'
).split(',');

var NEWS_DISCLOSURE_MARKERS = ['PR', '広告', 'アフィリエイト', 'プロモーション', 'スポンサー'];

// ─────────────────────────────────────────────────────────────
// メニュー
// ─────────────────────────────────────────────────────────────

function onOpenNewsPublish() {
  SpreadsheetApp.getActiveSpreadsheet().addMenu('ニュース 投稿', [
    { name: '▶ ドライラン（送信せず確認）', functionName: 'newsDryRunPublish' },
    { name: '▶ 下書き投稿（APPROVED行）',   functionName: 'newsPublishContent' },
  ]);
}

// ─────────────────────────────────────────────────────────────
// 共通ユーティリティ
// ─────────────────────────────────────────────────────────────

function newsGetProps() {
  var p = PropertiesService.getScriptProperties();
  return {
    wpUser:   p.getProperty('WP_USER')        || '',
    wpPass:   p.getProperty('WP_APP_PASS')    || '',
    sheetId:  p.getProperty('SPREADSHEET_ID') || NEWS_DEFAULT_SPREADSHEET_ID,
    sheetTab: p.getProperty('NEWS_TAB_NAME')  || NEWS_DEFAULT_TAB_NAME,
  };
}

function newsGetAuthHeader(props) {
  return 'Basic ' + Utilities.base64Encode(props.wpUser + ':' + props.wpPass);
}

function newsGetHeaderMap(sheet) {
  var headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];
  var map = {};
  headers.forEach(function(h, i) {
    var key = String(h == null ? '' : h).trim();
    if (key) map[key] = i;
  });
  return map;
}

function newsCell(row, hmap, name) {
  if (hmap[name] === undefined) return '';
  var v = row[hmap[name]];
  return String(v == null ? '' : v).trim();
}

function newsIsTruthy(v) {
  var s = String(v == null ? '' : v).trim().toUpperCase();
  return s === 'TRUE' || s === '1' || s === 'YES' || s === 'Y';
}

// ─────────────────────────────────────────────────────────────
// バリデーション
// ─────────────────────────────────────────────────────────────

/** @return {{ok: boolean, reason: string, warn: string}} */
function newsValidateRow(row, hmap) {
  var review = newsCell(row, hmap, 'review_status').toUpperCase();
  if (review !== 'APPROVED') {
    return { ok: false, reason: 'review_status が APPROVED でない', warn: '' };
  }
  if (!newsCell(row, hmap, 'title')) {
    return { ok: false, reason: 'title が空', warn: '' };
  }

  var body = newsCell(row, hmap, 'article_body');
  if (!body) {
    return { ok: false, reason: 'article_body が空', warn: '' };
  }

  // ── 景品表示法（ステマ規制）対応 ──
  var dType = newsCell(row, hmap, 'disclosure_type').toUpperCase();
  if (!dType) {
    return { ok: false, reason: 'disclosure_type が未設定（AFFILIATE / PR / GIFTED / NONE）', warn: '' };
  }

  var warn = '';
  if (dType !== 'NONE') {
    if (!newsIsTruthy(newsCell(row, hmap, 'disclosure_included'))) {
      return {
        ok: false,
        reason: 'disclosure_included が TRUE でない（' + dType + ' 記事にはPR表記が必須）',
        warn: '',
      };
    }
    if (!newsHasDisclosureMarker(body)) {
      warn = '本文にPR表記らしき文字列が見つかりません（表記位置を確認してください）';
    }
  }

  return { ok: true, reason: '', warn: warn };
}

function newsHasDisclosureMarker(body) {
  for (var i = 0; i < NEWS_DISCLOSURE_MARKERS.length; i++) {
    if (body.indexOf(NEWS_DISCLOSURE_MARKERS[i]) !== -1) return true;
  }
  return false;
}
