<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$config = include __DIR__ . '/includes/config.php';
$currentUserRole = null;
if (isset($_SESSION['user']) && is_array($_SESSION['user']) && isset($_SESSION['user']['role'])) {
    $currentUserRole = $_SESSION['user']['role'];
}
// determine current page for active link highlighting
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
function link_class($page) {
    global $currentPage;
    return 'sidebar-link' . ($currentPage === $page ? ' active' : '');
}
?>
<aside class="sidebar" id="sidebar" role="navigation">
    <div class="sidebar-top d-flex align-items-center">
        <div class="brand d-flex align-items-center gap-2">
            <div class="brand-mark"><img src="<?php echo $config->base; ?>assets/img/its_logo.png" alt="ITS" style="width:40px;height:40px;object-fit:contain;border-radius:8px"></div>
            <div class="brand-name">ITS System</div>
        </div>
        <button id="mobileSidebarClose" class="btn btn-sm btn-outline-light d-none ms-auto" aria-label="Close sidebar" title="Close sidebar">×</button>
    </div>

    <nav class="sidebar-nav">
        <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="<?php echo link_class('dashboard.php'); ?>"><span class="icon"> 
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                        <path d="M3 11.5L12 4l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1V11.5z" stroke="currentColor" stroke-width="1.4" fill="none" stroke-linejoin="round" stroke-linecap="round"/>
                    </svg>
                </span><span class="label">Dashboard</span></a></li>
            <li>
                <a href="inventory.php" class="<?php echo link_class('inventory.php'); ?>"><span class="icon">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                        <path d="M21 16V8l-9-4-9 4v8l9 4 9-4z" stroke="currentColor" stroke-width="1.4" fill="none" stroke-linejoin="round" stroke-linecap="round"/>
                    </svg>
                </span><span class="label">Inventory</span></a>
            </li>
                <li><a href="tech_chat.php" class="<?php echo link_class('tech_chat.php'); ?>"><span class="icon">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                        <path d="M21 15a2 2 0 0 1-2 2H8l-5 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10z" stroke="currentColor" stroke-width="1.4" fill="none" stroke-linejoin="round" stroke-linecap="round"/>
                    </svg>
                </span><span class="label">Chat</span></a></li>
            <!-- EMC Reservation removed -->
            <?php if ($currentUserRole === 'admin'): ?>
            <li>
                <a href="accounts.php" class="<?php echo link_class('accounts.php'); ?>"><span class="icon">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                        <path d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10zM2 22a10 10 0 0 1 20 0" stroke="currentColor" stroke-width="1.4" fill="none" stroke-linejoin="round" stroke-linecap="round"/>
                    </svg>
                </span><span class="label">Accounts</span></a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <div style="display:flex;justify-content:center;margin-bottom:8px;">
            <button id="sidebarToggle" class="sidebar-toggle-btn" aria-label="Toggle sidebar" title="Toggle sidebar">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 6H20" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 12H20" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 18H20" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <span class="toggle-label">Collapse</span>
            </button>
        </div>
        <a href="logout.php" class="sidebar-link logout-link"><span class="icon">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                    <path d="M16 17l5-5-5-5" stroke="currentColor" stroke-width="1.6" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M21 12H9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M13 19H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h7" stroke="currentColor" stroke-width="1.6" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span><span class="label">Logout</span></a>
    </div>
</aside>
<!-- Mobile open button: visible when sidebar is hidden on small screens -->
<button id="mobileSidebarOpen" class="mobile-sidebar-toggle btn btn-primary d-none" aria-label="Open sidebar" title="Open sidebar">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 6H20" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 12H20" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 18H20" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
</button>
<div id="sidebarBackdrop" class="sidebar-backdrop d-none" aria-hidden="true"></div>

