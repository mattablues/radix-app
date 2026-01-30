import SearchTable from './search-table';

export default class SearchUsers extends SearchTable {
  constructor(options) {
    super({
      ...options,
      colspan: 5,
      perPage: options.perPage ?? 20
    });

    // Modal: block user
    this.blockModal = document.getElementById('block-user-modal');
    this.blockBackdrop = document.getElementById('block-user-backdrop');
    this.blockCancel = document.getElementById('block-user-cancel');
    this.blockEmail = document.getElementById('block-user-email');
    this.blockForm = document.getElementById('block-user-form');

    // Modal: send activation
    this.activationModal = document.getElementById('activation-user-modal');
    this.activationBackdrop = document.getElementById('activation-user-backdrop');
    this.activationCancel = document.getElementById('activation-user-cancel');
    this.activationEmail = document.getElementById('activation-user-email');
    this.activationForm = document.getElementById('activation-user-form');

    // Viktigt: init() kan ha körts innan dessa fanns (pga super()).
    // Bind därför modal-events här också, när vi vet att referenserna är satta.
    this.bindModalHandlers();
  }

  bindModalHandlers() {
    // Stäng block-modal
    if (this.blockBackdrop && !this._blockBackdropBound) {
      this._blockBackdropBound = true;
      this.blockBackdrop.addEventListener('click', () => this.closeBlockModal());
    }
    if (this.blockCancel && !this._blockCancelBound) {
      this._blockCancelBound = true;
      this.blockCancel.addEventListener('click', () => this.closeBlockModal());
    }

    // Stäng activation-modal
    if (this.activationBackdrop && !this._activationBackdropBound) {
      this._activationBackdropBound = true;
      this.activationBackdrop.addEventListener('click', () => this.closeActivationModal());
    }
    if (this.activationCancel && !this._activationCancelBound) {
      this._activationCancelBound = true;
      this.activationCancel.addEventListener('click', () => this.closeActivationModal());
    }

    // ESC
    if (!this._boundEscHandler) {
      this._boundEscHandler = (e) => {
        if (e.key === 'Escape') {
          this.closeBlockModal();
          this.closeActivationModal();
        }
      };
      document.addEventListener('keydown', this._boundEscHandler);
    }
  }

  init() {
    super.init();

    if (!this.tbody) return;

    // OBS: ta gärna bort auto-fetch nu när du har serverrender + modaler i DOM
    // this.fetchAndRender(this.term, this.page, false);

    this.tbody.addEventListener('click', (e) => {
      const blockBtn = e.target.closest('button[data-action="block-user"]');
      if (blockBtn) {
        const id = blockBtn.getAttribute('data-user-id') || '';
        const email = blockBtn.getAttribute('data-user-email') || '';
        if (!id) return;
        this.openBlockModal(id, email);
        return;
      }

      const activationBtn = e.target.closest('button[data-action="send-activation"]');
      if (activationBtn) {
        const id = activationBtn.getAttribute('data-user-id') || '';
        const email = activationBtn.getAttribute('data-user-email') || '';
        if (!id) return;
        this.openActivationModal(id, email);
      }
    });

    // Bind igen (om init körts innan constructor hann sätta refs, eller om DOM ändrats)
    this.bindModalHandlers();
  }

  openBlockModal(id, email) {
    if (!this.blockModal || !this.blockForm || !this.blockEmail) return;

    this.blockEmail.textContent = email || '';

    const suffix = this.currentQuerySuffix();
    this.blockForm.setAttribute('action', `/admin/users/${encodeURIComponent(id)}/block${suffix}`);

    this.blockModal.classList.remove('hidden');
    this.blockModal.setAttribute('aria-hidden', 'false');
  }

  closeBlockModal() {
    if (!this.blockModal) return;
    this.blockModal.classList.add('hidden');
    this.blockModal.setAttribute('aria-hidden', 'true');
  }

  openActivationModal(id, email) {
    if (!this.activationModal || !this.activationForm || !this.activationEmail) return;

    this.activationEmail.textContent = email || '';

    const suffix = this.currentQuerySuffix();
    this.activationForm.setAttribute('action', `/admin/users/${encodeURIComponent(id)}/send-activation${suffix}`);

    this.activationModal.classList.remove('hidden');
    this.activationModal.setAttribute('aria-hidden', 'false');
  }

