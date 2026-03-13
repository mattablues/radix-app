import SearchProfiles from './search-profiles';

export function initHeaderSearch() {
  const mainContent = document.querySelector('main');
  const searchProfileInput = document.getElementById('search-profiles');

  if (searchProfileInput && mainContent) {
    new SearchProfiles('search-profiles', 'main');
  }

  const button = document.getElementById('search-toggle');
  const wrap = document.getElementById('search-wrap');

  if (!button || !wrap) {
    return;
  }

  const open = () => {
    wrap.classList.remove('hidden');
    wrap.style.position = 'absolute';
    wrap.style.left = '0';
    wrap.style.right = '0';
    wrap.style.top = '100%';
    wrap.style.marginTop = '0.5rem';
    wrap.style.zIndex = '70';

    window.setTimeout(() => {
      if (searchProfileInput) {
        searchProfileInput.focus();
      }
    }, 0);
  };

  const close = () => {
    wrap.classList.add('hidden');
    wrap.removeAttribute('style');

    if (searchProfileInput) {
      searchProfileInput.value = '';
    }

    const dropdown = document.getElementById('search-dropdown');

    if (!dropdown) {
      return;
    }

    const resultContainer = dropdown.querySelector('.result-container');

    if (resultContainer) {
      resultContainer.innerHTML = '';
    }

    dropdown.classList.add('hidden');
  };

  button.addEventListener('click', (event) => {
    event.stopPropagation();

    if (wrap.classList.contains('hidden')) {
      open();
      return;
    }

    close();
  });

  document.addEventListener('click', (event) => {
    if (!wrap.contains(event.target) && !button.contains(event.target)) {
      close();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      close();
    }
  });
}

export function initScrollToTop() {
  const scrollButton = document.getElementById('scrollToTop');

  if (!scrollButton) {
    return;
  }

  const showAfterPx = 300;

  const show = () => {
    scrollButton.classList.remove('opacity-0', 'pointer-events-none', 'translate-y-2');
    scrollButton.classList.add('opacity-100', 'translate-y-0');
  };

  const hide = () => {
    scrollButton.classList.add('opacity-0', 'pointer-events-none', 'translate-y-2');
    scrollButton.classList.remove('opacity-100', 'translate-y-0');
  };

  const prefersReducedMotion = window.matchMedia
    && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  const onScroll = () => {
    if (window.scrollY > showAfterPx) {
      show();
      return;
    }

    hide();
  };

  scrollButton.addEventListener('click', () => {
    window.scrollTo({
      top: 0,
      behavior: prefersReducedMotion ? 'auto' : 'smooth',
    });
  });

  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
}

export function initAnchorScroll() {
  const allowed = new Set(['kom-igang', 'versioner', 'github']);
  const headerOffset = 60;

  document.addEventListener('click', (event) => {
    const link = event.target.closest('a');

    if (!link) {
      return;
    }

    let url;

    try {
      url = new URL(link.href, window.location.href);
    } catch {
      return;
    }

    const hash = url.hash || '';

    if (!hash.startsWith('#') || hash.length < 2) {
      return;
    }

    const id = decodeURIComponent(hash.slice(1));

    if (!allowed.has(id)) {
      return;
    }

    const samePage = url.origin === window.location.origin
      && url.pathname === window.location.pathname;

    if (!samePage) {
      return;
    }

    const target = document.getElementById(id);

    if (!target) {
      return;
    }

    event.preventDefault();

    const targetY = Math.max(
      0,
      target.getBoundingClientRect().top + window.pageYOffset - headerOffset,
    );

    const prefersReducedMotion = window.matchMedia
      && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    window.scrollTo({
      top: targetY,
      behavior: prefersReducedMotion ? 'auto' : 'smooth',
    });

    const distance = Math.abs(window.scrollY - targetY);
    const durationMs = prefersReducedMotion
      ? 0
      : Math.min(900, Math.max(250, distance * 0.6));

    window.clearTimeout(window.__hashCleanupTimer);
    window.__hashCleanupTimer = window.setTimeout(() => {
      history.replaceState(null, '', window.location.pathname + window.location.search);
    }, durationMs);
  });
}

export function initLogoutFastTap() {
  const forms = document.querySelectorAll('form[data-logout-form]');

  if (!forms.length) {
    return;
  }

  const bind = (form) => {
    if (form.__logoutBound) {
      return;
    }

    form.__logoutBound = true;

    const submit = () => {
      const button = form.querySelector('button[type="submit"]');

      if (button) {
        button.disabled = true;
      }

      if (typeof form.requestSubmit === 'function') {
        form.requestSubmit();
        return;
      }

      form.submit();
    };

    form.addEventListener('touchend', (event) => {
      event.preventDefault();
      event.stopPropagation();
      submit();
    }, { passive: false });

    form.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();
      submit();
    }, true);
  };

  forms.forEach(bind);
}

export function initLogoutPendingState() {
  const form = document.querySelector('form[data-logout-form]');

  if (!form) {
    return;
  }

  form.addEventListener('submit', () => {
    const button = form.querySelector('button[type="submit"]');

    if (!button) {
      return;
    }

    button.disabled = true;
    button.classList.add('opacity-60', 'cursor-not-allowed');

    const label = button.querySelector('[data-logout-label]');
    if (label) {
      label.textContent = 'Loggar ut…';
    }

    const spinner = button.querySelector('[data-logout-spinner]');
    if (spinner) {
      spinner.classList.remove('hidden');
    }
  });
}
