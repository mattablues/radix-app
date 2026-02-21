import Alpine from '@alpinejs/csp'
import Collapse from "@alpinejs/collapse";
import Ajax from "@imacrayon/alpine-ajax";
import Ui from "@alpinejs/ui";
import Focus from "@alpinejs/focus";
import { addTableAria } from "./addTableAria";
import SearchProfiles from './search-profiles';
import SearchSystemEvents from './search-system-events';
import SearchSystemUpdates from './search-system-updates';
import SearchUsers from './search-users';
import SearchDeletedUsers from './search-deleted-users';

window.Alpine = Alpine;
Alpine.plugin(Collapse);
Alpine.plugin(Ui);
Alpine.plugin(Focus);
Alpine.plugin(Ajax);

Alpine.data('cookieConsent', () => ({
  showCookieBanner: false,

  init() {
    try {
      this.showCookieBanner = window.localStorage.getItem('cookies_choice') === null
    } catch (e) {
      // Om localStorage är blockat (privat läge, policy, etc) — visa bannern ändå.
      this.showCookieBanner = true
    }
  },

  accept() {
    try { window.localStorage.setItem('cookies_choice', 'accepted') } catch (e) {}
    this.showCookieBanner = false
  },

  reject() {
    try { window.localStorage.setItem('cookies_choice', 'rejected') } catch (e) {}
    this.showCookieBanner = false
  },
}));

Alpine.store('sidebar', {
  open: false,
  close() { this.open = false },
  openIt() { this.open = true },
  toggle() { this.open = !this.open },
});

Alpine.store('modals', {
  closeAccount: false,
  deleteAccount: false,

  openCloseAccount() { this.closeAccount = true },
  closeCloseAccount() { this.closeAccount = false },

  openDeleteAccount() { this.deleteAccount = true },
  closeDeleteAccount() { this.deleteAccount = false },
});

Alpine.data('modalFocusRestore', (refName) => ({
  restoreFocusEl: null,

  onToggle(isOpen) {
    if (isOpen) {
      if (!this.restoreFocusEl) this.restoreFocusEl = document.activeElement;

      // Fokusera ett element i modalen om det finns
      this.$nextTick(() => {
        const el = this.$refs && this.$refs[refName] ? this.$refs[refName] : null;
        if (el && typeof el.focus === 'function') el.focus();
      });
      return;
    }

    const el = this.restoreFocusEl;
    this.restoreFocusEl = null;

    if (el && typeof el.focus === 'function') {
      this.$nextTick(() => { el.focus(); });
    }
  },
}));

Alpine.data('apiTokenCopy', (token) => ({
  copied: false,

  async copy() {
    const value = String(token || '');
    if (!value) return;

    try {
      if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
        await navigator.clipboard.writeText(value);
      } else {
        const el = document.createElement('textarea');
        el.value = value;
        el.setAttribute('readonly', '');
        el.style.position = 'absolute';
        el.style.left = '-9999px';
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
      }

      this.copied = true;
      window.setTimeout(() => { this.copied = false; }, 2000);
    } catch (e) {
      this.copied = false;
    }
  },

  confirmReplace() {
    return window.confirm('Vill du verkligen generera en ny nyckel? Den gamla kommer sluta fungera direkt.');
  },

  replace(form) {
    if (!form || typeof form.requestSubmit !== 'function') {
      if (form && typeof form.submit === 'function' && this.confirmReplace()) form.submit();
      return;
    }

    if (this.confirmReplace()) {
      form.requestSubmit();
    }
  },
}));

Alpine.data('sidebarDropdown', (initialOpen = false) => ({
  open: !!initialOpen,
  toggle() { this.open = !this.open },
}));

Alpine.data('systemDropdown', (initialOpen = false) => ({
  open: !!initialOpen,
  toggle() { this.open = !this.open },
}));

Alpine.data('flashMessage', () => ({
  show: true,

  init() {
    window.setTimeout(() => { this.show = false; }, 5000);
  },
}));

Alpine.start();

document.addEventListener('click', (e) => {
  const btn = e.target.closest('[data-confirm-submit]');
  if (!btn) return;

  const msg = btn.getAttribute('data-confirm-submit') || '';
  if (!msg) return;

  const ok = window.confirm(msg);
  if (!ok) {
    e.preventDefault();
    e.stopPropagation();
  }
}, true);

