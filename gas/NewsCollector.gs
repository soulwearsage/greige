/**
 * GREIGE MAGAZINE - ニュース収集
 *
 * NEWS_KEYWORDS スプレッドシートからキーワードを読み込み、
 * prtimes.jp と atpress.ne.jp を検索して NEWS_POOL に保存する。
 *
 * ■ Script Properties
 *   NEWS_KEYWORDS_ID : NEWS_KEYWORDSスプレッドシートID
 *   NEWS_POOL_ID     : NEWS_POOLスプレッドシートID
 *
 * ■ 使い方
 *   「▶ ニュースを収集」を実行（1回あたり最大30件×2サイト）
 *   毎日自動実行する場合はトリガーで collectNews() を設定
 */

var NC_KEYWORDS_ID = '1H5WtyDPXzxdnzj7RCnt0EggipQmIyCZfn58MIK3gFJE';
var NC_POOL_ID     = '11e6LwzXi-5_1GyLz35zRV4e0pUPbq63fdB1NwJtWXMI';
var NC_POOL_TAB    = 'NEWS_POOL';

// 1回の実行でキーワードあたり取得する最大記事数
var NC_MAX_PER_KEYWORD = 5;
// 1回の実行で処理するキーワード数の上限（タイムアウト対策）
var NC_MAX_KEYWORDS = 20;
// 1回のループで収集する記事の合計上限
var NC_MAX_TOTAL = 100;
// collectNewsLooped() が繰り返す回数
var NC_COLLECT_LOOPS = 5;
// 画像が取れなかった記事は保存しない（ボードに NO IMAGE を出さないため）
var NC_REQUIRE_IMAGE = true;
// キーワード未一致の記事も埋め草として収集するか
var NC_INCLUDE_UNMATCHED = false;
// 1実行の時間上限（GASの6分制限に対する安全マージン）
var NC_TIME_BUDGET_MS = 290 * 1000;
// 新着一覧を何ページ分まで辿るか（取得件数の最大化）
var NC_AP_PAGES = 8;
var NC_PR_PAGES = 6;

// ボットUAだと弾くサイトがあるのでブラウザのUAを使う
var NC_UA = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 ' +
            '(KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

// ─────────────────────────────────────────────────────────────
// メニュー
// ─────────────────────────────────────────────────────────────

function onOpen() {
  onOpenNewsCollector();
}

function createOnOpenTrigger() {
  var ss = SpreadsheetApp.openById(NC_POOL_ID);
  ScriptApp.newTrigger('onOpenNewsCollector')
    .forSpreadsheet(ss)
    .onOpen()
    .create();
}

function onOpenNewsCollector() {
  SpreadsheetApp.getActiveSpreadsheet().addMenu('News Collect', [
    { name: '▶ Collect News (with images)',        functionName: 'collectNews' },
    { name: '▶ Clear Pool & Collect Fresh',        functionName: 'ncClearAndCollect' },
    { name: '▶ Clear NEWS_POOL',                   functionName: 'ncClearPool' },
    { name: '── ─ ──',                             functionName: 'ncDiagnose' },
    { name: '▶ Debug Collection (no image check)', functionName: 'ncDebugBulkCollect' },
    { name: '▶ Fill Missing Images',               functionName: 'ncFillMissingImages' },
    { name: '▶ Remove Google News Rows',           functionName: 'ncClearGoogleNewsRows' },
    { name: '▶ Diagnose Connection',               functionName: 'ncDiagnose' },
    { name: '── ─ ──',                             functionName: 'ncDiagnose' },
    { name: '▶ Transfer to SELECTED_NEWS',         functionName: 'ncTransferToSelected' },
    { name: '▶ Push News to WordPress',            functionName: 'ncPushNewsToWordPress' },
  ]);
}

// ─────────────────────────────────────────────────────────────
// SELECTED_NEWS / WordPress
// ─────────────────────────────────────────────────────────────
var NC_SELECTED_NEWS_ID  = '1ULM6KFGsNoUWXM2AG9uYkQO7pUFffgg2pOeT4x0Miw8';
var NC_SELECTED_NEWS_TAB = 'シート1';
var NC_WP_URL            = 'https://greige.online';
var NC_WP_USER           = 'AICO';
// WP_APP_PASS は Script Properties に設定: PropertiesService.getScriptProperties().setProperty('WP_APP_PASS','xxxx xxxx ...')

/**
 * NEWS_POOL のデータ行をすべて削除してヘッダーだけの状態に戻す。
 * 実行前に確認ダイアログを出す。
 */
function ncClearPool() {
  var props   = PropertiesService.getScriptProperties();
  var poolId  = props.getProperty('NEWS_POOL_ID') || NC_POOL_ID;
  var poolTab = props.getProperty('NEWS_POOL_TAB') || NC_POOL_TAB;
  var ss      = SpreadsheetApp.openById(poolId);
  var sheet   = ss.getSheetByName(poolTab);
  if (!sheet) { Logger.log('NEWS_POOL タブが見つかりません'); return; }

  var lastRow = sheet.getLastRow();
  if (lastRow <= 1) {
    try { SpreadsheetApp.getUi().alert('NEWS_POOL はすでに空です。'); } catch(e) {}
    Logger.log('NEWS_POOL はすでに空です');
    return;
  }

  var confirmed = false;
  try {
    var ui  = SpreadsheetApp.getUi();
    var res = ui.alert(
      'NEWS_POOL をクリアしますか？',
      (lastRow - 1) + '行のデータをすべて削除します。この操作は元に戻せません。',
      ui.ButtonSet.OK_CANCEL
    );
    confirmed = (res === ui.Button.OK);
  } catch(e) {
    confirmed = true; // トリガー実行時などUIなし → そのまま実行
  }

  if (!confirmed) { Logger.log('キャンセルされました'); return; }

  sheet.deleteRows(2, lastRow - 1);
  Logger.log('NEWS_POOL をクリアしました（' + (lastRow - 1) + '行削除）');
  try { SpreadsheetApp.getUi().alert('NEWS_POOL をクリアしました（' + (lastRow - 1) + '行削除）'); } catch(e) {}
}

/**
 * NEWS_POOL をクリアしてから ncCollect を実行する。
 */
function ncClearAndCollect() {
  ncClearPool();
  ncCollect(false);
}

/**
 * NEWS_POOL (status=NEW) の記事を SELECTED_NEWS へ転送する。
 * 重複URLはスキップ。転送後 NEWS_POOL の status を TRANSFERRED に更新。
 */
function ncTransferToSelected() {
  var poolSs    = SpreadsheetApp.openById(NC_POOL_ID);
  var poolSheet = poolSs.getSheetByName(NC_POOL_TAB);
  var poolData  = poolSheet.getDataRange().getValues();
  var poolH = {};
  poolData[0].forEach(function(h, i) { poolH[String(h).trim()] = i; });

  var selSs    = SpreadsheetApp.openById(NC_SELECTED_NEWS_ID);
  var selSheet = selSs.getSheetByName(NC_SELECTED_NEWS_TAB);
  var selHeaders = selSheet.getRange(1, 1, 1, selSheet.getLastColumn()).getValues()[0];
  var selH = {};
  selHeaders.forEach(function(h, i) { selH[String(h).trim()] = i; });

  // 重複チェック用：既存URLを取得
  var existingUrls = {};
  if (selSheet.getLastRow() > 2) {
    var col = selH['source_url'] + 1;
    var existing = selSheet.getRange(3, col, selSheet.getLastRow() - 2, 1).getValues();
    existing.forEach(function(r) { if (r[0]) existingUrls[String(r[0])] = true; });
  }

  var now = Utilities.formatDate(new Date(), 'Asia/Tokyo', 'yyyy-MM-dd HH:mm:ss');
  var transferred = 0;

  for (var i = 1; i < poolData.length; i++) {
    var row    = poolData[i];
    var status = String(row[poolH['status']] || '').trim();
    var url    = String(row[poolH['source_url']] || '').trim();
    if (status !== 'NEW' || !url || existingUrls[url]) continue;

    var newRow = new Array(selHeaders.length).fill('');
    newRow[selH['news_id']]      = String(row[poolH['news_id']] || '');
    newRow[selH['source_name']]  = String(row[poolH['source_name']] || '');
    newRow[selH['source_url']]   = url;
    newRow[selH['source_title']] = String(row[poolH['title']] || '');
    newRow[selH['source_date']]  = String(row[poolH['news_date']] || '');
    newRow[selH['source_body']]  = String(row[poolH['summary']] || '');
    newRow[selH['category']]     = String(row[poolH['category']] || '');
    newRow[selH['topic']]        = String(row[poolH['matched_keyword']] || '');
    newRow[selH['image_ids']]    = String(row[poolH['image_url']] || '');
    newRow[selH['status']]       = 'NEW';
    newRow[selH['updated_at']]   = now;

    selSheet.appendRow(newRow);
    existingUrls[url] = true;
    poolSheet.getRange(i + 1, poolH['status'] + 1).setValue('TRANSFERRED');
    poolSheet.getRange(i + 1, poolH['selected_date'] + 1).setValue(now);
    transferred++;
  }

  Logger.log('SELECTED_NEWS へ転送: ' + transferred + '件');
  SpreadsheetApp.getActiveSpreadsheet().toast(transferred + '件を SELECTED_NEWS へ転送しました。');
}

/**
 * SELECTED_NEWS (status=NEW) の記事を WordPress へ下書き投稿する。
 * 投稿済み (wordpress_post_id あり) はスキップ。
 */
