/**
 * GREIGE MAGAZINE - WordPress投稿 [2/3] REST API とペイロード
 * ※ PublishContent / PublishApi / PublishRun の3ファイルで1セット。
 *    どれか欠けると動きません。
 */

function wpFetch(path, method, payload, props) {
  var options = {
    method: method,
    headers: { Authorization: getAuthHeader(props) },
    muteHttpExceptions: true,
  };
  if (payload) {
    options.contentType = 'application/json';
    options.payload = JSON.stringify(payload);
  }
  var res  = UrlFetchApp.fetch(WP_SITE + '/wp-json/wp/v2/' + path, options);
  var text = res.getContentText();
  var body;
  try {
    body = JSON.parse(text);
  } catch (e) {
    body = { message: text.slice(0, 200) };
  }
  return { code: res.getResponseCode(), body: body };
}

function resolveCategoryIds(categoryStr) {
  if (!categoryStr) return [];
  var ids = [];
  String(categoryStr).split(/[,、]/).forEach(function(name) {
    var key = name.trim().toUpperCase();
    if (WP_CATEGORY_MAP[key] !== undefined) ids.push(WP_CATEGORY_MAP[key]);
  });
  return ids;
}

function resolveTagIds(tagStr, props) {
  if (!tagStr) return [];
  var ids = [];
  String(tagStr).split(/[,、]/).forEach(function(name) {
    var trimmed = name.trim();
    if (!trimmed) return;
    try {
      var searchRes = UrlFetchApp.fetch(
        WP_SITE + '/wp-json/wp/v2/tags?search=' + encodeURIComponent(trimmed) + '&per_page=1',
        { headers: { Authorization: getAuthHeader(props) }, muteHttpExceptions: true }
      );
      var found = JSON.parse(searchRes.getContentText());
      if (found.length > 0 && found[0].name === trimmed) {
        ids.push(found[0].id);
      } else {
        var createRes = UrlFetchApp.fetch(WP_SITE + '/wp-json/wp/v2/tags', {
          method: 'post',
          contentType: 'application/json',
          headers: { Authorization: getAuthHeader(props) },
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

function buildPayload(row, hmap, props, postType, isCreate) {
  var body = cell(row, hmap, 'article_body');
  var cta  = cell(row, hmap, 'call_to_action');
  if (cta) body += '\n\n' + cta;

  var payload = {
    title:   cell(row, hmap, 'title'),
    content: body,
    excerpt: cell(row, hmap, 'lead'),
  };

  // status は新規作成時のみ送る。
  // 更新時に送ると、WordPress 側で公開済みの記事を下書きに戻してしまう。
  if (isCreate) payload.status = 'draft';

  var slug = cell(row, hmap, 'slug');
  if (slug) payload.slug = slug;

  var featured = cell(row, hmap, 'featured_image_id');
  if (featured && !isNaN(Number(featured)) && Number(featured) > 0) {
    payload.featured_media = Number(featured);
  }

  var taxonomyKey = TAXONOMY_BY_TYPE[postType];
  var catIds = resolveCategoryIds(cell(row, hmap, 'wp_category'));
  if (taxonomyKey && catIds.length > 0) payload[taxonomyKey] = catIds;

  var tagIds = resolveTagIds(cell(row, hmap, 'wp_tags'), props);
  if (tagIds.length > 0) payload.tags = tagIds;

  var metaTitle = cell(row, hmap, 'meta_title');
  var metaDesc  = cell(row, hmap, 'meta_description');
  if (metaTitle || metaDesc) {
    payload.meta = {};
    if (metaTitle) payload.meta.rank_math_title       = metaTitle;
    if (metaDesc)  payload.meta.rank_math_description = metaDesc;
  }

  return payload;
}
