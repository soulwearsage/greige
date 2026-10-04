/**
 * GREIGE MAGAZINE - CONTENT_MASTER スキーマ移行
 *
 * これ1本で完結する。実行順を内部で保証しているので列は重複しない。
 *
 *   1. body → article_body にリネーム（列の位置も中身も保持したまま見出しだけ変える）
 *   2. そのあと不足列を右端に追加
 *
 * 何度実行しても結果は同じ（冪等）。削除と並べ替えは一切しない。
 */

function migrateContentMaster() {
  var TAB = 'CONTENT_MASTER';
  var RENAMES = [
    { from: 'body', to: 'article_body' },
  ];

  var SCHEMA = [
    'content_id',
    'plan_id',
    'content_type',
    'version',
    'prompt_version',
    'main_product_id',
    'related_product_ids',
    'main_image_id',
    'image_ids',
    'affiliate_link_ids',
    'editorial_theme',
    'editorial_reason',
    'target_reader',
    'season',
    'keywords',
    'key_points',
    'structure',
    'tone',
    'title',
    'subtitle',
    'lead',
    'editorial_note',
    'article_body',
    'generated_key_points',
    'call_to_action',
    'instagram_caption',
    'x_text',
    'video_copy',
    'status',
    'review_status',
    'created_at',
    'updated_at',
    'approved_at',
    'approved_by',
    'notes',
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
    'disclosure_type',
    'disclosure_included',
    'meta_title',
    'meta_description',
    'target_keyword',
    'secondary_keywords',
    'search_intent',
    'canonical_url',
    'duplicate_key',
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
    'claude_api_enabled',
    'claude_api_model',
    'claude_api_status',
    'writing_rules_version',
    'gemini_research_result',
    'gemini_research_at',
    'chatgpt_planning',
    'chatgpt_article',
    'chatgpt_article_at',
    'claude_article',
    'claude_article_at',
    'sns_post_type',
    'video_template_id',
    'remotion_video_url',
    'tiktok_caption',
    'youtube_title',
    'youtube_description',
    'threads_text',
  ];

  var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName(TAB);
  if (!sheet) {
    SpreadsheetApp.getUi().alert('タブが見つかりません: ' + TAB);
    return;
  }

  var log = [];

  // ── 1. リネーム ──────────────────────────────
  RENAMES.forEach(function(r) {
    var header = readHeader_(sheet);
    var iFrom  = indexOfCol_(header, r.from);
    var iTo    = indexOfCol_(header, r.to);

    if (iFrom === -1 && iTo !== -1) {
      log.push('= ' + r.to + ' は既にリネーム済み');
      return;
    }
    if (iFrom === -1) {
      log.push('= ' + r.from + ' は存在しない（新規追加で対応）');
      return;
    }
    if (iTo !== -1) {
      // 両方ある = 過去に重複が発生している。勝手に消さず報告だけする。
      log.push('! ' + r.from + '（' + colA1_(iFrom + 1) + '）と '
               + r.to + '（' + colA1_(iTo + 1) + '）が両方あります。'
               + '中身を確認して不要な方を手で削除してください。');
      return;
    }
    sheet.getRange(1, iFrom + 1).setValue(r.to);
    log.push('~ ' + r.from + ' → ' + r.to + '（' + colA1_(iFrom + 1) + '・データはそのまま）');
  });

  // ── 2. 不足列を追加 ──────────────────────────
  var header = readHeader_(sheet);
  var seen = {};
  header.forEach(function(h) {
    var k = norm_(h);
    if (k) seen[k] = true;
  });

  var missing = SCHEMA.filter(function(c) { return !seen[norm_(c)]; });

  if (missing.length > 0) {
    sheet.getRange(1, header.length + 1, 1, missing.length).setValues([missing]);
    log.push('');
    log.push('+ ' + missing.length + '列を追加:');
    missing.forEach(function(c) { log.push('    ' + c); });
  } else {
    log.push('');
    log.push('= 追加する列なし');
  }

  // ── 3. 結果 ──────────────────────────────────
  var after = readHeader_(sheet);
  var extra = after.filter(function(h) {
    return norm_(h) && SCHEMA.indexOf(String(h).trim()) === -1;
  });

  var out = [];
  out.push('【CONTENT_MASTER 移行完了】');
  out.push('列数: ' + header.length + ' → ' + after.length + '（スキーマ定義: ' + SCHEMA.length + '）');
  out.push('');
  out = out.concat(log);
  if (extra.length > 0) {
    out.push('');
    out.push('スキーマ外の列（削除していません）:');
    extra.forEach(function(c) { out.push('    ? ' + c); });
  }

  var text = out.join('\n');
  Logger.log(text);
  SpreadsheetApp.getUi().alert(text);
}

// ── ヘルパー ────────────────────────────────────

function readHeader_(sheet) {
  var last = sheet.getLastColumn();
  if (last < 1) return [];
  var row = sheet.getRange(1, 1, 1, last).getValues()[0];
  while (row.length > 0 && norm_(row[row.length - 1]) === '') row.pop();
  return row.map(function(v) { return String(v); });
}

function indexOfCol_(header, name) {
  var target = norm_(name);
  for (var i = 0; i < header.length; i++) {
    if (norm_(header[i]) === target) return i;
  }
  return -1;
}

function norm_(v) {
  return String(v == null ? '' : v).trim().toLowerCase();
}

/** 1 -> A, 27 -> AA（報告用の列記号） */
function colA1_(n) {
  var s = '';
  while (n > 0) {
    var m = (n - 1) % 26;
    s = String.fromCharCode(65 + m) + s;
    n = Math.floor((n - 1) / 26);
  }
  return s;
}
