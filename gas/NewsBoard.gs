/**
 * GREIGE NEWS BOARD — GAS Web App backend
 *
 * Deploy as Web App (Execute as: Me, Who has access: Anyone)
 * then paste the exec URL into astra-child/tools/news-board/config.js
 *
 * Script Properties required:
 *   NEWS_BOARD_TOKEN  : shared token (any random string, must match config.js API_TOKEN)
 *
 * Optional (for WordPress push, already used by NewsCollector):
 *   WP_APP_PASS       : WordPress application password
 */

var NB_POOL_ID           = '11e6LwzXi-5_1GyLz35zRV4e0pUPbq63fdB1NwJtWXMI';
var NB_POOL_TAB          = 'NEWS_POOL';
var NB_SELECTED_ID       = '1ULM6KFGsNoUWXM2AG9uYkQO7pUFffgg2pOeT4x0Miw8';
var NB_SELECTED_TAB      = 'シート1';
var NB_DRIVE_FOLDER_NAME = 'greige-news';  // root folder in My Drive

// ── Entry point ─────────────────────────────────────────────────────────────

function doGet(e) {
  var params = e && e.parameter ? e.parameter : {};
  var resp = route('GET', params, null);
  return ContentService
    .createTextOutput(JSON.stringify(resp))
    .setMimeType(ContentService.MimeType.JSON);
}

function doPost(e) {
  var body = {};
  try { body = JSON.parse(e.postData.contents); } catch(ex) { body = {}; }
  var resp = route('POST', {}, body);
  return ContentService
    .createTextOutput(JSON.stringify(resp))
    .setMimeType(ContentService.MimeType.JSON);
}

function route(method, params, body) {
  var token = method === 'GET' ? params.token : (body && body.token);
  if (!nbCheckToken(token)) return { error: 'unauthorized' };

  var action = method === 'GET' ? params.action : (body && body.action);

  if (action === 'listNews')     return nbListNews(params);
  if (action === 'updateStatus') return nbUpdateStatus(body);
  if (action === 'fetchImages')  return nbFetchImages(params);
  if (action === 'saveImages')   return nbSaveImages(body);

  return { error: 'unknown action: ' + action };
}

function nbCheckToken(t) {
  var expected = PropertiesService.getScriptProperties().getProperty('NEWS_BOARD_TOKEN') || '';
  if (!expected) return true; // no token set = open (for dev)
  return t === expected;
}

// ── listNews ─────────────────────────────────────────────────────────────────
// Returns all rows from NEWS_POOL as JSON

function nbListNews(params) {
  var ss    = SpreadsheetApp.openById(NB_POOL_ID);
  var sheet = ss.getSheetByName(NB_POOL_TAB);
  if (!sheet) return { error: 'NEWS_POOL sheet not found' };

  var lastRow = sheet.getLastRow();
  if (lastRow < 2) return { articles: [] };

  var headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];
  var rows    = sheet.getRange(2, 1, lastRow - 1, sheet.getLastColumn()).getValues();

  var col = function(name) {
    var idx = headers.indexOf(name);
    return idx;
  };

  var articles = rows.map(function(row, i) {
    return {
      _row:            i + 2,
      news_id:         String(row[col('news_id')]         || ''),
      source_name:     String(row[col('source_name')]     || ''),
      source_url:      String(row[col('source_url')]      || ''),
      title:           String(row[col('title')]           || ''),
      news_date:       String(row[col('news_date')]       || ''),
      summary:         String(row[col('summary')]         || ''),
      category:        String(row[col('category')]        || ''),
      matched_keyword: String(row[col('matched_keyword')] || ''),
      image_url:       String(row[col('image_url')]       || ''),
      image_ids:       String(row[col('image_ids')]       || ''),
      status:          String(row[col('status')]          || 'NEW'),
    };
  }).filter(function(a){ return a.news_id; });

  return { articles: articles };
}

// ── updateStatus ─────────────────────────────────────────────────────────────
// Updates status in NEWS_POOL; if status=SELECTED, also writes to SELECTED_NEWS

function nbUpdateStatus(body) {
  var newsId = String(body.news_id || '');
  var status = String(body.status  || '');
  if (!newsId || !status) return { error: 'missing news_id or status' };

  var ss    = SpreadsheetApp.openById(NB_POOL_ID);
  var sheet = ss.getSheetByName(NB_POOL_TAB);
  if (!sheet) return { error: 'NEWS_POOL not found' };

  var lastRow = sheet.getLastRow();
  var headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];
  var rows    = sheet.getRange(2, 1, lastRow - 1, sheet.getLastColumn()).getValues();

  var col = function(name) { return headers.indexOf(name); };

  var found = false;
  for (var i = 0; i < rows.length; i++) {
    if (String(rows[i][col('news_id')]) === newsId) {
      var sheetRow = i + 2;
      sheet.getRange(sheetRow, col('status') + 1).setValue(status);
      found = true;

      if (status === 'SELECTED') {
        nbWriteToSelected(rows[i], headers);
      }
      break;
    }
  }

  if (!found) return { error: 'news_id not found: ' + newsId };
  return { ok: true };
}

