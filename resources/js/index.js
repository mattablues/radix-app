import Alpine from '@alpinejs/csp';
import Collapse from '@alpinejs/collapse';
import Ajax from '@imacrayon/alpine-ajax';
import Ui from '@alpinejs/ui';
import Focus from '@alpinejs/focus';

import { addTableAria } from './addTableAria';
import { initTableSearches } from './search-init';
import {
  initAnchorScroll,
  initHeaderSearch,
  initLogoutFastTap,
  initLogoutPendingState,
  initScrollToTop,
} from './ui-init';

window.Alpine = Alpine;
Alpine.plugin(Collapse);
Alpine.plugin(Ui);
Alpine.plugin(Focus);
Alpine.plugin(Ajax);

Alpine.data('cookieConsent', () => ({
  showCookieBanner: false,

  init() {
    try {
      this.showCookieBanner = window.localStorage.getItem('cookies_choice') === null;
    } catch (e) {
      this.showCookieBanner = true;
    }
  },

  accept() {
    try {
      window.localStorage.setItem('cookies_choice', 'accepted');
    } catch (e) {}
    this.showCookieBanner = false;
  },

  reject() {
    try {
      window.localStorage.setItem('cookies_choice', 'rejected');
    } catch (e) {}
    this.showCookieBanner = false;
  },
}));

Alpine.data('tooltip', (opts = {}) => ({
  open: false,
  title: opts.title || 'Information',
  content: opts.content || '',
  maxWidthPx: Number(opts.maxWidthPx || 320),
  offsetPx: Number(opts.offsetPx || 10),

  panelStyle: '',

  // Intern state
  _closeTimer: null,

  init() {
    // Läs från data-attributes om inget skickats in (CSP-säkert)
    const ds = this.$el && this.$el.dataset ? this.$el.dataset : {};
    if (!opts.title && ds.tooltipTitle) this.title = ds.tooltipTitle;
    if (!opts.content && ds.tooltipContent) this.content = ds.tooltipContent;
    if (!opts.maxWidthPx && ds.tooltipMaxWidthPx) this.maxWidthPx = Number(ds.tooltipMaxWidthPx) || this.maxWidthPx;
    if (!opts.offsetPx && ds.tooltipOffsetPx) this.offsetPx = Number(ds.tooltipOffsetPx) || this.offsetPx;

    const onRelayout = () => { if (this.open) this.updatePosition(); };
    window.addEventListener('scroll', onRelayout, { passive: true });
    window.addEventListener('resize', onRelayout);

    this.$cleanup = () => {
      window.removeEventListener('scroll', onRelayout);
      window.removeEventListener('resize', onRelayout);
      window.clearTimeout(this._closeTimer);
    };
  },

  destroy() {
    if (typeof this.$cleanup === 'function') this.$cleanup();
  },

  isHoverCapable() {
    return window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches;
  },

  show() {
    this.open = true;
    this.$nextTick(() => this.updatePosition());
  },

  hide() {
    this.open = false;
  },

  toggle() {
    this.open ? this.hide() : this.show();
  },

  scheduleHide(delayMs = 120) {
    window.clearTimeout(this._closeTimer);
    this._closeTimer = window.setTimeout(() => this.hide(), delayMs);
  },

  cancelScheduledHide() {
    window.clearTimeout(this._closeTimer);
    this._closeTimer = null;
  },

  // Hover (desktop)
  onTriggerEnter() {
    if (!this.isHoverCapable()) return;
    this.cancelScheduledHide();
    this.show();
  },

  onTriggerLeave() {
    if (!this.isHoverCapable()) return;
    this.scheduleHide(160);
  },

  onPanelEnter() {
    if (!this.isHoverCapable()) return;
    this.cancelScheduledHide();
    this.show();
  },

  onPanelLeave() {
    if (!this.isHoverCapable()) return;
    this.scheduleHide(120);
  },

  updatePosition() {
    const trigger = this.$refs.trigger;
    const panel = this.$refs.panel;
    if (!trigger || !panel) return;

    const vw = window.innerWidth || document.documentElement.clientWidth;
    const vh = window.innerHeight || document.documentElement.clientHeight;

    const effectiveMaxWidth = Math.max(200, Math.min(this.maxWidthPx, vw - 16));
    panel.style.maxWidth = effectiveMaxWidth + 'px';

    const t = trigger.getBoundingClientRect();

    const prevDisplay = panel.style.display;
    const prevVisibility = panel.style.visibility;
    panel.style.display = 'block';
    panel.style.visibility = 'hidden';

    const p = panel.getBoundingClientRect();

    panel.style.display = prevDisplay;
    panel.style.visibility = prevVisibility;

    const spaceAbove = t.top;
    const spaceBelow = vh - t.bottom;

    const isMobile = vw < 768;

    const placeAboveDesktopRule =
      spaceAbove >= (p.height + this.offsetPx) || spaceAbove >= spaceBelow;

    // Mobil: föredra under. Desktop: föredra över men flippar vid behov.
    const placeAbove = isMobile ? false : placeAboveDesktopRule;

    let top;
    if (placeAbove) top = Math.max(8, t.top - p.height - this.offsetPx);
    else top = Math.min(vh - p.height - 8, t.bottom + this.offsetPx);

    const idealLeft = t.left + (t.width / 2) - (p.width / 2);
    const left = Math.min(Math.max(8, idealLeft), vw - p.width - 8);

    this.panelStyle = `position:fixed; top:${top}px; left:${left}px; width:${Math.min(p.width, effectiveMaxWidth)}px;`;
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

addTableAria();

document.addEventListener('DOMContentLoaded', () => {
  initHeaderSearch();
  initTableSearches();
  initScrollToTop();
  initAnchorScroll();
  initLogoutFastTap();
  initLogoutPendingState();
});
