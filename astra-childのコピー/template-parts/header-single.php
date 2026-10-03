<?php
$logo_id  = get_theme_mod('custom_logo');
$logo_url = '';
if ($logo_id) {
    $logo_src = wp_get_attachment_image_src($logo_id, 'full');
    if ($logo_src) $logo_url = $logo_src[0];
}
$greige_menus = wp_get_nav_menus();
?>
<style>
:root {
    --greige: #B8B2AC; --dark: #1a1a1a; --off: #F5F3EF;
    --gray: #999; --light: #E8E4DF; --gap: 36px; --rail: 56px;
    --nav-font: 'Avenir Next LT W01','Avenir Next','Montserrat',sans-serif;
}
#masthead, .ast-sticky-header, .ast-sticky-header-wrap, #ast-fixed-header,
.ast-above-header-wrap, .main-header-bar, .ast-primary-header-bar,
.ast-main-header, .site-header, #colophon, .site-footer {
    display: none !important; height: 0 !important;
    overflow: hidden !important; pointer-events: none !important;
}
.site, .ast-page-builder-template, #page, .entry-content,
.ast-container, .ast-flex, main, article {
    background: #fff !important; padding-top: 0 !important; margin-top: 0 !important;
}
/* ── NAV OVERLAY ─────────────────────── */
.g-nav-bg { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.45); z-index: 19000; }
.g-nav-bg.open, .g-nav-bg.is-open { display: block; }
.g-nav {
    position: fixed; top: 0; right: -320px; width: 320px; height: 100vh;
    background: #fff !important; z-index: 19001;
    transition: right 0.35s cubic-bezier(0.4,0,0.2,1);
    padding: 56px 28px 40px; display: flex; flex-direction: column; gap: 0;
    overflow-y: auto;
}
.g-nav.open, .g-nav.is-open { right: 0; }
.g-nav-close {
    position: absolute; top: 16px; right: 16px;
    background: transparent !important; border: none !important;
    box-shadow: none !important; outline: none !important;
    color: #1a1a1a !important; font-size: 21px;
    cursor: pointer; line-height: 1; padding: 0 !important;
    -webkit-appearance: none; appearance: none;
}
.g-nav-social { margin-top: 8px; padding-top: 24px; border-top: 1px solid #e8e4df; display: flex; gap: 16px; align-items: center; }
.g-nav-search { border-bottom-width: 0.5px !important; }
html body .g-nav button.g-nav-search-btn { background: #1a1a1a !important; color: #fff !important; border: 0 !important; box-shadow: none !important; }
html body .g-nav button.g-nav-search-btn:hover { opacity: 0.65 !important; }
body .g-nav-social a { display: flex !important; color: #1a1a1a !important; fill: #1a1a1a !important; transition: opacity 0.2s !important; }
body .g-nav-social a:hover { color: #1a1a1a !important; opacity: 0.45 !important; }
body .g-nav-social svg { width: 18px !important; height: 18px !important; fill: currentColor !important; }

/* ── MOBILE FIXED HEADER ─────────────── */
.gfx-header {
    position: fixed; top: 0; left: 0; right: 0; z-index: 9999;
    display: flex; justify-content: space-between; align-items: center;
    pointer-events: none;
    background: rgba(255,255,255,0.97);
    box-shadow: 0 1px 0 rgba(0,0,0,0.07);
    -webkit-transform: translateZ(0);
    transform: translateZ(0);
    -webkit-backface-visibility: hidden;
    backface-visibility: hidden;
}
.admin-bar .gfx-header { top: 32px; }
.gfx-logo {
    display: block; padding: 10px 0 10px 16px;
    pointer-events: all; flex: 1; min-width: 0;
}
.gfx-logo img { width: 110px; height: auto; }
button.gfx-menu-btn {
    background: transparent !important; border: none !important;
    box-shadow: none !important; cursor: pointer;
    padding: 14px 16px; display: flex; flex-direction: column; gap: 7px;
    pointer-events: all; flex-shrink: 0;
    -webkit-appearance: none; appearance: none;
    transition: opacity 0.2s;
}
button.gfx-menu-btn:hover { opacity: 0.45; }
button.gfx-menu-btn:focus, button.gfx-menu-btn:active {
    background: transparent !important; box-shadow: none !important; outline: none !important;
}
.gfx-menu-btn span { display: block; width: 25px; height: 2px; background: var(--dark); }

/* PC HEADER: モバイルでは非表示 */
.gv2-hbar { display: none; }

/* ── PC ≥768px ───────────────────────── */
@media (min-width: 768px) {
    .gfx-header { display: none; }
    .gv2-hbar {
        position: fixed; top: 0; left: 0; right: 0; z-index: 200;
        width: 100vw; height: var(--rail);
        display: flex; flex-direction: row; flex-wrap: nowrap; align-items: center;
        padding-left: var(--rail); padding-right: 0;
        background: #fff;
        border-bottom: 1px solid var(--light);
        pointer-events: all;
    }
    .admin-bar .gv2-hbar { top: 32px; }
    .gv2-hbar-logo { flex: 0 0 auto; }
    .gv2-hbar-logo img { width: 80px; height: auto; display: block; }
    .gv2-hbar-nav {
        flex: 1; min-width: 0;
        display: flex; align-items: center; justify-content: flex-end;
        padding-right: 0; overflow: hidden;
    }
    .gv2-hbar-nav ul, .gv2-hbar-nav .menu {
        display: flex !important; flex-direction: row !important;
        flex-wrap: nowrap !important; list-style: none !important;
        gap: 28px; align-items: center; margin: 0 !important; padding: 0 !important;
    }
    .gv2-hbar-nav ul li { margin: 0; padding: 0; }
    .gv2-hbar-nav ul li a {
        font-family: var(--nav-font);
        font-size: 12px; font-weight: 600; line-height: 1;
        letter-spacing: -0.02em; text-transform: uppercase;
        color: var(--dark); transition: opacity 0.2s;
        display: block; padding: 0; margin: 0; text-decoration: none;
    }
    .gv2-hbar-nav ul li a:hover { opacity: 0.45; }
    button.gv2-hbar-btn {
        position: static; flex: 0 0 67px;
        background: transparent !important; border: none !important;
        box-shadow: none !important; cursor: pointer;
        height: var(--rail);
        display: flex; flex-direction: column; gap: 8px;
        align-items: center; justify-content: center;
        -webkit-appearance: none; appearance: none;
        transition: opacity 0.2s; padding: 0;
    }
    button.gv2-hbar-btn:hover { opacity: 0.45; }
    button.gv2-hbar-btn:focus { outline: none; background: transparent !important; }
    .gv2-hbar-btn span { display: block; width: 24px; height: 2px; background: var(--dark); }
    .admin-bar .g-nav { top: 32px; height: calc(100vh - 32px); }
    .admin-bar .g-nav-bg { top: 32px; }
}
@media (min-width: 1024px) { .gv2-hbar-logo img { width: 94px; } }
@media screen and (max-width: 782px) {
    .admin-bar .gfx-header { top: 46px; }
    .admin-bar .g-nav { top: 46px; height: calc(100vh - 46px); }
    .admin-bar .g-nav-bg { top: 46px; }
}
</style>

<!-- ══ MOBILE FIXED HEADER ════════════ -->
<header class="gfx-header" id="gfxHeader">
    <a href="<?php echo home_url('/'); ?>" class="gfx-logo">
        <?php if ($logo_url): ?>
            <img src="<?php echo esc_url($logo_url); ?>" alt="<?php bloginfo('name'); ?>">
        <?php else: ?>
            <span style="font:700 36px/1 Helvetica,Arial,sans-serif;color:var(--dark);"><?php bloginfo('name'); ?></span>
        <?php endif; ?>
    </a>
    <button class="gfx-menu-btn js-menu-btn" aria-label="メニューを開く">
        <span></span><span></span>
    </button>
</header>

<!-- ══ PC STICKY HEADER ══════════════ -->
<header class="gv2-hbar" id="gv2Hbar">
    <a href="<?php echo home_url('/'); ?>" class="gv2-hbar-logo">
        <?php if ($logo_url): ?>
            <img src="<?php echo esc_url($logo_url); ?>" alt="<?php bloginfo('name'); ?>">
        <?php else: ?>
            <span style="font:700 20px/1 Helvetica,Arial,sans-serif;letter-spacing:-0.02em;color:var(--dark);"><?php bloginfo('name'); ?></span>
        <?php endif; ?>
    </a>
    <nav class="gv2-hbar-nav">
        <?php
        if (!empty($greige_menus)) {
            wp_nav_menu(array('menu' => $greige_menus[0], 'container' => false, 'fallback_cb' => false));
        }
        ?>
    </nav>
    <button class="gv2-hbar-btn js-menu-btn" aria-label="メニューを開く">
        <span></span><span></span>
    </button>
</header>
<script>
(function(){
    var nav      = document.getElementById('gNav');
    var navBg    = document.getElementById('gNavBg');
    var navClose = document.getElementById('gNavClose');
    var pcHdr = document.getElementById('gv2Hbar');

    function openNav()  { if(nav) nav.classList.add('open'); if(navBg) navBg.classList.add('open'); }
    function closeNav() { if(nav) nav.classList.remove('open'); if(navBg) navBg.classList.remove('open'); }

    document.querySelectorAll('.js-menu-btn').forEach(function(btn){
        btn.addEventListener('click', openNav);
    });
    if (navClose) navClose.addEventListener('click', closeNav);
    if (navBg)    navBg.addEventListener('click', closeNav);

    function onScroll() {
        if (pcHdr) pcHdr.classList.toggle('scrolled', window.scrollY > 10);
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    /* Astra スティッキーヘッダー強制非表示 */
    ['#ast-fixed-header','.ast-sticky-header','.ast-sticky-header-wrap','.main-header-bar','#masthead'].forEach(function(sel){
        document.querySelectorAll(sel).forEach(function(el){
            function suppress(){ el.style.setProperty('display','none','important'); el.style.setProperty('height','0','important'); }
            suppress();
            new MutationObserver(suppress).observe(el,{attributes:true,attributeFilter:['style','class']});
        });
    });
    document.body.style.setProperty('padding-top','0','important');
    document.body.style.setProperty('margin-top','0','important');

})();
</script>
