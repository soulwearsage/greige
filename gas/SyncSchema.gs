/**
 * GREIGE MAGAZINE - CONTENT_MASTER スキーマ同期
 *
 * 足りない列をシート右端に「追加するだけ」のスクリプト。
 * 削除・並べ替えは機能として持たせていない（既存行の値がずれる事故を防ぐため）。
 * 不要な列が出たら手で消すこと。
 *
 * ■ Script Properties（ファイル → プロジェクトの設定 → スクリプト プロパティ）
 *   SPREADSHEET_ID : 省略時 DEFAULT_SPREADSHEET_ID（SELECTED_PRODUCTS）
 *   TAB_NAME       : 省略時 CONTENT_MASTER
 *
 * ■ 使い方
 *   1.「スキーマ同期」→「ドライラン」で追加される列を確認
 *   2.「列を同期（追加のみ）」を実行
 *
 * ■ 注意
 *   SCHEMA は リポジトリの "SELECTED_PRODUCTS - CONTENT_MASTER.csv" と同じ並び。
 *   列を増やすときは両方を同じ順序で直す。
 */

var DEFAULT_SPREADSHEET_ID = '1C4ljnBvsJmGzQe6aaWbdIUj4mXYjl7Mk3bPhdQfJQWw';
var DEFAULT_TAB_NAME       = 'CONTENT_MASTER';

var SCHEMA = [
  // ── 識別・企画 ──
  'content_id',
  'plan_id',
  'content_type',
  'version',
  'prompt_version',

  // ── 素材参照 ──
  'main_product_id',
  'related_product_ids',
  'main_image_id',
  'image_ids',
  'affiliate_link_ids',

  // ── 編集方針 ──
  'editorial_theme',
  'editorial_reason',
  'target_reader',
  'season',
  'keywords',
  'key_points',
  'structure',
  'tone',

  // ── 本文 ──
  'title',
  'subtitle',
  'lead',
  'editorial_note',
  'body',
  'generated_key_points',
  'call_to_action',

  // ── SNS出力 ──
  'instagram_caption',
  'x_text',
  'video_copy',

  // ── 状態・監査 ──
  'status',
  'review_status',
  'created_at',
  'updated_at',
  'approved_at',
  'approved_by',
  'notes',

  // ── WordPress公開 ──
  'wp_post_id',
  'wp_url',
  'wp_post_type',
  'wp_status',
  'slug',
  'featured_image_id',
  'wp_category',
  'wp_tags',
  'published_at',
  'last_synced_at',

  // ── 法令・SEO ──
  'disclosure_type',
  'disclosure_included',
  'meta_title',
  'meta_description',
  'target_keyword',
  'secondary_keywords',
  'search_intent',
  'canonical_url',
  'duplicate_key',

  // ── 運用・計測 ──
  'primary_merchant',
  'price_range_min',
  'price_range_max',
  'link_check_status',
  'link_checked_at',
  'review_due_at',
  'generated_by',
  'generated_at',
  'human_edited',
  'word_count',
  'source_research_id',
  'scheduled_at',

  // ── Claude API 制御 ──
  'claude_api_enabled',
  'claude_api_model',
  'claude_api_status',
];

// ─────────────────────────────────────────────────────────────
// メニュー
// ─────────────────────────────────────────────────────────────

function onOpen() {
  SpreadsheetApp.getActiveSpreadsheet().addMenu('スキーマ同期', [
    { name: '▶ ドライラン（確認のみ）',   functionName: 'dryRunSchema' },
    { name: '▶ 列を同期（追加のみ）',     functionName: 'syncSchema'   },
  ]);
}

// ─────────────────────────────────────────────────────────────
// 本体
// ─────────────────────────────────────────────────────────────

function dryRunSchema() { runSchemaSync(true); }
function syncSchema()   { runSchemaSync(false); }

function runSchemaSync(dryRun) {
  var sheet   = resolveSheet();
  var header  = readHeader(sheet);
  var seen    = {};
  header.forEach(function(name) {
    var key = normalize(name);
    if (key) seen[key] = true;
  });

  var missing = SCHEMA.filter(function(name) { return !seen[normalize(name)]; });
  var extra   = header.filter(function(name) {
    return normalize(name) && SCHEMA.indexOf(name.trim()) === -1;
  });

  if (!dryRun && missing.length > 0) {
    var startCol = header.length + 1;
    sheet.getRange(1, startCol, 1, missing.length).setValues([missing]);
  }

  report(dryRun, sheet, header.length, missing, extra);
}

function resolveSheet() {
  var props   = PropertiesService.getScriptProperties();
  var ssId    = props.getProperty('SPREADSHEET_ID') || DEFAULT_SPREADSHEET_ID;
  var tabName = props.getProperty('TAB_NAME')       || DEFAULT_TAB_NAME;

  var ss = SpreadsheetApp.openById(ssId);
  var sheet = ss.getSheetByName(tabName);
  if (!sheet) {
    throw new Error('タブが見つかりません: "' + tabName + '"（Spreadsheet: ' + ssId + '）');
  }
  return sheet;
}

/** 1行目を読む。完全な空シートなら空配列。末尾の空セルは切り落とす。 */
function readHeader(sheet) {
  var lastCol = sheet.getLastColumn();
  if (lastCol < 1) return [];

  var row = sheet.getRange(1, 1, 1, lastCol).getValues()[0];
  while (row.length > 0 && normalize(row[row.length - 1]) === '') {
    row.pop();
  }
  return row.map(function(v) { return String(v); });
}

function normalize(v) {
  return String(v == null ? '' : v).trim().toLowerCase();
}

function report(dryRun, sheet, beforeCount, missing, extra) {
  var lines = [];
  lines.push(dryRun ? '【ドライラン】書き込みはしていません' : '【同期完了】');
  lines.push('シート: ' + sheet.getName());
  lines.push('現在の列数: ' + beforeCount + ' → ' + (beforeCount + (dryRun ? 0 : missing.length)));
  lines.push('');

  if (missing.length === 0) {
    lines.push('追加する列: なし（スキーマと一致しています）');
  } else {
    lines.push((dryRun ? '追加される列' : '追加した列') + '（' + missing.length + '）:');
    missing.forEach(function(name) { lines.push('  + ' + name); });
  }

  if (extra.length > 0) {
    lines.push('');
    lines.push('スキーマ外の列（' + extra.length + '）— 削除はしません:');
    extra.forEach(function(name) { lines.push('  ? ' + name); });
  }

  var text = lines.join('\n');
  Logger.log(text);
  try {
    SpreadsheetApp.getUi().alert(text);
  } catch (e) {
    // エディタから直接実行した場合は UI が使えないのでログのみ
  }
}
