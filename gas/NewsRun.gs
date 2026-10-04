/**
 * GREIGE MAGAZINE - ニュース投稿 [3/3] 実行本体
 * ※ NewsContent / NewsApi / NewsRun の3ファイルで一組。
 *   どれか欠けると動きません。
 *
 * ■ 投稿条件（全て満たす行のみ処理）
 *   - review_status = APPROVED
 *   - title, article_body が空でない
 *   - disclosure_type が設定済み
 *   - PR/AFFILIATE/GIFTED の場合は disclosure_included = TRUE かつ本文にPR表記あり
 *
 * ■ 動作
 *   - wp_post_id が空 → 新規作成（下書き）
 *   - wp_post_id が埋まっている → 更新（status は変更しない）
 *   - 結果は wp_post_id / wp_url / wp_status / wp_last_synced_at / wp_error に書き戻す
 */

function newsPublishContent() { newsRunPublish(false); }
function newsDryRunPublish()  { newsRunPublish(true);  }

function newsRunPublish(isDryRun) {
  var props = newsGetProps();

  if (!isDryRun && (!props.wpUser || !props.wpPass)) {
    newsAlertText('Script Properties に WP_USER と WP_APP_PASS を設定してください。');
    return;
  }

  var ss = SpreadsheetApp.openById(props.sheetId);
  var sheet = ss.getSheetByName(props.sheetTab);
  if (!sheet) {
    newsAlertText('タブが見つかりません: "' + props.sheetTab + '"');
    return;
  }

  var lastRow = sheet.getLastRow();
  if (lastRow < 2) {
    newsAlertText('データ行がありません。');
    return;
  }

  var hmap    = newsGetHeaderMap(sheet);
  var missing = NEWS_NEEDED_COLUMNS.filter(function(c) { return hmap[c] === undefined; });
  if (missing.length > 0) {
    newsAlertText(
      '必要な列がありません:\n  ' + missing.join('\n  ') +
      '\n\nNewsSyncSchema.gs の「列を同期」を先に実行してください。'
    );
    return;
  }

  var rows      = sheet.getRange(2, 1, lastRow - 1, sheet.getLastColumn()).getValues();
  var lines     = [];
  var warnings  = [];
  var processed = 0;
  var skipped   = 0;
  var failed    = 0;

  rows.forEach(function(row, idx) {
    var sheetRow = idx + 2;

    var check = newsValidateRow(row, hmap);
    if (!check.ok) { skipped++; return; }

    var title = newsCell(row, hmap, 'title');
    var label = 'R' + sheetRow + ' ' + (title.length > 28 ? title.slice(0, 28) + '…' : title);

    var wpPostId = newsCell(row, hmap, 'wp_post_id');
    var isCreate = !wpPostId;
    processed++;

    if (check.warn) warnings.push('⚠ ' + label + ' → ' + check.warn);

    if (isDryRun) {
      lines.push((isCreate ? '新規' : '更新 #' + wpPostId) + ': ' + label + ' [post]');
      return;
    }

    try {
      var payload  = newsBuildPayload(row, hmap, props, isCreate);
      var endpoint = isCreate ? NEWS_REST_BASE : NEWS_REST_BASE + '/' + wpPostId;
      var result   = newsFetch(endpoint, 'post', payload, props);

      if (result.code === 200 || result.code === 201) {
        var now = new Date().toISOString();
        sheet.getRange(sheetRow, hmap['wp_post_id']        + 1).setValue(result.body.id);
        sheet.getRange(sheetRow, hmap['wp_url']            + 1).setValue(result.body.link || '');
        sheet.getRange(sheetRow, hmap['wp_status']         + 1).setValue(result.body.status || '');
        sheet.getRange(sheetRow, hmap['wp_last_synced_at'] + 1).setValue(now);
        sheet.getRange(sheetRow, hmap['wp_error']          + 1).setValue('');

        if (result.body.status === 'publish' && !newsCell(row, hmap, 'published_at')) {
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

  // ── サマリー ──
  var out = [];
  out.push(isDryRun ? '【ドライラン】送信していません' : '【投稿完了】');
  out.push('対象 ' + processed + ' / スキップ ' + skipped + ' / 失敗 ' + failed);
  out.push('');
  if (processed === 0) {
    out.push('対象行がありません。');
    out.push('review_status=APPROVED かつ title / article_body / disclosure_type が');
    out.push('埋まっている行が必要です。');
  } else {
    out = out.concat(lines);
  }
  if (warnings.length > 0) {
    out.push('');
    out.push('── 警告 ──');
    out = out.concat(warnings);
  }

  newsAlertText(out.join('\n'));
}

function newsAlertText(text) {
  Logger.log(text);
}