function nbWriteToSelected(row, headers) {
  var col = function(name) { return headers.indexOf(name); };

  var selSs    = SpreadsheetApp.openById(NB_SELECTED_ID);
  var selSheet = selSs.getSheetByName(NB_SELECTED_TAB);
  if (!selSheet) return;

  // Ensure header row exists
  var selLastCol = selSheet.getLastColumn();
  var selHeaders = selLastCol > 0
    ? selSheet.getRange(1, 1, 1, selLastCol).getValues()[0]
    : [];
  var selCols = [
    'news_id','source_name','source_url','title','news_date',
    'summary','category','matched_keyword','image_url','selected_date','status',
    'wordpress_post_id','wordpress_url','wordpress_status'
  ];
  if (!selHeaders.length || selHeaders[0] === '') {
    selSheet.getRange(1, 1, 1, selCols.length).setValues([selCols]);
    selHeaders = selCols;
  }

  var selCol = function(name) { return selHeaders.indexOf(name); };

  // Check for duplicate
  var selLastRow = selSheet.getLastRow();
  if (selLastRow >= 2) {
    var existing = selSheet.getRange(2, 1, selLastRow - 1, selHeaders.length).getValues();
    for (var j = 0; j < existing.length; j++) {
      if (String(existing[j][selCol('news_id')]) === String(row[col('news_id')])) return;
    }
  }

  var newRow = new Array(selHeaders.length).fill('');
  function set(name, val) {
    var idx = selCol(name);
    if (idx >= 0) newRow[idx] = val;
  }
  set('news_id',         row[col('news_id')]);
  set('source_name',     row[col('source_name')]);
  set('source_url',      row[col('source_url')]);
  set('title',           row[col('title')]);
  set('news_date',       row[col('news_date')]);
  set('summary',         row[col('summary')]);
  set('category',        row[col('category')]);
  set('matched_keyword', row[col('matched_keyword')]);
  set('image_url',       row[col('image_url')]);
  set('selected_date',   new Date().toISOString());
  set('status',          'NEW');

  selSheet.appendRow(newRow);
}

// ── fetchImages ───────────────────────────────────────────────────────────────
// Fetches the article page and extracts image URLs

function nbFetchImages(params) {
  var url = String(params.url || '');
  if (!url) return { images: [] };

  try {
    var resp = UrlFetchApp.fetch(url, { muteHttpExceptions: true, followRedirects: true });
    if (resp.getResponseCode() !== 200) return { images: [], error: 'HTTP ' + resp.getResponseCode() };

    var html = resp.getContentText();
    var images = [];
    var seen = {};

    // Extract all <img src> and <img data-src> values
    var patterns = [
      /\bdata-src=["']([^"']+\.(?:jpe?g|png|webp|gif)[^"']*)["']/gi,
      /<img[^>]+\bsrc=["']([^"']+\.(?:jpe?g|png|webp|gif)[^"']*)["']/gi,
    ];
    patterns.forEach(function(re){
      var m;
      while ((m = re.exec(html)) !== null) {
        var src = m[1].split('?')[0];
        // Skip tiny icons
        if (/logo|icon|sprite|pixel|spacer|avatar|thumb[^/]*\/(1[0-9]|[1-9])\d*x/i.test(src)) continue;
        if (seen[src]) continue;
        seen[src] = true;
        // Make absolute if needed
        if (src.startsWith('//')) src = 'https:' + src;
        else if (src.startsWith('/')) {
          var base = url.match(/^https?:\/\/[^/]+/);
          if (base) src = base[0] + src;
        }
        images.push(src);
      }
    });

    return { images: images.slice(0, 20) }; // cap at 20
  } catch(e) {
    return { images: [], error: e.message };
  }
}

// ── saveImages ────────────────────────────────────────────────────────────────
// Downloads selected images and saves to Google Drive
// Path: NB_DRIVE_FOLDER_NAME / YYYY / MM / {sanitized-title} /

function nbSaveImages(body) {
  var newsId    = String(body.news_id   || '');
  var title     = String(body.title     || newsId || 'untitled');
  var newsDate  = String(body.news_date || '');
  var imageUrls = body.image_urls || [];

  if (!imageUrls.length) return { error: 'no image_urls provided' };

  // Determine year/month from news_date (or today)
  var dateObj = newsDate ? new Date(newsDate) : new Date();
  var yyyy = String(dateObj.getFullYear());
  var mm   = String(dateObj.getMonth() + 1).padStart(2, '0');

  // Sanitize title for folder name (keep Japanese, alphanumeric, spaces → hyphen)
  var safeTitle = title
    .replace(/[\\/:*?"<>|]/g, '')
    .replace(/\s+/g, '-')
    .slice(0, 60);

  // Get/create folder structure
  var rootFolder   = nbGetOrCreateFolder(DriveApp.getRootFolder(), NB_DRIVE_FOLDER_NAME);
  var yearFolder   = nbGetOrCreateFolder(rootFolder, yyyy);
  var monthFolder  = nbGetOrCreateFolder(yearFolder, mm);
  var articleFolder = nbGetOrCreateFolder(monthFolder, safeTitle);

  var saved = 0;
  var driveUrls = [];
  var errors = [];

  imageUrls.forEach(function(imgUrl, idx){
    try {
      var resp = UrlFetchApp.fetch(imgUrl, { muteHttpExceptions: true });
      if (resp.getResponseCode() !== 200) {
        errors.push('HTTP ' + resp.getResponseCode() + ' for ' + imgUrl);
        return;
      }
      var blob = resp.getBlob();
      // Give a sensible filename
      var ext = (imgUrl.match(/\.(jpe?g|png|webp|gif)/i) || ['','.jpg'])[0];
      var filename = (idx + 1) + ext;
      blob.setName(filename);
      var file = articleFolder.createFile(blob);
      driveUrls.push(file.getUrl());
      saved++;
    } catch(e) {
      errors.push(e.message);
    }
  });

  return {
    ok: true,
    saved: saved,
    folder_path: NB_DRIVE_FOLDER_NAME + '/' + yyyy + '/' + mm + '/' + safeTitle,
    folder_url: articleFolder.getUrl(),
    drive_urls: driveUrls,
    errors: errors,
  };
}

function nbGetOrCreateFolder(parent, name) {
  var iter = parent.getFoldersByName(name);
  if (iter.hasNext()) return iter.next();
  return parent.createFolder(name);
}
