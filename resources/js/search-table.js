export default class SearchTable {
  // Håll koll på alla instanser + bara en popstate-listener
  static instances = new Set();
  static popstateBound = false;

  constructor(options) {
    this.form = options.formId ? document.getElementById(options.formId) : null;
    this.clearBtn = options.clearBtnId ? document.getElementById(options.clearBtnId) : null;
    this.csrfToken = this.getCsrfToken();

    this.input = document.getElementById(options.inputId);
    this.tbody = document.getElementById(options.tbodyId);
    this.pager = document.getElementById(options.pagerId);
    this.notice = options.noticeId ? document.getElementById(options.noticeId) : null;
    this.summary = options.summaryId ? document.getElementById(options.summaryId) : null;
    this.noticeHtml = options.noticeHtml ?? '';

    this.summarySingular = options.summarySingular ?? 'resultat';
    this.summaryPlural = options.summaryPlural ?? 'resultat';
    this.summarySuffix = options.summarySuffix ?? 'totalt';

    this.endpoint = options.endpoint;
    this.routeBase = options.routeBase;

    this.perPage = options.perPage ?? (Number.parseInt(this.form?.dataset?.perPage || '10', 10) || 10);
    this.colspan = options.colspan ?? 1;

    this.term = options.initialTerm ?? '';
    this.page = options.initialPage ?? 1;

    this._abort = null;

    this._onSubmit = null;
    this._onInput = null;
    this._onClear = null;
    this._onPagerClick = null;

    this._debounced = this.debounce(() => {
      this.page = 1;
      this.fetchAndRender(this.term, this.page, true);
    }, options.debounceMs ?? 250);

    SearchTable.instances.add(this);
    if (!SearchTable.popstateBound) {
      SearchTable.popstateBound = true;
      window.addEventListener('popstate', () => {
        for (const inst of SearchTable.instances) {
          inst.handlePopState();
        }
      });
    }

    this.init();
  }

  init() {
    if (!this.input || !this.tbody) return;

    this.input.value = this.term;
    this.setClearEnabled();
    this.renderNotice(this.term);
    this.renderSummary({
      term: this.term,
      total: Number.parseInt(this.summary?.dataset?.total || '0', 10) || 0
    });

    if (this.form) {
      this._onSubmit = (e) => {
        e.preventDefault();
        this.term = (this.input.value || '').trim();
        this.page = 1;
        this.fetchAndRender(this.term, this.page, true);
      };
      this.form.addEventListener('submit', this._onSubmit);
    }

    this._onInput = (e) => {
      this.term = (e.target.value || '').trim();
      this.setClearEnabled();
      this._debounced();
    };
    this.input.addEventListener('input', this._onInput);

    if (this.clearBtn) {
      this._onClear = (e) => {
        e.preventDefault();
        this.term = '';
        this.page = 1;
        this.input.value = '';
        this.setClearEnabled();
        this.fetchAndRender(this.term, this.page, true);
      };
      this.clearBtn.addEventListener('click', this._onClear);
    }

    if (this.pager) {
      this._onPagerClick = (e) => {
        const a = e.target.closest('a');
        if (!a) return;

        const href = a.getAttribute('href') || '';
        if (!href) return;
        if (!this.routeBase || !href.includes(this.routeBase)) return;

        e.preventDefault();

        const url = new URL(href, window.location.origin);
        const page = parseInt(url.searchParams.get('page') || '1', 10) || 1;
        const q = (url.searchParams.get('q') || '').trim();

        this.term = q;
        this.page = page;
        this.input.value = this.term;
        this.setClearEnabled();

        this.fetchAndRender(this.term, this.page, true);
      };
      this.pager.addEventListener('click', this._onPagerClick);
    }
  }

  getCsrfToken() {
    return this.form?.querySelector('input[name="csrf_token"]')?.value ?? '';
  }

  csrfField() {
    if (!this.csrfToken) {
      return '';
    }

    return `<input type="hidden" name="csrf_token" value="${this.escapeHtml(this.csrfToken)}">`;
  }

  currentQuerySuffix() {
    const params = new URLSearchParams();

    if (this.term) {
      params.set('q', this.term);
    }

    if (this.page > 1) {
      params.set('page', String(this.page));
    }

    const query = params.toString();

    return query ? `?${query}` : '';
  }

  destroy() {
    if (this._abort) {
      this._abort.abort();
      this._abort = null;
    }

    if (this.form && this._onSubmit) {
      this.form.removeEventListener('submit', this._onSubmit);
    }
    if (this.input && this._onInput) {
      this.input.removeEventListener('input', this._onInput);
    }
    if (this.clearBtn && this._onClear) {
      this.clearBtn.removeEventListener('click', this._onClear);
    }
    if (this.pager && this._onPagerClick) {
      this.pager.removeEventListener('click', this._onPagerClick);
    }

    this._onSubmit = null;
    this._onInput = null;
    this._onClear = null;
    this._onPagerClick = null;

    SearchTable.instances.delete(this);
  }

  handlePopState() {
    const params = new URLSearchParams(window.location.search);
    const term = (params.get('q') || '').trim();
    const page = parseInt(params.get('page') || '1', 10) || 1;

    this.term = term;
    this.page = page;

    if (this.input) {
      this.input.value = this.term;
    }
    this.setClearEnabled();
    this.renderNotice(this.term);

    this.fetchAndRender(this.term, this.page, false);
  }

  renderNotice(term) {
    if (!this.notice) return;

    if (term) {
      this.notice.classList.remove('hidden');
      return;
    }

    this.notice.classList.add('hidden');
  }

  getSummaryNoun(total) {
    return total === 1 ? this.summarySingular : this.summaryPlural;
  }

  renderSummary(meta = {}) {
    if (!this.summary) return;

    const term = String(meta.term ?? this.term ?? '').trim();
    const total = Number.parseInt(String(meta.total ?? 0), 10) || 0;
    const noun = this.getSummaryNoun(total);
    const suffix = this.summarySuffix ? ` ${this.summarySuffix}` : '';

    if (term) {
      this.summary.innerHTML = `Resultat för <span class="font-semibold text-slate-700">"${this.escapeHtml(term)}"</span> <span class="text-slate-300">•</span> ${total} ${this.escapeHtml(noun)}${this.escapeHtml(suffix)}`;
      return;
    }

    this.summary.textContent = `${total} ${noun}${suffix}`;
  }

  setClearEnabled() {
    if (!this.clearBtn) return;
    this.clearBtn.disabled = !this.term;
  }

  async fetchAndRender(term, page, updateUrl) {
    if (this._abort) this._abort.abort();
    this._abort = new AbortController();

    this.showLoading();

    try {
      const res = await fetch(this.endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        signal: this._abort.signal,
        body: JSON.stringify({
          search: {
            term,
            current_page: page,
            per_page: this.perPage
          }
        })
      });

      const contentType = res.headers.get('content-type') || '';
      const rawText = await res.text();

      if (!res.ok) {
        this.renderError(`Sökningen misslyckades (HTTP ${res.status}).`);
        return;
      }

      if (!contentType.includes('application/json')) {
        this.renderError('Servern svarade inte med JSON.');
        return;
      }

      const json = JSON.parse(rawText);
      const items = Array.isArray(json.data) ? json.data : [];
      const meta = json.meta || {
        total: 0,
        per_page: this.perPage,
        current_page: page,
        last_page: 0,
        first_page: 1,
        term
      };

      this.renderRows(items);
      this.renderPager(meta, term);
      this.renderNotice(term);
      this.renderSummary(meta);

      if (updateUrl) {
        const u = new URL(this.routeBase, window.location.origin);
        if (term) u.searchParams.set('q', term);
        if (meta.current_page && meta.current_page > 1) u.searchParams.set('page', String(meta.current_page));
        window.history.pushState({}, '', u.toString());
      }
    } catch (err) {
      if (err && err.name === 'AbortError') return;
      this.renderError('Kunde inte hämta resultaten.');
    }
  }

  renderRows(items) {
    this.tbody.innerHTML = '';
    if (!items.length) {
      this.tbody.innerHTML = `
        <tr>
          <td colspan="${this.colspan}" class="px-4 py-10 text-center text-slate-400">
            Inga resultat hittades.
          </td>
        </tr>
      `;
    }
  }

  renderPager(meta, term) {
    const total = meta.total ?? 0;
    const perPage = meta.per_page ?? this.perPage;
    const current = meta.current_page ?? 1;
    const last = meta.last_page ?? 0;

    if (!this.pager) return;

    if (!last || last <= 1 || total <= perPage) {
      this.pager.innerHTML = '';
      return;
    }

    const mkUrl = (p) => {
      const u = new URL(this.routeBase, window.location.origin);
      if (term) u.searchParams.set('q', term);
      u.searchParams.set('page', String(p));
      return u.toString();
    };

    const btnCls = (disabled, active) => {
      const base = 'h-7 min-w-7 px-2 py-1 inline-flex items-center justify-center align-middle border rounded text-sm';
      if (active) return base + ' pager-active';
      if (disabled) return base + ' pager-disabled';
      return base + ' pager-base pager-hover';
    };

    const makePageBtn = (p, isActive) => {
      if (isActive) {
        return `<span class="${btnCls(false, true)}" aria-current="page" style="line-height:1">${p}</span>`;
      }
      return `<a href="${mkUrl(p)}" class="${btnCls(false, false)}" style="line-height:1">${p}</a>`;
    };

    const interval = 2;
    const pages = [];

    if (last <= 7) {
      for (let p = 1; p <= last; p++) pages.push(p);
    } else {
      const startRange = Math.max(2, current - interval);
      const endRange = Math.min(last - 1, current + interval);

      pages.push(1);
      if (startRange > 2) pages.push('…');
      for (let p = startRange; p <= endRange; p++) pages.push(p);
      if (endRange < last - 1) pages.push('…');
      pages.push(last);
    }

    const firstDisabled = current <= 1;
    const prevDisabled = current <= 1;
    const nextDisabled = current >= last;
    const lastDisabled = current >= last;

    const icon = (which) => {
      const size = 18;
      const common = `class="pointer-events-none" width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"`;
      if (which === 'chevrons-left') return `<svg ${common}><polyline points="11 17 6 12 11 7"></polyline><polyline points="18 17 13 12 18 7"></polyline></svg>`;
      if (which === 'chevron-left') return `<svg ${common}><polyline points="15 18 9 12 15 6"></polyline></svg>`;
      if (which === 'chevron-right') return `<svg ${common}><polyline points="9 18 15 12 9 6"></polyline></svg>`;
      if (which === 'chevrons-right') return `<svg ${common}><polyline points="13 17 18 12 13 7"></polyline><polyline points="6 17 11 12 6 7"></polyline></svg>`;
      return '';
    };

    const first = firstDisabled
      ? `<span class="${btnCls(true, false)} rounded-l-lg" aria-hidden="true" style="line-height:1">${icon('chevrons-left')}</span>`
      : `<a href="${mkUrl(1)}" class="${btnCls(false, false)} rounded-l-lg" aria-label="Gå till första sidan" style="line-height:1">${icon('chevrons-left')}</a>`;

    const prev = prevDisabled
      ? `<span class="${btnCls(true, false)}" aria-hidden="true" style="line-height:1">${icon('chevron-left')}</span>`
      : `<a href="${mkUrl(current - 1)}" class="${btnCls(false, false)}" aria-label="Föregående sida" style="line-height:1">${icon('chevron-left')}</a>`;

    const next = nextDisabled
      ? `<span class="${btnCls(true, false)}" aria-hidden="true" style="line-height:1">${icon('chevron-right')}</span>`
      : `<a href="${mkUrl(current + 1)}" class="${btnCls(false, false)}" aria-label="Nästa sida" style="line-height:1">${icon('chevron-right')}</a>`;

    const lastBtn = lastDisabled
      ? `<span class="${btnCls(true, false)} rounded-r-lg" aria-hidden="true" style="line-height:1">${icon('chevrons-right')}</span>`
      : `<a href="${mkUrl(last)}" class="${btnCls(false, false)} rounded-r-lg" aria-label="Gå till sista sidan" style="line-height:1">${icon('chevrons-right')}</a>`;

    const pageHtml = pages.map((p) => {
      if (p === '…') return `<span class="h-6 min-w-6 px-1.5 py-0.5 inline-flex items-center justify-center align-middle pager-ellipsis" style="line-height:1">…</span>`;
      return makePageBtn(p, p === current);
    }).join('');

    this.pager.innerHTML = `
      <div class="hidden md:flex items-center justify-center gap-1.5" aria-label="Sidnavigering">
        ${first}${prev}${pageHtml}${next}${lastBtn}
      </div>
      <div class="md:hidden w-full overflow-x-auto pb-2 snap-x" aria-label="Sidnavigering">
        <div class="flex min-w-fit shrink-0 items-center justify-center gap-1.5 px-2 text-sm">
          ${first}${prev}${pageHtml}${next}${lastBtn}
        </div>
      </div>
    `;
  }

  showLoading() {
    this.tbody.innerHTML = `
      <tr>
        <td colspan="${this.colspan}" class="px-4 py-6 text-center text-slate-400">
          Laddar...
        </td>
      </tr>
    `;
  }

  renderError(msg) {
    this.tbody.innerHTML = `
      <tr>
        <td colspan="${this.colspan}" class="px-4 py-6 text-center text-red-500">
          ${this.escapeHtml(msg)}
        </td>
      </tr>
    `;
  }

  debounce(fn, wait) {
    let t;
    return (...args) => {
      clearTimeout(t);
      t = setTimeout(() => fn.apply(this, args), wait);
    };
  }

  escapeHtml(s) {
    return String(s)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }
}