  closeActivationModal() {
    if (!this.activationModal) return;
    this.activationModal.classList.add('hidden');
    this.activationModal.setAttribute('aria-hidden', 'true');
  }


  statusBadgeHtml(status) {
    const s = String(status || '').toLowerCase();

    const label = s === 'activated' ? 'Aktiverad'
      : s === 'blocked' ? 'Blockerad'
      : s ? s : 'Okänd';

    const cls = s === 'activated'
      ? 'bg-emerald-50 text-emerald-700 border border-emerald-100'
      : s === 'blocked'
        ? 'bg-red-50 text-red-700 border border-red-100'
        : 'bg-amber-50 text-amber-700 border border-amber-100';

    const dot = s === 'activated'
      ? 'bg-emerald-500'
      : s === 'blocked'
        ? 'bg-red-500'
        : 'bg-amber-500';

    return `
      <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wide ${cls}">
        <span class="size-1.5 rounded-full ${dot}"></span>
        ${this.escapeHtml(label)}
      </span>
    `;
  }

  renderRows(items) {
    this.tbody.innerHTML = '';

    if (!items.length) {
      this.tbody.innerHTML = `
        <tr>
          <td colspan="${this.colspan}" class="px-4 py-10 text-center text-gray-400">
            Inga resultat hittades.
          </td>
        </tr>
      `;
      return;
    }

    const mailIcon = `
      <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
      </svg>
    `;

    const blockIcon = `
      <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728A9 9 0 115.636 5.636m12.728 12.728L5.636 5.636" />
      </svg>
    `;

    const html = items.map((u) => {
      const id = this.escapeHtml(u.id || '');
      const first = this.escapeHtml(u.first_name || '');
      const last = this.escapeHtml(u.last_name || '');
      const email = this.escapeHtml(u.email || '');
      const showUrl = this.escapeHtml(u.show_url || '#');

      const status = u.status || '';
      const active = this.escapeHtml(u.active || '');
      const activeAt = this.escapeHtml(u.active_at || 'Aldrig');

      const isAdmin = !!u.is_admin;
      const isBlocked = String(u.status || '').toLowerCase() === 'blocked';

      const actionHtml = isAdmin
        ? `<span class="p-1.5 text-gray-300" title="Admin kan ej ändras här">
             <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
             </svg>
           </span>`
        : `
          <button
            type="button"
            class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all"
            title="Skicka aktivering"
            data-action="send-activation"
            data-user-id="${id}"
            data-user-email="${email}"
          >
            ${mailIcon}
          </button>

          ${isBlocked ? '' : `
            <button
              type="button"
              class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all"
              title="Blockera användare"
              data-action="block-user"
              data-user-id="${id}"
              data-user-email="${email}"
            >
              ${blockIcon}
            </button>
          `}
        `;

      return `
        <tr class="group hover:bg-blue-50/30 transition-all duration-200">
          <td class="px-4 py-4 text-xs font-medium text-gray-400 max-md:hidden">
            #${id}
          </td>

          <td class="px-4 py-4">
            <div class="flex flex-col">
              <a href="${showUrl}" class="text-sm font-semibold text-gray-900 group-hover:text-blue-600 transition-colors">
                ${first} ${last}
              </a>
              <span class="text-xs text-gray-500">${email}</span>
            </div>
          </td>

          <td class="px-4 py-4 max-sm:hidden">
            ${this.statusBadgeHtml(status)}
          </td>

          <td class="px-4 py-4 max-sm:hidden">
            <div class="flex flex-col">
              <span class="text-xs font-medium ${String(active).toLowerCase() === 'online' ? 'text-emerald-600' : 'text-gray-600'}">
                ${active ? (active.charAt(0).toUpperCase() + active.slice(1)) : 'Offline'}
              </span>
              <span class="text-[10px] text-gray-400 italic">
                ${activeAt}
              </span>
            </div>
          </td>

          <td class="px-4 py-4 text-right">
            <div class="flex items-center justify-end gap-2">
              ${actionHtml}
            </div>
          </td>
        </tr>
      `;
    }).join('');

    this.tbody.innerHTML = html;
  }
}