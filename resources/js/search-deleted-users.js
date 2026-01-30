import SearchTable from './search-table';

export default class SearchDeletedUsers extends SearchTable {
  constructor(options) {
    super({
      ...options,
      colspan: 5,
      perPage: options.perPage ?? 20
    });

    // Restore-modal
    this.modal = document.getElementById('restore-user-modal');
    this.modalBackdrop = document.getElementById('restore-user-backdrop');
    this.modalCancel = document.getElementById('restore-user-cancel');
    this.modalEmail = document.getElementById('restore-user-email');
    this.modalForm = document.getElementById('restore-user-form');

    // Viktigt: SearchTable kan ha triggat init() redan i super()
    this.bindModalHandlers();
  }

  bindModalHandlers() {
    if (this.modalBackdrop && !this._modalBackdropBound) {
      this._modalBackdropBound = true;
      this.modalBackdrop.addEventListener('click', () => this.closeRestoreModal());
    }

    if (this.modalCancel && !this._modalCancelBound) {
      this._modalCancelBound = true;
      this.modalCancel.addEventListener('click', () => this.closeRestoreModal());
    }

    if (!this._boundEscHandler) {
      this._boundEscHandler = (e) => {
        if (e.key === 'Escape') this.closeRestoreModal();
      };
      document.addEventListener('keydown', this._boundEscHandler);
    }
  }

  init() {
    super.init();

    if (!this.tbody) return;

    this.tbody.addEventListener('click', (e) => {
      const btn = e.target.closest('button[data-action="restore-user"]');
      if (!btn) return;

      const id = btn.getAttribute('data-user-id') || '';
      const email = btn.getAttribute('data-user-email') || '';
      if (!id) return;

      this.openRestoreModal(id, email);
    });

    this.bindModalHandlers();
  }

  openRestoreModal(id, email) {
    if (!this.modal || !this.modalForm || !this.modalEmail) return;

    this.modalEmail.textContent = email || '';

    const suffix = this.currentQuerySuffix();
    this.modalForm.setAttribute('action', `/admin/users/${encodeURIComponent(id)}/restore${suffix}`);

    this.modal.classList.remove('hidden');
    this.modal.setAttribute('aria-hidden', 'false');
  }

  closeRestoreModal() {
    if (!this.modal) return;
    this.modal.classList.add('hidden');
    this.modal.setAttribute('aria-hidden', 'true');
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

    const restoreIcon = `
      <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.001 0 01-15.357-2m15.357 2H15" />
      </svg>
    `;

    const html = items.map((u) => {
      const id = this.escapeHtml(u.id || '');
      const first = this.escapeHtml(u.first_name || '');
      const last = this.escapeHtml(u.last_name || '');
      const email = this.escapeHtml(u.email || '');
      const deletedAt = this.escapeHtml(u.deleted_at || '');

      return `
        <tr class="group hover:bg-emerald-50/30 transition-all duration-200">
          <td class="px-4 py-4 text-xs font-medium text-gray-400 max-md:hidden">
            #${id}
          </td>

          <td class="px-4 py-4">
            <div class="flex flex-col">
              <span class="text-sm font-semibold text-gray-900">${first} ${last}</span>
              <span class="text-xs text-gray-500">${email}</span>
            </div>
          </td>

          <td class="px-4 py-4 max-sm:hidden">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wide bg-gray-100 text-gray-600 border border-gray-200">
              <span class="size-1.5 rounded-full bg-gray-400"></span>
              Stängt
            </span>
          </td>

          <td class="px-4 py-4 max-sm:hidden">
            <div class="flex flex-col text-xs text-gray-500">
              <span class="font-medium italic">Kontot är inaktivt</span>
              <span class="text-[10px] text-gray-400">${deletedAt || 'Datum saknas'}</span>
            </div>
          </td>

          <td class="px-4 py-4 text-right">
            <div class="flex items-center justify-end gap-2">
              <button
                type="button"
                data-action="restore-user"
                data-user-id="${id}"
                data-user-email="${email}"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-100 rounded-lg transition-all"
                title="Återställ konto"
              >
                ${restoreIcon}
                Återställ
              </button>
            </div>
          </td>
        </tr>
      `;
    }).join('');

    this.tbody.innerHTML = html;
  }
}