function ncPushNewsToWordPress() {
  var ss      = SpreadsheetApp.openById(NC_SELECTED_NEWS_ID);
  var sheet   = ss.getSheetByName(NC_SELECTED_NEWS_TAB);
  var headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];
  var sh = {};
  headers.forEach(function(h, i) { sh[String(h).trim()] = i; });

  if (sheet.getLastRow() < 3) { Logger.log('SELECTED_NEWS にデータなし'); return; }

  var rows   = sheet.getRange(3, 1, sheet.getLastRow() - 2, sheet.getLastColumn()).getValues();
  var wpPass = PropertiesService.getScriptProperties().getProperty('WP_APP_PASS') || '';
  var auth   = Utilities.base64Encode(NC_WP_USER + ':' + wpPass);
  var pushed = 0;

  rows.forEach(function(row, i) {
    var rowNum   = i + 3;
    var wpPostId = String(row[sh['wordpress_post_id']] || '').trim();
    var status   = String(row[sh['status']] || '').trim();
    if (wpPostId) return;
    if (status !== 'NEW' && status !== 'SELECTED') return;

    var title = String(row[sh['source_title']] || '').trim();
    var body  = String(row[sh['source_body']]  || '').trim();
    if (!title) return;

    var postData = {
      title:   title,
      content: body,
      status:  'draft',
      meta: {
        news_source_url:  String(row[sh['source_url']]   || ''),
        news_source_name: String(row[sh['source_name']]  || ''),
        news_image_url:   String(row[sh['image_ids']]    || ''),
        news_category:    String(row[sh['category']]     || '')
      }
    };

    try {
      var resp = UrlFetchApp.fetch(NC_WP_URL + '/wp-json/wp/v2/posts', {
        method: 'POST',
        contentType: 'application/json',
        headers: { Authorization: 'Basic ' + auth },
        payload: JSON.stringify(postData),
        muteHttpExceptions: true
      });
      var code = resp.getResponseCode();
      if (code === 201) {
        var result = JSON.parse(resp.getContentText());
        sheet.getRange(rowNum, sh['wordpress_post_id'] + 1).setValue(result.id);
        sheet.getRange(rowNum, sh['wordpress_url']     + 1).setValue(result.link);
        sheet.getRange(rowNum, sh['wordpress_status']  + 1).setValue('draft');
        sheet.getRange(rowNum, sh['status']            + 1).setValue('WP_DRAFT');
        Logger.log('投稿成功: ' + title + ' (WP ID: ' + result.id + ')');
        pushed++;
      } else {
        Logger.log('投稿失敗 行' + rowNum + ' HTTP ' + code + ': ' + resp.getContentText().slice(0, 300));
      }
    } catch(e) {
      Logger.log('エラー 行' + rowNum + ': ' + e.message);
    }
  });

  Logger.log('WordPress 下書き投稿: ' + pushed + '件');
  SpreadsheetApp.getActiveSpreadsheet().toast(pushed + '件を WordPress に下書き投稿しました。');
}

/**
 * 収集のどこで件数が絞られているかを確認するデバッグ関数。
 * 画像チェックなし・DBへの書き込みなしでキーワード照合まで実行する。
 * ログに「どのソースから何件・何キーワードにヒット・タイトル例」が出る。
 */
function ncDebugBulkCollect() {
  var t0 = new Date().getTime();
  var props   = PropertiesService.getScriptProperties();
  var kwId    = props.getProperty('NEWS_KEYWORDS_ID') || NC_KEYWORDS_ID;
  var keywords = ncLoadKeywords(kwId);
  Logger.log('キーワード総数: ' + keywords.length);

  NC_PR_FEED_CACHE = null;
  var prFeed    = ncLoadPrTimesFeed();
  var prTopics  = ncLoadPrTimesTopics();
  var prFashion = ncLoadPrTimesFashionSearch();
  var apFeed    = ncLoadAtPressFeed();

  Logger.log('=== 取得件数 ===');
  Logger.log('PR TIMES RSS: '          + prFeed.length);
  Logger.log('PR TIMES トピック: '     + prTopics.length);
  Logger.log('PR TIMES ファッション検索: ' + prFashion.length);
  Logger.log('AT PRESS: '              + apFeed.length);

  // AT PRESS のタイトル取得状況
  var apNoTitle = 0;
  apFeed.forEach(function(a) { if (!a.title || a.title.length < 3) apNoTitle++; });
  Logger.log('AT PRESS タイトルなし: ' + apNoTitle + '件 / ' + apFeed.length + '件');

  // PR TIMES トピックのタイトル取得状況
  var prNoTitle = 0;
  prTopics.forEach(function(a) { if (!a.title || a.title.length < 3) prNoTitle++; });
  Logger.log('PR TIMES トピック タイトルなし: ' + prNoTitle + '件');

  var combined = prFeed.concat(prTopics).concat(prFashion).concat(apFeed);
  Logger.log('=== 合計記事: ' + combined.length + '件 ===');

  // キーワード照合
  var queue    = [];
  var seenUrls = {};
  var kwHits   = {};

  for (var j = 0; j < combined.length; j++) {
    var art = combined[j];
    if (!art.source_url || seenUrls[art.source_url]) continue;
    // ジャンルフィルタ（アニメ・ゲーム・食品等を除外）
    if (!ncIsFashionGenre(art.title + ' ' + art.summary)) continue;
    var rawHay  = (art.title + ' ' + art.summary).toLowerCase();
    var normHay = ncNorm(art.title + ' ' + art.summary);

    for (var k = 0; k < keywords.length; k++) {
      var terms = keywords[k].terms || ncKeywordTerms(keywords[k].keyword);
      var hit   = false;
      for (var t = 0; t < terms.length; t++) {
        var hay = terms[t].normalized ? normHay : rawHay;
        if (hay.indexOf(terms[t].t) !== -1) { hit = true; break; }
      }
      if (hit) {
        art._kw = keywords[k];
        seenUrls[art.source_url] = true;
        queue.push(art);
        kwHits[keywords[k].keyword] = (kwHits[keywords[k].keyword] || 0) + 1;
        break;
      }
    }
  }

  Logger.log('=== キーワードヒット: ' + queue.length + '件 ===');

  // ヒットキーワード上位10
  var kwSorted = Object.keys(kwHits).sort(function(a, b) { return kwHits[b] - kwHits[a]; });
  Logger.log('--- ヒットキーワード上位10 ---');
  kwSorted.slice(0, 10).forEach(function(kw) {
    Logger.log('  ' + kw + ': ' + kwHits[kw] + '件');
  });

  // ヒット記事の先頭20件のタイトル
  Logger.log('--- ヒット記事タイトル（先頭20件）---');
  queue.slice(0, 20).forEach(function(a, i) {
    Logger.log((i+1) + '. [' + a.source_name + '] ' + (a.title || '★タイトルなし★').slice(0, 60) +
               ' (kw=' + (a._kw ? a._kw.keyword : '') + ')');
  });

  Logger.log('=== 経過時間: ' + Math.round((new Date().getTime() - t0) / 1000) + '秒 ===');
  var msg = '収集診断結果:\n' +
    'PR TIMES RSS: ' + prFeed.length + '件\n' +
    'PR TIMES トピック: ' + prTopics.length + '件\n' +
    'PR TIMES ファッション検索: ' + prFashion.length + '件\n' +
    'AT PRESS: ' + apFeed.length + '件\n' +
    '合計: ' + combined.length + '件\n' +
    'キーワードヒット: ' + queue.length + '件\n' +
    '(画像チェック前)';
  try { SpreadsheetApp.getUi().alert(msg); } catch(e) {}
}

// ─────────────────────────────────────────────────────────────
// エントリーポイント
// ─────────────────────────────────────────────────────────────

function collectNewsDryRun() { ncCollect(true); }
function collectNews()       { ncCollect(false); }

/** 両サイト新着を一括取得しキーワード照合（1ボタンで完了） */
function collectNewsLooped() {
  for (var i = 0; i < NC_COLLECT_LOOPS; i++) {
    Logger.log('=== ループ ' + (i + 1) + ' / ' + NC_COLLECT_LOOPS + ' ===');
    ncCollect(false);
  }
  Logger.log('全ループ完了');
}

