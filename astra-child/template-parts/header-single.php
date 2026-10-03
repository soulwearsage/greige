<?php
$logo_id  = get_theme_mod('custom_logo');
$logo_url = '';
if ($logo_id) {
    $logo_src = wp_get_attachment_image_src($logo_id, 'full');
    if ($logo_src) $logo_url = $logo_src[0];
}
$greige_menus = wp_get_nav_menus();
?>

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