// Kör dina funktioner
addTableAria();

document.addEventListener('DOMContentLoaded', () => {
    const mainContent = document.querySelector('main');

    const searchProfileInput = document.getElementById('search-profiles');

    if (searchProfileInput && mainContent) {
        new SearchProfiles('search-profiles', 'main');
    }

    // ---------- Header-sök (robust / valfri) ----------
    const btn = document.getElementById('search-toggle');
    const wrap = document.getElementById('search-wrap');

    if (btn && wrap) {
        const open = () => {
            wrap.classList.remove('hidden');
            wrap.style.position = 'absolute';
            wrap.style.left = '0';
            wrap.style.right = '0';
            wrap.style.top = '100%';
            wrap.style.marginTop = '0.5rem';
            wrap.style.zIndex = '70';

            setTimeout(() => {
                if (searchProfileInput) searchProfileInput.focus();
            }, 0);
        };

        const close = () => {
            wrap.classList.add('hidden');
            wrap.removeAttribute('style');

            if (searchProfileInput) searchProfileInput.value = '';

            const dropdown = document.getElementById('search-dropdown');

            if (dropdown) {
                const resultContainer = dropdown.querySelector('.result-container');
                if (resultContainer) resultContainer.innerHTML = '';
                dropdown.classList.add('hidden');
            }
        };

        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            if (wrap.classList.contains('hidden')) open(); else close();
        });

        document.addEventListener('click', (e) => {
            if (!wrap.contains(e.target) && !btn.contains(e.target)) close();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') close();
        });
    }
    // ---------- /Header-sök ----------

    // ---------- Tabell-sök (robust / destroy / guards) ----------
    window.__tableSearchInstances = window.__tableSearchInstances || {};

    const getPageId = () =>
        document.querySelector('[data-page-id]')?.getAttribute('data-page-id')
        || (document.body ? document.body.id : null);

    const exists = (id) => !!document.getElementById(id);

    const getEndpointFromForm = (formId, fallback) => {
        const form = document.getElementById(formId);
        const endpoint = form ? (form.getAttribute('data-search-endpoint') || '') : '';
        return endpoint || fallback;
    };

    const getInitialState = () => {
        const params = new URLSearchParams(window.location.search);
        return {
            initialTerm: (params.get('q') || '').trim(),
            initialPage: parseInt(params.get('page') || '1', 10) || 1
        };
    };

    const initTableSearch = (key, requiredIds, createInstance) => {
        const ok = requiredIds.every(exists);
        if (!ok) return null;

        const prev = window.__tableSearchInstances[key];
        if (prev && typeof prev.destroy === 'function') {
            prev.destroy();
        }

        const instance = createInstance();
        window.__tableSearchInstances[key] = instance;
        return instance;
    };

    const pageId = getPageId();

    if (pageId === 'admin-events-index') {
        const { initialTerm, initialPage } = getInitialState();
        const endpoint = getEndpointFromForm('system-events-search-form', '/api/v1/search/system-events');

        initTableSearch(
            'systemEvents',
            ['system-events-search-form', 'system-events-search', 'system-events-tbody'],
            () => new SearchSystemEvents({
                formId: 'system-events-search-form',
                clearBtnId: 'system-events-clear',
                inputId: 'system-events-search',
                tbodyId: 'system-events-tbody',
                pagerId: 'system-events-pager',
                endpoint,
                routeBase: '/admin/events',
                perPage: 20,
                initialTerm,
                initialPage
            })
        );
    }

    if (pageId === 'admin-updates-index') {
        const { initialTerm, initialPage } = getInitialState();
        const endpoint = getEndpointFromForm('system-updates-search-form', '/api/v1/search/system-updates');

        initTableSearch(
            'systemUpdates',
            ['system-updates-search-form', 'system-updates-search', 'system-updates-tbody'],
            () => new SearchSystemUpdates({
                formId: 'system-updates-search-form',
                clearBtnId: 'system-updates-clear',
                inputId: 'system-updates-search',
                tbodyId: 'system-updates-tbody',
                pagerId: 'system-updates-pager',
                endpoint,
                routeBase: '/admin/updates',
                perPage: 10,
                initialTerm,
                initialPage
            })
        );
    }

    if (pageId === 'admin-users-index') {
        const { initialTerm, initialPage } = getInitialState();
        const endpoint = getEndpointFromForm('users-search-form', '/api/v1/search/users');

        initTableSearch(
            'users',
            ['users-search-form', 'users-search', 'users-tbody'],
            () => new SearchUsers({
                formId: 'users-search-form',
                clearBtnId: 'users-clear',
                inputId: 'users-search',
                tbodyId: 'users-tbody',
                pagerId: 'users-pager',
                endpoint,
                routeBase: '/admin/users',
                perPage: 20,
                initialTerm,
                initialPage
            })
        );
    }

    if (pageId === 'admin-user-closed') {
        const { initialTerm, initialPage } = getInitialState();
        const endpoint = getEndpointFromForm('deleted-users-search-form', '/api/v1/search/deleted-users');

        initTableSearch(
            'deletedUsers',
            ['deleted-users-search-form', 'deleted-users-search', 'deleted-users-tbody'],
            () => new SearchDeletedUsers({
                formId: 'deleted-users-search-form',
                clearBtnId: 'deleted-users-clear',
                inputId: 'deleted-users-search',
                tbodyId: 'deleted-users-tbody',
                pagerId: 'deleted-users-pager',
                endpoint,
                routeBase: '/admin/users/closed',
                perPage: 20,
                initialTerm,
                initialPage
            })
        );
    }
    // ---------- /Tabell-sök ----------

    // ---------- Scroll to top (dold tills du scrollar) ----------
    (function initScrollToTop() {
        const scrollBtn = document.getElementById('scrollToTop');
        if (!scrollBtn) return;

        const SHOW_AFTER_PX = 300;

        const show = () => {
            scrollBtn.classList.remove('opacity-0', 'pointer-events-none', 'translate-y-2');
            scrollBtn.classList.add('opacity-100', 'translate-y-0');
        };

        const hide = () => {
            scrollBtn.classList.add('opacity-0', 'pointer-events-none', 'translate-y-2');
            scrollBtn.classList.remove('opacity-100', 'translate-y-0');
        };

        const prefersReducedMotion =
            window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        const onScroll = () => {
            if (window.scrollY > SHOW_AFTER_PX) show();
            else hide();
        };

        scrollBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: prefersReducedMotion ? 'auto' : 'smooth',
            });
        });

        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll(); // init-läge
    })();
    // ---------- /Scroll to top ----------
    // ---------- Smooth scroll + rensa hash (mjukare: rensa efter scroll) ----------
    (function initAnchorScroll() {
        const allowed = new Set(['kom-igang', 'versioner', 'github']);
        const headerOffset = 60; // px

        document.addEventListener('click', (e) => {
            const a = e.target.closest('a');
            if (!a) return;

            let url;
            try {
                url = new URL(a.href, window.location.href);
            } catch {
                return;
            }

            const hash = url.hash || '';
            if (!hash.startsWith('#') || hash.length < 2) return;

            const id = decodeURIComponent(hash.slice(1));
            if (!allowed.has(id)) return;

            const samePage =
                url.origin === window.location.origin &&
                url.pathname === window.location.pathname;

            if (!samePage) return;

            const el = document.getElementById(id);
            if (!el) return;

            e.preventDefault();

            const targetY = Math.max(
                0,
                el.getBoundingClientRect().top + window.pageYOffset - headerOffset
            );

            const prefersReducedMotion =
                window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            window.scrollTo({
                top: targetY,
                behavior: prefersReducedMotion ? 'auto' : 'smooth',
            });

            // Rensa hash EFTER scroll (mjukare känsla)
            const distance = Math.abs(window.scrollY - targetY);
            const durationMs = prefersReducedMotion ? 0 : Math.min(900, Math.max(250, distance * 0.6));

            window.clearTimeout(window.__hashCleanupTimer);
            window.__hashCleanupTimer = window.setTimeout(() => {
                history.replaceState(null, '', window.location.pathname + window.location.search);
            }, durationMs);
        });
    })();
    // ---------- /Smooth scroll + rensa hash ----------
});
