/**
 * GREIGE MAGAZINE - CONTENT_MASTER → WordPress 下書き投稿
 *
 * CONTENT_MASTER の承認済み行を WordPress に下書きとして送る。
 * 公開（publish）はこのスクリプトでは行わない。WordPress 管理画面で人が行う。
 *
 * ■ Script Properties（ファイル → プロジェクトの設定 → スクリプト プロパティ）
 *   WP_USER        : WordPress ユーザー名
 *   WP_APP_PASS    : アプリケーションパスワード（スペースなし）
 *   SPREADSHEET_ID : 省略時 DEFAULT_SPREADSHEET_ID
 *   TAB_NAME       : 省略時 CONTENT_MASTER
 *
 * ■ 投稿される条件（すべて満たす行のみ）
 *   review_status = APPROVED
 *   title / body が空でない
 *   disclosure_type が設定済み
 *   disclosure_type が NONE 以外なら disclosure_included = TRUE
 *
 * ■ 冪等性
 *   wp_post_id が空 → 新規作成し、ID と URL を書き戻す
 *   wp_post_id あり → その記事を更新する（重複投稿しない）
 */

var WP_SITE = 'https://greige.online';

var DEFAULT_SPREADSHEET_ID = '1C4ljnBvsJmGzQe6aaWbdIUj4mXYjl7Mk3bPhdQfJQWw';
var DEFAULT_TAB_NAME       = 'CONTENT_MASTER';
var DEFAULT_POST_TYPE      = 'greige_product';

// wp_post_type → REST エンドポイント
// 組み込み投稿タイプ post の REST ベースは "posts"（複数形）。ここを間違えると 404 になる。
var REST_BASE_BY_TYPE = {
  'greige_product': 'greige_product',
  'post':           'posts',
};

// wp_post_type → カテゴリータクソノミーのキー
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

// 投稿処理に必要な列（SyncSchema.gs で作られる）
var NEEDED_COLUMNS = [
  'title', 'article_body', 'lead', 'slug', 'call_to_action',
  'review_status', 'disclosure_type', 'disclosure_included',
  'wp_post_id', 'wp_url', 'wp_post_type', 'wp_status',
  'featured_image_id', 'wp_category', 'wp_tags',
  'published_at', 'wp_last_synced_at', 'wp_error',
];

// 本文中のPR表記らしき文字列（見つからなければ警告。ブロックはしない）
var DISCLOSURE_MARKERS = ['PR', '広告', 'アフィリエイト', 'プロモーション', 'スポンサー'];

// ─────────────────────────────────────────────────────────────
// メニュー
// ─────────────────────────────────────────────────────────────

function onOpen() {
  SpreadsheetApp.getActiveSpreadsheet().addMenu('WordPress 投稿', [
    { name: '▶ ドライラン（送信せず確認）', functionName: 'dryRunPublish' },
    { name: '▶ 下書き投稿（APPROVED行）',   functionName: 'publishContent' },
  ]);
}

// ─────────────────────────────────────────────────────────────
// 設定・シートアクセス
// ─────────────────────────────────────────────────────────────

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

// ─────────────────────────────────────────────────────────────
// 行の検証
// ─────────────────────────────────────────────────────────────

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

// ─────────────────────────────────────────────────────────────
// WordPress REST API
// ─────────────────────────────────────────────────────────────

function wpFetch(path, method, payload, props) {
  var options = {
    method: method,
    headers: { Authorization: getAuthHeader(props) },
    muteHttpExceptions: true,
  };
  if (payload) {
    options.contentType = 'application/json';
    options.payload = JSON.stringify(payload);
  }
  var res  = UrlFetchApp.fetch(WP_SITE + '/wp-json/wp/v2/' + path, options);
  var text = res.getContentText();
  var body;
  try {
    body = JSON.parse(text);
  } catch (e) {
    body = { message: text.slice(0, 200) };
  }
  return { code: res.getResponseCode(), body: body };
}

function resolveCategoryIds(categoryStr) {
  if (!categoryStr) return [];
  var ids = [];
  String(categoryStr).split(/[,、]/).forEach(function(name) {
    var key = name.trim().toUpperCase();
    if (WP_CATEGORY_MAP[key] !== undefined) ids.push(WP_CATEGORY_MAP[key]);
  });
  return ids;
}

