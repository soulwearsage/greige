<?php
$greige_menus = wp_get_nav_menus();
?>
<div class="g-nav-bg" id="gNavBg"></div>
<nav class="g-nav" id="gNav" aria-label="メインナビゲーション">
    <button class="g-nav-close" id="gNavClose" aria-label="閉じる">×</button>
    <?php
    if (!empty($greige_menus)) {
        wp_nav_menu(array('menu' => $greige_menus[0], 'container' => false, 'fallback_cb' => false));
    }
    if (is_user_logged_in()): ?>
    <a href="<?php echo esc_url(admin_url('admin.php?page=greige-product-board')); ?>" class="g-nav-board-link">CURATION</a>
    <?php endif; ?>
    <div class="g-nav-social">
        <a href="#" aria-label="Instagram">
            <svg viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
        </a>
        <a href="#" aria-label="Pinterest">
            <svg viewBox="0 0 24 24"><path d="M12 0C5.373 0 0 5.373 0 12c0 5.084 3.163 9.426 7.627 11.174-.105-.949-.2-2.405.042-3.441.218-.937 1.407-5.965 1.407-5.965s-.359-.719-.359-1.782c0-1.668.967-2.914 2.171-2.914 1.023 0 1.518.769 1.518 1.69 0 1.029-.655 2.568-.994 3.995-.283 1.194.599 2.169 1.777 2.169 2.133 0 3.772-2.249 3.772-5.495 0-2.873-2.064-4.882-5.012-4.882-3.414 0-5.418 2.561-5.418 5.207 0 1.031.397 2.138.893 2.738a.36.36 0 0 1 .083.345l-.333 1.36c-.053.22-.174.267-.402.161-1.499-.698-2.436-2.889-2.436-4.649 0-3.785 2.75-7.262 7.929-7.262 4.163 0 7.398 2.967 7.398 6.931 0 4.136-2.607 7.464-6.227 7.464-1.216 0-2.359-.632-2.75-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0z"/></svg>
        </a>
        <a href="#" aria-label="YouTube">
            <svg viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
        </a>
        <a href="#" aria-label="TikTok">
            <svg viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
        </a>
        <a href="#" aria-label="X">
            <svg viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.748l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
        </a>
        <a href="#" aria-label="Threads">
            <svg viewBox="0 0 24 24"><path d="M18.263 11.097c-.03-3.486-1.92-5.586-5.111-5.586-2.13 0-3.922.963-4.863 2.499l2.062 1.438c.535-.843 1.272-1.543 2.628-1.543 1.528 0 2.318.85 2.544 2.431a15 15 0 0 0-2.236-.173c-4.125 0-6.068 1.867-6.068 4.336s1.943 3.99 4.804 3.99c3.139 0 5.013-2.115 5.781-4.735.798.361 1.348 1.204 1.348 2.47 0 3.387-3.907 5.232-7.22 5.232-4.885 0-8.077-3.207-8.077-8.424 0-6.392 4.223-10.487 9.9-10.487 3.808 0 5.69 1.671 6.97 3.914l2.108-1.475C21.44 2.078 18.331 0 13.663 0 6.227 0 1.168 5.277 1.168 12.934c0 7 4.953 11.066 10.856 11.066 4.878 0 9.809-2.846 9.809-7.716 0-2.545-1.46-4.231-3.569-5.187m-6.33 4.855c-1.077 0-2.026-.512-2.026-1.453 0-1.483 1.822-1.934 3.606-1.934.678 0 1.34.045 1.927.173-.422 1.927-1.671 3.215-3.508 3.214Z"/></svg>
        </a>
        <?php $wp_link = is_user_logged_in() ? admin_url() : wp_login_url(home_url('/')); ?>
        <a href="<?php echo esc_url($wp_link); ?>" aria-label="管理画面" class="g-nav-wp-link">
            <svg viewBox="0 0 24 24"><path d="M20 3H4c-1.1 0-2 .9-2 2v11c0 1.1.9 2 2 2h7v2H8v2h8v-2h-3v-2h7c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 13H4V5h16v11z"/></svg>
        </a>
    </div>
    <form class="g-nav-search" role="search" action="<?php echo esc_url(home_url('/')); ?>" method="get">
        <input type="search" name="s" class="g-nav-search-input" placeholder="" autocomplete="off">
        <button type="submit" class="g-nav-search-btn">SEARCH</button>
    </form>

    <div class="g-nav-moon">
        <div class="g-nav-moon-header">
            <span class="g-nav-moon-title">MOON</span>
            <span class="g-nav-moon-ym" id="gNavMoonYM"></span>
        </div>
        <div class="g-nav-moon-phases" id="gNavMoonPhases"></div>
    </div>
</nav>

<script>
(function() {
    var REF   = 946137240000;
    var CYCLE = 29.530589 * 86400000;
    function pad(n) { return String(n).padStart(2, '0'); }
    function fmtDate(ms) { var d = new Date(ms); return pad(d.getMonth()+1) + '.' + pad(d.getDate()); }
    function moonSVG(type) {
        var s = '<circle cx="10" cy="10" r="8.5" fill="none" stroke="#1a1a1a" stroke-width="1.5"/>';
        var b = { new: '<circle cx="10" cy="10" r="8.5" fill="#1a1a1a"/>', first: s+'<path d="M10,1.5 A8.5,8.5 0 0,1 10,18.5 Z" fill="#1a1a1a"/>', full: s, last: s+'<path d="M10,1.5 A8.5,8.5 0 0,0 10,18.5 Z" fill="#1a1a1a"/>' };
        return '<svg viewBox="0 0 20 20" width="26" height="26" xmlns="http://www.w3.org/2000/svg">'+(b[type]||s)+'</svg>';
    }
    var now = Date.now();
    var lastNew = REF + Math.floor((now - REF) / CYCLE) * CYCLE;
    var phases = [
        { ms: lastNew,              type:'new',   l1:'NEW',   l2:'MOON'    },
        { ms: lastNew+CYCLE*0.25,   type:'first', l1:'FIRST', l2:'QUARTER' },
        { ms: lastNew+CYCLE*0.5,    type:'full',  l1:'FULL',  l2:'MOON'    },
        { ms: lastNew+CYCLE*0.75,   type:'last',  l1:'LAST',  l2:'QUARTER' },
        { ms: lastNew+CYCLE,        type:'new',   l1:'NEW',   l2:'MOON'    },
    ];
    var MN = ['JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'];
    var d = new Date();
    var ym = document.getElementById('gNavMoonYM');
    if (ym) ym.textContent = d.getFullYear() + '年' + (d.getMonth()+1) + '月' + d.getDate() + '日';
    var cont = document.getElementById('gNavMoonPhases');
    if (!cont) return;
    cont.innerHTML = phases.map(function(p) {
        return '<div class="g-nav-moon-phase">'+moonSVG(p.type)+'<span class="g-nav-moon-date">'+fmtDate(p.ms)+'</span><span class="g-nav-moon-label">'+p.l1+'<br>'+p.l2+'</span></div>';
    }).join('');
})();
</script>
