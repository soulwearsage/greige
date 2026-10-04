/**
 * GREIGE MAGAZINE - WordPress投稿 [3/3] 実行本体
 * ※ PublishContent / PublishApi / PublishRun の3ファイルで1セット。
 *    どれか欠けると動きません。
 */

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
  // getUi() はエディタ実行時に固まるため呼ばない。結果は実行ログで見る。
  Logger.log(text);
}