function resolveTagIds(tagStr, props) {
  if (!tagStr) return [];
  var ids = [];
  String(tagStr).split(/[,、]/).forEach(function(name) {
    var trimmed = name.trim();
    if (!trimmed) return;
    try {
      var searchRes = UrlFetchApp.fetch(
        WP_SITE + '/wp-json/wp/v2/tags?search=' + encodeURIComponent(trimmed) + '&per_page=1',
        { headers: { Authorization: getAuthHeader(props) }, muteHttpExceptions: true }
      );
      var found = JSON.parse(searchRes.getContentText());
      if (found.length > 0 && found[0].name === trimmed) {
        ids.push(found[0].id);
      } else {
        var createRes = UrlFetchApp.fetch(WP_SITE + '/wp-json/wp/v2/tags', {
          method: 'post',
          contentType: 'application/json',
          headers: { Authorization: getAuthHeader(props) },
          payload: JSON.stringify({ name: trimmed }),
          muteHttpExceptions: true,
        });
        var newTag = JSON.parse(createRes.getContentText());
        if (newTag.id) ids.push(newTag.id);
      }
    } catch (e) {
      // タグ解決の失敗は投稿自体を止めない
    }
  });
  return ids;
}

// ─────────────────────────────────────────────────────────────
// ペイロード生成
// ─────────────────────────────────────────────────────────────

function buildPayload(row, hmap, props, postType, isCreate) {
  var body = cell(row, hmap, 'article_body');
  var cta  = cell(row, hmap, 'call_to_action');
  if (cta) body += '\n\n' + cta;

  var payload = {
    title:   cell(row, hmap, 'title'),
    content: body,
    excerpt: cell(row, hmap, 'lead'),
  };

  // status は新規作成時のみ送る。
  // 更新時に送ると、WordPress 側で公開済みの記事を下書きに戻してしまう。
  if (isCreate) payload.status = 'draft';

  var slug = cell(row, hmap, 'slug');
  if (slug) payload.slug = slug;

  var featured = cell(row, hmap, 'featured_image_id');
  if (featured && !isNaN(Number(featured)) && Number(featured) > 0) {
    payload.featured_media = Number(featured);
  }

  var taxonomyKey = TAXONOMY_BY_TYPE[postType];
  var catIds = resolveCategoryIds(cell(row, hmap, 'wp_category'));
  if (taxonomyKey && catIds.length > 0) payload[taxonomyKey] = catIds;

  var tagIds = resolveTagIds(cell(row, hmap, 'wp_tags'), props);
  if (tagIds.length > 0) payload.tags = tagIds;

  // RankMath SEO フィールド
  var metaTitle = cell(row, hmap, 'meta_title');
  var metaDesc  = cell(row, hmap, 'meta_description');
  if (metaTitle || metaDesc) {
    payload.meta = {};
    if (metaTitle) payload.meta.rank_math_title       = metaTitle;
    if (metaDesc)  payload.meta.rank_math_description = metaDesc;
  }

  return payload;
}

// ─────────────────────────────────────────────────────────────
// メイン処理
// ─────────────────────────────────────────────────────────────

function publishContent() { runPublish(false); }
function dryRunPublish()  { runPublish(true);  }