function ncCollect(dryRun) {
  var t0 = new Date().getTime();

  var props   = PropertiesService.getScriptProperties();
  var kwId    = props.getProperty('NEWS_KEYWORDS_ID') || NC_KEYWORDS_ID;
  var poolId  = props.getProperty('NEWS_POOL_ID')     || NC_POOL_ID;
  var poolTab = props.getProperty('NEWS_POOL_TAB')    || NC_POOL_TAB;

  var keywords  = ncLoadKeywords(kwId);
  var pool      = ncLoadPool(poolId, poolTab);
  var existUrls = pool.existUrls;
  var sheet     = pool.sheet;
  var header    = pool.header;

  // ① 両サイト新着を一括取得してRSS照合用プールを作る
  NC_PR_FEED_CACHE = null;
  var prFeed    = ncLoadPrTimesFeed();          // PR TIMES 公式RSS（ファッション系topics優先）
  var prTopics  = ncLoadPrTimesTopics();        // PR TIMES トピックページ（HTML）
  var prFashion = ncLoadPrTimesFashionSearch(); // PR TIMES ファッション検索（fetchAll並列）
  var apFeed    = ncLoadAtPressFeed();          // AT PRESS 新着一覧（複数ページ）
  Logger.log('PR TIMES RSS ' + prFeed.length + '件 / トピック ' + prTopics.length + '件 / ファッション検索 ' + prFashion.length + '件 / AT PRESS ' + apFeed.length + '件');

  // ② RSS記事をキーワード照合（CULTURE・TOPICなど英語でも一致しやすいもの）
  var queue    = [];
  var seenUrls = {};

  var combined = prFeed.concat(prTopics).concat(prFashion).concat(apFeed);
  for (var j = 0; j < combined.length; j++) {
    var art = combined[j];
    if (!art.source_url || seenUrls[art.source_url]) continue;

    // ファッション・美容・ライフスタイル以外のジャンルを事前フィルタリング
    if (!ncIsFashionGenre(art.title + ' ' + art.summary)) continue;

    var rawHay  = (art.title + ' ' + art.summary).toLowerCase();
    var normHay = ncNorm(art.title + ' ' + art.summary);

    for (var k = 0; k < keywords.length; k++) {
      var terms = keywords[k].terms || ncKeywordTerms(keywords[k].keyword);
      var hit   = false;
      for (var t = 0; t < terms.length; t++) {
        var hay = terms[t].normalized ? normHay : rawHay;
        if (hay.indexOf(terms[t].t) !== -1) { hit = true; break; }
      }
      if (hit) {
        art._kw = keywords[k];
        seenUrls[art.source_url] = true;
        queue.push(art);
        break;
      }
    }
  }
  Logger.log('キーワード照合ヒット: ' + queue.length + '件 / 記事 ' + combined.length + '件 / キーワード ' + keywords.length + '件');

  var today = Utilities.formatDate(new Date(), 'Asia/Tokyo', 'yyyy-MM-dd');
  var now   = Utilities.formatDate(new Date(), 'Asia/Tokyo', 'yyyy-MM-dd HH:mm:ss');

  // 重複除去済みの候補を上限まで絞る
  var candidates = [];
  for (var q = 0; q < queue.length && candidates.length < NC_MAX_TOTAL; q++) {
    if (!queue[q].source_url || existUrls[queue[q].source_url]) continue;
    candidates.push(queue[q]);
  }
  Logger.log('重複除去後: ' + candidates.length + '件');

  // 画像URLが未取得の記事だけ fetchAll で並列取得
  if (!dryRun) {
    var noImgArts = candidates.filter(function(a) { return !a.image_url; });
    if (noImgArts.length > 0) {
      var requests = noImgArts.map(function(a) {
        return { url: a.source_url, muteHttpExceptions: true, followRedirects: true,
                 deadline: 10, headers: { 'User-Agent': NC_UA } };
      });
      var responses = UrlFetchApp.fetchAll(requests);
      for (var r = 0; r < responses.length; r++) {
        try {
          if (responses[r].getResponseCode() === 200) {
            var html = responses[r].getContentText('UTF-8');
            var img  = ncPickOgImage(html);
            if (img) {
              noImgArts[r].image_url = img;
              if (!noImgArts[r].title || noImgArts[r].title.length < 3)
                noImgArts[r].title = ncPickOgTitle(html) || noImgArts[r].title;
            }
          }
        } catch(e) { Logger.log('fetchAll error: ' + e.message); }
      }
      Logger.log('fetchAll完了: ' + Math.round((new Date().getTime() - t0)/1000) + '秒');
    }
  }

  var saved = 0, noImage = 0, dup = 0;
  for (var s = 0; s < candidates.length; s++) {
    var art = candidates[s];
    if (!art.image_url) { noImage++; continue; } // 画像なしは除外

    var kwRef = art._kw;
    var row = {
      news_id:         ncGenId(),
      title:           art.title,
      summary:         art.summary,
      source_url:      art.source_url,
      source_name:     art.source_name,
      news_date:       art.news_date,
      image_url:       art.image_url,
      category:        kwRef ? kwRef.category : 'TREND',
      matched_keyword: kwRef ? kwRef.keyword  : '',
      keywords:        kwRef ? kwRef.keyword  : '',
      collected_date:  today,
      status:          'NEW',
      created_at:      now,
      updated_at:      now,
    };

    existUrls[art.source_url] = true;
    if (!dryRun) ncAppendRow(sheet, header, row);
    saved++;
    Logger.log('✓ ' + String(art.title || art.source_url).slice(0, 40));
  }

  var msg = (dryRun ? '「ドライラン」' : '「収集完了」') + '\n' +
    '新規保存: ' + saved + '件（すべて画像付き）\n' +
    '画像なしで除外: ' + noImage + '件\n' +
    '重複スキップ: ' + dup + '件\n' +
    '経過時間: ' + Math.round((new Date().getTime() - t0)/1000) + '秒';
  Logger.log(msg);
  try { SpreadsheetApp.getUi().alert(msg); } catch (e) {}
}

// ─────────────────────────────────────────────────────────────
// NEWS_KEYWORDS 読み込み
// ─────────────────────────────────────────────────────────────

// ─────────────────────────────────────────────────────────────
// キーワード正規化と別表記辞書
//   英語キーワードは日本語記事タイトルにそのまま現れないことが多い。
//   検索方式に戻さず、表記ゆれを吸収して一括照合でヒットさせる。
// ─────────────────────────────────────────────────────────────

