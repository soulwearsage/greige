/**
 * GREIGE MAGAZINE - WordPress投稿 [1/3] 設定と検証
 * ※ PublishContent / PublishApi / PublishRun の3ファイルで一組。
 *   どれか欠けると動きません。
 */

var WP_SITE = 'https://greige.online';

var DEFAULT_SPREADSHEET_ID = '1C4ljnBvsJmGzQe6aaWbdIUj4mXYjl7Mk3bPhdQfJQWw';
var DEFAULT_TAB_NAME       = 'CONTENT_MASTER';
var DEFAULT_POST_TYPE      = 'greige_product';

// 組み込み投稿タイプ post の REST ベースは "posts"（複数形）。ここを間違えると 404 になる。
var REST_BASE_BY_TYPE = {
  'greige_product': 'greige_product',
  'post':           'posts',
};

var TAXONOMY_BY_TYPE = {
  'greige_product': 'product_category',
  'post':           'categories',
};

var WP_CATEGORY_MAP = {
  'FASHION':    13,
  'LIFE STYLE': 16,
  'LIFESTYLE':  16,
  'WELLBEING':  18,
  'TOPS':       14,
  'BOTTOMS':    15,
  'T-SHIRT':    39,
  'INTERIOR':   17,
  'HAIR CARE':  20,
};

var NEEDED_COLUMNS = ('title,article_body,lead,slug,call_to_action,review_status,disclosure_type,disclosure_included,wp_post_id,wp_url,wp_post_type,wp_status,featured_image_id,wp_category,wp_tags,published_at,wp_last_synced_at,wp_error').split(',');

// 本文中のPR表記らしき文字列（見つからなければ警告。ブロックはしない）
var DISCLOSURE_MARKERS = ['PR', '広告', 'アフィリエイト', 'プロモーション', 'スポンサー'];

function onOpen() {
  SpreadsheetApp.getActiveSpreadsheet().addMenu('WordPress 投稿', [
    { name: '▶ ドライラン（送信せず確認）', functionName: 'dryRunPublish' },
    { name: '▶ 下書き投稿（APPROVED行）',   functionName: 'publishContent' },
  ]);
}

function getProps() {
  var p = PropertiesService.getScriptProperties();
  return {
    wpUser:   p.getProperty('WP_USER')        || '',
    wpPass:   p.getProperty('WP_APP_PASS')    || '',
    sheetId:  p.getProperty('SPREADSHEET_ID') || DEFAULT_SPREADSHEET_ID,
    sheetTab: p.getProperty('TAB_NAME')       || DEFAULT_TAB_NAME,
  };
}

function getAuthHeader(props) {
  return 'Basic ' + Utilities.base64Encode(props.wpUser + ':' + props.wpPass);
}

function getHeaderMap(sheet) {
  var headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];
  var map = {};
  headers.forEach(function(h, i) {
    var key = String(h == null ? '' : h).trim();
    if (key) map[key] = i;
  });
  return map;
}

/** hmap 経由でセル値を文字列として取り出す。列が無ければ空文字。 */
function cell(row, hmap, name) {
  if (hmap[name] === undefined) return '';
  var v = row[hmap[name]];
  return String(v == null ? '' : v).trim();
}

function isTruthy(v) {
  var s = String(v == null ? '' : v).trim().toUpperCase();
  return s === 'TRUE' || s === '1' || s === 'YES' || s === 'Y';
}

/** @return {{ok: boolean, reason: string, warn: string}} */
function validateRow(row, hmap) {
  var review = cell(row, hmap, 'review_status').toUpperCase();
  if (review !== 'APPROVED') {
    return { ok: false, reason: 'review_status が APPROVED でない', warn: '' };
  }
  if (!cell(row, hmap, 'title')) {
    return { ok: false, reason: 'title が空', warn: '' };
  }

  var body = cell(row, hmap, 'article_body');
  if (!body) {
    return { ok: false, reason: 'body が空', warn: '' };
  }

  // ── 景品表示法（ステマ規制）対応 ──
  var dType = cell(row, hmap, 'disclosure_type').toUpperCase();
  if (!dType) {
    return { ok: false, reason: 'disclosure_type が未設定（AFFILIATE / PR / GIFTED / NONE）', warn: '' };
  }

  var warn = '';
  if (dType !== 'NONE') {
    if (!isTruthy(cell(row, hmap, 'disclosure_included'))) {
      return {
        ok: false,
        reason: 'disclosure_included が TRUE でない（' + dType + ' 記事にはPR表記が必須）',
        warn: '',
      };
    }
    if (!hasDisclosureMarker(body)) {
      warn = '本文にPR表記らしき文字列が見つかりません（表記位置を確認してください）';
    }
  }

  return { ok: true, reason: '', warn: warn };
}

function hasDisclosureMarker(body) {
  for (var i = 0; i < DISCLOSURE_MARKERS.length; i++) {
    if (body.indexOf(DISCLOSURE_MARKERS[i]) !== -1) return true;
  }
  return false;
}