function runPublish(isDryRun) {
  var props = getProps();

  if (!isDryRun && (!props.wpUser || !props.wpPass)) {
    return alertText('Script Properties に WP_USER と WP_APP_PASS を設定してください。');
  }

  var ss = SpreadsheetApp.openById(props.sheetId);
  var sheet = ss.getSheetByName(props.sheetTab);
  if (!sheet) {
    return alertText('タブが見つかりません: "' + props.sheetTab + '"');
  }

  var lastRow = sheet.getLastRow();
  if (lastRow < 2) return alertText('データ行がありません。');

  var hmap    = getHeaderMap(sheet);
  var missing = NEEDED_COLUMNS.filter(function(c) { return hmap[c] === undefined; });
  if (missing.length > 0) {
    return alertText(
      '必要な列がありません:\n  ' + missing.join('\n  ') +
      '\n\nSyncSchema.gs の「列を同期」を先に実行してください。'
    );
  }

  var rows      = sheet.getRange(2, 1, lastRow - 1, sheet.getLastColumn()).getValues();
  var lines     = [];
  var warnings  = [];
  var processed = 0;
  var skipped   = 0;
  var failed    = 0;

  rows.forEach(function(row, idx) {
    var sheetRow = idx + 2;

    var check = validateRow(row, hmap);
    if (!check.ok) { skipped++; return; }

    var title    = cell(row, hmap, 'title');
    var label    = 'R' + sheetRow + ' ' + (title.length > 28 ? title.slice(0, 28) + '…' : title);
    var postType = cell(row, hmap, 'wp_post_type') || DEFAULT_POST_TYPE;
    var restBase = REST_BASE_BY_TYPE[postType];

    if (!restBase) {
      failed++;
      lines.push('✗ ' + label + ' → 未知の wp_post_type: "' + postType + '"');
      return;
    }

    var wpPostId = cell(row, hmap, 'wp_post_id');
    var isCreate = !wpPostId;
    processed++;

    if (check.warn) warnings.push('⚠ ' + label + ' → ' + check.warn);

    if (isDryRun) {
      lines.push((isCreate ? '新規' : '更新 #' + wpPostId) + ': ' + label + ' [' + postType + ']');
      return;
    }

    try {
      var payload  = buildPayload(row, hmap, props, postType, isCreate);
      var endpoint = isCreate ? restBase : restBase + '/' + wpPostId;
      var result   = wpFetch(endpoint, 'post', payload, props);

      if (result.code === 200 || result.code === 201) {
        var now = new Date().toISOString();
        sheet.getRange(sheetRow, hmap['wp_post_id']     + 1).setValue(result.body.id);
        sheet.getRange(sheetRow, hmap['wp_url']         + 1).setValue(result.body.link || '');
        sheet.getRange(sheetRow, hmap['wp_status']      + 1).setValue(result.body.status || '');
        sheet.getRange(sheetRow, hmap['wp_last_synced_at'] + 1).setValue(now);
        sheet.getRange(sheetRow, hmap['wp_error']           + 1).setValue('');

        // 公開済みになった場合のみ published_at を記録（未記入のときだけ）
        if (result.body.status === 'publish' && !cell(row, hmap, 'published_at')) {
          sheet.getRange(sheetRow, hmap['published_at'] + 1).setValue(now);
        }

        lines.push('✓ ' + (isCreate ? '新規' : '更新') + ' ' + label + ' (ID=' + result.body.id + ')');
      } else {
        failed++;
        var msg = result.body.message || JSON.stringify(result.body);
        sheet.getRange(sheetRow, hmap['wp_error'] + 1).setValue('HTTP ' + result.code + ': ' + msg);
        lines.push('✗ ' + label + ' → HTTP ' + result.code + ': ' + msg);
      }

      Utilities.sleep(300);

    } catch (e) {
      failed++;
      try { sheet.getRange(sheetRow, hmap['wp_error'] + 1).setValue('例外: ' + e.message); } catch (e2) {}
      lines.push('✗ ' + label + ' → 例外: ' + e.message);
    }
  });

  var out = [];
  out.push(isDryRun ? '【ドライラン】送信していません' : '【投稿完了】');
  out.push('対象 ' + processed + ' / スキップ ' + skipped + ' / 失敗 ' + failed);
  out.push('');
  if (processed === 0) {
    out.push('対象行がありません。');
    out.push('review_status=APPROVED かつ title / body / disclosure_type が');
    out.push('埋まっている行が必要です。');
  } else {
    out = out.concat(lines);
  }
  if (warnings.length > 0) {
    out.push('');
    out.push('── 警告 ──');
    out = out.concat(warnings);
  }

  alertText(out.join('\n'));
}

function alertText(text) {
  Logger.log(text);
  try {
    SpreadsheetApp.getUi().alert(text);
  } catch (e) {
    // エディタから直接実行した場合は UI が無いのでログのみ
  }
}