/** 全角英数を半角にし、記号・空白を落として小文字化する */
function ncNorm(s) {
  if (!s) return '';
  var t = String(s);
  var out = '';
  for (var i = 0; i < t.length; i++) {
    var code = t.charCodeAt(i);
    // 全角英数記号(FF01-FF5E) → 半角(21-7E)
    if (code >= 0xFF01 && code <= 0xFF5E) code -= 0xFEE0;
    // 全角スペース
    if (code === 0x3000) code = 0x20;
    out += String.fromCharCode(code);
  }
  return out
    .toLowerCase()
    .replace(/[\s\.\-_'’`"“”&,:;!\?\(\)\[\]\/\\\+]/g, '');
}

/** 英語キーワード(正規化後) → 日本語・カタカナ別表記 */
var NC_KW_ALIASES = {
  // BRAND
  'apc':['アーペーセー'], 'acnestudios':['アクネストゥディオズ','アクネ'],
  'adidas':['アディダス'], 'nike':['ナイキ'], 'puma':['プーマ'], 'reebok':['リーボック'],
  'asics':['アシックス'], 'newbalance':['ニューバランス'], 'converse':['コンバース'],
  'vans':['バンズ'], 'crocs':['クロックス'], 'birkenstock':['ビルケンシュトック'],
  'drmartens':['ドクターマーチン'], 'salomon':['サロモン'], 'arcteryx':['アークテリクス'],
  'hoka':['ホカ'], 'onrunning':['オンランニング'],
  'gucci':['グッチ'], 'prada':['プラダ'], 'miumiu':['ミュウミュウ'],
  'chanel':['シャネル'], 'dior':['ディオール'], 'fendi':['フェンディ'],
  'hermes':['エルメス'], 'louisvuitton':['ルイヴィトン','ルイ・ヴィトン'],
  'balenciaga':['バレンシアガ'], 'celine':['セリーヌ'], 'loewe':['ロエベ'],
  'saintlaurent':['サンローラン'], 'bottegaveneta':['ボッテガヴェネタ'],
  'valentino':['ヴァレンティノ'], 'versace':['ヴェルサーチ'], 'burberry':['バーバリー'],
  'moncler':['モンクレール'], 'maisonmargiela':['メゾンマルジェラ','マルジェラ'],
  'jilsander':['ジルサンダー'], 'lemaire':['ルメール'], 'therow':['ザロウ'],
  'toteme':['トーテム'], 'ganni':['ガニー'], 'stoneisland':['ストーンアイランド'],
  'uniqlo':['ユニクロ'], 'zara':['ザラ'], 'hm':['エイチアンドエム'],
  'supreme':['シュプリーム'], 'stussy':['ステューシー'], 'carhartt':['カーハート'],
  'patagonia':['パタゴニア'], 'thenorthface':['ノースフェイス'],
  'nanamica':['ナナミカ'], 'sacai':['サカイ'],
  'commedesgarcons':['コムデギャルソン','ギャルソン'],
  'yohjiyamamoto':['ヨウジヤマモト'], 'isseymiyake':['イッセイミヤケ'],
  'undercover':['アンダーカバー'], 'visvim':['ヴィスヴィム'], 'needles':['ニードルズ'],
  'beams':['ビームス'], 'unitedarrows':['ユナイテッドアローズ'],
  'journalstandard':['ジャーナルスタンダード'],
  // PERSON
  'pharrellwilliams':['ファレル・ウィリアムス','ファレル'],
  'demnagvasalia':['デムナ・ヴァザリア','デムナ'],
  'jonathananderson':['ジョナサン・アンダーソン'],
  // CULTURE / TOPIC
  'collaboration':['コラボ','コラボレーション'],
  'brandcollaboration':['コラボ','コラボレーション'],
  'capsulecollection':['カプセルコレクション'],
  'collection':['コレクション'], 'runway':['ランウェイ'],
  'sneaker':['スニーカー'], 'sneakers':['スニーカー'],
  'denim':['デニム'], 'vintage':['ヴィンテージ','ビンテージ'],
  'streetwear':['ストリート'], 'sustainability':['サステナブル','サステナビリティ'],
  'popup':['ポップアップ'], 'flagshipstore':['旗艦店','フラッグシップ'],
  'exhibition':['展示会','展覧会'],
  '90sfashion':['90年代'], '90ssportswear':['90年代'], '2000sfashion':['2000年代'],
};

/** キーワード1件を照合用の語リストにする（正規化語 / 生語 / 別表記） */
function ncKeywordTerms(keyword) {
  var raw  = String(keyword || '').trim();
  var norm = ncNorm(raw);
  var terms = [];
  if (norm.length >= 3) terms.push({ t: norm, normalized: true });
  if (raw.length >= 2)  terms.push({ t: raw.toLowerCase(), normalized: false });
  var aliases = NC_KW_ALIASES[norm];
  if (aliases) {
    for (var i = 0; i < aliases.length; i++) {
      if (aliases[i].length >= 2) terms.push({ t: aliases[i].toLowerCase(), normalized: false });
    }
  }
  return terms;
}

function ncLoadKeywords(ssId) {
  var ss       = SpreadsheetApp.openById(ssId);
  var tabs     = ['BRAND', 'PERSON', 'CULTURE', 'TOPIC'];
  var keywords = [];

  tabs.forEach(function(tabName) {
    var sheet = ss.getSheetByName(tabName);
    if (!sheet) return;

    var lastRow = sheet.getLastRow();
    if (lastRow < 2) return;

    var values = sheet.getRange(1, 1, lastRow, sheet.getLastColumn()).getValues();
    var header = values[0];
    var hmap   = {};
    header.forEach(function(h, i) { hmap[String(h).trim()] = i; });

    for (var r = 1; r < values.length; r++) {
      var row     = values[r];
      var keyword = String(row[hmap['keyword'] || 0] || '').trim();
      var enabled = row[hmap['enabled'] !== undefined ? hmap['enabled'] : -1];
      var category= String(row[hmap['category'] !== undefined ? hmap['category'] : -1] || '').trim();

      if (!keyword) continue;
      if (enabled === false || String(enabled).toUpperCase() === 'FALSE') continue;

      var terms = ncKeywordTerms(keyword);

      // シートに別表記列があれば追加（aliases / keyword_ja / alt / 日本語表記）
      ['aliases', 'keyword_ja', 'alt', '別表記', '日本語表記'].forEach(function(col) {
        if (hmap[col] === undefined) return;
        String(row[hmap[col]] || '').split(/[,;、\/|]/).forEach(function(a) {
          var s = a.trim();
          if (s.length >= 2) terms.push({ t: s.toLowerCase(), normalized: false });
        });
      });

      keywords.push({ keyword: keyword, category: category, type: tabName, terms: terms });
    }
  });

  return keywords;
}

// ─────────────────────────────────────────────────────────────
// NEWS_POOL 読み込み
// ─────────────────────────────────────────────────────────────

var NC_POOL_SCHEMA = [
  'news_id', 'title', 'summary', 'source_url', 'source_name',
  'news_date', 'collected_date', 'category', 'keywords', 'matched_keyword',
  'image_url', 'ai_score', 'ai_reason', 'status', 'selected_date',
  'selected_by', 'notes', 'image_saved', 'image_folder_url',
  'news_master_id', 'created_at', 'updated_at',
];

function ncLoadPool(ssId, tabName) {
  var ss    = SpreadsheetApp.openById(ssId);
  var sheet = ss.getSheetByName(tabName);
  if (!sheet) throw new Error('NEWS_POOLタブが見つかりません');

  var existUrls = {};
  var lastRow   = sheet.getLastRow();

  var header = [];
  if (lastRow >= 1) {
    var lastCol = sheet.getLastColumn();
    if (lastCol >= 1) {
      header = sheet.getRange(1, 1, 1, lastCol).getValues()[0].map(function(v) { return String(v).trim(); });
    }
  }

  // ヘッダーが空なら自動でスキーマを書き込む
  if (header.filter(function(h) { return h; }).length === 0) {
    sheet.getRange(1, 1, 1, NC_POOL_SCHEMA.length).setValues([NC_POOL_SCHEMA]);
    header = NC_POOL_SCHEMA.slice();
    Logger.log('NEWS_POOL ヘッダーを初期化しました');
  }

  if (lastRow >= 2 && header.length > 0) {
    var urlIdx = header.indexOf('source_url');
    if (urlIdx >= 0) {
      var urls = sheet.getRange(2, urlIdx + 1, lastRow - 1, 1).getValues();
      urls.forEach(function(row) {
        var u = String(row[0]).trim();
        if (u) existUrls[u] = true;
      });
    }
  }

  return { sheet: sheet, header: header, existUrls: existUrls };
}

// ─────────────────────────────────────────────────────────────
// 行追加
// ─────────────────────────────────────────────────────────────

function ncAppendRow(sheet, header, art) {
  var row = header.map(function(col) { return art[col] || ''; });
  sheet.appendRow(row);
}

// ─────────────────────────────────────────────────────────────
// prtimes.jp 検索
//
// 経路A（優先）: prtimes.jp 公式RSS。実URLが直接取れるので og:image が確実に取れる。
//                1回の実行で1度だけ取得してキャッシュし、キーワードで絞り込む。
// 経路B（代替）: Google News RSS。CBMi URLは暗号化されていて復号できないため、
//                記事ページを1回開いて実URLを取り出す。
// ─────────────────────────────────────────────────────────────

// ─────────────────────────────────────────────────────────────
// ファッション・美容・ライフスタイル ジャンル判定
// ─────────────────────────────────────────────────────────────

/**
 * 記事テキストがファッション・美容・ライフスタイル関連かどうか判定する。
 * アニメ・ゲーム・食品・スポーツイベント等のジャンル外を弾く。
 * true = ファッション/美容/ライフスタイル系、false = 除外
 */
function ncIsFashionGenre(text) {
  if (!text) return false;
  var t = text.toLowerCase();

  // 除外ワード: これらが含まれる記事はジャンル外として弾く
  var NG_TERMS = [
    'アニメ', 'anime', 'マンガ', '漫画', '声優', 'ゲーム', 'game', 'ゲームキャラ',
    'コスプレ', 'cosplay', '同人', 'ライトノベル', 'ラノベ', 'vtuber', 'vチューバー',
    'アイドル', 'idol', 'k-pop', 'kpop', 'j-pop', 'jpop',
    'ラーメン', 'ramen', '居酒屋', '焼肉', 'グルメ', 'レシピ', '食材', '調理',
    'スポーツカー', 'auto', 'モータースポーツ', '競馬', '競輪', 'パチンコ',
    '不動産', '保険', '金融', '株式', '仮想通貨', '暗号通貨', 'crypto',
    '医療', '病院', '製薬', 'クリニック',
    '葬儀', '婚活', '就活',
  ];
  for (var i = 0; i < NG_TERMS.length; i++) {
    if (t.indexOf(NG_TERMS[i]) !== -1) return false;
  }

  // 必要条件: ファッション・美容・ライフスタイル系の語を少なくとも1つ含む
  var OK_TERMS = [
    'ファッション', 'fashion', 'スタイル', 'style', 'コーデ', 'コーディネート',
    '美容', 'beauty', 'コスメ', 'cosme', 'メイク', 'makeup', 'スキンケア', 'skincare',
    'フレグランス', '香水', 'perfume', 'fragrance',
    'ブランド', 'brand', 'コレクション', 'collection',
    'トレンド', 'trend', 'ストリート', 'street',
    'バッグ', 'bag', '財布', 'wallet', 'ジュエリー', 'jewelry', 'アクセサリー',
    'シューズ', 'shoes', 'スニーカー', 'sneaker', 'ブーツ', 'boots',
    'アパレル', 'apparel', '衣料', '服', 'ウェア', 'wear', 'ドレス', 'dress',
    'ライフスタイル', 'lifestyle', 'インテリア', 'interior', 'ホーム', 'home',
    'コラボ', 'collab', 'collaboration', 'ポップアップ', 'pop-up', 'popup',
    'ランウェイ', 'runway', 'デザイナー', 'designer',
    // ブランド名
    'ユニクロ', 'uniqlo', 'zara', 'h&m', 'エイチアンドエム',
    'ルイヴィトン', 'シャネル', 'プラダ', 'グッチ', 'エルメス', 'ディオール',
    'ナイキ', 'アディダス', 'ニューバランス', 'コンバース',
  ];
  for (var j = 0; j < OK_TERMS.length; j++) {
    if (t.indexOf(OK_TERMS[j]) !== -1) return true;
  }

  // 上記どちらにも一致しない場合は通過（キーワード照合に任せる）
  return true;
}

// ─────────────────────────────────────────────────────────────
// PR TIMES ファッション系キーワード検索（fetchAll 並列）
// ─────────────────────────────────────────────────────────────

/**
 * PR TIMES の検索ページをファッション・美容系キーワードで並列取得。
 * UrlFetchApp.fetchAll() で全キーワードを同時リクエストして時間短縮。
 */
function ncLoadPrTimesFashionSearch() {
  var fashionKeywords = [
    'ファッション', '美容', 'コスメ', 'スキンケア', 'ブランド',
    'コレクション', 'ライフスタイル', 'スニーカー', 'バッグ', 'コラボ ファッション'
  ];

  var baseUrl = 'https://prtimes.jp/main/action.php?run=html&page=searchkey&search_word=';
  var requests = fashionKeywords.map(function(kw) {
    return {
      url: baseUrl + encodeURIComponent(kw),
      muteHttpExceptions: true,
      followRedirects: true,
      deadline: 15,
      headers: { 'User-Agent': NC_UA }
    };
  });

  var responses;
  try {
    responses = UrlFetchApp.fetchAll(requests);
  } catch(e) {
    Logger.log('PR TIMES ファッション検索 fetchAll エラー: ' + e.message);
    return [];
  }

  var articles = [];
  var seen = {};

  for (var i = 0; i < responses.length; i++) {
    try {
      if (responses[i].getResponseCode() !== 200) continue;
      var html = responses[i].getContentText('UTF-8');
      var linkRe = /href="(\/main\/html\/rd\/p\/\d+\.\d+\.html)"/gi;
      var m;
      while ((m = linkRe.exec(html)) !== null) {
        var fullUrl = 'https://prtimes.jp' + m[1];
        if (seen[fullUrl]) continue;
        seen[fullUrl] = true;

        var pos   = m.index;
        var block = html.slice(Math.max(0, pos - 600), pos + 600);
        var title = '';
        var titleM = block.match(/class="[^"]*title[^"]*"[^>]*>([\s\S]*?)<\/(?:h[1-6]|p|a|span|div)/i);
        if (titleM) title = ncStripTags(titleM[1]).trim();
        if (!title) {
          var aM = block.match(/<a[^>]+href="[^"]*rd\/p\/[^"]*"[^>]*>([\s\S]*?)<\/a>/i);
          if (aM) title = ncStripTags(aM[1]).trim();
        }
        if (!title || title.length < 3) continue;

        var dateM = block.match(/(\d{4}[-\/]\d{2}[-\/]\d{2})/);
        var imgM  = block.match(/<img[^>]+src="(https?:[^"]+\.(?:jpe?g|png|webp)[^"]*)"/i);
        var imgUrl = (imgM && !ncIsGenericImage(imgM[1])) ? imgM[1] : '';

        articles.push({
          title:       title,
          source_url:  fullUrl,
          source_name: 'PR TIMES',
          news_date:   dateM ? dateM[1].replace(/\//g, '-') : '',
          summary:     '',
          image_url:   imgUrl,
        });
      }
    } catch(e) {
      Logger.log('PR TIMES ファッション検索 parse エラー[' + i + ']: ' + e.message);
    }
  }

  Logger.log('PR TIMES ファッション検索: ' + articles.length + '件');
  return articles;
}

// 1実行内で使い回す公式RSSのキャッシュ（null=未取得, []=取得失敗）
var NC_PR_FEED_CACHE = null;

/** prtimes.jp 公式RSSを1度だけ取得して記事配列にする */
function ncLoadPrTimesFeed() {
  if (NC_PR_FEED_CACHE !== null) return NC_PR_FEED_CACHE;

  var feeds = ['https://prtimes.jp/topics/11.rdf', 'https://prtimes.jp/topics/46.rdf', 'https://prtimes.jp/index.rdf', 'https://prtimes.jp/rss/index.rdf'];
  for (var f = 0; f < feeds.length; f++) {
    var xml = ncFetch(feeds[f]);
    if (!xml) { Logger.log('公式RSS NG(取得失敗): ' + feeds[f]); continue; }
    if (xml.indexOf('<item') === -1) {
      Logger.log('公式RSS NG(item無し): ' + feeds[f] + ' / 先頭: ' + xml.slice(0, 200));
      continue;
    }

    var items = [];
    // <items><rdf:Seq> を拾わないよう、item の直後は空白か > に限定する
    var re = /<item(?:\s[^>]*)?>([\s\S]*?)<\/item>/gi;
    var m;
    while ((m = re.exec(xml)) !== null) {
      var b = m[1];
      var link = ncTagText(b, 'link');
      if (!link || link.indexOf('prtimes.jp') === -1) continue;

      // media:content / enclosure / media:thumbnail から画像URLを取得
      var imgUrl = '';
      var mcM = b.match(/<media:content[^>]+url=["']([^"']+\.(?:jpe?g|png|webp)[^"']*)["']/i);
      if (mcM) imgUrl = mcM[1];
      if (!imgUrl) {
        var encM = b.match(/<enclosure[^>]+url=["']([^"']+\.(?:jpe?g|png|webp)[^"']*)["']/i);
        if (encM) imgUrl = encM[1];
      }
      if (!imgUrl) {
        var thM = b.match(/<media:thumbnail[^>]+url=["']([^"']+)["']/i);
        if (thM) imgUrl = thM[1];
      }
      items.push({
        title:       ncTagText(b, 'title'),
        source_url:  link,
        source_name: 'PR TIMES',
        news_date:   ncParsePubDate(ncTagText(b, 'dc:date') || ncTagText(b, 'pubDate')),
        summary:     ncStripTags(ncTagText(b, 'description')).slice(0, 300),
        image_url:   imgUrl,
      });
    }
    if (items.length) {
      Logger.log('公式RSS取得: ' + items.length + '件 (' + feeds[f] + ')');
      NC_PR_FEED_CACHE = items;
      return items;
    }
  }

  Logger.log('公式RSS使用不可 → Google News にフォールバック');
  NC_PR_FEED_CACHE = [];
  return NC_PR_FEED_CACHE;
}

/** CDATA対応でタグの中身を取り出す */
function ncLoadAtPressFeed() {
  var articles = [];
  var seen     = {};

  // ?sort=date&page=N を回して新着をできるだけ広く集める
  for (var page = 1; page <= NC_AP_PAGES; page++) {
    var url  = 'https://www.atpress.ne.jp/?sort=date' + (page > 1 ? '&page=' + page : '');
    var html = ncFetch(url);
    if (!html) { Logger.log('AT PRESS 取得失敗: page ' + page); continue; }

    var before = articles.length;
    ncParseAtPressListing(html, seen, articles);
    var added = articles.length - before;
    Logger.log('AT PRESS page ' + page + ': +' + added + '件 (累計 ' + articles.length + '件)');
    if (added === 0 && page > 1) break;   // 新規が出なくなったら打ち切り
  }

  Logger.log('AT PRESS 合計: ' + articles.length + '件');
  return articles;
}

/** AT PRESS一覧HTMLから /news/数字 の記事を抽出して articles に積む */
function ncParseAtPressListing(html, seen, articles) {
  var re = /href="(\/news\/(\d+)[^"]*)"/gi;
  var m;
  while ((m = re.exec(html)) !== null) {
    var path    = m[1].split('?')[0].split('#')[0];
    var fullUrl = 'https://www.atpress.ne.jp' + path;
    if (seen[fullUrl]) continue;
    seen[fullUrl] = true;

    var pos   = m.index;
    // AT PRESS の記事ブロックは前後1000字くらい取る
    var block = html.slice(Math.max(0, pos - 1000), pos + 1000);

    var title = ncExtractAtPressTitle(block, m[2]);

    var dateM    = block.match(/(\d{4}[-\/\.]\d{1,2}[-\/\.]\d{1,2})/);
    var newsDate = dateM ? dateM[1].replace(/[\/\.]/g, '-') : '';

    // og:image または data-src で取れる画像をリストページから拾う（あれば）
    var imgM = block.match(/<img[^>]+(?:src|data-src)="(https?:[^"]+\.(?:jpe?g|png|webp)[^"]*)"/i);
    var imgUrl = imgM ? imgM[1] : '';

    articles.push({
      title:       title,
      source_url:  fullUrl,
      source_name: 'AT PRESS',
      news_date:   newsDate,
      summary:     '',
      image_url:   imgUrl,
    });
  }
}

/** AT PRESS の記事ブロックからタイトルを複数パターンで抽出する */
function ncExtractAtPressTitle(block, newsId) {
  // パターン1: class に title/heading を含む要素
  var m = block.match(/class="[^"]*(?:title|heading)[^"]*"[^>]*>([\s\S]{5,300}?)<\/(?:h[1-6]|p|span|a|div)/i);
  if (m) { var t = ncStripTags(m[1]).trim(); if (t.length >= 5) return t; }

  // パターン2: /news/数字 リンクのテキスト（直接aタグ）
  var re2 = new RegExp('<a[^>]+href="[^"]*news\\/' + newsId + '[^"]*"[^>]*>([\\s\\S]{5,300}?)<\\/a>', 'i');
  var m2 = block.match(re2);
  if (m2) { var t2 = ncStripTags(m2[1]).trim(); if (t2.length >= 5) return t2; }

  // パターン3: h1-h3 タグ
  var m3 = block.match(/<h[1-3][^>]*>([\s\S]{5,200}?)<\/h[1-3]>/i);
  if (m3) { var t3 = ncStripTags(m3[1]).trim(); if (t3.length >= 5) return t3; }

  // パターン4: p タグのテキスト（20文字以上）
  var m4 = block.match(/<p[^>]*>([\s\S]{10,200}?)<\/p>/i);
  if (m4) { var t4 = ncStripTags(m4[1]).trim(); if (t4.length >= 10) return t4; }

  // パターン5: alt 属性
  var m5 = block.match(/alt="([^"]{10,200})"/i);
  if (m5) return m5[1].trim();

  return '';
}

/** PR TIMES の新着一覧ページから記事を集める（キーワード検索ではない） */
function ncLoadPrTimesListing() {
  var articles = [];
  var seen     = {};

  // トップページ + ページネーション（/main/html/index/page/N）
  var pages = ['https://prtimes.jp/'];
  for (var n = 2; n <= NC_PR_PAGES; n++) {
    pages.push('https://prtimes.jp/main/html/index/page/' + n);
  }

  for (var i = 0; i < pages.length; i++) {
    var html = ncFetch(pages[i]);
    if (!html) { Logger.log('PR TIMES 一覧取得失敗: ' + pages[i]); continue; }

    var before = articles.length;
    ncParsePrTimesListingHtml(html, seen, articles);
    var added = articles.length - before;
    Logger.log('PR TIMES 一覧 ' + pages[i] + ': +' + added + '件');
    if (added === 0 && i > 0) break;
  }

  Logger.log('PR TIMES 一覧合計: ' + articles.length + '件');
  return articles;
}

/**
 * PR TIMES のファッション・美容・ライフスタイル系トピックページを取得する。
 * /topics/11 = ファッション・美容、/topics/46 = ライフスタイルなど。
 * RSSは404だがHTMLページは存在するので直接スクレイピングする。
 */
var NC_PR_TOPICS = [11, 46, 12, 34, 43];  // ファッション/美容/ライフスタイル/インテリア/食品

function ncLoadPrTimesTopics() {
  var articles = [];
  var seen     = {};

  for (var t = 0; t < NC_PR_TOPICS.length; t++) {
    var topicId = NC_PR_TOPICS[t];
    for (var p = 1; p <= 3; p++) {
      var url = 'https://prtimes.jp/topics/' + topicId + (p > 1 ? '/page/' + p : '');
      var html = ncFetch(url);
      if (!html) { Logger.log('PR TIMES topics/' + topicId + ' page' + p + ' 取得失敗'); break; }

      var before = articles.length;
      ncParsePrTimesListingHtml(html, seen, articles);
      var added = articles.length - before;
      Logger.log('PR TIMES topics/' + topicId + ' p' + p + ': +' + added + '件 (累計 ' + articles.length + ')');
      if (added === 0 && p > 1) break;
    }
  }

  Logger.log('PR TIMES トピック合計: ' + articles.length + '件');
  return articles;
}

/** PR TIMESのHTMLから記事リンクを抽出する（一覧・トピック共通） */
function ncParsePrTimesListingHtml(html, seen, articles) {
  // パターン1: /main/html/rd/p/数字.数字.html
  var re1 = /href="(\/main\/html\/rd\/p\/\d+\.\d+\.html)"/gi;
  var m;
  while ((m = re1.exec(html)) !== null) {
    var fullUrl = 'https://prtimes.jp' + m[1];
    if (seen[fullUrl]) continue;
    seen[fullUrl] = true;

    var pos   = m.index;
    var block = html.slice(Math.max(0, pos - 800), pos + 800);
    var title = ncExtractPrTimesTitle(block, m[1]);
    var dateM = block.match(/(\d{4}[-\/\.]\d{1,2}[-\/\.]\d{1,2})/);
    var imgM  = block.match(/<img[^>]+src="(https?:[^"]+\.(?:jpe?g|png|webp)[^"]*)"/i);
    var imgUrl = (imgM && !ncIsGenericImage(imgM[1])) ? imgM[1] : '';

    articles.push({
      title:       title,
      source_url:  fullUrl,
      source_name: 'PR TIMES',
      news_date:   dateM ? dateM[1].replace(/[\/\.]/g, '-') : '',
      summary:     '',
      image_url:   imgUrl,
    });
  }

  // パターン2: /main/html/releaseDetail/p/数字.html （詳細直リンク）
  var re2 = /href="(\/main\/html\/releaseDetail\/p\/\d+\.html)"/gi;
  while ((m = re2.exec(html)) !== null) {
    var fullUrl = 'https://prtimes.jp' + m[1];
    if (seen[fullUrl]) continue;
    seen[fullUrl] = true;

    var pos   = m.index;
    var block = html.slice(Math.max(0, pos - 800), pos + 800);
    var title = ncExtractPrTimesTitle(block, m[1]);
    var dateM = block.match(/(\d{4}[-\/\.]\d{1,2}[-\/\.]\d{1,2})/);
    var imgM2 = block.match(/<img[^>]+src="(https?:[^"]+\.(?:jpe?g|png|webp)[^"]*)"/i);
    var imgUrl2 = (imgM2 && !ncIsGenericImage(imgM2[1])) ? imgM2[1] : '';

    articles.push({
      title:       title,
      source_url:  fullUrl,
      source_name: 'PR TIMES',
      news_date:   dateM ? dateM[1].replace(/[\/\.]/g, '-') : '',
      summary:     '',
      image_url:   imgUrl2,
    });
  }
}

/** PR TIMES の記事ブロックからタイトルを抽出（複数パターンをフォールバック） */
function ncExtractPrTimesTitle(block, path) {
  // パターン1: class に "title" を含む要素
  var m = block.match(/class="[^"]*title[^"]*"[^>]*>([\s\S]{5,200}?)<\/(?:h[1-6]|p|span|a|div)/i);
  if (m) { var t = ncStripTags(m[1]).trim(); if (t.length >= 5) return t; }

  // パターン2: リンクの直後のテキスト（aタグのテキスト）
  var re = new RegExp('<a[^>]+href="[^"]*' + path.replace(/\./g, '\\.') + '[^"]*"[^>]*>([\\s\\S]{5,300}?)<\\/a>', 'i');
  var m2 = block.match(re);
  if (m2) { var t2 = ncStripTags(m2[1]).trim(); if (t2.length >= 5) return t2; }

  // パターン3: h1-h3 タグ
  var m3 = block.match(/<h[1-3][^>]*>([\s\S]{5,200}?)<\/h[1-3]>/i);
  if (m3) { var t3 = ncStripTags(m3[1]).trim(); if (t3.length >= 5) return t3; }

  // パターン4: alt 属性（サムネイル画像のalt）
  var m4 = block.match(/alt="([^"]{5,200})"/i);
  if (m4) { var t4 = m4[1].trim(); if (t4.length >= 5) return t4; }

  return '';
}

function ncTagText(block, tag) {
  var re = new RegExp('<' + tag + '[^>]*>(?:<!\\[CDATA\\[)?([\\s\\S]*?)(?:\\]\\]>)?<\\/' + tag + '>', 'i');
  var m  = block.match(re);
  return m ? m[1].trim() : '';
}

function ncSearchPrTimes(keyword) {
  // 経路A: PR TIMES 検索ページをキーワードで直接検索（実URLが得られる）
  var results = ncSearchPrTimesPage(keyword);
  if (results.length) return results;

  // 経路B: 公式RSSをキーワードで絞り込む（補助）
  var feed = ncLoadPrTimesFeed();
  if (feed.length) {
    var hit = [];
    var kw  = keyword.toLowerCase();
    for (var i = 0; i < feed.length && hit.length < NC_MAX_PER_KEYWORD; i++) {
      var a = feed[i];
      if ((a.title + ' ' + a.summary).toLowerCase().indexOf(kw) !== -1) hit.push(a);
    }
    if (hit.length) return hit;
  }

  return [];
}

/**
 * PR TIMES 検索ページを直接スクレイピングしてキーワード検索する。
 * 実記事URLが得られるので og:image も確実に取れる。
 */
function ncSearchPrTimesPage(keyword) {
  var url = 'https://prtimes.jp/main/action.php?run=html&page=searchkey&search_word=' +
            encodeURIComponent(keyword);
  var html = ncFetch(url);
  if (!html) {
    Logger.log('PR TIMES検索ページ取得失敗: ' + keyword);
    return [];
  }

  var articles = [];
  var seen = {};

  // 記事URLパターン: /main/html/rd/p/数字.数字.html
  var linkRe = /href="(\/main\/html\/rd\/p\/\d+\.\d+\.html)"/gi;
  var m;
  while ((m = linkRe.exec(html)) !== null && articles.length < NC_MAX_PER_KEYWORD) {
    var path    = m[1];
    var fullUrl = 'https://prtimes.jp' + path;
    if (seen[fullUrl]) continue;
    seen[fullUrl] = true;

    // URLの前後からタイトルを探す
    var pos   = m.index;
    var block = html.slice(Math.max(0, pos - 500), pos + 500);
    var titleM = block.match(/class="[^"]*title[^"]*"[^>]*>([\s\S]*?)<\/(?:h[1-6]|p|a|span|div)/i);
    var title  = titleM ? ncStripTags(titleM[1]).trim() : '';
    if (!title) {
      // aタグのテキストを使う
      var aM = block.match(/<a[^>]+href="[^"]*rd\/p\/[^"]*"[^>]*>([\s\S]*?)<\/a>/i);
      title  = aM ? ncStripTags(aM[1]).trim() : '';
    }
    if (!title || title.length < 3) continue;

    var dateM = block.match(/(\d{4}[-\/]\d{2}[-\/]\d{2})/);
    var newsDate = dateM ? dateM[1].replace(/\//g, '-') : '';

    articles.push({
      title:       title,
      source_url:  fullUrl,
      source_name: 'PR TIMES',
      news_date:   newsDate,
      summary:     '',
      image_url:   '',
    });
  }

  Logger.log('PR TIMES検索「' + keyword + '」→ ' + articles.length + '件');
  return articles;
}

/**
 * Google News RSS を取得して記事配列に変換。
 * CDATA を正しく扱うためテキストパースを使用する。
 * description 内の <a href> から元記事URLを抽出する。
 */
function ncFetchGNewsRSS(rssUrl, defaultSource) {
  var res;
  try {
    res = UrlFetchApp.fetch(rssUrl, {
      muteHttpExceptions: true,
      followRedirects: true,
      headers: { 'User-Agent': 'Mozilla/5.0 (compatible; GreigeBot/1.0)' },
    });
  } catch(e) {
    Logger.log('GNews RSS fetch error: ' + e.message);
    return [];
  }
  if (res.getResponseCode() !== 200) {
    Logger.log('GNews RSS HTTP ' + res.getResponseCode() + ': ' + rssUrl);
    return [];
  }

  var text     = res.getContentText();
  var articles = [];

  // <item>...</item> を正規表現で抽出（CDATA対応）
  var itemRe = /<item>([\s\S]*?)<\/item>/gi;
  var im;
  while ((im = itemRe.exec(text)) !== null && articles.length < NC_MAX_PER_KEYWORD) {
    var block = im[1];

    // <title>: CDATA or plain text
    var titleM = block.match(/<title[^>]*>(?:<!\[CDATA\[)?([\s\S]*?)(?:\]\]>)?<\/title>/i);
    var title  = titleM ? ncStripTags(titleM[1]).trim() : '';
    if (!title) continue;

    // <description>: CDATA の中に <a href="元記事URL"> が入っている
    var descM = block.match(/<description[^>]*>(?:<!\[CDATA\[)?([\s\S]*?)(?:\]\]>)?<\/description>/i);
    var desc  = descM ? descM[1] : '';

    // <link>: Google News URL（CBMi...をbase64デコードして元URLを復元）
    var linkM  = block.match(/<link>([\s\S]*?)<\/link>/i);
    var gnLink = linkM ? linkM[1].trim() : '';

    var link = gnLink;
    if (!link) continue;

    // <pubDate>
    var dateM   = block.match(/<pubDate>([\s\S]*?)<\/pubDate>/i);
    var pubDate = dateM ? dateM[1].trim() : '';

    // <source>: 配信元名
    var srcM       = block.match(/<source[^>]*>([\s\S]*?)<\/source>/i);
    var sourceName = srcM ? ncStripTags(srcM[1]).trim() : defaultSource;
    if (!sourceName) sourceName = defaultSource;

    var descHtml = desc.replace(/&lt;/g,'<').replace(/&gt;/g,'>').replace(/&amp;/g,'&');

    // Google NewsのCBMi URLをデコードして実際の記事URLを取得
    var realUrl = ncDecodeGNewsUrl(gnLink) || gnLink;

    articles.push({
      title:       title,
      source_url:  realUrl,
      source_name: sourceName,
      news_date:   ncParsePubDate(pubDate),
      summary:     ncStripTags(descHtml).slice(0, 300),
      image_url:   '',
    });
  }

  return articles;
}

/**
 * Google News RSS の CBMi... URL から実際の記事URLをデコードする。
 * base64url デコードしてバイト列から http(s):// を探す。
 */
function ncDecodeGNewsUrl(gnUrl) {
  var m = gnUrl.match(/\/articles\/(CBMi[^?&#\s]+)/);
  if (!m) return '';
  try {
    var bytes = Utilities.base64DecodeWebSafe(m[1]);
    for (var i = 0; i < bytes.length - 4; i++) {
      // 'h'=104 't'=116 't'=116 'p'=112
      if (bytes[i] === 104 && bytes[i+1] === 116 && bytes[i+2] === 116 && bytes[i+3] === 112) {
        var chars = [];
        for (var j = i; j < bytes.length; j++) {
          if (bytes[j] < 0x20) break;
          chars.push(String.fromCharCode(bytes[j]));
        }
        return chars.join('');
      }
    }
    return '';
  } catch(e) {
    return '';
  }
}

function ncParsePubDate(pubDate) {
  if (!pubDate) return '';
  try {
    var d = new Date(pubDate);
    if (isNaN(d.getTime())) return '';
    return Utilities.formatDate(d, 'Asia/Tokyo', 'yyyy-MM-dd');
  } catch(e) { return ''; }
}


// ─────────────────────────────────────────────────────────────
// atpress.ne.jp 検索
// ─────────────────────────────────────────────────────────────

function ncSearchAtPress(keyword) {
  var url = 'https://www.atpress.ne.jp/search?keyword=' +
            encodeURIComponent(keyword) + '&sort=date';
  var html = ncFetch(url);
  // 取得失敗 or 空なら Google News RSS にフォールバック
  if (!html) {
    var rssUrl = 'https://news.google.com/rss/search?q=' +
                 encodeURIComponent('site:atpress.ne.jp ' + keyword) +
                 '&hl=ja&gl=JP&ceid=JP:ja';
    return ncFetchGNewsRSS(rssUrl, 'AT PRESS');
  }

  var articles = [];
  var seen     = {};

  // パターン1: li.p-result__item
  var blockRe = /<li[^>]*class="[^"]*(?:result|item|release)[^"]*"[^>]*>([\s\S]*?)<\/li>/gi;
  var m;
  while ((m = blockRe.exec(html)) !== null && articles.length < NC_MAX_PER_KEYWORD) {
    var item = ncExtractAtPressItem(m[1], seen);
    if (item) { articles.push(item); seen[item.source_url] = true; }
  }

  // パターン2: article ブロック
  if (articles.length === 0) {
    var artRe = /<article[^>]*>([\s\S]*?)<\/article>/gi;
    while ((m = artRe.exec(html)) !== null && articles.length < NC_MAX_PER_KEYWORD) {
      var item = ncExtractAtPressItem(m[1], seen);
      if (item) { articles.push(item); seen[item.source_url] = true; }
    }
  }

  // フォールバック1: /releases/数字 リンクを直接抽出
  if (articles.length === 0) {
    articles = ncParseAtPressSimple(html, seen);
  }

  // フォールバック2: それでも0件なら Google News RSS
  if (articles.length === 0) {
    var rssUrl = 'https://news.google.com/rss/search?q=' +
                 encodeURIComponent('site:atpress.ne.jp ' + keyword) +
                 '&hl=ja&gl=JP&ceid=JP:ja';
    articles = ncFetchGNewsRSS(rssUrl, 'AT PRESS');
  }

  return articles;
}

function ncExtractAtPressItem(block, seen) {
  var linkRe = /<a[^>]+href="(\/releases\/\d+)"[^>]*>([\s\S]*?)<\/a>/i;
  var lm = block.match(linkRe);
  if (!lm) return null;

  var relUrl  = lm[1];
  var fullUrl = 'https://www.atpress.ne.jp' + relUrl;
  if (seen && seen[fullUrl]) return null;

  // タイトル: リンクテキスト or class="*title*"
  var title = ncStripTags(lm[2]).trim();
  if (title.length < 5) {
    var tm = block.match(/class="[^"]*title[^"]*"[^>]*>([\s\S]*?)<\/(?:h[1-6]|p|span|div)/i);
    if (tm) title = ncStripTags(tm[1]).trim();
  }
  if (!title || title.length < 5) return null;

  var datm     = block.match(/<time[^>]+datetime="([^"]+)"/i);
  var newsDate = datm ? datm[1].slice(0, 10) : '';

  var compRe    = /class="[^"]*(?:company|corp|name)[^"]*"[^>]*>([\s\S]*?)<\/(?:span|div|p|a)/i;
  var cm        = block.match(compRe);
  var sourceName= cm ? ncStripTags(cm[1]).trim() : 'AT PRESS';
  if (!sourceName || sourceName.length < 2) sourceName = 'AT PRESS';

  var sumRe  = /class="[^"]*(?:summary|lead|body|text)[^"]*"[^>]*>([\s\S]*?)<\/(?:p|div|span)/i;
  var sm     = block.match(sumRe);
  var summary= sm ? ncStripTags(sm[1]).trim() : '';

  var imgRe  = /<img[^>]+src="(https?:[^"]+(?:\.jpe?g|\.png|\.webp)[^"]*)"[^>]*/i;
  var im     = block.match(imgRe);
  var imgUrl = im ? im[1] : '';

  return {
    title:       title,
    source_url:  fullUrl,
    source_name: sourceName,
    news_date:   newsDate,
    summary:     summary,
    image_url:   imgUrl,
  };
}

function ncParseAtPressSimple(html, seen) {
  var articles = [];
  var re = /<a[^>]+href="(\/releases\/\d+)"[^>]*>([\s\S]*?)<\/a>/gi;
  var m;
  while ((m = re.exec(html)) !== null && articles.length < NC_MAX_PER_KEYWORD) {
    var relUrl  = m[1];
    var fullUrl = 'https://www.atpress.ne.jp' + relUrl;
    if (seen && seen[fullUrl]) continue;
    var title = ncStripTags(m[2]).trim();
    if (title.length < 5) continue;
    articles.push({
      title:       title,
      source_url:  fullUrl,
      source_name: 'AT PRESS',
      news_date:   '',
      summary:     '',
      image_url:   '',
    });
  }
  return articles;
}

// ─────────────────────────────────────────────────────────────
// ユーティリティ
// ─────────────────────────────────────────────────────────────

function ncFetch(url) {
  try {
    var res = UrlFetchApp.fetch(url, {
      muteHttpExceptions: true,
      followRedirects: true,
      deadline: 15,
      headers: {
        'User-Agent': NC_UA,
        'Accept': 'application/rss+xml, application/xml, text/xml, text/html, */*',
        'Accept-Language': 'ja,en;q=0.8',
      },
    });
    var code = res.getResponseCode();
    if (code !== 200) {
      Logger.log('fetch HTTP ' + code + ': ' + url);
      return null;
    }
    return res.getContentText('UTF-8');
  } catch (e) {
    Logger.log('fetch error: ' + url + ' / ' + e.message);
    return null;
  }
}

function ncStripTags(s) {
  return String(s || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
}

function ncGenId() {
  return 'NW' + new Date().getTime() + Math.floor(Math.random() * 1000);
}

// ─────────────────────────────────────────────────────────────
// 画像バックフィル
//   image_url が空の記事を元URLから og:image で埋める。
//   1回の実行で最大 NC_IMAGE_FILL_MAX 件処理（タイムアウト対策）。
// ─────────────────────────────────────────────────────────────

var NC_IMAGE_FILL_MAX = NC_MAX_TOTAL;

function ncFillMissingImages() {
  var props   = PropertiesService.getScriptProperties();
  var poolId  = props.getProperty('NEWS_POOL_ID')  || NC_POOL_ID;
  var poolTab = props.getProperty('NEWS_POOL_TAB') || NC_POOL_TAB;

  var pool = ncLoadPool(poolId, poolTab);
  var sheet = pool.sheet;
  var header = pool.header;

  var imgIdx = header.indexOf('image_url');
  var urlIdx = header.indexOf('source_url');
  if (imgIdx < 0 || urlIdx < 0) {
    Logger.log('image_url / source_url 列が見つかりません');
    return;
  }

  var lastRow = sheet.getLastRow();
  if (lastRow < 2) return;

  var data    = sheet.getRange(2, 1, lastRow - 1, header.length).getValues();
  var updated = 0;

  for (var i = 0; i < data.length && updated < NC_IMAGE_FILL_MAX; i++) {
    var row      = data[i];
    var existing = String(row[imgIdx] || '').trim();
    if (existing) continue;

    var sourceUrl = String(row[urlIdx] || '').trim();
    if (!sourceUrl) continue;

    var ogImage = ncFetchOgImage(sourceUrl);
    if (ogImage) {
      sheet.getRange(i + 2, imgIdx + 1).setValue(ogImage);
      updated++;
      Logger.log('OK: ' + ogImage.slice(0, 80));
    } else {
      Logger.log('画像なし: ' + sourceUrl.slice(0, 80));
    }
    Utilities.sleep(200);
  }

  Logger.log('画像URL更新: ' + updated + '件');
}

function ncGetHtml(url) {
  try {
    var res = UrlFetchApp.fetch(url, {
      muteHttpExceptions: true,
      followRedirects: true,
      headers: { 'User-Agent': NC_UA },
      deadline: 8,
    });
    if (res.getResponseCode() !== 200) return '';
    return res.getContentText('UTF-8');
  } catch(e) {
    return '';
  }
}

/** Google側のロゴ/サムネ or サイト共通OG画像は記事固有の画像ではないので弾く */
function ncIsGenericImage(url) {
  if (!url) return true;
  // Googleの画像
  if (/googleusercontent\.com|gstatic\.com|ggpht\.com|google\.[a-z.]+\//i.test(url)) return true;
  // PR TIMESのデフォルトOG画像
  if (/prtimes\.jp\/common\//i.test(url)) return true;
  // atpressのデフォルトOG画像
  if (/atpress\.ne\.jp\/common\//i.test(url)) return true;
  return false;
}

// 後方互換
function ncIsGoogleImage(url) { return ncIsGenericImage(url); }

/** HTMLエンティティを戻す。&amp; のまま保存すると画像が表示されない */
function ncDecodeEntities(s) {
  if (!s) return '';
  return String(s)
    .replace(/&amp;/gi,  '&')
    .replace(/&quot;/gi, '"')
    .replace(/&#0?39;/g, "'")
    .replace(/&apos;/gi, "'")
    .replace(/&lt;/gi,   '<')
    .replace(/&gt;/gi,   '>');
}

function ncPickOgImage(html) {
  if (!html) return '';
  var m = html.match(/<meta[^>]+property=["']og:image["'][^>]+content=["']([^"']+)["']/i);
  if (!m) m = html.match(/<meta[^>]+content=["']([^"']+)["'][^>]+property=["']og:image["']/i);
  var img = ncDecodeEntities(m ? m[1] : '');
  if (ncIsGenericImage(img)) return '';
  return img;
}

/**
 * 記事URLを実URLに解決し、og:image も取得する。
 * Google News URL の場合は記事ページHTMLから実URLを拾い直す。
 * @return {{url:string, image:string}}
 */
function ncPickOgTitle(html) {
  if (!html) return '';
  var m = html.match(/<meta[^>]+property=["']og:title["'][^>]+content=["']([^"']+)["']/i);
  if (!m) m = html.match(/<meta[^>]+content=["']([^"']+)["'][^>]+property=["']og:title["']/i);
  if (!m) m = html.match(/<title[^>]*>([\s\S]*?)<\/title>/i);
  return ncDecodeEntities(m ? ncStripTags(m[1]).trim() : '');
}

function ncResolveArticle(url) {
  var html = ncGetHtml(url);
  var img  = ncPickOgImage(html);
  if (img) return { url: url, image: img, title: ncPickOgTitle(html) };

  // 画像が取れない＝Google Newsの中継ページの可能性。実URLを探す。
  if (html) {
    var hits = html.match(/https?:\/\/(?:www\.)?(?:prtimes\.jp|atpress\.ne\.jp)\/[^"'\s<\\]+/i);
    if (hits && hits[0] !== url) {
      var real = hits[0].replace(/&amp;/g, '&');
      var h2   = ncGetHtml(real);
      var i2   = ncPickOgImage(h2);
      if (i2) return { url: real, image: i2 };
      return { url: real, image: '' };
    }
  }
  return { url: url, image: '' };
}

/**
 * 既存行に入ってしまった Google のロゴ画像URLを空に戻す。
 * 記事そのものは消さない。実行後に「画像URLだけ補完」で取り直せる。
 */
function ncClearGoogleImages() {
  var props  = PropertiesService.getScriptProperties();
  var pool   = ncLoadPool(props.getProperty('NEWS_POOL_ID')  || NC_POOL_ID,
                          props.getProperty('NEWS_POOL_TAB') || NC_POOL_TAB);
  var imgIdx = pool.header.indexOf('image_url');
  if (imgIdx < 0) { Logger.log('image_url 列がありません'); return; }

  var lastRow = pool.sheet.getLastRow();
  if (lastRow < 2) return;

  var rng     = pool.sheet.getRange(2, imgIdx + 1, lastRow - 1, 1);
  var vals    = rng.getValues();
  var cleared = 0;
  for (var i = 0; i < vals.length; i++) {
    if (vals[i][0] && ncIsGoogleImage(String(vals[i][0]))) { vals[i][0] = ''; cleared++; }
  }
  rng.setValues(vals);

  var msg = 'Google画像を消去: ' + cleared + '件';
  Logger.log(msg);
  try { SpreadsheetApp.getUi().alert(msg); } catch(e) {}
}

/** 後方互換: og:image だけ欲しいとき */
function ncFetchOgImage(url) {
  return ncResolveArticle(url).image;
}

/**
 * Google News の CBMi URL が入った行を全削除する。
 * CBMi URL は暗号化されていて実記事URLに復号できないため、
 * これらの行からは og:image を永久に取得できない。
 * 1行ずつ deleteRow するとタイムアウトするので残す行だけ一括で書き戻す。
 */
function ncClearGoogleNewsRows() {
  var props  = PropertiesService.getScriptProperties();
  var pool   = ncLoadPool(props.getProperty('NEWS_POOL_ID')  || NC_POOL_ID,
                          props.getProperty('NEWS_POOL_TAB') || NC_POOL_TAB);
  var sheet  = pool.sheet;
  var header = pool.header;
  var urlIdx = header.indexOf('source_url');
  if (urlIdx < 0) { Logger.log('source_url 列がありません'); return; }

  var lastRow = sheet.getLastRow();
  if (lastRow < 2) { Logger.log('データ行がありません'); return; }

  var data = sheet.getRange(2, 1, lastRow - 1, header.length).getValues();
  var keep = [];
  for (var i = 0; i < data.length; i++) {
    if (String(data[i][urlIdx]).indexOf('news.google.com') === -1) keep.push(data[i]);
  }
  var deleted = data.length - keep.length;

  sheet.getRange(2, 1, data.length, header.length).clearContent();
  if (keep.length) sheet.getRange(2, 1, keep.length, header.length).setValues(keep);

  var msg = 'Google News行を削除: ' + deleted + '件 / 残り: ' + keep.length + '件';
  Logger.log(msg);
  try { SpreadsheetApp.getUi().alert(msg); } catch (e) {}
}

// ─────────────────────────────────────────────────────────────
// デバッグ用：実際のHTMLを確認する
// ─────────────────────────────────────────────────────────────

/**
 * Google News RSS 経由の prtimes.jp 検索テスト。
 * GASエディタで実行 → 件数と記事タイトルが出れば成功。
 */
function ncDebugPrTimes() {
  var keyword = 'ファッション';
  var articles = ncSearchPrTimes(keyword);
  Logger.log('prtimes.jp「' + keyword + '」→ ' + articles.length + '件');
  articles.forEach(function(a) {
    Logger.log(a.news_date + ' | ' + a.title.slice(0, 60) + '\n  URL: ' + a.source_url + '\n  IMG: ' + (a.image_url || '（なし）'));
  });
}

/** RSSのdescription生テキストを確認する */
function ncDebugRssDescription() {
  var url = 'https://news.google.com/rss/search?q=' +
            encodeURIComponent('site:prtimes.jp ファッション') +
            '&hl=ja&gl=JP&ceid=JP:ja';
  var res = UrlFetchApp.fetch(url, { muteHttpExceptions: true, followRedirects: true });
  var text = res.getContentText();
  // 最初の<item>だけ取り出す
  var m = text.match(/<item>([\s\S]*?)<\/item>/i);
  if (!m) { Logger.log('itemなし'); return; }
  Logger.log('=== 最初のitem ===');
  Logger.log(m[1].slice(0, 2000));
}

/**
 * ★これを1回実行してログを全部コピペして渡してください★
 * 画像が取れない原因を特定するための診断。3つの経路を同時に試す。
 */
function ncDiagnose() {
  // ── テスト1: prtimes.jp の公式RSSが使えるか ──
  Logger.log('========== TEST 1: prtimes.jp 公式RSS ==========');
  try {
    var r1 = UrlFetchApp.fetch('https://prtimes.jp/index.rdf', {
      muteHttpExceptions: true, followRedirects: true, deadline: 20,
      headers: { 'User-Agent': 'Mozilla/5.0 (compatible; GreigeBot/1.0)' },
    });
    Logger.log('HTTP: ' + r1.getResponseCode());
    if (r1.getResponseCode() === 200) {
      var x = r1.getContentText('UTF-8');
      Logger.log('サイズ: ' + x.length);
      var it = x.match(/<item[\s\S]*?<\/item>/i);
      Logger.log('--- 最初のitem ---');
      Logger.log(it ? it[0].slice(0, 1200) : '(itemなし) 先頭800字:\n' + x.slice(0, 800));
    }
  } catch (e) { Logger.log('例外: ' + e.message); }

  // ── テスト2: Google News の記事URLを開くと何が返るか ──
  Logger.log('========== TEST 2: Google News 記事URL ==========');
  try {
    var rss = UrlFetchApp.fetch(
      'https://news.google.com/rss/search?q=' + encodeURIComponent('site:prtimes.jp ファッション') +
      '&hl=ja&gl=JP&ceid=JP:ja',
      { muteHttpExceptions: true, followRedirects: true, deadline: 20 }
    ).getContentText();
    var lm = rss.match(/<link>(https:\/\/news\.google\.com\/rss\/articles\/[^<]+)<\/link>/i);
    if (!lm) { Logger.log('記事linkが取れません'); }
    else {
      var gn = lm[1];
      Logger.log('GNews URL: ' + gn.slice(0, 120));
      Logger.log('デコード結果: [' + (ncDecodeGNewsUrl(gn) || '★失敗★') + ']');

      var r2 = UrlFetchApp.fetch(gn, {
        muteHttpExceptions: true, followRedirects: true, deadline: 20,
        headers: { 'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36' },
      });
      Logger.log('HTTP: ' + r2.getResponseCode());
      var h = r2.getContentText('UTF-8');
      Logger.log('返却サイズ: ' + h.length);
      // HTML内に実記事URLが埋まっているか
      var hits = h.match(/https?:\/\/(?:www\.)?prtimes\.jp[^"'\s<\\]*/g);
      Logger.log('prtimes.jp URLの出現: ' + (hits ? hits.length + '件' : '0件'));
      if (hits) {
        for (var i = 0; i < Math.min(5, hits.length); i++) Logger.log('  ' + hits[i]);
      } else {
        Logger.log('--- HTML先頭1000字 ---');
        Logger.log(h.slice(0, 1000));
      }
    }
  } catch (e) { Logger.log('例外: ' + e.message); }

  // ── テスト3: 公式RSSの実記事から og:image が取れるか ──
  Logger.log('========== TEST 3: prtimes.jp og:image ==========');
  try {
    NC_PR_FEED_CACHE = null;              // キャッシュを捨てて取り直す
    var feed = ncLoadPrTimesFeed();
    Logger.log('公式RSSの記事数: ' + feed.length);
    if (!feed.length) {
      Logger.log('★公式RSSから記事が取れていません★');
    } else {
      for (var k = 0; k < Math.min(3, feed.length); k++) {
        var a = feed[k];
        var r = ncResolveArticle(a.source_url);
        Logger.log((k + 1) + '. ' + a.title.slice(0, 40));
        Logger.log('   URL: ' + a.source_url);
        Logger.log('   IMG: ' + (r.image || '★取れず★'));
      }
    }
  } catch (e) { Logger.log('例外: ' + e.message); }

  Logger.log('========== 診断おわり ==========');
}

function ncDebugAtPress() {
  var url = 'https://www.atpress.ne.jp/search?keyword=' +
            encodeURIComponent('Adidas') + '&sort=date';
  var html = ncFetch(url);
  if (!html) { Logger.log('取得失敗'); return; }
  Logger.log('=== URL ===');
  Logger.log(url);
  Logger.log('=== HTML 先頭3000文字 ===');
  Logger.log(html.slice(0, 3000));
  Logger.log('=== releases を含む行 ===');
  var lines = html.split('\n');
  lines.forEach(function(line) {
    if (line.indexOf('/releases/') !== -1 || line.indexOf('release') !== -1) {
      Logger.log(line.trim().slice(0, 200));
    }
  });
}