<script>
(function(){
    try{
    var path = '<?php echo $config->base; ?>assets/img/its_logo.png';
        

        // Sidebar collapse toggle: persist state in localStorage
        var sidebar = document.getElementById('sidebar');
        var toggle = document.getElementById('sidebarToggle');
        var key = 'its_sidebar_collapsed';
        function applyState() {
            try{
                var val = localStorage.getItem(key);
                if(val === '1') sidebar.classList.add('collapsed'); else sidebar.classList.remove('collapsed');
            } catch(e){}
        }
        function updateToggleLabel(){
            try{
                var label = document.querySelector('#sidebarToggle .toggle-label');
                if(!label) return;
                label.textContent = sidebar.classList.contains('collapsed') ? 'Expand' : 'Collapse';
            }catch(e){}
        }

        if(toggle && sidebar){
            toggle.addEventListener('click', function(){
                var isCollapsed = sidebar.classList.toggle('collapsed');
                try{ localStorage.setItem(key, isCollapsed ? '1':'0'); }catch(e){}
                updateToggleLabel();
                // ensure mobile overlay classes are cleared when toggling on wider screens
                try{ if(window.innerWidth > 768){ sidebar.classList.remove('mobile-hidden'); sidebar.classList.remove('mobile-open'); var mo = document.getElementById('mobileSidebarOpen'); if(mo) mo.classList.add('d-none'); var mc = document.getElementById('mobileSidebarClose'); if(mc) mc.classList.add('d-none'); } }catch(e){}
            });
            // apply saved state on load
            applyState();
            updateToggleLabel();
        }
        // Mobile-specific open/close controls (overlay behavior)
        var mobileOpen = document.getElementById('mobileSidebarOpen');
        var mobileClose = document.getElementById('mobileSidebarClose');
        function setMobileState(){
            try{
                if(window.innerWidth <= 768){
                    // entering mobile: remember desktop collapsed state, then remove collapsed
                    try{ sidebar.dataset.desktopCollapsed = sidebar.classList.contains('collapsed') ? '1':'0'; }catch(e){}
                    sidebar.classList.remove('collapsed');
                    // hide sidebar by default on small screens unless explicitly opened
                    if(!sidebar.classList.contains('mobile-open')){
                        sidebar.classList.add('mobile-hidden');
                        if(mobileOpen) mobileOpen.classList.remove('d-none');
                    } else {
                        // if the sidebar is already open, ensure the floating open button stays hidden
                        if(mobileOpen) mobileOpen.classList.add('d-none');
                    }
                } else {
                    // leaving mobile: restore desktop collapsed state from saved value or localStorage
                    try{
                        var saved = sidebar.dataset.desktopCollapsed;
                        if(typeof saved !== 'undefined'){
                            if(saved === '1') sidebar.classList.add('collapsed'); else sidebar.classList.remove('collapsed');
                        } else {
                            applyState();
                        }
                    }catch(e){ applyState(); }
                    sidebar.classList.remove('mobile-hidden');
                    sidebar.classList.remove('mobile-open');
                    if(mobileOpen) mobileOpen.classList.add('d-none');
                    if(mobileClose) mobileClose.classList.add('d-none');
                }
            }catch(e){}
        }
        if(mobileOpen || mobileClose){
            var backdrop = document.getElementById('sidebarBackdrop');
            function showMobileSidebar(){
                sidebar.classList.remove('mobile-hidden');
                sidebar.classList.add('mobile-open');
                if(mobileClose) mobileClose.classList.remove('d-none');
                if(mobileOpen) mobileOpen.classList.add('d-none');
                try{ document.body.classList.add('no-scroll'); }catch(e){}
                if(backdrop){ backdrop.classList.remove('d-none'); setTimeout(()=> backdrop.classList.add('visible'), 20); }
            }
            function hideMobileSidebar(){
                sidebar.classList.remove('mobile-open');
                sidebar.classList.add('mobile-hidden');
                if(mobileClose) mobileClose.classList.add('d-none');
                if(mobileOpen) mobileOpen.classList.remove('d-none');
                try{ document.body.classList.remove('no-scroll'); }catch(e){}
                if(backdrop){ backdrop.classList.remove('visible'); setTimeout(()=> backdrop.classList.add('d-none'), 240); }
            }

            if(mobileOpen){
                mobileOpen.addEventListener('click', function(){ showMobileSidebar(); });
            }
            if(mobileClose){
                mobileClose.addEventListener('click', function(){ hideMobileSidebar(); });
            }
            if(backdrop){
                backdrop.addEventListener('click', function(){ hideMobileSidebar(); });
            }

            // close sidebar when clicking a sidebar link on small screens
            document.querySelectorAll('.sidebar-link').forEach(function(a){
                a.addEventListener('click', function(){ if(window.innerWidth <= 768) hideMobileSidebar(); });
            });

            // escape key to close
            window.addEventListener('keydown', function(e){ if(e.key === 'Escape' && sidebar.classList.contains('mobile-open')) hideMobileSidebar(); });

            // adjust on resize
            window.addEventListener('resize', setMobileState);
            // clear any body lock when initializing
            try{ document.body.classList.remove('no-scroll'); }catch(e){}
            setMobileState();
        }
    }catch(e){ console.warn('favicon helper:', e); }
})();
</script>
<!-- Global notification overlay (visible across pages) -->
<div id="sessionNotif" class="notif-overlay notif-hidden" role="status" aria-live="polite">
    <div style="width:42px;height:42px;border-radius:6px;background:var(--accent-color);display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff">!</div>
    <div>
        <div class="n-title">New session</div>
        <div class="n-body" id="nBody">A new session was started</div>
    </div>
    <div class="n-actions">
        <button id="nOpen" class="btn btn-sm btn-light">Open</button>
        <button id="nDismiss" class="btn btn-sm btn-outline-light">Dismiss</button>
    </div>
</div>
<audio id="notifAudio" preload="auto">
    <source src="assets/sound/notif.mp3" type="audio/mpeg">
</audio>