/**
 * GREIGE MAGAZINE - CONTENT_MASTER スキーマ移行
 *
 * 1. body -> article_body にリネーム（位置・データはそのまま、見出しだけ変更）
 * 2. そのあと不足列を右端に追加
 *
 * 冪等。何度実行しても結果は同じ。削除と並べ替えはしない。
 */

function migrateContentMaster() {
  var TAB = 'CONTENT_MASTER';
  var RENAMES = [['body', 'article_body'], ['last_synced_at', 'wp_last_synced_at']];
  // 中身が完全に空のときだけ消す。1セルでも値があれば消さない。
  var DROP_IF_EMPTY = ['last_synced_at'];

  var SCHEMA = (
    'content_id,plan_id,content_type,version,prompt_version,main_product_id,' +
    'related_product_ids,main_image_id,image_ids,affiliate_link_ids,' +
    'editorial_theme,editorial_reason,target_reader,season,keywords,key_points,' +
    'structure,tone,title,subtitle,lead,editorial_note,article_body,' +
    'generated_key_points,call_to_action,instagram_caption,x_text,video_copy,' +
    'status,review_status,created_at,updated_at,approved_at,approved_by,notes,' +
    'wp_post_id,wp_url,wp_post_type,wp_status,slug,featured_image_id,wp_category,' +
    'wp_tags,published_at,wp_last_synced_at,wp_content_version,wp_error,' +
    'disclosure_type,disclosure_included,meta_title,meta_description,' +
    'target_keyword,secondary_keywords,search_intent,canonical_url,duplicate_key,' +
    'primary_merchant,price_range_min,price_range_max,link_check_status,' +
    'link_checked_at,review_due_at,generated_by,generated_at,human_edited,' +
    'word_count,source_research_id,scheduled_at,claude_api_enabled,' +
    'claude_api_model,claude_api_status,writing_rules_version,' +
    'gemini_research_result,gemini_research_at,chatgpt_planning,chatgpt_article,' +
    'chatgpt_article_at,claude_article,claude_article_at,sns_post_type,' +
    'video_template_id,remotion_video_url,tiktok_caption,youtube_title,' +
    'youtube_description,threads_text'
  ).split(',');

  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(TAB);
  if (!sheet) { SpreadsheetApp.getUi().alert('タブが見つかりません: ' + TAB); return; }

  var log = [];

  // 1. リネーム
  RENAMES.forEach(function(r) {
    var h = mcHeader_(sheet);
    var a = mcFind_(h, r[0]);
    var b = mcFind_(h, r[1]);
    if (a === -1 && b !== -1) { log.push('= ' + r[1] + ' は既にリネーム済み'); return; }
    if (a === -1) { log.push('= ' + r[0] + ' なし（新規追加で対応）'); return; }
    if (b !== -1) {
      log.push('= ' + r[0] + ' と ' + r[1] + ' が両方あります（下の処理で判定します）');
      return;
    }
    sheet.getRange(1, a + 1).setValue(r[1]);
    log.push('~ ' + r[0] + ' -> ' + r[1] + '（データはそのまま）');
  });

  // 2. 不足列を追加
  var header = mcHeader_(sheet);
  var seen = {};
  header.forEach(function(x) { var k = mcNorm_(x); if (k) seen[k] = true; });
  var missing = SCHEMA.filter(function(c) { return !seen[mcNorm_(c)]; });

  if (missing.length) {
    sheet.getRange(1, header.length + 1, 1, missing.length).setValues([missing]);
    log.push('');
    log.push('+ ' + missing.length + '列を追加:');
    missing.forEach(function(c) { log.push('    ' + c); });
  } else {
    log.push('');
    log.push('= 追加する列なし');
  }


  // 3. 重複した空列を削除（空であることを確認できた場合のみ）
  DROP_IF_EMPTY.forEach(function(name) {
    var h = mcHeader_(sheet);
    var i = mcFind_(h, name);
    if (i === -1) return;
    var lastRow = sheet.getLastRow();
    if (lastRow > 1) {
      var vals = sheet.getRange(2, i + 1, lastRow - 1, 1).getValues();
      var filled = vals.filter(function(r) { return mcNorm_(r[0]) !== ''; }).length;
      if (filled > 0) {
        log.push('! ' + name + ' に ' + filled + '件データがあるため削除しませんでした');
        return;
      }
    }
    sheet.deleteColumn(i + 1);
    log.push('- ' + name + ' を削除（空だったため）');
  });

  // 4. 結果
  var after = mcHeader_(sheet);
  var extra = after.filter(function(x) {
    return mcNorm_(x) && SCHEMA.indexOf(String(x).trim()) === -1;
  });

  var out = ['【CONTENT_MASTER 移行完了】',
             '列数: ' + header.length + ' -> ' + after.length + '（定義: ' + SCHEMA.length + '）',
             ''].concat(log);
  if (extra.length) {
    out.push('');
    out.push('スキーマ外の列（削除していません）:');
    extra.forEach(function(c) { out.push('    ? ' + c); });
  }

  var text = out.join('\n');
  Logger.log(text);
  SpreadsheetApp.getUi().alert(text);
}

function mcHeader_(sheet) {
  var last = sheet.getLastColumn();
  if (last < 1) return [];
  var row = sheet.getRange(1, 1, 1, last).getValues()[0];
  while (row.length && mcNorm_(row[row.length - 1]) === '') row.pop();
  return row.map(function(v) { return String(v); });
}

function mcFind_(header, name) {
  var t = mcNorm_(name);
  for (var i = 0; i < header.length; i++) if (mcNorm_(header[i]) === t) return i;
  return -1;
}

function mcNorm_(v) {
  return String(v == null ? '' : v).trim().toLowerCase();
}
