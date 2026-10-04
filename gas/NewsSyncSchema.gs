/**
 * GREIGE MAGAZINE - NEWS_MASTER スキーマ同期
 *
 * NEWS_MASTER タブに足りない列を右端に追加するだけのスクリプト。
 * 削除・並べ替えは機能として持たせていない（既存行の値がずれる事故を防ぐため）。
 *
 * ■ Script Properties（ファイル → プロジェクトの設定 → スクリプト プロパティ）
 *   SPREADSHEET_ID : 省略時 DEFAULT_SPREADSHEET_ID
 *   NEWS_TAB_NAME  : 省略時 NEWS_MASTER
 *
 * ■ 使い方
 *   1.「ニュース スキーマ同期」→「ドライラン」で追加される列を確認
 *   2.「列を同期（追加のみ）」を実行
 */

var NEWS_DEFAULT_SPREADSHEET_ID = '1C4ljnBvsJmGzQe6aaWbdIUj4mXYjl7Mk3bPhdQfJQWw';
var NEWS_DEFAULT_TAB_NAME       = 'NEWS_MASTER';

var NEWS_SCHEMA = [
  // ── 識別・管理 ──
  'news_id',
  'content_type',
  'version',
  'prompt_version',

  // ── 企画 ──
  'editorial_theme',
  'editorial_reason',
  'target_reader',
  'season',
  'source_url',
  'source_name',
  'news_date',

  // ── キーワード・構造 ──
  'keywords',
  'key_points',
  'structure',
  'tone',

  // ── 本文 ──
  'title',
  'subtitle',
  'lead',
  'article_body',
  'call_to_action',

  // ── SNS用 ──
  'instagram_caption',
  'x_text',
  'threads_text',

  // ── ステータス ──
  'status',
  'review_status',
  'created_at',
  'updated_at',
  'approved_at',
  'approved_by',
  'notes',

  // ── WordPress同期 ──
  'wp_post_id',
  'wp_url',
  'wp_post_type',
  'wp_status',
  'slug',
  'featured_image_id',
  'wp_category',
  'wp_tags',
  'published_at',
  'wp_last_synced_at',
  'wp_content_version',
  'wp_error',

  // ── SEO ──
  'meta_title',
  'meta_description',
  'target_keyword',
  'secondary_keywords',
  'search_intent',
  'canonical_url',

  // ── 景品表示法 ──
  'disclosure_type',
  'disclosure_included',

  // ── 生成情報 ──
  'generated_by',
  'generated_at',
  'human_edited',
  'word_count',
  'scheduled_at',

  // ── AI生成フィールド ──
  'gemini_research_result',
  'gemini_research_at',
  'chatgpt_planning',
  'chatgpt_article',
  'chatgpt_article_at',
  'claude_article',
  'claude_article_at',
  'claude_api_model',
  'claude_api_status',
  'writing_rules_version',
];

// ─────────────────────────────────────────────────────────────
// メニュー
// ─────────────────────────────────────────────────────────────

function onOpenNews() {
  SpreadsheetApp.getActiveSpreadsheet().addMenu('ニュース スキーマ同期', [
    { name: '▶ ドライラン（確認のみ）',   functionName: 'newsDryRunSchema' },
    { name: '▶ 列を同期（追加のみ）',     functionName: 'newsSyncSchema'   },
  ]);
}

// ─────────────────────────────────────────────────────────────
// 本体
// ─────────────────────────────────────────────────────────────

function newsDryRunSchema() { newsRunSchemaSync(true); }
function newsSyncSchema()   { newsRunSchemaSync(false); }

function newsRunSchemaSync(dryRun) {
  var sheet   = newsResolveSheet();
  var header  = newsReadHeader(sheet);
  var seen    = {};
  header.forEach(function(name) {
    var key = newsNormalize(name);
    if (key) seen[key] = true;
  });

  var missing = NEWS_SCHEMA.filter(function(name) { return !seen[newsNormalize(name)]; });
  var extra   = header.filter(function(name) {
    return newsNormalize(name) && NEWS_SCHEMA.indexOf(name.trim()) === -1;
  });

  if (!dryRun && missing.length > 0) {
    var startCol = header.length + 1;
    sheet.getRange(1, startCol, 1, missing.length).setValues([missing]);
  }

  newsReport(dryRun, sheet, header.length, missing, extra);
}

function newsResolveSheet() {
  var props   = PropertiesService.getScriptProperties();
  var ssId    = props.getProperty('SPREADSHEET_ID') || NEWS_DEFAULT_SPREADSHEET_ID;
  var tabName = props.getProperty('NEWS_TAB_NAME')  || NEWS_DEFAULT_TAB_NAME;

  var ss = SpreadsheetApp.openById(ssId);
  var sheet = ss.getSheetByName(tabName);
  if (!sheet) {
    throw new Error('タブが見つかりません: "' + tabName + '"（Spreadsheet: ' + ssId + '）');
  }
  return sheet;
}

function newsReadHeader(sheet) {
  var lastCol = sheet.getLastColumn();
  if (lastCol < 1) return [];

  var row = sheet.getRange(1, 1, 1, lastCol).getValues()[0];
  while (row.length > 0 && newsNormalize(row[row.length - 1]) === '') {
    row.pop();
  }
  return row.map(function(v) { return String(v); });
}

function newsNormalize(v) {
  return String(v == null ? '' : v).trim().toLowerCase();
}

function newsReport(dryRun, sheet, beforeCount, missing, extra) {
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
