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

// ── Notification badge polling ────────────────────────────────
(function () {
    var badge = document.getElementById('notifBadge');
    if (!badge) return;

    function updateBadge() {
        fetch(APP_BASE + '/index.php?url=notifications/count')
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var n = data.count || 0;
                if (n > 0) {
                    badge.textContent    = n > 99 ? '99+' : n;
                    badge.style.display  = 'inline-flex';
                } else {
                    badge.style.display  = 'none';
                }
            })
            .catch(function () { /* silent — badge stays as-is on network error */ });
    }

    updateBadge();                    // Immediate check on page load
    setInterval(updateBadge, 30000);  // Poll every 30 seconds
})();

// ── Password visibility toggle ──────────────────────────────
(function () {
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.password-toggle');
        if (!btn) return;

        var input = document.getElementById(btn.dataset.target);
        if (!input) return;

        var show = input.type === 'password';

        input.type = show ? 'text' : 'password';
        btn.classList.toggle('is-visible', show);
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
})();
