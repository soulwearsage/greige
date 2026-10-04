/**
 * GREIGE MAGAZINE - NEWS_POOL スキーマ同期
 *
 * News Board（キュレーション画面）が読むニュース候補テーブル。
 * 足りない列を右端に追加するだけ。削除・並べ替えはしない。
 *
 * ■ Script Properties
 *   SPREADSHEET_ID : 省略時 NP_DEFAULT_SPREADSHEET_ID
 *   NEWS_POOL_TAB  : 省略時 NEWS_POOL
 *
 * ■ 使い方
 *   1.「NEWS_POOL スキーマ」→「ドライラン」で追加される列を確認
 *   2.「列を同期（追加のみ）」を実行
 */

var NP_DEFAULT_SPREADSHEET_ID = '11e6LwzXi-5_1GyLz35zRV4e0pUPbq63fdB1NwJtWXMI';
var NP_DEFAULT_TAB_NAME       = 'NEWS_POOL';

var NP_SCHEMA = [
  // ── 識別 ──
  'news_id',

  // ── 記事情報 ──
  'title',
  'summary',
  'source_url',
  'source_name',
  'news_date',
  'collected_date',

  // ── 分類（NEWS_KEYWORDS由来）──
  'category',
  'keywords',
  'matched_keyword',

  // ── カード表示 ──
  'image_url',

  // ── AI判定 ──
  'ai_score',
  'ai_reason',

  // ── キュレーション ──
  'status',           // NEW / SELECTED / REVIEW / REJECTED / DUPLICATE
  'selected_date',
  'selected_by',
  'notes',

  // ── 画像保存（SELECT後の工程）──
  'image_saved',      // ドライブに保存した枚数
  'image_folder_url', // 保存先フォルダのURL

  // ── 後工程への引き継ぎ ──
  'news_master_id',   // NEWS_MASTER に起こしたときのID
  'created_at',
  'updated_at',
];

// ─────────────────────────────────────────────────────────────
// メニュー
// ─────────────────────────────────────────────────────────────

function onOpenNewsPool() {
  SpreadsheetApp.getActiveSpreadsheet().addMenu('NEWS_POOL スキーマ', [
    { name: '▶ ドライラン（確認のみ）', functionName: 'npDryRunSchema' },
    { name: '▶ 列を同期（追加のみ）',   functionName: 'npSyncSchema'   },
  ]);
}

// ─────────────────────────────────────────────────────────────
// 本体
// ─────────────────────────────────────────────────────────────

function npDryRunSchema() { npRunSchemaSync(true); }
function npSyncSchema()   { npRunSchemaSync(false); }

function npRunSchemaSync(dryRun) {
  var sheet  = npResolveSheet();
  var header = npReadHeader(sheet);

  var seen = {};
  header.forEach(function(name) {
    var key = npNormalize(name);
    if (key) seen[key] = true;
  });

  var missing = NP_SCHEMA.filter(function(name) { return !seen[npNormalize(name)]; });
  var extra   = header.filter(function(name) {
    return npNormalize(name) && NP_SCHEMA.indexOf(String(name).trim()) === -1;
  });

  if (!dryRun && missing.length > 0) {
    sheet.getRange(1, header.length + 1, 1, missing.length).setValues([missing]);
  }

  npReport(dryRun, sheet, header.length, missing, extra);
}

function npResolveSheet() {
  var props   = PropertiesService.getScriptProperties();
  var ssId    = props.getProperty('SPREADSHEET_ID') || NP_DEFAULT_SPREADSHEET_ID;
  var tabName = props.getProperty('NEWS_POOL_TAB')  || NP_DEFAULT_TAB_NAME;

  var ss    = SpreadsheetApp.openById(ssId);
  var sheet = ss.getSheetByName(tabName);
  if (!sheet) {
    sheet = ss.insertSheet(tabName);
  }
  return sheet;
}

function npReadHeader(sheet) {
  var lastCol = sheet.getLastColumn();
  if (lastCol < 1) return [];

  var row = sheet.getRange(1, 1, 1, lastCol).getValues()[0];
  while (row.length > 0 && npNormalize(row[row.length - 1]) === '') {
    row.pop();
  }
  return row.map(function(v) { return String(v); });
}

function npNormalize(v) {
  return String(v == null ? '' : v).trim().toLowerCase();
}

function npReport(dryRun, sheet, beforeCount, missing, extra) {
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
