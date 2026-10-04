<?php if (!defined('ABSPATH')) exit; ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SEARCH EDIT | CURATION</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@300;400;500&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri(); ?>/css/greige-base.css?v=<?php echo filemtime(get_stylesheet_directory() . '/css/greige-base.css'); ?>">
<link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri(); ?>/css/greige-nav.css?v=<?php echo filemtime(get_stylesheet_directory() . '/css/greige-nav.css'); ?>">
<link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri(); ?>/css/greige-components.css?v=<?php echo filemtime(get_stylesheet_directory() . '/css/greige-components.css'); ?>">
<style>
  :root{
    --bg: #ffffff;
    --fg: #1a1a1a;
    --fg-muted: #999;
    --border: #E8E4DF;
    --accent: #1a1a1a;
    --reject: #b9483f;
    --hold: #8a7a4a;
  }
  *{ box-sizing: border-box; }
  [hidden]{ display: none !important; }
  html,body{ margin:0; padding:0; }
  body{
    background: var(--bg);
    color: var(--fg);
    font-family: 'Noto Sans JP', sans-serif;
    font-weight: 300;
    -webkit-font-smoothing: antialiased;
  }
  a{ color: inherit; }
  button{ font-family: var(--nav-font) !important; }
  input, textarea, select{ font-family: 'Noto Sans JP', sans-serif; }

  header{
    position: sticky;
    top: 0;
    background: var(--bg);
    z-index: 10;
    border-bottom: 1px solid var(--border);
  }
  .wordmark .brand b{ font-weight: 500; }

  .toolbar{
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 14px 16px;
  }
  @media (min-width: 768px){ .toolbar{ padding-left: 40px; padding-right: 40px; } }
  @media (min-width: 1024px){ .toolbar{ padding-left: var(--rail); padding-right: var(--rail); } }

  .date-nav{
    display: flex;
    gap: 18px;
    overflow-x: auto;
    scrollbar-width: none;
    -ms-overflow-style: none;
    flex: 1;
  }
  .date-nav::-webkit-scrollbar{ display:none; }
  .date-nav button{
    flex: 0 0 auto;
    background: none;
    border: none;
    font-size: 12px;
    letter-spacing: 0.08em;
    color: var(--fg-muted);
    padding: 6px 0;
    cursor: pointer;
    white-space: nowrap;
    font-family: var(--nav-font);
    font-weight: 500;
  }
  .date-nav button.active{
    color: var(--fg);
    border-bottom: 1px solid var(--fg);
  }

  .category-nav{
    display: flex;
    gap: 16px;
    overflow-x: auto;
    scrollbar-width: none;
    -ms-overflow-style: none;
    padding: 0 16px 12px;
    border-bottom: 1px solid var(--border);
  }
  @media (min-width: 768px){ .category-nav{ padding-left: 40px; padding-right: 40px; } }
  @media (min-width: 1024px){ .category-nav{ padding-left: var(--rail); padding-right: var(--rail); } }
  .category-nav::-webkit-scrollbar{ display:none; }
  .category-nav button{
    flex: 0 0 auto;
    background: none;
    border: 1px solid var(--border);
    padding: 5px 12px;
    font-size: 10.5px;
    letter-spacing: 0.08em;
    color: var(--fg-muted);
    cursor: pointer;
    white-space: nowrap;
    font-family: var(--nav-font);
    font-weight: 600;
    text-transform: uppercase;
  }
  .category-nav button.active{
    color: var(--bg);
    background: var(--fg);
    border-color: var(--fg);
  }

  .cols{
    display: flex;
    gap: 6px;
    flex: 0 0 auto;
  }
  .cols button{
    width: 44px;
    height: 44px;
    border: 1px solid var(--border);
    background: var(--bg);
    color: var(--fg-muted);
    font-size: 11px;
    cursor: pointer;
    font-family: inherit;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  @media (min-width: 768px){
    .cols button{ width: 26px; height: 26px; }
  }
  .cols button.active{
    background: var(--fg);
    color: var(--bg);
    border-color: var(--fg);
  }

  .status-banner{
    font-size: 11px;
    letter-spacing: 0.04em;
    color: var(--fg-muted);
    text-align: center;
    padding: 8px 16px;
    border-bottom: 1px solid var(--border);
  }

  main{ padding: 20px 16px 80px; }
  @media (min-width: 768px){ main{ padding: 20px 40px 80px; } }
  @media (min-width: 1024px){ main{ padding: 20px var(--rail) 80px; } }

  .grid{
    display: grid;
    gap: 28px 16px;
  }
  .grid.cols-1{ grid-template-columns: repeat(1, 1fr); }
  .grid.cols-2{ grid-template-columns: repeat(2, 1fr); }
  .grid.cols-3{ grid-template-columns: repeat(3, 1fr); }
  .grid.cols-4{ grid-template-columns: repeat(4, 1fr); }

  .card{ cursor: pointer; }
  .card .thumb{
    width: 100%;
    aspect-ratio: 3 / 4;
    background: #f4f4f4;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .card .thumb img{
    width: 100%; height: 100%; object-fit: cover;
  }
  .card .thumb .noimg{
    color: #c9c9c9;
    font-size: 10px;
    letter-spacing: 0.12em;
  }
  .card .meta{ padding-top: 10px; text-align: center; }
  .card .brand{
    font-size: 10px;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--fg-muted);
    font-family: var(--nav-font);
    font-weight: 600;
  }
  .card .name{
    font-size: 13px;
    margin-top: 4px;
    line-height: 1.5;
    font-weight: 400;
  }
  .card .price{
    font-size: 12px;
    margin-top: 4px;
    color: var(--fg-muted);
  }
  .card .img-status{
    font-size: 9.5px;
    margin-top: 3px;
    letter-spacing: 0.06em;
    color: var(--fg-muted);
  }
  .card.is-decided{ opacity: 0.35; }

  .empty{
    text-align: center;
    color: var(--fg-muted);
    font-size: 13px;
    padding: 80px 0;
    letter-spacing: 0.04em;
  }

  /* Detail overlay */
  .overlay{
    position: fixed; inset: 0;
    background: rgba(0,0,0,0.35);
    display: none;
    z-index: 100;
  }
  .overlay.open{ display: block; }
  .sheet{
    position: fixed;
    right: 0; top: 0; bottom: 0;
    width: 100%;
    max-width: 460px;
    background: #ffffff !important;
    overflow-y: auto;
    transform: translateX(100%);
    transition: transform 0.25s ease;
  }
  .overlay.open .sheet{ transform: translateX(0); }

  .sheet-close{
    position: sticky; top: 0;
    display: flex; justify-content: flex-end;
    padding: 14px 16px;
    background: #ffffff !important;
  }
  .sheet-close button{
    background: none; border: none; font-size: 20px; cursor: pointer; color: var(--fg);
    font-family: inherit; line-height: 1;
  }

  .sheet .detail-img{
    width: 100%;
    aspect-ratio: 4 / 5;
    background: #f4f4f4;
    display:flex; align-items:center; justify-content:center;
  }
  .sheet .detail-img img{ width:100%; height:100%; object-fit: cover; }

  .detail-body{ padding: 20px 24px 40px; }
  .detail-body .brand{
    font-size: 11px; letter-spacing: 0.14em; text-transform: uppercase; color: var(--fg-muted); font-family: var(--nav-font); font-weight: 600;
  }
  .detail-body .name{ font-size: 17px; margin: 6px 0 2px; font-weight: 400; }
  .detail-body .price{ font-size: 15px; color: var(--fg-muted); margin-bottom: 18px; }

  .field-row{
    display:flex; justify-content: space-between; gap: 16px;
    padding: 9px 0;
    border-top: 1px solid var(--border);
    font-size: 12.5px;
  }
  .field-row .k{ color: var(--fg-muted); flex: 0 0 108px; }
  .field-row .v{ text-align: right; flex: 1; word-break: break-word; }
  .field-row .v a{ text-decoration: underline; }

  .ai-reason{
    margin-top: 18px;
    padding-top: 14px;
    border-top: 1px solid var(--border);
    font-size: 12.5px;
    line-height: 1.8;
    color: var(--fg-muted);
  }
  .ai-reason .k{
    display:block; color: var(--fg); font-size: 11px; letter-spacing: 0.1em;
    text-transform: uppercase; margin-bottom: 6px;
  }

  .actions{
    display: flex; gap: 8px; margin-top: 26px;
  }
  .actions button{
    flex: 1;
    padding: 13px 0;
    border: 1px solid var(--fg);
    background: var(--bg);
    color: var(--fg);
    font-size: 12px;
    letter-spacing: 0.1em;
    cursor: pointer;
    font-family: var(--nav-font);
    font-weight: 600;
  }
  .actions button.select{ background: var(--fg); color: var(--bg); }
  .actions button.hold{ border-color: var(--hold); color: var(--hold); }
  .actions button.reject{ border-color: var(--reject); color: var(--reject); }
  .actions button:disabled{ opacity: 0.4; cursor: default; }

  .current-status{
    margin-top: 14px;
    font-size: 11px;
    letter-spacing: 0.08em;
    color: var(--fg-muted);
    text-align: center;
  }

  .requeue-row{ text-align: center; margin-top: 10px; }
  .requeue-row button{
    background: none; border: none; text-decoration: underline;
    font-size: 11px; letter-spacing: 0.06em; color: var(--fg-muted); cursor: pointer; font-family: inherit;
  }
  .requeue-row button:disabled{ opacity: 0.4; cursor: default; }

  @media (min-width: 900px){
    .grid.cols-1{ max-width: 420px; margin: 0 auto; }
  }

  .app-nav{
    display: flex;
    justify-content: center;
    gap: 28px;
    padding: 14px 16px;
    border-bottom: 1px solid var(--border);
  }
  @media (min-width: 768px){ .app-nav{ padding-left: 40px; padding-right: 40px; } }
  @media (min-width: 1024px){ .app-nav{ padding-left: var(--rail); padding-right: var(--rail); } }
  .app-nav button{
    background: none;
    border: none;
    font-size: 11px;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--fg-muted);
    cursor: pointer;
    font-family: var(--nav-font);
    font-weight: 600;
    padding: 4px 0;
  }
  .app-nav button.active{
    color: var(--fg);
    border-bottom: 1px solid var(--fg);
  }

  .studio-layout{
    display: grid;
    grid-template-columns: 280px 1fr;
    min-height: 60vh;
  }
  @media (max-width: 800px){
    .studio-layout{ grid-template-columns: 1fr; }
  }
  .studio-list{
    border-right: 1px solid var(--border);
    overflow-y: auto;
    max-height: 80vh;
  }
  .studio-list-item{
    padding: 14px 16px;
    border-bottom: 1px solid var(--border);
    cursor: pointer;
  }
  .studio-list-item.active{ background: #f7f7f7; }
  .studio-list-item .brand{ font-size: 10px; letter-spacing: 0.1em; color: var(--fg-muted); text-transform: uppercase; }
  .studio-list-item .name{ font-size: 13px; margin-top: 3px; }
  .studio-list-item .price{ font-size: 11px; color: var(--fg-muted); margin-top: 2px; }

  .platform-row{
    display: flex; gap: 8px; flex-wrap: wrap;
    padding: 14px 16px;
    border-bottom: 1px solid var(--border);
  }
  .platform-chip{
    border: 1px solid var(--border);
    padding: 5px 10px;
    font-size: 10.5px;
    letter-spacing: 0.06em;
    cursor: pointer;
    background: var(--bg);
    color: var(--fg-muted);
    font-family: inherit;
  }
  .platform-chip.has-content{ border-color: var(--fg); color: var(--fg); }
  .platform-chip.active{ background: var(--fg); color: var(--bg); border-color: var(--fg); }
  .platform-chip .status-dot{ font-size: 9px; margin-left: 4px; color: var(--fg-muted); }

  .studio-form{ padding: 16px 24px 40px; max-width: 640px; }
  .studio-form label{
    display: block;
    font-size: 10.5px;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--fg-muted);
    margin: 18px 0 6px;
  }
  .studio-form label:first-child{ margin-top: 0; }
  .studio-form input[type=text], .studio-form textarea, .studio-form select{
    width: 100%;
    border: 1px solid var(--border);
    padding: 9px 10px;
    font-size: 13px;
    font-family: inherit;
    color: var(--fg);
    background: var(--bg);
  }
  .studio-form textarea{ resize: vertical; min-height: 64px; line-height: 1.6; }
  .studio-form .row2{ display: flex; gap: 12px; }
  .studio-form .row2 > div{ flex: 1; }

  .studio-actions{ display: flex; gap: 8px; margin-top: 26px; flex-wrap: wrap; }
  .studio-actions button{
    padding: 12px 18px;
    border: 1px solid var(--fg);
    background: var(--bg);
    color: var(--fg);
    font-size: 11px;
    letter-spacing: 0.08em;
    cursor: pointer;
    font-family: inherit;
  }
  .studio-actions button.primary{ background: var(--fg); color: var(--bg); }
  .studio-actions button.warn{ border-color: var(--reject); color: var(--reject); }
  .studio-actions button:disabled{ opacity: 0.4; cursor: default; }

  .studio-empty{ padding: 60px 24px; text-align: center; color: var(--fg-muted); font-size: 13px; }

  .publish-card{
    border: 1px solid var(--border);
    padding: 18px;
    margin-bottom: 20px;
    max-width: 640px;
  }
  .publish-card .head{ display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 10px; }
  .publish-card .brand{ font-size: 10px; letter-spacing: 0.1em; color: var(--fg-muted); text-transform: uppercase; }
  .publish-card .name{ font-size: 14px; margin-top: 3px; }
  .publish-card .platform{ font-size: 10.5px; letter-spacing: 0.1em; text-transform: uppercase; color: var(--fg-muted); }
  .publish-card .links{ display: flex; gap: 14px; margin: 10px 0; font-size: 12px; }
  .publish-card .links a{ text-decoration: underline; }
  .publish-card .copy-row{ display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px; }
  .publish-card .copy-row button{
    padding: 8px 12px; border: 1px solid var(--border); background: var(--bg);
    color: var(--fg); font-size: 10.5px; letter-spacing: 0.06em; cursor: pointer; font-family: inherit;
  }
  .publish-card .post-row{ display: flex; gap: 8px; margin-top: 10px; }
  .publish-card .post-row input{
    flex: 1; border: 1px solid var(--border); padding: 9px 10px; font-size: 12.5px; font-family: inherit;
  }
  .publish-card .post-row button{
    padding: 9px 16px; border: 1px solid var(--fg); background: var(--fg); color: var(--bg);
    font-size: 11px; letter-spacing: 0.06em; cursor: pointer; font-family: inherit;
  }
  .publish-card .post-row button:disabled{ opacity: 0.4; cursor: default; }

  .publish-group{
    border: 1px solid var(--border);
    padding: 18px;
    margin-bottom: 22px;
    max-width: 720px;
  }
  .publish-group-head{
    display: flex; justify-content: space-between; align-items: flex-start;
    padding-bottom: 14px; margin-bottom: 14px; border-bottom: 1px solid var(--border);
  }
  .publish-group-head .brand{ font-size: 10px; letter-spacing: 0.1em; color: var(--fg-muted); text-transform: uppercase; }
  .publish-group-head .name{ font-size: 14px; margin-top: 3px; }
  .group-actions{ display: flex; gap: 8px; flex: 0 0 auto; }
  .group-actions button{
    padding: 9px 14px; border: 1px solid var(--fg); background: var(--bg); color: var(--fg);
    font-size: 10.5px; letter-spacing: 0.06em; cursor: pointer; font-family: inherit; white-space: nowrap;
  }
  .group-actions button.primary{ background: var(--fg); color: var(--bg); }

  .platform-pub-row{ padding: 14px 0; border-top: 1px solid var(--border); }
  .platform-pub-row:first-child{ border-top: none; padding-top: 0; }
  .ppr-head{ display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 8px; }
  .ppr-platform{ font-size: 12px; letter-spacing: 0.08em; text-transform: uppercase; font-weight: 400; }
  .ppr-status{ font-size: 10px; letter-spacing: 0.06em; color: var(--fg-muted); border: 1px solid var(--border); padding: 2px 7px; }
  .ppr-status.is-failed{ color: var(--reject); border-color: var(--reject); }
  .ppr-mode{ margin-left: auto; font-size: 10px; letter-spacing: 0.06em; color: var(--fg-muted); display: flex; align-items: center; gap: 6px; }
  .ppr-mode select{ font-size: 10.5px; font-family: inherit; border: 1px solid var(--border); padding: 3px 5px; background: var(--bg); color: var(--fg); }
  .ppr-links{ display: flex; gap: 14px; font-size: 12px; margin-bottom: 10px; }
  .ppr-links a{ text-decoration: underline; }
  .ppr-links .muted{ color: var(--fg-muted); }
  .ppr-manual{ display: flex; gap: 8px; align-items: center; margin-bottom: 8px; flex-wrap: wrap; }
  .ppr-manual button{
    padding: 8px 12px; border: 1px solid var(--border); background: var(--bg);
    color: var(--fg); font-size: 10.5px; letter-spacing: 0.06em; cursor: pointer; font-family: inherit;
  }
  .ppr-manual input{
    flex: 1; min-width: 140px; border: 1px solid var(--border); padding: 8px 10px; font-size: 12px; font-family: inherit;
  }
  .ppr-manual button:disabled{ opacity: 0.4; cursor: default; }
  .ppr-auto{ display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
  .ppr-auto select{ font-size: 10.5px; font-family: inherit; border: 1px solid var(--border); padding: 8px 8px; background: var(--bg); color: var(--fg); }
  .ppr-auto button{
    padding: 8px 14px; border: 1px solid var(--fg); background: var(--fg); color: var(--bg);
    font-size: 10.5px; letter-spacing: 0.06em; cursor: pointer; font-family: inherit;
  }
  .ppr-auto button.warn{ background: var(--bg); border-color: var(--reject); color: var(--reject); }
  .ppr-auto button:disabled{ opacity: 0.4; cursor: default; }
  .ppr-result{ font-size: 11px; color: var(--fg-muted); }

  .confirm-overlay{
    position: fixed; inset: 0; background: rgba(0,0,0,0.4);
    display: flex; align-items: center; justify-content: center; z-index: 200; padding: 20px;
  }
  .confirm-box{
    background: var(--bg); max-width: 420px; width: 100%; padding: 26px; max-height: 80vh; overflow-y: auto;
  }
  .confirm-title{ font-size: 14px; margin-bottom: 16px; line-height: 1.6; }
  .confirm-list{ list-style: none; padding: 0; margin: 0 0 18px; font-size: 12.5px; }
  .confirm-list li{ padding: 6px 0; border-top: 1px solid var(--border); }
  .confirm-actions{ display: flex; gap: 8px; }
  .confirm-actions button{
    flex: 1; padding: 12px 0; border: 1px solid var(--fg); background: var(--bg); color: var(--fg);
    font-size: 11px; letter-spacing: 0.08em; cursor: pointer; font-family: inherit;
  }
  .confirm-actions button.primary{ background: var(--fg); color: var(--bg); }
  .confirm-actions button:disabled{ opacity: 0.4; cursor: default; }
  .confirm-results{ margin-top: 16px; font-size: 12px; }
  .confirm-results div{ padding: 6px 0; border-top: 1px solid var(--border); }
  .confirm-results .btnClose{
    margin-top: 14px; width: 100%; padding: 11px 0; border: 1px solid var(--fg); background: var(--fg); color: var(--bg);
    font-size: 11px; letter-spacing: 0.08em; cursor: pointer; font-family: inherit;
  }
  .mode-note{ font-size: 11px; color: var(--fg-muted); line-height: 1.7; padding: 12px 16px; border: 1px solid var(--border); max-width: 720px; margin: 0 0 20px; }

  .studio-tab-nav{
    display: flex; border-bottom: 1px solid var(--border); padding: 0 16px;
  }
  .studio-tab-nav button{
    background: none; border: none; border-bottom: 2px solid transparent; margin-bottom: -1px;
    padding: 11px 14px; font-size: 11.5px; letter-spacing: 0.08em; cursor: pointer;
    color: var(--fg-muted); font-family: inherit; position: relative;
  }
  .studio-tab-nav button.active{ color: var(--fg); border-bottom-color: var(--fg); }
  .tab-badge{
    display: inline-flex; align-items: center; justify-content: center;
    background: var(--fg); color: var(--bg); border-radius: 10px;
    font-size: 9px; min-width: 16px; height: 16px; padding: 0 4px;
    margin-left: 5px; vertical-align: middle;
  }
  .nav-badge{
    display: inline-flex; align-items: center; justify-content: center;
    background: var(--fg); color: var(--bg); border-radius: 8px;
    font-size: 9px; min-width: 14px; height: 14px; padding: 0 3px; margin-left: 4px; vertical-align: middle;
  }
  .cand-toolbar{
    display: flex; align-items: center; gap: 12px;
    padding: 12px 16px; border-bottom: 1px solid var(--border);
  }
  .btn-gen{
    padding: 9px 16px; border: 1px solid var(--fg); background: var(--fg); color: var(--bg);
    font-size: 11px; letter-spacing: 0.08em; cursor: pointer; font-family: inherit;
  }
  .btn-gen:disabled{ opacity: 0.4; cursor: default; }
  .gen-status{ font-size: 11px; color: var(--fg-muted); letter-spacing: 0.04em; }
  #candidatesList{ padding: 16px; max-width: 680px; }
  .candidate-card{
    border: 1px solid var(--border); padding: 18px; margin-bottom: 16px; cursor: pointer;
    transition: border-color 0.15s;
  }
  .candidate-card:hover{ border-color: var(--fg); }
  .candidate-card.cand-done{ opacity: 0.45; pointer-events: none; }
  .cand-head{ margin-bottom: 8px; }
  .cand-type{ font-size: 9.5px; letter-spacing: 0.12em; text-transform: uppercase; color: var(--fg-muted); margin-bottom: 4px; }
  .cand-title{ font-size: 15px; font-weight: 400; }
  .cand-theme{ font-size: 11px; color: var(--fg-muted); margin-top: 3px; }
  .cand-reason{
    font-size: 12.5px; line-height: 1.7; color: var(--fg-muted);
    margin: 10px 0; padding: 10px 0; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border);
  }
  .cand-products{ font-size: 11.5px; color: var(--fg-muted); margin-bottom: 12px; }
  .cand-prod-label{ margin-right: 8px; }
  .cand-reuse{ font-size: 10.5px; opacity: 0.8; }
  .cand-actions{ display: flex; gap: 8px; }
  .cand-actions .btn-adopt{
    padding: 9px 18px; border: 1px solid var(--fg); background: var(--fg); color: var(--bg);
    font-size: 11px; letter-spacing: 0.08em; cursor: pointer; font-family: inherit;
  }
  .cand-actions .btn-reject{
    padding: 9px 18px; border: 1px solid var(--reject); background: none; color: var(--reject);
    font-size: 11px; letter-spacing: 0.08em; cursor: pointer; font-family: inherit;
  }
  .cand-actions button:disabled{ opacity: 0.4; cursor: default; }
  .cand-done-msg{ font-size: 11px; color: var(--fg-muted); letter-spacing: 0.04em; line-height: 2.2; }
  .cand-overlay{
    position: fixed; inset: 0; background: rgba(0,0,0,0.35); display: none; z-index: 100;
  }
  .cand-overlay.open{ display: block; }
  .cand-sheet{
    position: fixed; right: 0; top: 0; bottom: 0; width: 100%; max-width: 520px;
    background: var(--bg); overflow-y: auto; transform: translateX(100%); transition: transform 0.25s ease;
  }
  .cand-overlay.open .cand-sheet{ transform: translateX(0); }
  .cand-sheet-close{
    position: sticky; top: 0; display: flex; justify-content: flex-end;
    padding: 14px 16px; background: var(--bg);
  }
  .cand-sheet-close button{ background: none; border: none; font-size: 20px; cursor: pointer; color: var(--fg); font-family: inherit; line-height: 1; }
  .cand-sheet-body{ padding: 4px 24px 40px; }
  .cand-sheet-title{ font-size: 20px; margin: 6px 0 8px; font-weight: 400; }
  .cand-sheet-section{ margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--border); }
  .cand-sheet-label{ font-size: 10px; letter-spacing: 0.12em; text-transform: uppercase; color: var(--fg-muted); margin-bottom: 8px; }
  .cand-sheet-text{ font-size: 13px; line-height: 1.75; }
  .cand-detail-prods{ display: grid; grid-template-columns: repeat(auto-fill, minmax(90px, 1fr)); gap: 10px; margin-top: 10px; }
  .cand-detail-prod{ text-align: center; }
  .cand-detail-prod img{ width: 100%; aspect-ratio: 3/4; object-fit: cover; background: #f4f4f4; display: block; }
  .cand-detail-noimg{ width: 100%; aspect-ratio: 3/4; background: #f4f4f4; display: flex; align-items: center; justify-content: center; font-size: 9px; color: #ccc; letter-spacing: 0.1em; }
  .cand-detail-brand{ font-size: 9px; letter-spacing: 0.1em; text-transform: uppercase; color: var(--fg-muted); margin-top: 5px; }
  .cand-detail-name{ font-size: 11.5px; margin-top: 2px; line-height: 1.4; }

  /* 企画候補 編集UI */
  .cand-detail-prod{ position: relative; }
  .cand-remove-prod{
    position: absolute; top: 2px; right: 2px;
    width: 18px; height: 18px; line-height: 16px; text-align: center;
    background: var(--fg); color: var(--bg); border: none; cursor: pointer;
    font-size: 11px; padding: 0; font-family: inherit;
  }
  .cand-add-section{ margin-top: 14px; border-top: 1px dashed var(--border); padding-top: 12px; }
  .cand-add-list{ max-height: 200px; overflow-y: auto; margin-top: 8px; }
  .cand-add-item{
    display: flex; align-items: center; gap: 10px;
    padding: 7px 0; border-bottom: 1px solid var(--border);
    font-size: 12px;
  }
  .cand-add-brand{ font-size: 9.5px; letter-spacing: 0.1em; text-transform: uppercase; color: var(--fg-muted); width: 80px; flex: 0 0 80px; }
  .cand-add-name{ flex: 1; font-size: 12px; }
  .cand-add-prod-btn{
    flex: 0 0 auto; padding: 5px 10px; border: 1px solid var(--fg); background: var(--bg);
    color: var(--fg); font-size: 10px; letter-spacing: 0.06em; cursor: pointer; font-family: inherit;
  }
  .cand-save-row{
    display: flex; align-items: center; gap: 10px;
    margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--border);
  }
  .cand-save-btn{
    padding: 10px 20px; border: 1px solid var(--fg); background: var(--fg); color: var(--bg);
    font-size: 11px; letter-spacing: 0.08em; cursor: pointer; font-family: inherit;
  }
  .cand-save-btn:disabled{ opacity: 0.4; cursor: default; }
  .cand-save-result{ font-size: 11px; color: var(--fg-muted); }

  /* MASTER IMAGE UI */
  .mi-section{
    margin-top: 18px; padding-top: 14px; border-top: 1px solid var(--border);
  }
  .mi-section-head{
    font-size: 10px; letter-spacing: 0.14em; text-transform: uppercase;
    color: var(--fg-muted); margin-bottom: 10px;
  }
  .mi-status-row{ font-size: 12.5px; margin-bottom: 6px; }
  .mi-status-val{ font-weight: 400; }
  .mi-status-val.verified{ color: #2a7a2a; }
  .mi-status-val.url-found{ color: var(--hold); }
  .mi-current-url{ font-size: 12px; margin-bottom: 12px; }
  .mi-current-url a{ text-decoration: underline; }
  .mi-form label{
    display: block; font-size: 10px; letter-spacing: 0.08em; text-transform: uppercase;
    color: var(--fg-muted); margin: 10px 0 4px;
  }
  .mi-form label:first-child{ margin-top: 0; }
  .mi-form input[type=text]{
    width: 100%; border: 1px solid var(--border); padding: 8px 10px;
    font-size: 12.5px; font-family: inherit; color: var(--fg); background: var(--bg);
  }
  .mi-form select{
    width: 100%; border: 1px solid var(--border); padding: 8px 10px;
    font-size: 12.5px; font-family: inherit; color: var(--fg); background: var(--bg);
  }
  .mi-row{ display: flex; gap: 8px; align-items: center; margin-top: 12px; }
  .mi-save-btn{
    padding: 10px 18px; border: 1px solid var(--fg); background: var(--fg); color: var(--bg);
    font-size: 11px; letter-spacing: 0.08em; cursor: pointer; font-family: inherit;
  }
  .mi-save-btn:disabled{ opacity: 0.4; cursor: default; }
  .mi-result{ font-size: 11px; color: var(--fg-muted); }
  .mi-loading{ font-size: 12px; color: var(--fg-muted); }

  /* ===== STAGE 3A: CONFIRMED PLANS & CONTENT MASTER ===== */
  .confirmed-plan-card{
    border:1px solid var(--border); border-radius:6px; padding:16px;
    margin-bottom:10px; cursor:pointer; transition:background 0.12s;
  }
  .confirmed-plan-card:hover{ background:rgba(0,0,0,0.025); }
  .plan-card-header{ display:flex; justify-content:space-between; align-items:flex-start; }
  .plan-card-title{ font-size:14px; font-weight:600; letter-spacing:-0.01em; }
  .plan-card-meta{ font-size:11px; color:var(--fg-muted); margin-top:5px; display:flex; gap:10px; flex-wrap:wrap; }
  .plan-card-reason{ font-size:12px; color:var(--fg-muted); margin-top:6px; line-height:1.5; }
  .cm-list{ margin-top:10px; display:flex; flex-direction:column; gap:6px; }
  .cm-card{
    border:1px solid var(--border); border-radius:5px; padding:11px 12px;
    display:flex; justify-content:space-between; align-items:center; gap:10px;
  }
  .cm-card-info{ flex:1; min-width:0; }
  .cm-card-type{ font-size:10px; letter-spacing:0.1em; text-transform:uppercase; color:var(--fg-muted); }
  .cm-card-title{ font-size:13px; margin-top:3px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  .cm-card-right{ display:flex; align-items:center; gap:8px; flex-shrink:0; }
  .cm-edit-btn{
    background:none; border:1px solid var(--border); font-size:11px;
    padding:4px 10px; border-radius:3px; cursor:pointer; font-family:inherit;
  }
  .cm-edit-btn:hover{ background:var(--fg); color:var(--bg); }
  .s-badge{
    display:inline-block; font-size:10px; font-weight:600; letter-spacing:0.08em;
    padding:2px 7px; border-radius:10px;
  }
  .s-badge.PENDING{ background:#eee; color:#666; }
  .s-badge.GENERATING{ background:#e8f0ff; color:#2255cc; }
  .s-badge.DRAFT{ background:#fff3cd; color:#7a5500; }
  .s-badge.REVIEW{ background:#cce5ff; color:#004085; }
  .s-badge.APPROVED{ background:#d4edda; color:#155724; }
  .s-badge.ERROR{ background:#f8d7da; color:#721c24; }
  .plan-overlay{
    position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:300;
    display:flex; align-items:flex-end; justify-content:center;
    opacity:0; transition:opacity 0.2s; pointer-events:none;
  }
  .plan-overlay.open{ opacity:1; pointer-events:auto; }
  .plan-sheet{
    background:var(--bg); width:100%; max-width:680px; max-height:90vh;
    border-radius:12px 12px 0 0; overflow-y:auto; padding-bottom:40px;
  }
  .plan-sheet-header{
    position:sticky; top:0; background:var(--bg);
    border-bottom:1px solid var(--border); padding:14px 20px;
    display:flex; justify-content:space-between; align-items:center; z-index:1;
  }
  .plan-sheet-header h2{ font-size:14px; font-weight:600; margin:0; }
  .plan-close{ background:none; border:none; font-size:20px; cursor:pointer; color:var(--fg-muted); }
  .plan-sheet-body{ padding:20px; }
  .plan-detail-meta{ display:flex; gap:10px; font-size:11px; color:var(--fg-muted); margin-bottom:10px; flex-wrap:wrap; }
  .plan-detail-reason{ font-size:12.5px; line-height:1.6; color:var(--fg-muted); margin-bottom:14px; }
  .plan-detail-prods{ display:flex; gap:6px; flex-wrap:wrap; margin-bottom:14px; }
  .plan-detail-prod{ border:1px solid var(--border); border-radius:4px; padding:5px 9px; font-size:11px; }
  .plan-section-head{
    font-size:10px; letter-spacing:0.14em; text-transform:uppercase; color:var(--fg-muted);
    border-top:1px solid var(--border); padding-top:12px; margin-top:16px; margin-bottom:10px;
  }
  .cm-create-form{
    border:1px dashed var(--border); border-radius:6px; padding:16px; margin-top:8px;
  }
  .cm-create-form-label{ font-size:12px; font-weight:600; margin-bottom:12px; }
  .cm-field{ margin-bottom:10px; }
  .cm-field label{
    display:block; font-size:10px; letter-spacing:0.08em; text-transform:uppercase;
    color:var(--fg-muted); margin-bottom:4px;
  }
  .cm-field input,.cm-field textarea,.cm-field select{
    width:100%; box-sizing:border-box; border:1px solid var(--border);
    border-radius:4px; padding:7px 9px; font-size:12.5px; font-family:inherit;
    background:var(--bg); color:var(--fg); resize:vertical;
  }
  .cm-field textarea{ min-height:60px; }
  .cm-field input:focus,.cm-field textarea:focus,.cm-field select:focus{ outline:none; border-color:var(--fg); }
  .cm-row2{ display:grid; grid-template-columns:1fr 1fr; gap:8px; }
  .cm-submit-row{ display:flex; align-items:center; gap:10px; margin-top:12px; }
  .cm-submit-btn{
    padding:9px 18px; background:var(--fg); color:var(--bg); border:none;
    font-size:12px; letter-spacing:0.06em; cursor:pointer; font-family:inherit; border-radius:3px;
  }
  .cm-submit-btn:disabled{ opacity:0.4; cursor:default; }
  .cm-form-result{ font-size:12px; color:var(--fg-muted); }
  .cm-overlay{
    position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:400;
    display:flex; align-items:flex-end; justify-content:center;
    opacity:0; transition:opacity 0.2s; pointer-events:none;
  }
  .cm-overlay.open{ opacity:1; pointer-events:auto; }
  .cm-sheet{
    background:var(--bg); width:100%; max-width:760px; max-height:94vh;
    border-radius:12px 12px 0 0; overflow-y:auto; padding-bottom:40px;
  }
  .cm-sheet-header{
    position:sticky; top:0; background:var(--bg);
    border-bottom:1px solid var(--border); padding:13px 20px;
    display:flex; justify-content:space-between; align-items:center; z-index:1;
  }
  .cm-sheet-hinfo{ flex:1; min-width:0; }
  .cm-sheet-htype{ font-size:10px; letter-spacing:0.1em; text-transform:uppercase; color:var(--fg-muted); }
  .cm-sheet-htitle{ font-size:14px; font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  .cm-sheet-close{ background:none; border:none; font-size:20px; cursor:pointer; color:var(--fg-muted); }
  .cm-sheet-body{ padding:20px; }
  .cm-edit-section-head{
    font-size:10px; letter-spacing:0.14em; text-transform:uppercase; color:var(--fg-muted);
    border-top:1px solid var(--border); padding-top:14px; margin-top:18px; margin-bottom:12px;
  }
  .cm-edit-section-head:first-child{ border-top:none; margin-top:0; padding-top:0; }
  .cm-ai-pending{
    border:1px dashed var(--border); border-radius:6px; padding:20px;
    text-align:center; margin:12px 0;
  }
  .cm-ai-pending p{ font-size:13px; color:var(--fg-muted); margin:0 0 12px; }
  .cm-gen-btn{
    padding:10px 20px; background:var(--fg); color:var(--bg); border:none;
    font-size:12px; letter-spacing:0.06em; cursor:pointer; font-family:inherit; border-radius:3px;
  }
  .cm-gen-btn:disabled{ opacity:0.4; cursor:default; }
  .cm-gen-note{ font-size:11px; color:var(--fg-muted); margin-top:8px; }
  .cm-ai-block{ margin-bottom:14px; }
  .cm-ai-block-label{
    font-size:10px; letter-spacing:0.08em; color:var(--fg-muted);
    text-transform:uppercase; margin-bottom:5px;
  }
  .cm-edit-field{
    width:100%; box-sizing:border-box; border:1px solid var(--border);
    border-radius:4px; padding:7px 9px; font-size:12.5px; font-family:inherit;
    background:var(--bg); color:var(--fg); resize:vertical; display:block;
  }
  .cm-edit-field:focus{ outline:none; border-color:var(--fg); }
  textarea.cm-edit-field{ min-height:60px; }
  .cm-version{ font-size:11px; color:var(--fg-muted); }
  .cm-edit-actions{
    display:flex; gap:8px; align-items:center; flex-wrap:wrap;
    margin-top:20px; padding-top:16px; border-top:1px solid var(--border);
  }
  .cm-edit-actions button{
    padding:8px 16px; font-size:12px; cursor:pointer; font-family:inherit;
    border-radius:3px; border:1px solid var(--border); background:none;
  }
  .cm-btn-primary{ background:var(--fg) !important; color:var(--bg) !important; border-color:var(--fg) !important; }
  .cm-action-result{ font-size:11px; color:var(--fg-muted); }

  .pb-wrap { padding-top: 60px; background: var(--bg); }
  @media (min-width: 768px){ .pb-wrap { padding-top: var(--rail); } }
  .app-nav { background: var(--bg); }
  header.pb-header {
    position: sticky; top: 60px; background: #fff; z-index: 9;
    border-bottom: 1px solid var(--border);
  }
  @media (min-width: 768px){ header.pb-header { top: var(--rail); } }

  /* ===== NEWS BOARD ===== */
  .news-wrap{
    padding: 24px 16px 80px;
  }
  @media (min-width: 768px){ .news-wrap{ padding: 28px 40px 80px; } }
  @media (min-width: 1024px){ .news-wrap{ padding: 28px var(--rail) 80px; } }
  .news-block{
    max-width: 480px;
    margin-bottom: 32px;
  }
  .news-block-label{
    font-family: var(--nav-font);
    font-size: 9px; letter-spacing: 0.2em; text-transform: uppercase;
    color: var(--fg-muted); margin: 0 0 14px;
  }
  .news-srch-form{
    display: flex; align-items: flex-end; gap: 0;
    border-bottom: 2px solid var(--fg);
  }
  .news-srch-input{
    flex: 1; min-width: 0; border: none; outline: none;
    background: transparent; box-shadow: none;
    -webkit-appearance: none; appearance: none;
    font-family: var(--nav-font); font-size: 13px;
    letter-spacing: 0.04em; color: var(--fg); padding: 9px 0;
  }
  .news-srch-input::placeholder{ color: var(--fg-muted); }
  .news-srch-btn{
    display: flex; align-items: center; gap: 7px;
    background: var(--fg); border: none; outline: none;
    font-family: var(--nav-font); font-size: 11px; font-weight: 600;
    letter-spacing: 0.08em; text-transform: uppercase;
    color: var(--bg); cursor: pointer; padding: 10px 14px;
    white-space: nowrap;
    -webkit-appearance: none; appearance: none; transition: opacity 0.2s;
  }
  .news-srch-btn svg{ width: 13px; height: 13px; flex-shrink: 0; }
  .news-srch-btn:hover{ opacity: 0.65; }
  .news-sites{
    display: flex; flex-direction: column; gap: 0;
  }
  .news-site{ border-top: 1px solid var(--border); }
  .news-site:last-child{ border-bottom: 1px solid var(--border); }
  .news-site a{
    display: flex; flex-direction: column; gap: 2px;
    padding: 11px 0; text-decoration: none; transition: opacity 0.2s;
  }
  .news-site a:hover{ opacity: 0.45; }
  .news-site-name{
    font-family: var(--nav-font); font-size: 13px; font-weight: 600;
    letter-spacing: -0.01em; color: var(--fg); line-height: 1.3;
  }
  .news-site-url{
    font-family: var(--nav-font); font-size: 10px;
    letter-spacing: 0.02em; color: var(--fg-muted);
  }
  .news-img-area{
    border-top: 1px solid var(--border); padding-top: 24px;
  }
  .news-img-wrap{
    width: 100%; aspect-ratio: 4 / 3;
    background: #f4f4f4; overflow: hidden;
    display: flex; align-items: center; justify-content: center;
    position: relative;
  }
  .news-img-wrap img{
    width: 100%; height: 100%; object-fit: cover; display: block;
  }
  .news-img-placeholder{
    font-family: var(--nav-font); font-size: 9px;
    letter-spacing: 0.2em; text-transform: uppercase; color: #ccc;
  }
</style>
</head>
<body class="greige-board">

<div class="pb-wrap">

<nav class="app-nav" id="appNav">
  <button data-view="board" class="active">Products Board</button>
  <button data-view="news">News Board</button>
  <button data-view="studio">Content Studio<span class="nav-badge" id="studioBadge" hidden></span></button>
  <button data-view="publish">Publish</button>
</nav>

<section id="view-news" hidden>
  <div class="news-wrap">

    <div class="news-block">
      <p class="news-block-label">Search</p>
      <form class="news-srch-form" role="search" action="<?php echo esc_url(home_url('/')); ?>" method="get">
        <input type="search" name="s" class="news-srch-input" placeholder="SEARCH..." autocomplete="off">
        <button type="submit" class="news-srch-btn">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="16.5" y1="16.5" x2="22" y2="22"/></svg>
          SEARCH
        </button>
      </form>
    </div>

    <div class="news-block">
      <p class="news-block-label">Information Sites</p>
      <div class="news-sites">
        <div class="news-site">
          <a href="https://prtimes.jp" target="_blank" rel="noopener noreferrer">
            <span class="news-site-name">PR TIMES</span>
            <span class="news-site-url">prtimes.jp</span>
          </a>
        </div>
        <div class="news-site">
          <a href="https://atpress.ne.jp" target="_blank" rel="noopener noreferrer">
            <span class="news-site-name">@Press</span>
            <span class="news-site-url">atpress.ne.jp</span>
          </a>
        </div>
        <div class="news-site">
          <a href="https://fashion-press.net" target="_blank" rel="noopener noreferrer">
            <span class="news-site-name">Fashion Press</span>
            <span class="news-site-url">fashion-press.net</span>
          </a>
        </div>
        <div class="news-site">
          <a href="https://fashionsnap.com" target="_blank" rel="noopener noreferrer">
            <span class="news-site-name">FASHIONSNAP</span>
            <span class="news-site-url">fashionsnap.com</span>
          </a>
        </div>
        <div class="news-site">
          <a href="https://hypebeast.com/jp" target="_blank" rel="noopener noreferrer">
            <span class="news-site-name">HYPEBEAST Japan</span>
            <span class="news-site-url">hypebeast.com/jp</span>
          </a>
        </div>
      </div>
    </div>

    <div class="news-block news-img-area">
      <div class="news-img-wrap" id="newsImgWrap">
        <span class="news-img-placeholder">NO IMAGE</span>
      </div>
    </div>

  </div>
</section>

<section id="view-board">
<header class="pb-header">
  <div class="toolbar">
    <nav class="date-nav" id="dateNav"></nav>
    <div class="cols" id="colSwitch">
      <button data-cols="1">1</button>
      <button data-cols="2">2</button>
      <button data-cols="3">3</button>
      <button data-cols="4">4</button>
    </div>
  </div>
  <nav class="category-nav" id="categoryNav"></nav>
  <div class="status-banner" id="statusBanner" hidden></div>
</header>

<main>
  <div class="grid cols-4" id="grid"></div>
  <div class="empty" id="emptyState" hidden>この日の候補はまだありません</div>
</main>

<div class="overlay" id="overlay">
  <div class="sheet" id="sheet">
    <div class="sheet-close"><button id="closeSheet" aria-label="close">×</button></div>
    <div id="sheetContent"></div>
  </div>
</div>
</section>

<section id="view-studio" hidden>
  <nav class="studio-tab-nav" id="studioTabNav">
    <button data-tab="candidates" class="active">企画候補<span class="tab-badge" id="candidateBadge" hidden></span></button>
    <button data-tab="plans">確定企画</button>
    <button data-tab="create">コンテンツ制作</button>
  </nav>
  <div id="studioTabCandidates">
    <div class="cand-toolbar">
      <button class="btn-gen" id="btnGeneratePlans">企画候補を生成</button>
      <span class="gen-status" id="genStatus"></span>
    </div>
    <div id="candidatesList"><div class="studio-empty">読み込み中...</div></div>
  </div>
  <div id="studioTabPlans" hidden>
    <div class="cand-toolbar">
      <button class="btn-gen" id="btnReloadPlans">確定企画を読み込む</button>
      <span class="gen-status" id="plansStatus"></span>
    </div>
    <div id="confirmedPlansList"><div class="studio-empty">「確定企画を読み込む」を押してください</div></div>
  </div>
  <div id="studioTabCreate" hidden>
    <div class="studio-layout">
      <div class="studio-list" id="studioList"></div>
      <div id="studioMain">
        <div class="studio-empty">左のリストから商品を選んでください</div>
      </div>
    </div>
  </div>
</section>

<section id="view-publish" hidden>
  <main id="publishMain">
    <div class="studio-empty">読み込み中...</div>
  </main>
</section>

</div><!-- /.pb-wrap -->

<script>
window.SAMPLE_DATA_URL = '<?php echo esc_js(get_stylesheet_directory_uri()); ?>/tools/product-board/sample_data.json';
</script>
<script>
<?php
$cfg = get_stylesheet_directory() . '/tools/product-board/config.js';
if (file_exists($cfg)) echo file_get_contents($cfg);
?>
</script>
<script>
(function(){
  var CONFIG = window.PRODUCT_BOARD_CONFIG || {};
  var LIVE = !!CONFIG.API_BASE_URL;

  var CATEGORIES = ['ALL', 'FASHION', 'BEAUTY', 'SELF_CARE', 'HEALTH_WELLNESS', 'LIFESTYLE', 'PREMIUM_HIGH_TICKET'];
  var CATEGORY_LABELS = {
    ALL: 'ALL',
    FASHION: 'Fashion',
    BEAUTY: 'Beauty',
    SELF_CARE: 'Self Care',
    HEALTH_WELLNESS: 'Health / Wellness',
    LIFESTYLE: 'Lifestyle',
    PREMIUM_HIGH_TICKET: 'Premium'
  };

  var state = {
    dates: [],
    currentDate: null,
    products: [],
    category: 'ALL',
    columns: parseInt(localStorage.getItem('pb_columns') || '4', 10)
  };

  var grid = document.getElementById('grid');
  var dateNav = document.getElementById('dateNav');
  var categoryNav = document.getElementById('categoryNav');
  var emptyState = document.getElementById('emptyState');
  var overlay = document.getElementById('overlay');
  var sheetContent = document.getElementById('sheetContent');
  var statusBanner = document.getElementById('statusBanner');

  if (!LIVE) {
    statusBanner.hidden = false;
    statusBanner.textContent = 'デモモード（sample_data.json） — config.js に Apps Script の URL を設定すると実データに接続されます';
  }

  function apiUrl(params){
    var url = CONFIG.API_BASE_URL + '?token=' + encodeURIComponent(CONFIG.API_TOKEN || '');
    Object.keys(params || {}).forEach(function(k){
      url += '&' + k + '=' + encodeURIComponent(params[k]);
    });
    return url;
  }

  function loadSampleData(){
    return fetch(window.SAMPLE_DATA_URL || 'sample_data.json').then(function(r){ return r.json(); });
  }

  function fetchDates(){
    if (!LIVE) {
      return loadSampleData().then(function(rows){
        var set = {};
        rows.forEach(function(r){ set[r.collected_date] = true; });
        return Object.keys(set).sort().reverse();
      });
    }
    return fetch(apiUrl({ action: 'dates' })).then(function(r){ return r.json(); });
  }

  function fetchProducts(date){
    if (!LIVE) {
      return loadSampleData().then(function(rows){
        return rows.filter(function(r){ return r.collected_date === date; });
      });
    }
    return fetch(apiUrl({ action: 'list', date: date })).then(function(r){ return r.json(); });
  }

  function postAction(product, status){
    if (!LIVE) {
      product.status = status;
      if (status === 'SELECTED') product.selected_date = new Date().toISOString().slice(0,10);
      return Promise.resolve({ ok: true, local: true });
    }
    return fetch(CONFIG.API_BASE_URL, {
      method: 'POST',
      body: JSON.stringify({
        token: CONFIG.API_TOKEN,
        action: 'updateStatus',
        product_id: product.product_id,
        status: status,
        experience_type: 'CURATED'
      })
    }).then(function(r){ return r.json(); }).then(function(result){
      product.status = status;
      if (status === 'SELECTED') product.selected_date = new Date().toISOString().slice(0,10);
      return result;
    });
  }

  function formatDateLabel(d){
    var parts = d.split('-');
    return parts[1] + '.' + parts[2] + '.' + parts[0];
  }

  function renderDateNav(){
    dateNav.innerHTML = '';
    state.dates.forEach(function(d){
      var btn = document.createElement('button');
      btn.textContent = formatDateLabel(d);
      if (d === state.currentDate) btn.classList.add('active');
      btn.addEventListener('click', function(){
        state.currentDate = d;
        loadProducts();
      });
      dateNav.appendChild(btn);
    });
  }

  function renderCategoryNav(){
    categoryNav.innerHTML = '';
    CATEGORIES.forEach(function(c){
      var btn = document.createElement('button');
      btn.textContent = CATEGORY_LABELS[c] || c;
      if (c === state.category) btn.classList.add('active');
      btn.addEventListener('click', function(){
        state.category = c;
        renderCategoryNav();
        renderGrid();
      });
      categoryNav.appendChild(btn);
    });
  }

  function renderColSwitch(){
    document.querySelectorAll('#colSwitch button').forEach(function(b){
      b.classList.toggle('active', parseInt(b.dataset.cols, 10) === state.columns);
    });
    grid.className = 'grid cols-' + state.columns;
  }

  function priceLabel(p){
    if (p.price === '' || p.price === undefined || p.price === null) return '';
    var n = Number(p.price);
    if (isNaN(n)) return String(p.price);
    return '¥' + n.toLocaleString('ja-JP');
  }

  var IMAGE_STATUS_LABELS = {
    PENDING: '画像収集待ち',
    DONE: '画像取得済み',
    FAILED: '画像取得失敗',
    SKIPPED_HAS_IMAGE: '',
    MANUAL: '対象外（手動）'
  };

  function imageStatusLabel(p){
    return IMAGE_STATUS_LABELS[p.image_status] !== undefined ? IMAGE_STATUS_LABELS[p.image_status] : '';
  }

  function renderGrid(){
    grid.innerHTML = '';
    var visible = state.products.filter(function(p){
      if (p.status === 'REJECTED' || p.status === 'DUPLICATE') return false;
      if (state.category !== 'ALL' && p.primary_category !== state.category) return false;
      return true;
    });
    emptyState.hidden = visible.length > 0;
    visible.forEach(function(p){
      var card = document.createElement('div');
      card.className = 'card' + (p.status === 'SELECTED' ? ' is-decided' : '');
      var thumb = document.createElement('div');
      thumb.className = 'thumb';
      if (p.image_url) {
        var img = document.createElement('img');
        img.src = p.image_url;
        img.alt = p.product_name;
        img.loading = 'lazy';
        thumb.appendChild(img);
      } else {
        var noimg = document.createElement('div');
        noimg.className = 'noimg';
        noimg.textContent = 'NO IMAGE';
        thumb.appendChild(noimg);
      }
      var meta = document.createElement('div');
      meta.className = 'meta';
      var imgStatusLabel = imageStatusLabel(p);
      meta.innerHTML =
        '<div class="brand">' + escapeHtml(p.brand) + '</div>' +
        '<div class="name">' + escapeHtml(p.product_name) + '</div>' +
        '<div class="price">' + priceLabel(p) + '</div>' +
        (imgStatusLabel ? '<div class="img-status">' + escapeHtml(imgStatusLabel) + '</div>' : '');
      card.appendChild(thumb);
      card.appendChild(meta);
      card.addEventListener('click', function(){ openDetail(p); });
      grid.appendChild(card);
    });
  }

  function escapeHtml(s){
    return String(s == null ? '' : s).replace(/[&<>"]/g, function(c){
      return { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;' }[c];
    });
  }

  function field(label, value, isLink){
    if (value === '' || value === undefined || value === null) return '';
    var v = isLink ? '<a href="' + escapeHtml(value) + '" target="_blank" rel="noopener">開く</a>' : escapeHtml(value);
    return '<div class="field-row"><div class="k">' + label + '</div><div class="v">' + v + '</div></div>';
  }

  function openDetail(p){
    sheetContent.innerHTML =
      '<div class="detail-img">' +
        (p.image_url ? '<img src="' + escapeHtml(p.image_url) + '" alt="">' : '<div class="noimg">NO IMAGE</div>') +
      '</div>' +
      '<div class="detail-body">' +
        '<div class="brand">' + escapeHtml(p.brand) + '</div>' +
        '<div class="name">' + escapeHtml(p.product_name) + '</div>' +
        '<div class="price">' + priceLabel(p) + '</div>' +
        field('カテゴリー', p.primary_category) +
        field('サブカテゴリー', p.subcategory) +
        field('商品URL', p.product_url, true) +
        field('公式URL', p.official_url, true) +
        field('アフィリエイト可否', p.affiliate_available) +
        field('価格帯', p.price_segment) +
        field('収益区分', p.content_role) +
        field('テイスト', p.style_tags) +
        field('カラー', p.color) +
        field('素材', p.material) +
        field('想定ターゲット', p.target_age) +
        field('目的・ニーズ', p.need_tags) +
        field('情報源', p.source) +
        field('収集日', p.collected_date) +
        field('デザイン年', p.design_year) +
        field('ステータス', p.status) +
        field('画像収集状況', imageStatusLabel(p) || p.image_status) +
        (p.ai_reason ? '<div class="ai-reason"><span class="k">AIの選定理由</span>' + escapeHtml(p.ai_reason) + '</div>' : '') +
        '<div class="actions">' +
          '<button class="select" data-action="SELECTED">SELECT</button>' +
          '<button class="hold" data-action="REVIEW">HOLD</button>' +
          '<button class="reject" data-action="REJECTED">REJECT</button>' +
        '</div>' +
        '<div class="current-status">現在のステータス: <span id="curStatus">' + escapeHtml(p.status) + '</span></div>' +
        ((p.image_status === 'DONE' || p.image_status === 'FAILED') ?
          '<div class="requeue-row"><button id="btnRequeueImage">画像を再取得</button></div>' : '') +
        '<div class="mi-section">' +
          '<div class="mi-section-head">Master Image</div>' +
          '<div id="masterImageContent" class="mi-loading">読み込み中...</div>' +
        '</div>' +
      '</div>';

    sheetContent.querySelectorAll('.actions button').forEach(function(btn){
      btn.addEventListener('click', function(){
        var status = btn.dataset.action;
        sheetContent.querySelectorAll('.actions button').forEach(function(b){ b.disabled = true; });
        postAction(p, status).then(function(){
          document.getElementById('curStatus').textContent = status;
          renderGrid();
          setTimeout(closeDetail, 350);
        }).catch(function(err){
          alert('更新に失敗しました: ' + err);
          sheetContent.querySelectorAll('.actions button').forEach(function(b){ b.disabled = false; });
        });
      });
    });

    var requeueBtn = document.getElementById('btnRequeueImage');
    if (requeueBtn) {
      requeueBtn.addEventListener('click', function(){
        requeueBtn.disabled = true;
        postJson({ action: 'requeueImage', product_id: p.product_id }).then(function(){
          p.image_status = 'PENDING';
          renderGrid();
          closeDetail();
        }).catch(function(err){
          alert('更新に失敗しました: ' + err);
          requeueBtn.disabled = false;
        });
      });
    }

    if (LIVE) {
      loadMasterImageForProduct(p.product_id);
    } else {
      document.getElementById('masterImageContent').innerHTML = '<span class="mi-loading">API設定が必要です</span>';
    }
    overlay.classList.add('open');
  }

  function loadMasterImageForProduct(productId){
    fetch(apiUrl({ action: 'listImageAssets' })).then(function(r){ return r.json(); }).then(function(rows){
      var asset = rows.filter(function(r){
        return String(r.product_id) === String(productId) && r.image_type === 'MASTER';
      })[0] || null;
      renderMasterImageSection(productId, asset);
    }).catch(function(){
      var el = document.getElementById('masterImageContent');
      if (el) el.innerHTML = '<span class="mi-loading">読み込み失敗</span>';
    });
  }

  function renderMasterImageSection(productId, asset){
    var el = document.getElementById('masterImageContent');
    if (!el) return;
    var url = asset ? (asset.image_url || '') : '';
    var st  = asset ? (asset.image_status || 'PENDING') : 'PENDING';
    var stCls = st === 'VERIFIED' ? 'verified' : st === 'URL_FOUND' ? 'url-found' : '';
    el.innerHTML =
      '<div class="mi-status-row">ステータス: <span class="mi-status-val ' + stCls + '">' + escapeHtml(st) + '</span></div>' +
      (url ? '<div class="mi-current-url"><a href="' + escapeHtml(url) + '" target="_blank" rel="noopener">現在の画像を確認 ↗</a></div>' : '') +
      '<div class="mi-form">' +
        '<label>画像URL</label>' +
        '<input type="text" id="miUrl" value="' + escapeAttr(url) + '" placeholder="https://...">' +
        '<label>ステータスを設定</label>' +
        '<select id="miStatus">' +
          '<option value="URL_FOUND"' + (st === 'URL_FOUND' ? ' selected' : '') + '>URL_FOUND（URL確認済み）</option>' +
          '<option value="VERIFIED"' + (st === 'VERIFIED' ? ' selected' : '') + '>VERIFIED（使用確認済み）</option>' +
        '</select>' +
        '<div class="mi-row">' +
          '<button class="mi-save-btn" id="miSave">保存</button>' +
          '<span class="mi-result" id="miResult"></span>' +
        '</div>' +
      '</div>';
    document.getElementById('miSave').addEventListener('click', function(){
      var newUrl = document.getElementById('miUrl').value.trim();
      var newSt  = document.getElementById('miStatus').value;
      var btn    = document.getElementById('miSave');
      var result = document.getElementById('miResult');
      if (!newUrl) {
        result.textContent = '画像URLを入力してください';
        return;
      }
      function doSave(){
        btn.disabled = true; result.textContent = '保存中...';
        postJson({ action: 'updateMasterImage', product_id: productId, image_url: newUrl, status: newSt }).then(function(res){
          btn.disabled = false;
          if (res.ok) {
            result.textContent = '保存しました';
            loadMasterImageForProduct(productId);
          } else {
            result.textContent = 'エラー: ' + (res.error || '不明');
          }
        }).catch(function(){ btn.disabled = false; result.textContent = '通信エラー'; });
      }
      if (newSt === 'VERIFIED') {
        btn.disabled = true; result.textContent = '画像を確認中...';
        var testImg = new Image();
        testImg.onload = function(){
          btn.disabled = false; result.textContent = '';
          if (!confirm('画像URLの読み込みを確認しました。\nVERIFIED（使用確認済み）として保存しますか？')) return;
          doSave();
        };
        testImg.onerror = function(){
          btn.disabled = false;
          result.textContent = '画像URLを読み込めませんでした。URLを確認してください';
        };
        testImg.src = newUrl;
        return;
      }
      doSave();
    });
  }

  function closeDetail(){
    overlay.classList.remove('open');
  }

  document.getElementById('closeSheet').addEventListener('click', closeDetail);
  overlay.addEventListener('click', function(e){ if (e.target === overlay) closeDetail(); });

  document.getElementById('colSwitch').addEventListener('click', function(e){
    var btn = e.target.closest('button');
    if (!btn) return;
    state.columns = parseInt(btn.dataset.cols, 10);
    localStorage.setItem('pb_columns', state.columns);
    renderColSwitch();
  });

  function loadProducts(){
    renderDateNav();
    fetchProducts(state.currentDate).then(function(rows){
      state.products = rows;
      renderGrid();
    });
  }

  function init(){
    renderColSwitch();
    renderCategoryNav();
    fetchDates().then(function(dates){
      state.dates = dates;
      state.currentDate = dates[0] || null;
      if (!state.currentDate) {
        emptyState.hidden = false;
        return;
      }
      loadProducts();
    });
  }

  init();

  // ===== App-level view switching =====
  var views = {
    board: document.getElementById('view-board'),
    news: document.getElementById('view-news'),
    studio: document.getElementById('view-studio'),
    publish: document.getElementById('view-publish')
  };
  var studioLoaded = false;
  var publishLoaded = false;

  document.getElementById('appNav').addEventListener('click', function(e){
    var btn = e.target.closest('button');
    if (!btn) return;
    var view = btn.dataset.view;
    document.querySelectorAll('#appNav button').forEach(function(b){ b.classList.toggle('active', b === btn); });
    Object.keys(views).forEach(function(k){ views[k].hidden = (k !== view); });
    if (view === 'studio' && !studioLoaded) { studioLoaded = true; loadPlanCandidates(); }
    if (view === 'studio') { updateStudioBadge(); }
    if (view === 'publish') { loadPublishQueue(); }
  });

  // ===== STUDIO TAB SWITCHING =====
  document.getElementById('studioTabNav').addEventListener('click', function(e){
    var btn = e.target.closest('[data-tab]');
    if (!btn) return;
    var tab = btn.dataset.tab;
    document.querySelectorAll('#studioTabNav button').forEach(function(b){ b.classList.toggle('active', b === btn); });
    document.getElementById('studioTabCandidates').hidden = (tab !== 'candidates');
    document.getElementById('studioTabPlans').hidden = (tab !== 'plans');
    document.getElementById('studioTabCreate').hidden = (tab !== 'create');
    if (tab === 'create' && studioState.products.length === 0) loadStudioList();
    if (tab === 'plans' && !studioState.plansLoaded) loadConfirmedPlans();
  });

  // ===== PLAN CANDIDATES =====
  function fetchPlanCandidates(){
    return fetch(apiUrl({ action: 'getPlanCandidates', status: 'UNCONFIRMED' })).then(function(r){ return r.json(); });
  }
  function fetchUnconfirmedCount(){
    return fetch(apiUrl({ action: 'getUnconfirmedCount' })).then(function(r){ return r.json(); });
  }

  function loadPlanCandidates(){
    if (!LIVE) {
      document.getElementById('candidatesList').innerHTML = '<div class="studio-empty">config.js にAPI設定が必要です</div>';
      return;
    }
    document.getElementById('candidatesList').innerHTML = '<div class="studio-empty">読み込み中...</div>';
    fetchPlanCandidates().then(function(rows){ renderPlanCandidates(rows); }).catch(function(){
      document.getElementById('candidatesList').innerHTML = '<div class="studio-empty">読み込み失敗</div>';
    });
  }

  function renderPlanCandidates(rows){
    var container = document.getElementById('candidatesList');
    if (!rows || rows.length === 0) {
      container.innerHTML = '<div class="studio-empty">未確認の企画候補がありません。「企画候補を生成」を押してください。</div>';
      updateStudioBadge(0);
      return;
    }
    updateStudioBadge(rows.length);
    container.innerHTML = '';
    rows.forEach(function(c){ container.appendChild(buildCandidateCard(c)); });
  }

  function buildCandidateCard(c){
    var pids = String(c.product_ids || '').split(',').map(function(s){ return s.trim(); }).filter(Boolean);
    var card = document.createElement('div');
    card.className = 'candidate-card';
    card.innerHTML =
      '<div class="cand-head">' +
        '<div class="cand-type">' + escapeHtml(c.plan_type || '') + '</div>' +
        '<div class="cand-title">' + escapeHtml(c.plan_title || '') + '</div>' +
        (c.theme ? '<div class="cand-theme">' + escapeHtml(c.theme) + '</div>' : '') +
      '</div>' +
      '<div class="cand-reason">' + escapeHtml(c.reason || '') + '</div>' +
      '<div class="cand-products">' +
        '<span class="cand-prod-label">' + pids.length + '点の商品</span>' +
        (c.notes ? '<span class="cand-reuse">' + escapeHtml(c.notes) + '</span>' : '') +
      '</div>' +
      '<div class="cand-actions">' +
        '<button class="btn-adopt">採用</button>' +
        '<button class="btn-reject">却下</button>' +
      '</div>';
    card.querySelector('.btn-adopt').addEventListener('click', function(e){
      e.stopPropagation();
      adoptCandidate(c.candidate_id, card);
    });
    card.querySelector('.btn-reject').addEventListener('click', function(e){
      e.stopPropagation();
      var reason = prompt('却下理由（任意・空欄でもOK）');
      if (reason === null) return;
      rejectCandidate(c.candidate_id, reason, card);
    });
    card.addEventListener('click', function(){ openCandidateDetail(c, pids); });
    return card;
  }

  function adoptCandidate(cid, card){
    card.querySelectorAll('button').forEach(function(b){ b.disabled = true; });
    postJson({ action: 'adoptPlan', candidate_id: cid }).then(function(result){
      if (result.ok) {
        card.classList.add('cand-done');
        card.querySelector('.cand-actions').innerHTML = '<span class="cand-done-msg">採用済み — PLAN: ' + escapeHtml(result.plan_id || '') + '</span>';
        updateStudioBadge();
      } else {
        alert('採用に失敗しました: ' + (result.error || '不明'));
        card.querySelectorAll('button').forEach(function(b){ b.disabled = false; });
      }
    }).catch(function(){ alert('通信エラー'); card.querySelectorAll('button').forEach(function(b){ b.disabled = false; }); });
  }

  function rejectCandidate(cid, reason, card){
    card.querySelectorAll('button').forEach(function(b){ b.disabled = true; });
    postJson({ action: 'rejectPlan', candidate_id: cid, reason: reason || '' }).then(function(result){
      if (result.ok) {
        card.classList.add('cand-done');
        card.querySelector('.cand-actions').innerHTML = '<span class="cand-done-msg">却下済み</span>';
        updateStudioBadge();
      } else {
        alert('却下に失敗しました: ' + (result.error || '不明'));
        card.querySelectorAll('button').forEach(function(b){ b.disabled = false; });
      }
    }).catch(function(){ alert('通信エラー'); card.querySelectorAll('button').forEach(function(b){ b.disabled = false; }); });
  }

  function openCandidateDetail(c, pids){
    var doOpen = function(prods){
      var pMap = {};
      prods.forEach(function(p){ pMap[String(p.product_id)] = p; });
      var editablePids = pids.slice();
      var changed = false;
      var ov = document.createElement('div');
      ov.className = 'cand-overlay';
      document.body.appendChild(ov);

      function reattach(){
        ov.querySelector('.cand-sheet-close button').addEventListener('click', function(){ ov.remove(); });
        ov.addEventListener('click', function(e){ if (e.target === ov) ov.remove(); });
        ov.querySelectorAll('.cand-remove-prod').forEach(function(btn){
          btn.addEventListener('click', function(e){
            e.stopPropagation();
            editablePids = editablePids.filter(function(p){ return p !== btn.dataset.pid; });
            changed = true;
            render();
          });
        });
        ov.querySelectorAll('.cand-add-prod-btn').forEach(function(btn){
          btn.addEventListener('click', function(e){
            e.stopPropagation();
            var pid = btn.dataset.pid;
            if (editablePids.indexOf(pid) < 0) editablePids.push(pid);
            changed = true;
            render();
          });
        });
        var saveBtn = ov.querySelector('#candSaveBtn');
        if (saveBtn) {
          saveBtn.addEventListener('click', function(){
            if (editablePids.length < 2) { alert('商品は2点以上必要です'); return; }
            saveBtn.disabled = true;
            ov.querySelector('#candSaveResult').textContent = '保存中...';
            postJson({ action: 'recalcCandidate', candidate_id: c.candidate_id, product_ids: editablePids })
              .then(function(res){
                saveBtn.disabled = false;
                if (res.ok) {
                  ov.querySelector('#candSaveResult').textContent = '保存しました';
                  if (res.new_title) c.plan_title = res.new_title;
                  c.product_ids = editablePids.join(',');
                  pids = editablePids.slice();
                  changed = false;
                  loadPlanCandidates();
                  render();
                } else {
                  ov.querySelector('#candSaveResult').textContent = 'エラー: ' + (res.error || '不明');
                }
              }).catch(function(){ saveBtn.disabled = false; ov.querySelector('#candSaveResult').textContent = '通信エラー'; });
          });
        }
      }

      function render(){
        var productHtml = editablePids.map(function(pid){
          var p = pMap[pid] || {};
          return '<div class="cand-detail-prod">' +
            (p.image_url ? '<img src="' + escapeHtml(p.image_url) + '" alt="" loading="lazy">' : '<div class="cand-detail-noimg">NO IMAGE</div>') +
            '<div class="cand-detail-brand">' + escapeHtml(p.brand || pid) + '</div>' +
            '<div class="cand-detail-name">' + escapeHtml(p.product_name || '') + '</div>' +
            '<button class="cand-remove-prod" data-pid="' + escapeAttr(pid) + '" title="除外">×</button>' +
          '</div>';
        }).join('');

        var addableProds = prods.filter(function(p){ return editablePids.indexOf(String(p.product_id)) < 0; });
        var addableHtml = addableProds.length > 0 ?
          '<div class="cand-add-section">' +
            '<div class="cand-sheet-label">SELECT済みから追加</div>' +
            '<div class="cand-add-list">' +
              addableProds.map(function(p){
                return '<div class="cand-add-item">' +
                  '<div class="cand-add-brand">' + escapeHtml(p.brand || '') + '</div>' +
                  '<div class="cand-add-name">' + escapeHtml(p.product_name || '') + '</div>' +
                  '<button class="cand-add-prod-btn" data-pid="' + escapeAttr(String(p.product_id)) + '">＋追加</button>' +
                '</div>';
              }).join('') +
            '</div>' +
          '</div>' : '';

        ov.innerHTML =
          '<div class="cand-sheet">' +
            '<div class="cand-sheet-close"><button aria-label="close">×</button></div>' +
            '<div class="cand-sheet-body">' +
              '<div class="cand-type">' + escapeHtml(c.plan_type || '') + '</div>' +
              '<div class="cand-sheet-title">' + escapeHtml(c.plan_title || '') + '</div>' +
              (c.theme ? '<div class="cand-theme" style="margin-bottom:12px">' + escapeHtml(c.theme) + '</div>' : '') +
              '<div class="cand-sheet-section">' +
                '<div class="cand-sheet-label">選定理由</div>' +
                '<div class="cand-sheet-text">' + escapeHtml(c.reason || '') + '</div>' +
              '</div>' +
              '<div class="cand-sheet-section">' +
                '<div class="cand-sheet-label">対象商品 <span id="candProdCount">' + editablePids.length + '</span>点</div>' +
                '<div class="cand-detail-prods">' + productHtml + '</div>' +
                addableHtml +
              '</div>' +
              (c.notes ? '<div class="cand-sheet-section"><div class="cand-sheet-label">利用状況</div><div class="cand-sheet-text">' + escapeHtml(c.notes) + '</div></div>' : '') +
              (changed ?
                '<div class="cand-save-row">' +
                  '<button class="cand-save-btn" id="candSaveBtn">変更を保存</button>' +
                  '<span class="cand-save-result" id="candSaveResult"></span>' +
                '</div>' : '') +
            '</div>' +
          '</div>';
        ov.classList.add('open');
        reattach();
      }

      render();
      requestAnimationFrame(function(){ ov.classList.add('open'); });
    };
    if (studioState.products && studioState.products.length > 0) {
      doOpen(studioState.products);
    } else {
      fetchSelected().then(function(rows){ studioState.products = rows; doOpen(rows); });
    }
  }

  function updateStudioBadge(count){
    var tabBadge = document.getElementById('candidateBadge');
    var navBadge = document.getElementById('studioBadge');
    if (count === undefined) {
      if (!LIVE) return;
      fetchUnconfirmedCount().then(function(result){ updateStudioBadge(result.count || 0); }).catch(function(){});
      return;
    }
    if (count > 0) {
      tabBadge.textContent = count; tabBadge.hidden = false;
      navBadge.textContent = count; navBadge.hidden = false;
    } else {
      tabBadge.hidden = true; navBadge.hidden = true;
    }
  }

  document.getElementById('btnGeneratePlans').addEventListener('click', function(){
    if (!LIVE) { alert('config.js にAPI設定が必要です'); return; }
    var btn = document.getElementById('btnGeneratePlans');
    var status = document.getElementById('genStatus');
    btn.disabled = true; status.textContent = '生成中...';
    postJson({ action: 'generatePlans' }).then(function(result){
      btn.disabled = false;
      if (result.ok) {
        status.textContent = result.generated + '件生成しました';
        loadPlanCandidates();
      } else {
        status.textContent = '生成失敗: ' + (result.error || '不明');
      }
    }).catch(function(){ btn.disabled = false; status.textContent = '通信エラー'; });
  });

  // ===== CONTENT STUDIO =====
  var PLATFORMS = ['INSTAGRAM', 'TIKTOK', 'YOUTUBE', 'X'];
  var studioList = document.getElementById('studioList');
  var studioMain = document.getElementById('studioMain');
  var studioState = { products: [], currentProduct: null, contents: [], currentPlatform: null, plans: [], plansLoaded: false };

  function fetchSelected(){
    return fetch(apiUrl({ action: 'listSelected' })).then(function(r){ return r.json(); });
  }
  function fetchContentForProduct(productId){
    return fetch(apiUrl({ action: 'listContent', product_id: productId })).then(function(r){ return r.json(); });
  }
  function fetchPublishReady(){
    return fetch(apiUrl({ action: 'listPublishReady' })).then(function(r){ return r.json(); });
  }
  function postJson(body){
    return fetch(CONFIG.API_BASE_URL, {
      method: 'POST',
      body: JSON.stringify(Object.assign({ token: CONFIG.API_TOKEN }, body))
    }).then(function(r){ return r.json(); });
  }

  function loadStudioList(){
    if (!LIVE) {
      studioList.innerHTML = '<div class="studio-empty">config.js にAPI設定が必要です</div>';
      return;
    }
    fetchSelected().then(function(rows){
      studioState.products = rows;
      renderStudioList();
    });
  }

  function renderStudioList(){
    studioList.innerHTML = '';
    if (studioState.products.length === 0) {
      studioList.innerHTML = '<div class="studio-empty">SELECTED_PRODUCTSに商品がありません</div>';
      return;
    }
    studioState.products.forEach(function(p){
      var item = document.createElement('div');
      item.className = 'studio-list-item' + (studioState.currentProduct && studioState.currentProduct.product_id === p.product_id ? ' active' : '');
      item.innerHTML =
        '<div class="brand">' + escapeHtml(p.brand) + '</div>' +
        '<div class="name">' + escapeHtml(p.product_name) + '</div>' +
        '<div class="price">' + priceLabel(p) + '</div>';
      item.addEventListener('click', function(){
        studioState.currentProduct = p;
        studioState.currentPlatform = null;
        renderStudioList();
        loadContentForCurrentProduct();
      });
      studioList.appendChild(item);
    });
  }

  function loadContentForCurrentProduct(){
    var p = studioState.currentProduct;
    fetchContentForProduct(p.product_id).then(function(rows){
      studioState.contents = rows;
      if (!studioState.currentPlatform) studioState.currentPlatform = PLATFORMS[0];
      renderStudioMain();
    });
  }

  function findContent(platform){
    return studioState.contents.filter(function(c){ return c.platform === platform; })[0] || null;
  }

  function renderStudioMain(){
    var p = studioState.currentProduct;
    if (!p) { studioMain.innerHTML = '<div class="studio-empty">左のリストから商品を選んでください</div>'; return; }

    var chips = PLATFORMS.map(function(pl){
      var c = findContent(pl);
      var cls = 'platform-chip' + (c ? ' has-content' : '') + (pl === studioState.currentPlatform ? ' active' : '');
      return '<button class="' + cls + '" data-platform="' + pl + '">' + pl + (c ? ' <span class="status-dot">' + c.status + '</span>' : '') + '</button>';
    }).join('');

    var content = findContent(studioState.currentPlatform) || {};

    studioMain.innerHTML =
      '<div class="platform-row" id="platformRow">' + chips + '</div>' +
      '<div class="studio-form">' +
        '<label>Content Type</label>' +
        '<select id="f_content_type">' +
          ['REEL','SHORT','POST','CAROUSEL','ARTICLE'].map(function(t){ return '<option value="' + t + '"' + (content.content_type === t ? ' selected' : '') + '>' + t + '</option>'; }).join('') +
        '</select>' +
        '<div class="row2">' +
          '<div><label>Template ID</label><input type="text" id="f_template_id" value="' + escapeAttr(content.template_id) + '"></div>' +
          '<div><label>Duration</label><input type="text" id="f_duration" value="' + escapeAttr(content.duration) + '" placeholder="15 sec"></div>' +
        '</div>' +
        '<label>Title</label><input type="text" id="f_title" value="' + escapeAttr(content.title) + '">' +
        '<label>Script</label><textarea id="f_script">' + escapeHtml(content.script) + '</textarea>' +
        '<label>On Screen Text</label><textarea id="f_on_screen_text">' + escapeHtml(content.on_screen_text) + '</textarea>' +
        '<label>Caption</label><textarea id="f_caption">' + escapeHtml(content.caption) + '</textarea>' +
        '<label>Hashtags</label><input type="text" id="f_hashtags" value="' + escapeAttr(content.hashtags) + '" placeholder="#タグ #タグ2">' +
        '<label>Affiliate URL</label><input type="text" id="f_affiliate_url" value="' + escapeAttr(content.affiliate_url || p.affiliate_url) + '">' +
        '<div class="row2">' +
          '<div><label>Video Path (Drive)</label><input type="text" id="f_video_path" value="' + escapeAttr(content.video_path) + '" placeholder="https://drive.google.com/..."></div>' +
          '<div><label>Thumbnail Path (Drive)</label><input type="text" id="f_thumbnail_path" value="' + escapeAttr(content.thumbnail_path) + '"></div>' +
        '</div>' +
        '<div class="studio-actions">' +
          '<button id="btnSaveDraft">SAVE DRAFT</button>' +
          '<button id="btnSubmitReview">SUBMIT FOR REVIEW</button>' +
          '<button id="btnApprove" class="primary">APPROVE</button>' +
          '<button id="btnReject" class="warn">REJECT</button>' +
        '</div>' +
        '<div class="current-status">現在のステータス: ' + escapeHtml(content.status || 'DRAFT（未作成）') + '</div>' +
      '</div>';

    document.getElementById('platformRow').querySelectorAll('.platform-chip').forEach(function(btn){
      btn.addEventListener('click', function(){
        studioState.currentPlatform = btn.dataset.platform;
        renderStudioMain();
      });
    });

    function collectFormContent(){
      return {
        content_id: content.content_id || '',
        product_id: p.product_id,
        platform: studioState.currentPlatform,
        content_type: document.getElementById('f_content_type').value,
        template_id: document.getElementById('f_template_id').value,
        duration: document.getElementById('f_duration').value,
        title: document.getElementById('f_title').value,
        script: document.getElementById('f_script').value,
        on_screen_text: document.getElementById('f_on_screen_text').value,
        caption: document.getElementById('f_caption').value,
        hashtags: document.getElementById('f_hashtags').value,
        affiliate_url: document.getElementById('f_affiliate_url').value,
        video_path: document.getElementById('f_video_path').value,
        thumbnail_path: document.getElementById('f_thumbnail_path').value
      };
    }

    function afterSave(result){
      return loadContentForCurrentProduct();
    }

    document.getElementById('btnSaveDraft').addEventListener('click', function(){
      postJson({ action: 'saveContent', content: collectFormContent() }).then(afterSave);
    });
    document.getElementById('btnSubmitReview').addEventListener('click', function(){
      postJson({ action: 'saveContent', content: collectFormContent() }).then(function(result){
        return postJson({ action: 'setContentStatus', content_id: result.content_id, status: 'REVIEW' });
      }).then(afterSave);
    });
    document.getElementById('btnApprove').addEventListener('click', function(){
      postJson({ action: 'saveContent', content: collectFormContent() }).then(function(result){
        return postJson({ action: 'setContentStatus', content_id: result.content_id, status: 'APPROVED' });
      }).then(afterSave);
    });
    document.getElementById('btnReject').addEventListener('click', function(){
      if (!content.content_id) return;
      postJson({ action: 'setContentStatus', content_id: content.content_id, status: 'REJECTED' }).then(afterSave);
    });
  }

  function escapeAttr(s){
    return escapeHtml(s).replace(/\n/g, '');
  }

  // ===== PUBLISH =====
  var publishMain = document.getElementById('publishMain');

  function loadPublishQueue(){
    if (!LIVE) {
      publishMain.innerHTML = '<div class="studio-empty">config.js にAPI設定が必要です</div>';
      return;
    }
    publishMain.innerHTML = '<div class="studio-empty">読み込み中...</div>';
    Promise.all([fetchPublishReady(), fetchSelected()]).then(function(results){
      renderPublishQueue(results[0], results[1]);
    });
  }

  function buildCopyText(c){
    var hashtags = c.hashtags || '';
    if (c.platform === 'YOUTUBE') {
      return [c.title, c.caption, hashtags].filter(Boolean).join('\n\n');
    }
    if (c.platform === 'X') {
      return [c.caption, hashtags].filter(Boolean).join('\n\n');
    }
    return [c.caption, hashtags].filter(Boolean).join('\n\n');
  }

  function copyToClipboard(text){
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).catch(function(){ fallbackCopy(text); });
    } else {
      fallbackCopy(text);
    }
  }
  function fallbackCopy(text){
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    try { document.execCommand('copy'); } catch (e) {}
    document.body.removeChild(ta);
  }

  function renderPublishQueue(rows, products){
    if (rows.length === 0) {
      publishMain.innerHTML = '<div class="studio-empty">APPROVED状態のコンテンツはまだありません</div>';
      return;
    }
    var productMap = {};
    (products || []).forEach(function(p){ productMap[p.product_id] = p; });
    var grouped = {};
    var order = [];
    rows.forEach(function(c){
      if (!grouped[c.product_id]) { grouped[c.product_id] = []; order.push(c.product_id); }
      grouped[c.product_id].push(c);
    });

    publishMain.innerHTML =
      '<div class="mode-note">投稿方法は3通りです。①COPY POST + DOWNLOAD VIDEOで手動投稿し MARK AS POSTED を押す。②各プラットフォームの POST NOW でそのプラットフォームだけ自動投稿する。③ POST ALL で対象プラットフォームへ一括自動投稿する。現在、②③は本物のSNS APIには接続されていないモック（テスト用の疑似投稿）です。モードの MANUAL / AUTO は将来の実API接続に備えた設定項目で、今はどちらを選んでも投稿方法は自由に選べます。</div>';

    order.forEach(function(pid){
      var p = productMap[pid] || {};
      var contents = grouped[pid];
      var group = document.createElement('div');
      group.className = 'publish-group';
      group.innerHTML =
        '<div class="publish-group-head">' +
          '<div><div class="brand">' + escapeHtml(p.brand || pid) + '</div><div class="name">' + escapeHtml(p.product_name || '') + '</div></div>' +
          '<div class="group-actions"><button class="btnCopyAll">COPY ALL</button><button class="btnPostAll primary">POST ALL</button></div>' +
        '</div>';
      var rowsWrap = document.createElement('div');
      rowsWrap.className = 'ppr-rows';
      contents.forEach(function(c){
        rowsWrap.appendChild(buildPlatformRow(c));
      });
      group.appendChild(rowsWrap);

      group.querySelector('.btnCopyAll').addEventListener('click', function(){
        copyToClipboard(contents.map(buildCopyText).join('\n\n---\n\n'));
      });
      group.querySelector('.btnPostAll').addEventListener('click', function(){
        openPostAllModal(p, contents);
      });

      publishMain.appendChild(group);
    });
  }

  function buildPlatformRow(c){
    var row = document.createElement('div');
    row.className = 'platform-pub-row';
    var isFailed = c.status === 'PUBLISH_FAILED';
    row.innerHTML =
      '<div class="ppr-head">' +
        '<span class="ppr-platform">' + escapeHtml(c.platform) + '</span>' +
        '<span class="ppr-status' + (isFailed ? ' is-failed' : '') + '">' + escapeHtml(c.status) + '</span>' +
        '<label class="ppr-mode">モード' +
          '<select class="modeSelect">' +
            '<option value="MANUAL"' + (c.posting_mode !== 'AUTO' ? ' selected' : '') + '>MANUAL</option>' +
            '<option value="AUTO"' + (c.posting_mode === 'AUTO' ? ' selected' : '') + '>AUTO（モック）</option>' +
          '</select>' +
        '</label>' +
      '</div>' +
      '<div class="ppr-links">' +
        (c.video_path ? '<a href="' + escapeHtml(c.video_path) + '" download target="_blank" rel="noopener">DOWNLOAD VIDEO</a>' : '<span class="muted">動画未設定</span>') +
        (c.thumbnail_path ? '<a href="' + escapeHtml(c.thumbnail_path) + '" target="_blank" rel="noopener">サムネイルを開く</a>' : '') +
      '</div>' +
      '<div class="ppr-manual">' +
        '<button class="btnCopy">COPY POST</button>' +
        '<input type="text" class="postUrlInput" placeholder="POST URL（任意・手動投稿後に入力）">' +
        '<button class="btnMarkPosted">MARK AS POSTED</button>' +
      '</div>' +
      '<div class="ppr-auto">' +
        '<select class="forceResultSelect">' +
          '<option value="">ランダム（テスト用・成功80%）</option>' +
          '<option value="SUCCESS">テスト：強制的に成功させる</option>' +
          '<option value="FAIL">テスト：強制的に失敗させる</option>' +
        '</select>' +
        '<button class="btnPostNow' + (isFailed ? ' warn' : '') + '">' + (isFailed ? 'RETRY' : 'POST NOW（モック）') + '</button>' +
        '<span class="ppr-result"></span>' +
      '</div>';

    row.querySelector('.modeSelect').addEventListener('change', function(e){
      var newMode = e.target.value;
      postJson({ action: 'saveContent', content: { content_id: c.content_id, product_id: c.product_id, platform: c.platform, posting_mode: newMode } });
      c.posting_mode = newMode;
    });
    row.querySelector('.btnCopy').addEventListener('click', function(){
      copyToClipboard(buildCopyText(c));
    });
    row.querySelector('.btnMarkPosted').addEventListener('click', function(){
      var btn = row.querySelector('.btnMarkPosted');
      var url = row.querySelector('.postUrlInput').value;
      btn.disabled = true;
      postJson({ action: 'markPosted', content_id: c.content_id, post_url: url }).then(function(){
        loadPublishQueue();
      }).catch(function(err){
        alert('更新に失敗しました: ' + err);
        btn.disabled = false;
      });
    });
    row.querySelector('.btnPostNow').addEventListener('click', function(){
      postNowForRow(c, row);
    });

    return row;
  }

  function postNowForRow(c, row){
    var btn = row.querySelector('.btnPostNow');
    var forceResult = row.querySelector('.forceResultSelect').value;
    var resultSpan = row.querySelector('.ppr-result');
    btn.disabled = true;
    resultSpan.textContent = '送信中...';
    return postJson({ action: 'postNow', content_id: c.content_id, platform: c.platform, force_result: forceResult || undefined }).then(function(result){
      if (result.ok) {
        resultSpan.textContent = '投稿成功';
        setTimeout(loadPublishQueue, 500);
      } else {
        resultSpan.textContent = '失敗: ' + (result.error || '不明なエラー');
        btn.textContent = 'RETRY';
        btn.classList.add('warn');
        btn.disabled = false;
      }
      return result;
    }).catch(function(err){
      resultSpan.textContent = '通信エラー: ' + err;
      btn.disabled = false;
      return { ok: false, error: String(err) };
    });
  }

  function openPostAllModal(p, contents){
    var overlay = document.createElement('div');
    overlay.className = 'confirm-overlay';
    var listHtml = contents.map(function(c){
      return '<li>' + escapeHtml(c.platform) + ' — ' + escapeHtml(c.status) + '</li>';
    }).join('');
    overlay.innerHTML =
      '<div class="confirm-box">' +
        '<div class="confirm-title">' + escapeHtml(p.brand || '') + ' ' + escapeHtml(p.product_name || '') + '<br>を対象プラットフォームへ一括投稿しますか？（モック）</div>' +
        '<ul class="confirm-list">' + listHtml + '</ul>' +
        '<div class="confirm-actions">' +
          '<button class="btnCancel">CANCEL</button>' +
          '<button class="btnConfirm primary">CONFIRM &amp; POST ALL</button>' +
        '</div>' +
        '<div class="confirm-results"></div>' +
      '</div>';
    document.body.appendChild(overlay);

    overlay.querySelector('.btnCancel').addEventListener('click', function(){ overlay.remove(); });
    overlay.addEventListener('click', function(e){ if (e.target === overlay) overlay.remove(); });

    overlay.querySelector('.btnConfirm').addEventListener('click', function(){
      var confirmBtn = overlay.querySelector('.btnConfirm');
      var cancelBtn = overlay.querySelector('.btnCancel');
      confirmBtn.disabled = true;
      cancelBtn.disabled = true;
      var resultsDiv = overlay.querySelector('.confirm-results');
      var chain = Promise.resolve();
      contents.forEach(function(c){
        chain = chain.then(function(){
          var line = document.createElement('div');
          line.textContent = c.platform + ': 送信中...';
          resultsDiv.appendChild(line);
          return postJson({ action: 'postNow', content_id: c.content_id, platform: c.platform }).then(function(result){
            line.textContent = c.platform + ': ' + (result.ok ? '成功' : '失敗（' + (result.error || '') + '）');
            return result;
          }).catch(function(err){
            line.textContent = c.platform + ': 通信エラー';
            return { ok: false };
          });
        });
      });
      chain.then(function(){
        var closeBtn = document.createElement('button');
        closeBtn.textContent = 'CLOSE';
        closeBtn.className = 'btnClose';
        closeBtn.addEventListener('click', function(){
          overlay.remove();
          loadPublishQueue();
        });
        resultsDiv.appendChild(closeBtn);
      });
    });
  }

  // ===== STAGE 3A: CONFIRMED PLANS & CONTENT MASTER =====

  var CONTENT_TYPES_3A = ['編集者セレクト','商品紹介','3選','5選','複数商品のセレクション','比較','季節特集','用途別','スタイル別','色別','素材別','ブランド特集','ギフト','テーマ特集'];

  document.getElementById('btnReloadPlans').addEventListener('click', function(){
    studioState.plansLoaded = false;
    loadConfirmedPlans();
  });

  function loadConfirmedPlans(){
    if (!LIVE){
      document.getElementById('confirmedPlansList').innerHTML = '<div class="studio-empty">config.js にAPI設定が必要です</div>';
      return;
    }
    document.getElementById('confirmedPlansList').innerHTML = '<div class="studio-empty">読み込み中...</div>';
    document.getElementById('plansStatus').textContent = '';
    fetch(apiUrl({ action: 'listPlans', status: 'ADOPTED' }))
      .then(function(r){ return r.json(); })
      .then(function(rows){
        studioState.plans = rows || [];
        studioState.plansLoaded = true;
        renderConfirmedPlans();
      })
      .catch(function(){
        document.getElementById('confirmedPlansList').innerHTML = '<div class="studio-empty">読み込み失敗</div>';
      });
  }

  function renderConfirmedPlans(){
    var container = document.getElementById('confirmedPlansList');
    if (!studioState.plans || studioState.plans.length === 0){
      container.innerHTML = '<div class="studio-empty">採用済み企画がありません。企画候補タブから企画を採用してください。</div>';
      return;
    }
    document.getElementById('plansStatus').textContent = studioState.plans.length + '件の確定企画';
    container.innerHTML = '';
    studioState.plans.forEach(function(plan){ container.appendChild(buildConfirmedPlanCard(plan)); });
  }

  function buildConfirmedPlanCard(plan){
    var pids = String(plan.product_ids || '').split(',').map(function(s){ return s.trim(); }).filter(Boolean);
    var card = document.createElement('div');
    card.className = 'confirmed-plan-card';
    card.innerHTML =
      '<div class="plan-card-header"><div class="plan-card-title">' + escapeHtml(plan.plan_title || '') + '</div></div>' +
      '<div class="plan-card-meta">' +
        (plan.plan_type ? '<span>' + escapeHtml(plan.plan_type) + '</span>' : '') +
        (plan.season ? '<span>' + escapeHtml(plan.season) + '</span>' : '') +
        '<span>' + pids.length + '点の商品</span>' +
        (plan.adopted_at ? '<span>' + escapeHtml(String(plan.adopted_at).slice(0,10)) + ' 採用</span>' : '') +
      '</div>' +
      (plan.theme ? '<div class="plan-card-reason">' + escapeHtml(plan.theme) + '</div>' : '');
    card.addEventListener('click', function(){ openConfirmedPlanDetail(plan, pids); });
    return card;
  }

  function openConfirmedPlanDetail(plan, pids){
    var ov = document.createElement('div');
    ov.className = 'plan-overlay';
    document.body.appendChild(ov);

    function fetchCMs(){
      return fetch(apiUrl({ action: 'listContentMasters', plan_id: plan.plan_id })).then(function(r){ return r.json(); });
    }

    function doOpen(prods, contentMasters){
      var pMap = {};
      (prods || []).forEach(function(p){ pMap[String(p.product_id)] = p; });

      function buildCmCardsHtml(){
        if (!contentMasters || contentMasters.length === 0)
          return '<div class="studio-empty" style="padding:10px 0;font-size:12px;">コンテンツマスターがありません</div>';
        return '<div class="cm-list">' + contentMasters.map(function(cm){
          var st = cm.status || 'PENDING';
          return '<div class="cm-card">' +
            '<div class="cm-card-info">' +
              '<div class="cm-card-type">' + escapeHtml(cm.content_type || '') + '</div>' +
              '<div class="cm-card-title">' + escapeHtml(cm.title || '（タイトル未生成）') + '</div>' +
            '</div>' +
            '<div class="cm-card-right">' +
              '<span class="s-badge ' + escapeAttr(st) + '">' + escapeHtml(st) + '</span>' +
              '<span class="cm-version">V' + escapeHtml(String(cm.version || 1)) + '</span>' +
              '<button class="cm-edit-btn" data-cmid="' + escapeAttr(cm.content_id) + '">編集</button>' +
            '</div>' +
          '</div>';
        }).join('') + '</div>';
      }

      function render(){
        ov.innerHTML =
          '<div class="plan-sheet">' +
            '<div class="plan-sheet-header">' +
              '<h2>' + escapeHtml(plan.plan_title || '') + '</h2>' +
              '<button class="plan-close" aria-label="close">×</button>' +
            '</div>' +
            '<div class="plan-sheet-body">' +
              '<div class="plan-detail-meta">' +
                escapeHtml(plan.plan_type || '') +
                (plan.season ? ' · ' + escapeHtml(plan.season) : '') +
                ' · ' + pids.length + '点' +
              '</div>' +
              (plan.reason ? '<div class="plan-detail-reason">' + escapeHtml(plan.reason) + '</div>' : '') +
              '<div class="plan-detail-prods">' +
                pids.map(function(pid){
                  var p = pMap[pid] || {};
                  return '<div class="plan-detail-prod">' + escapeHtml((p.brand || '') + (p.brand && p.product_name ? ' / ' : '') + (p.product_name || pid).slice(0,28)) + '</div>';
                }).join('') +
              '</div>' +
              '<div class="plan-section-head">コンテンツマスター</div>' +
              buildCmCardsHtml() +
              '<button class="cm-submit-btn" id="btnAddCm" style="margin-top:12px;">＋ コンテンツを作成</button>' +
              '<div id="cmCreateForm" hidden></div>' +
            '</div>' +
          '</div>';

        ov.querySelector('.plan-close').addEventListener('click', function(){ ov.remove(); });
        ov.addEventListener('click', function(e){ if (e.target === ov) ov.remove(); });

        ov.querySelectorAll('.cm-edit-btn').forEach(function(btn){
          btn.addEventListener('click', function(e){
            e.stopPropagation();
            var cmid = btn.dataset.cmid;
            var cm = null;
            for (var i = 0; i < contentMasters.length; i++){
              if (contentMasters[i].content_id === cmid){ cm = contentMasters[i]; break; }
            }
            if (!cm) return;
            openContentMasterEditor(cm, plan, pids, prods, function(updatedCm){
              var replaced = false;
              for (var i = 0; i < contentMasters.length; i++){
                if (contentMasters[i].content_id === updatedCm.content_id){ contentMasters[i] = updatedCm; replaced = true; break; }
              }
              if (!replaced) contentMasters.push(updatedCm);
              render();
            });
          });
        });

        document.getElementById('btnAddCm').addEventListener('click', function(){
          var formDiv = document.getElementById('cmCreateForm');
          if (!formDiv.hidden){ formDiv.hidden = true; return; }
          renderCmCreateForm(formDiv, prods, plan, pids, function(newCm){
            contentMasters.push(newCm);
            formDiv.hidden = true;
            render();
          });
          formDiv.hidden = false;
        });
      }

      render();
      requestAnimationFrame(function(){ ov.classList.add('open'); });
    }

    if (studioState.products && studioState.products.length > 0){
      fetchCMs().then(function(cms){ doOpen(studioState.products, cms || []); }).catch(function(){ doOpen(studioState.products, []); });
    } else {
      Promise.all([fetchSelected(), fetchCMs()]).then(function(results){
        studioState.products = results[0] || [];
        doOpen(studioState.products, results[1] || []);
      }).catch(function(){ doOpen([], []); });
    }
  }

  function renderCmCreateForm(formDiv, prods, plan, pids, onCreated){
    var pMap = {};
    (prods || []).forEach(function(p){ pMap[String(p.product_id)] = p; });
    formDiv.innerHTML =
      '<div class="cm-create-form">' +
        '<div class="cm-create-form-label">新しいコンテンツマスターを作成</div>' +
        '<div class="cm-row2">' +
          '<div class="cm-field"><label>コンテンツタイプ</label>' +
            '<select id="cf_type">' + CONTENT_TYPES_3A.map(function(t){ return '<option>' + escapeHtml(t) + '</option>'; }).join('') + '</select>' +
          '</div>' +
          '<div class="cm-field"><label>主役商品</label>' +
            '<select id="cf_main_pid">' + pids.map(function(pid){
              var p = pMap[pid] || {};
              return '<option value="' + escapeAttr(pid) + '">' + escapeHtml(((p.brand || '') + ' ' + (p.product_name || pid)).slice(0,32)) + '</option>';
            }).join('') + '</select>' +
          '</div>' +
        '</div>' +
        '<div class="cm-field"><label>編集テーマ</label><input type="text" id="cf_theme" placeholder="例：秋の通勤バッグ" value="' + escapeAttr(plan.theme || '') + '"></div>' +
        '<div class="cm-field"><label>編集理由</label><input type="text" id="cf_reason" placeholder="例：きちんと見えるが仕事専用になりすぎない" value="' + escapeAttr(plan.reason || '') + '"></div>' +
        '<div class="cm-row2">' +
          '<div class="cm-field"><label>想定読者</label><input type="text" id="cf_reader" placeholder="例：30代女性、シンプル志向"></div>' +
          '<div class="cm-field"><label>季節</label><input type="text" id="cf_season" value="' + escapeAttr(plan.season || '') + '" placeholder="例：秋冬"></div>' +
        '</div>' +
        '<div class="cm-row2">' +
          '<div class="cm-field"><label>トーン</label><input type="text" id="cf_tone" placeholder="例：上品・実用的"></div>' +
          '<div class="cm-field"><label>キーワード</label><input type="text" id="cf_keywords" placeholder="例：通勤,バッグ,秋"></div>' +
        '</div>' +
        '<div class="cm-field"><label>伝えるポイント</label><textarea id="cf_keypoints" placeholder="例：&#10;1. デザイン&#10;2. 実用性&#10;3. 季節との相性"></textarea></div>' +
        '<div class="cm-submit-row">' +
          '<button class="cm-submit-btn" id="cfSubmit">作成する</button>' +
          '<span class="cm-form-result" id="cfResult"></span>' +
        '</div>' +
      '</div>';

    document.getElementById('cfSubmit').addEventListener('click', function(){
      var btn = document.getElementById('cfSubmit');
      var result = document.getElementById('cfResult');
      var mainPid = document.getElementById('cf_main_pid').value;
      var relPids = pids.filter(function(p){ return p !== mainPid; });
      btn.disabled = true; result.textContent = '作成中...';
      postJson({
        action: 'createContentMaster',
        plan_id: plan.plan_id,
        content_type: document.getElementById('cf_type').value,
        main_product_id: mainPid,
        related_product_ids: relPids,
        editorial_theme: document.getElementById('cf_theme').value,
        editorial_reason: document.getElementById('cf_reason').value,
        target_reader: document.getElementById('cf_reader').value,
        season: document.getElementById('cf_season').value,
        tone: document.getElementById('cf_tone').value,
        keywords: document.getElementById('cf_keywords').value,
        key_points: document.getElementById('cf_keypoints').value,
      }).then(function(res){
        btn.disabled = false;
        if (res.ok){
          result.textContent = '作成しました: ' + res.content_id;
          // Build a stub to display immediately
          var stub = {
            content_id: res.content_id, plan_id: plan.plan_id,
            content_type: document.getElementById('cf_type').value,
            status: 'PENDING', version: 1, title: '',
          };
          if (onCreated) onCreated(stub);
        } else if (res.existing_id){
          result.textContent = '既存: ' + res.existing_id;
        } else {
          result.textContent = 'エラー: ' + (res.error || '不明');
        }
      }).catch(function(){ btn.disabled = false; result.textContent = '通信エラー'; });
    });
  }

  function openContentMasterEditor(cm, plan, pids, prods, onUpdate){
    var ov = document.createElement('div');
    ov.className = 'cm-overlay';
    document.body.appendChild(ov);
    var localCm = JSON.parse(JSON.stringify(cm));

    function sBadge(st){
      return '<span class="s-badge ' + escapeAttr(st || 'PENDING') + '">' + escapeHtml(st || 'PENDING') + '</span>';
    }

    function aiSection(){
      var st = localCm.status || 'PENDING';
      if (st === 'PENDING'){
        return '<div class="cm-ai-pending">' +
          '<p>AI下書きがまだ生成されていません。</p>' +
          '<button class="cm-gen-btn" id="cmGenBtn">AI下書きを生成</button>' +
          '<div class="cm-gen-note">生成には10〜30秒かかる場合があります</div>' +
        '</div>';
      }
      if (st === 'GENERATING'){
        return '<div class="cm-ai-pending"><p>生成中... しばらくお待ちください</p></div>';
      }
      if (st === 'ERROR'){
        return '<div class="cm-ai-pending">' +
          '<p style="color:#721c24;">生成に失敗しました: ' + escapeHtml(localCm.notes || '') + '</p>' +
          '<button class="cm-gen-btn" id="cmGenBtn">再生成する</button>' +
          '<div class="cm-gen-note">生成には10〜30秒かかる場合があります</div>' +
        '</div>';
      }
      var fields = [
        { key:'title',              label:'タイトル',           ml:false },
        { key:'subtitle',           label:'サブタイトル',        ml:false },
        { key:'lead',               label:'導入文',              ml:true  },
        { key:'editorial_note',     label:'編集者コメント',      ml:true  },
        { key:'body',               label:'本文',                ml:true  },
        { key:'generated_key_points',label:'キーポイント',       ml:true  },
        { key:'call_to_action',     label:'行動喚起（CTA）',    ml:false },
        { key:'instagram_caption',  label:'Instagram キャプション', ml:true },
        { key:'x_text',             label:'X 投稿文',            ml:true  },
        { key:'video_copy',         label:'動画コピー',          ml:false },
      ];
      return fields.map(function(f){
        var val = localCm[f.key] || '';
        var rows = f.ml ? Math.min(10, Math.max(2, Math.ceil(val.length / 55))) : undefined;
        return '<div class="cm-ai-block">' +
          '<div class="cm-ai-block-label">' + f.label + '</div>' +
          (f.ml
            ? '<textarea class="cm-edit-field" id="cmf_' + f.key + '" rows="' + rows + '">' + escapeHtml(val) + '</textarea>'
            : '<input type="text" class="cm-edit-field" id="cmf_' + f.key + '" value="' + escapeAttr(val) + '">') +
        '</div>';
      }).join('');
    }

    function actionBtns(){
      var st = localCm.status || 'PENDING';
      var html = '<button id="cmSaveBtn" class="cm-btn-primary">変更を保存</button>';
      if (st === 'DRAFT') html += '<button id="cmReviewBtn">確認待ちにする</button>';
      if (st === 'REVIEW') html += '<button id="cmApproveBtn">承認する</button>';
      return html + '<span class="cm-action-result" id="cmActionResult"></span>';
    }

    function render(){
      var st = localCm.status || 'PENDING';
      ov.innerHTML =
        '<div class="cm-sheet">' +
          '<div class="cm-sheet-header">' +
            '<div class="cm-sheet-hinfo">' +
              '<div class="cm-sheet-htype">' + escapeHtml(localCm.content_type || '') + ' · ' + escapeHtml(localCm.content_id || '') + '</div>' +
              '<div class="cm-sheet-htitle">' + escapeHtml(localCm.title || '（タイトル未生成）') + '</div>' +
            '</div>' +
            '<div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">' +
              sBadge(st) +
              '<span class="cm-version">V' + escapeHtml(String(localCm.version || 1)) + '</span>' +
              '<button class="cm-sheet-close" aria-label="close">×</button>' +
            '</div>' +
          '</div>' +
          '<div class="cm-sheet-body">' +
            '<div class="cm-edit-section-head">編集情報</div>' +
            '<div class="cm-row2">' +
              '<div class="cm-field"><label>編集テーマ</label><input type="text" id="cme_theme" value="' + escapeAttr(localCm.editorial_theme || '') + '" placeholder="例：秋の通勤バッグ"></div>' +
              '<div class="cm-field"><label>想定読者</label><input type="text" id="cme_reader" value="' + escapeAttr(localCm.target_reader || '') + '" placeholder="例：30代女性"></div>' +
            '</div>' +
            '<div class="cm-field"><label>編集理由</label><input type="text" id="cme_reason" value="' + escapeAttr(localCm.editorial_reason || '') + '"></div>' +
            '<div class="cm-row2">' +
              '<div class="cm-field"><label>季節</label><input type="text" id="cme_season" value="' + escapeAttr(localCm.season || '') + '"></div>' +
              '<div class="cm-field"><label>トーン</label><input type="text" id="cme_tone" value="' + escapeAttr(localCm.tone || '') + '"></div>' +
            '</div>' +
            '<div class="cm-field"><label>伝えるポイント</label><textarea id="cme_keypoints">' + escapeHtml(localCm.key_points || '') + '</textarea></div>' +
            '<div class="cm-edit-section-head">AI生成コンテンツ</div>' +
            aiSection() +
            '<div class="cm-edit-actions">' + actionBtns() + '</div>' +
          '</div>' +
        '</div>';

      ov.querySelector('.cm-sheet-close').addEventListener('click', function(){ ov.remove(); });
      ov.addEventListener('click', function(e){ if (e.target === ov) ov.remove(); });

      var genBtn = document.getElementById('cmGenBtn');
      if (genBtn){
        genBtn.addEventListener('click', function(){
          genBtn.disabled = true;
          genBtn.textContent = '生成中...';
          var note = ov.querySelector('.cm-gen-note');
          if (note) note.textContent = 'AIが下書きを生成しています（10〜30秒）';
          postJson({ action: 'generateAiDraft', content_id: localCm.content_id }).then(function(res){
            if (res.ok){
              // Re-fetch to get all AI fields
              fetch(apiUrl({ action: 'listContentMasters', plan_id: localCm.plan_id }))
                .then(function(r){ return r.json(); })
                .then(function(cms){
                  for (var i = 0; i < (cms || []).length; i++){
                    if (cms[i].content_id === localCm.content_id){ localCm = cms[i]; break; }
                  }
                  render();
                  if (onUpdate) onUpdate(localCm);
                });
            } else {
              genBtn.disabled = false;
              genBtn.textContent = '再生成する';
              var resultEl = document.getElementById('cmActionResult');
              if (resultEl) resultEl.textContent = 'エラー: ' + (res.error || '不明');
            }
          }).catch(function(){
            genBtn.disabled = false;
            genBtn.textContent = '再生成する';
          });
        });
      }

      function collectFields(){
        var f = {};
        ['editorial_theme','editorial_reason','target_reader','season','tone','key_points'].forEach(function(k){
          var el = document.getElementById('cme_' + (k === 'editorial_theme' ? 'theme' : k === 'editorial_reason' ? 'reason' : k === 'target_reader' ? 'reader' : k));
          if (!el) el = document.getElementById('cme_' + k);
          if (el) f[k] = el.value;
        });
        // remap IDs
        var idMap = { editorial_theme:'cme_theme', editorial_reason:'cme_reason', target_reader:'cme_reader', season:'cme_season', tone:'cme_tone', key_points:'cme_keypoints' };
        Object.keys(idMap).forEach(function(k){
          var el = document.getElementById(idMap[k]);
          if (el) f[k] = el.value;
        });
        ['title','subtitle','lead','editorial_note','body','generated_key_points','call_to_action','instagram_caption','x_text','video_copy'].forEach(function(k){
          var el = document.getElementById('cmf_' + k);
          if (el) f[k] = el.value;
        });
        return f;
      }

      var saveBtn = document.getElementById('cmSaveBtn');
      if (saveBtn){
        saveBtn.addEventListener('click', function(){
          var result = document.getElementById('cmActionResult');
          saveBtn.disabled = true; result.textContent = '保存中...';
          var fields = collectFields();
          postJson(Object.assign({ action: 'updateContentMaster', content_id: localCm.content_id }, fields))
            .then(function(res){
              saveBtn.disabled = false;
              if (res.ok){
                result.textContent = '保存しました';
                Object.assign(localCm, fields);
                if (fields.title !== undefined) localCm.title = fields.title;
                localCm.version = (parseInt(localCm.version) || 1) + 1;
                render();
                if (onUpdate) onUpdate(localCm);
              } else {
                result.textContent = 'エラー: ' + (res.error || '不明');
              }
            }).catch(function(){ saveBtn.disabled = false; result.textContent = '通信エラー'; });
        });
      }

      function doStatus(newSt){
        var result = document.getElementById('cmActionResult');
        result.textContent = '更新中...';
        postJson({ action: 'setContentMasterStatus', content_id: localCm.content_id, status: newSt })
          .then(function(res){
            if (res.ok){
              localCm.status = newSt;
              render();
              if (onUpdate) onUpdate(localCm);
            } else {
              result.textContent = 'エラー: ' + (res.error || '不明');
            }
          }).catch(function(){ result.textContent = '通信エラー'; });
      }

      var reviewBtn = document.getElementById('cmReviewBtn');
      if (reviewBtn) reviewBtn.addEventListener('click', function(){ doStatus('REVIEW'); });
      var approveBtn = document.getElementById('cmApproveBtn');
      if (approveBtn) approveBtn.addEventListener('click', function(){
        if (confirm('このコンテンツを承認しますか？')) doStatus('APPROVED');
      });
    }

    render();
    requestAnimationFrame(function(){ ov.classList.add('open'); });
  }

})();
</script>

</script>

<?php get_template_part('template-parts/footer-site'); ?>
<?php wp_footer(); ?>
</body>
</html>
