(() => {
  const paths = {
    'bars-3':'<path d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>',
    'x-mark':'<path d="M6 18 18 6M6 6l12 12"/>',
    'home':'<path d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75V21h6.75v-6.75h1.5V21h6.75V9.75"/>',
    'building-office-2':'<path d="M3.75 21V5.25A2.25 2.25 0 0 1 6 3h7.5a2.25 2.25 0 0 1 2.25 2.25V21M3 21h18M8.25 6.75h3M8.25 10.5h3M8.25 14.25h3M15.75 9.75H18A2.25 2.25 0 0 1 20.25 12v9"/>',
    'users':'<path d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72M18 18.72c0-1.217-.436-2.33-1.16-3.196M18 18.72A9.06 9.06 0 0 1 12 21c-2.307 0-4.413-.862-6-2.28M15 11.25a3 3 0 1 0-6 0 3 3 0 0 0 6 0ZM6.941 15.52A3 3 0 0 0 2.26 18.24 9.094 9.094 0 0 0 6 18.72"/>',
    'user':'<path d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 21a7.5 7.5 0 0 1 15 0"/>',
    'cube':'<path d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9L12 21.75m0-9L3 7.5m9 5.25v9M3 7.5v9l9 5.25"/>',
    'clipboard-document-list':'<path d="M9 5.25H6.375A2.625 2.625 0 0 0 3.75 7.875v10.5A2.625 2.625 0 0 0 6.375 21h11.25a2.625 2.625 0 0 0 2.625-2.625V7.875a2.625 2.625 0 0 0-2.625-2.625H15M9 5.25a3 3 0 0 1 6 0M9 5.25V6h6v-.75M8.25 12h7.5M8.25 15.75h7.5"/>',
    'wallet':'<path d="M21 12.75v6A2.25 2.25 0 0 1 18.75 21H5.25A2.25 2.25 0 0 1 3 18.75V6.75A2.25 2.25 0 0 1 5.25 4.5H18a1.5 1.5 0 0 1 0 3H6.75A1.5 1.5 0 0 0 5.25 9a1.5 1.5 0 0 0 1.5 1.5h12A2.25 2.25 0 0 1 21 12.75Zm-4.5 2.25h.008v.008H16.5V15Z"/>',
    'document-chart-bar':'<path d="M14.25 2.25H6.375A2.625 2.625 0 0 0 3.75 4.875v14.25a2.625 2.625 0 0 0 2.625 2.625h11.25a2.625 2.625 0 0 0 2.625-2.625V8.25l-6-6Zm0 0V8.25h6M8.25 17.25v-3M12 17.25V12M15.75 17.25v-6.75"/>',
    'chart-bar':'<path d="M3 3v18h18M7.5 16.5V12M12 16.5v-9M16.5 16.5V5.25"/>',
    'shield-check':'<path d="M9 12.75 11.25 15 15 9.75M12 2.25c2.22 1.5 4.91 2.355 7.5 2.625v5.25c0 5.372-3.446 9.687-7.5 11.625-4.054-1.938-7.5-6.253-7.5-11.625v-5.25C7.09 4.605 9.78 3.75 12 2.25Z"/>',
    'clock':'<path d="M12 6v6h4.5M21 12a9 9 0 1 1-9-9 9 9 0 0 1 9 9Z"/>',
    'magnifying-glass':'<path d="m21 21-4.35-4.35m0 0A7.5 7.5 0 1 0 6.045 6.045 7.5 7.5 0 0 0 16.65 16.65Z"/>',
    'bell':'<path d="M14.857 17.082a23.85 23.85 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.311 6.022 23.85 23.85 0 0 0 5.454 1.31M14.857 17.082a3 3 0 0 1-5.714 0"/>',
    'chevron-down':'<path d="m19.5 8.25-7.5 7.5-7.5-7.5"/>',
    'chevron-right':'<path d="m9 18 6-6-6-6"/>',
    'funnel':'<path d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v.954c0 .742-.294 1.454-.819 1.979L14.5 13.39V19.5l-5 2v-8.11L3.819 7.707A2.798 2.798 0 0 1 3 5.728v-.954c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z"/>',
    'arrow-down-tray':'<path d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M7.5 10.5 12 15m0 0 4.5-4.5M12 15V3"/>',
    'lock-closed':'<path d="M16.5 10.5V6.75a4.5 4.5 0 0 0-9 0v3.75m-.75 0h10.5A2.25 2.25 0 0 1 19.5 12.75v6A2.25 2.25 0 0 1 17.25 21H6.75A2.25 2.25 0 0 1 4.5 18.75v-6a2.25 2.25 0 0 1 2.25-2.25Z"/>',
    'eye':'<path d="M2.25 12S5.25 5.25 12 5.25 21.75 12 21.75 12 18.75 18.75 12 18.75 2.25 12 2.25 12Z"/><path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>',
    'eye-slash':'<path d="M3 3l18 18M10.6 10.6A2 2 0 0 0 13.4 13.4M6.2 6.2C3.5 8.15 2.25 12 2.25 12S5.25 18.75 12 18.75c1.69 0 3.17-.42 4.43-1.05M9.7 5.55A9.8 9.8 0 0 1 12 5.25C18.75 5.25 21.75 12 21.75 12a14.08 14.08 0 0 1-2.1 3.2"/>',
    'arrow-trending-up':'<path d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518L21.75 9m0 0v6.75M21.75 9H15"/>',
    'arrow-trending-down':'<path d="M2.25 6 9 12.75l4.306-4.306a11.95 11.95 0 0 0 5.814 5.518L21.75 15m0 0V8.25M21.75 15H15"/>',
    'check-circle':'<path d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-9-9 9 9 0 0 1 9 9Z"/>'
  };

  function iconSvg(name, className='icon') {
    return `<svg class="${className}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${paths[name] || paths['home']}</svg>`;
  }

  function renderIcons(scope=document) {
    scope.querySelectorAll('[data-icon]').forEach((el) => {
      const name = el.dataset.icon;
      const size = el.dataset.iconSize || '';
      el.innerHTML = iconSvg(name, `icon ${size}`.trim());
    });
  }

  window.KPIIcons = { renderIcons, iconSvg };
  document.addEventListener('DOMContentLoaded', () => {
    renderIcons();

    // Password show/hide
    const password = document.querySelector('#password');
    const toggle = document.querySelector('#togglePassword');
    if (password && toggle) {
      toggle.addEventListener('click', () => {
        const showing = password.type === 'text';
        password.type = showing ? 'password' : 'text';
        toggle.dataset.icon = showing ? 'eye' : 'eye-slash';
        toggle.setAttribute('aria-label', showing ? 'Tampilkan password' : 'Sembunyikan password');
        toggle.innerHTML = iconSvg(showing ? 'eye' : 'eye-slash');
      });
    }

    // Login form UI demo only; no password is stored.
    const form = document.querySelector('#loginForm');
    if (form) {
      form.addEventListener('submit', (event) => {
        event.preventDefault();
        const username = form.querySelector('#username');
        const passwordInput = form.querySelector('#password');
        const alert = form.querySelector('#formAlert');
        const button = form.querySelector('button[type="submit"]');
        let valid = true;
        [username, passwordInput].forEach((input) => {
          const group = input.closest('.field-group');
          const error = group.querySelector('.field-error');
          if (!input.value.trim()) {
            valid = false;
            group.classList.add('has-error');
            error.hidden = false;
          } else {
            group.classList.remove('has-error');
            error.hidden = true;
          }
        });
        if (!valid) {
          alert.textContent = 'Mohon lengkapi data yang wajib diisi.';
          alert.hidden = false;
          return;
        }
        alert.hidden = true;
        const old = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<span class="spinner" aria-hidden="true"></span><span>Memproses...</span>';
        setTimeout(() => {
          sessionStorage.setItem('kpi-demo-session', '1');
          window.location.href = 'dashboard.html';
          button.disabled = false;
          button.innerHTML = old;
        }, 550);
      });
    }

    // Dashboard sidebar - collapsible on desktop, drawer on mobile.
    const shell = document.querySelector('.admin-shell');
    const sidebar = document.querySelector('#sidebar');
    const overlay = document.querySelector('#sidebarOverlay');
    const toggles = document.querySelectorAll('[data-sidebar-toggle]');
    const closeSidebar = () => {
      document.body.classList.remove('sidebar-mobile-open');
      toggles.forEach((t) => t.setAttribute('aria-expanded', 'false'));
    };
    if (shell && sidebar) {
      const saved = localStorage.getItem('kpi-sidebar-collapsed') === '1';
      if (saved && window.innerWidth >= 1024) shell.classList.add('sidebar-collapsed');
      toggles.forEach((toggleButton) => {
        toggleButton.addEventListener('click', () => {
          if (window.innerWidth < 1024) {
            const open = !document.body.classList.contains('sidebar-mobile-open');
            document.body.classList.toggle('sidebar-mobile-open', open);
            toggles.forEach((t) => t.setAttribute('aria-expanded', String(open)));
          } else {
            shell.classList.toggle('sidebar-collapsed');
            localStorage.setItem('kpi-sidebar-collapsed', shell.classList.contains('sidebar-collapsed') ? '1' : '0');
          }
        });
      });
      overlay?.addEventListener('click', closeSidebar);
      window.addEventListener('resize', () => {
        if (window.innerWidth >= 1024) closeSidebar();
      });
      document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeSidebar(); });
    }

    // Lightweight dropdown behavior.
    document.querySelectorAll('[data-dropdown-button]').forEach((button) => {
      const target = document.getElementById(button.dataset.dropdownButton);
      if (!target) return;
      button.addEventListener('click', (e) => {
        e.stopPropagation();
        const isOpen = !target.hidden;
        document.querySelectorAll('.dropdown-menu').forEach((menu) => menu.hidden = true);
        target.hidden = isOpen;
      });
    });
    document.addEventListener('click', () => document.querySelectorAll('.dropdown-menu').forEach((menu) => menu.hidden = true));

    // Demo filter drawer.
    const filterBtn = document.querySelector('#filterButton');
    const filterPanel = document.querySelector('#filterPanel');
    const filterClose = document.querySelector('#filterClose');
    if (filterBtn && filterPanel) {
      filterBtn.addEventListener('click', () => filterPanel.classList.add('is-open'));
      filterClose?.addEventListener('click', () => filterPanel.classList.remove('is-open'));
    }
  });
})();
