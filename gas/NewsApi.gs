/**
 * GREIGE MAGAZINE - ニュース投稿 [2/3] REST API とペイロード
 * ※ NewsContent / NewsApi / NewsRun の3ファイルで一組。
 *   どれか欠けると動きません。
 */

// ─────────────────────────────────────────────────────────────
// WP REST API 低レベル呼び出し
// ─────────────────────────────────────────────────────────────

function newsFetch(path, method, payload, props) {
  var options = {
    method: method,
    headers: { Authorization: newsGetAuthHeader(props) },
    muteHttpExceptions: true,
  };
  if (payload) {
    options.contentType = 'application/json';
    options.payload = JSON.stringify(payload);
  }
  var res  = UrlFetchApp.fetch(NEWS_WP_SITE + '/wp-json/wp/v2/' + path, options);
  var text = res.getContentText();
  var body;
  try {
    body = JSON.parse(text);
  } catch (e) {
    body = { message: text.slice(0, 200) };
  }
  return { code: res.getResponseCode(), body: body };
}

// ─────────────────────────────────────────────────────────────
// カテゴリー / タグ解決
// ─────────────────────────────────────────────────────────────

function newsResolveCategoryIds(categoryStr) {
  if (!categoryStr) return [];
  var ids = [];
  String(categoryStr).split(/[,、]/).forEach(function(name) {
    var key = name.trim().toUpperCase();
    if (NEWS_CATEGORY_MAP[key] !== undefined) ids.push(NEWS_CATEGORY_MAP[key]);
  });
  return ids;
}

function newsResolveTagIds(tagStr, props) {
  if (!tagStr) return [];
  var ids = [];
  String(tagStr).split(/[,、]/).forEach(function(name) {
    var trimmed = name.trim();
    if (!trimmed) return;
    try {
      var searchRes = UrlFetchApp.fetch(
        NEWS_WP_SITE + '/wp-json/wp/v2/tags?search=' + encodeURIComponent(trimmed) + '&per_page=1',
        { headers: { Authorization: newsGetAuthHeader(props) }, muteHttpExceptions: true }
      );
      var found = JSON.parse(searchRes.getContentText());
      if (found.length > 0 && found[0].name === trimmed) {
        ids.push(found[0].id);
      } else {
        var createRes = UrlFetchApp.fetch(NEWS_WP_SITE + '/wp-json/wp/v2/tags', {
          method: 'post',
          contentType: 'application/json',
          headers: { Authorization: newsGetAuthHeader(props) },
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
// ペイロード構築
// ─────────────────────────────────────────────────────────────

function newsBuildPayload(row, hmap, props, isCreate) {
  var body = newsCell(row, hmap, 'article_body');
  var cta  = newsCell(row, hmap, 'call_to_action');
  if (cta) body += '\n\n' + cta;

  var payload = {
    title:   newsCell(row, hmap, 'title'),
    content: body,
    excerpt: newsCell(row, hmap, 'lead'),
    type:    NEWS_POST_TYPE,
  };

  // status は新規作成時のみ送る。
  // 更新時に送ると、WordPress 側で公開済みの記事を下書きに戻してしまう。
  if (isCreate) payload.status = 'draft';

  var slug = newsCell(row, hmap, 'slug');
  if (slug) payload.slug = slug;

  var featured = newsCell(row, hmap, 'featured_image_id');
  if (featured && !isNaN(Number(featured)) && Number(featured) > 0) {
    payload.featured_media = Number(featured);
  }

  var catIds = newsResolveCategoryIds(newsCell(row, hmap, 'wp_category'));
  if (catIds.length > 0) payload.categories = catIds;

  var tagIds = newsResolveTagIds(newsCell(row, hmap, 'wp_tags'), props);
  if (tagIds.length > 0) payload.tags = tagIds;

  // SEO（RankMath）
  var metaTitle = newsCell(row, hmap, 'meta_title');
  var metaDesc  = newsCell(row, hmap, 'meta_description');
  if (metaTitle || metaDesc) {
    payload.meta = {};
    if (metaTitle) payload.meta.rank_math_title       = metaTitle;
    if (metaDesc)  payload.meta.rank_math_description = metaDesc;
  }

  return payload;
}

// ─────────────────────────────────────────────────────────────
// 接続テスト
// ─────────────────────────────────────────────────────────────

function newsTestConnection() {
  var props = newsGetProps();
  if (!props.wpUser || !props.wpPass) {
    Logger.log('Script Properties に WP_USER と WP_APP_PASS を設定してください。');
    return;
  }
  var result = newsFetch('posts?per_page=1', 'get', null, props);
  Logger.log('接続テスト結果 HTTP ' + result.code + ': ' + JSON.stringify(result.body).slice(0, 300));
}
