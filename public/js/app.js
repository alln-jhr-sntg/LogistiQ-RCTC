// ── Sidebar toggle ───────────────────────────────────────────
(function () {
    var shell     = document.querySelector('.app-shell');
    var overlay   = document.getElementById('sidebarOverlay');
    var toggleBtn = document.getElementById('sidebarOpen');
    var closeBtn  = document.getElementById('sidebarClose');

    if (!shell) return;

    var isMobile = function () { return window.innerWidth <= 768; };

    // Restore desktop collapsed state
    if (!isMobile() && localStorage.getItem('sidebarCollapsed') === '1') {
        shell.classList.add('sidebar-collapsed');
    }

    function toggle() {
        if (isMobile()) {
            var open = shell.classList.toggle('mobile-open');
            document.body.style.overflow = open ? 'hidden' : '';
        } else {
            var collapsed = shell.classList.toggle('sidebar-collapsed');
            localStorage.setItem('sidebarCollapsed', collapsed ? '1' : '0');
        }
    }

    function closeMobile() {
        shell.classList.remove('mobile-open');
        document.body.style.overflow = '';
    }

    toggleBtn && toggleBtn.addEventListener('click', toggle);
    closeBtn  && closeBtn.addEventListener('click', closeMobile);
    overlay   && overlay.addEventListener('click', closeMobile);

    window.addEventListener('resize', function () {
        if (!isMobile()) {
            shell.classList.remove('mobile-open');
            document.body.style.overflow = '';
        }
    });
})();
