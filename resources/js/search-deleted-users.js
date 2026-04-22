import SearchTable from './search-table';

export default class SearchDeletedUsers extends SearchTable {
  constructor(options) {
    super({
      ...options,
      colspan: 5,
    });

    this.modal = document.getElementById('restore-user-modal');
    this.modalBackdrop = document.getElementById('restore-user-backdrop');
    this.modalCancel = document.getElementById('restore-user-cancel');
    this.modalEmail = document.getElementById('restore-user-email');
    this.modalForm = document.getElementById('restore-user-form');

    this._onTbodyClick = null;
    this._boundEscHandler = null;

    this.bindModalHandlers();
  }

  bindModalHandlers() {
    if (this.modalBackdrop && !this._modalBackdropBound) {
      this._modalBackdropBound = true;
      this._onModalBackdropClick = () => this.closeRestoreModal();
      this.modalBackdrop.addEventListener('click', this._onModalBackdropClick);
    }

    if (this.modalCancel && !this._modalCancelBound) {
      this._modalCancelBound = true;
      this._onModalCancelClick = () => this.closeRestoreModal();
      this.modalCancel.addEventListener('click', this._onModalCancelClick);
    }

    if (!this._boundEscHandler) {
      this._boundEscHandler = (e) => {
        if (e.key === 'Escape') {
          this.closeRestoreModal();
        }
      };

      document.addEventListener('keydown', this._boundEscHandler);
    }
  }

  init() {
    super.init();

    if (!this.tbody) {
      return;
    }

    if (!this._onTbodyClick) {
      this._onTbodyClick = (e) => {
        const btn = e.target.closest('button[data-action="restore-user"]');

        if (!btn) {
          return;
        }

        const actionUrl = btn.getAttribute('data-action-url') || '';
        const email = btn.getAttribute('data-user-email') || '';

        if (!actionUrl) {
          return;
        }

        this.openRestoreModal(actionUrl, email);
      };

      this.tbody.addEventListener('click', this._onTbodyClick);
    }

    this.bindModalHandlers();
  }

  destroy() {
    if (this.tbody && this._onTbodyClick) {
      this.tbody.removeEventListener('click', this._onTbodyClick);
    }

    if (this.modalBackdrop && this._onModalBackdropClick) {
      this.modalBackdrop.removeEventListener('click', this._onModalBackdropClick);
    }

    if (this.modalCancel && this._onModalCancelClick) {
      this.modalCancel.removeEventListener('click', this._onModalCancelClick);
    }

    if (this._boundEscHandler) {
      document.removeEventListener('keydown', this._boundEscHandler);
    }

    this._onTbodyClick = null;
    this._onModalBackdropClick = null;
    this._onModalCancelClick = null;
    this._boundEscHandler = null;

    super.destroy();
  }

  openRestoreModal(actionUrl, email) {
    if (!this.modal || !this.modalForm || !this.modalEmail) {
      return;
    }

    this.modalEmail.textContent = email || '';
    this.modalForm.setAttribute('action', this.withQuerySuffix(actionUrl));

    this.__restoreFocusEl = document.activeElement;

    this.modal.classList.remove('hidden');
    this.modal.setAttribute('aria-hidden', 'false');

    const cancel = this.modalCancel || document.getElementById('restore-user-cancel');

    if (cancel && typeof cancel.focus === 'function') {
      setTimeout(() => cancel.focus(), 0);
    }
  }

  closeRestoreModal() {
    if (!this.modal) {
      return;
    }

    const restoreEl = this.__restoreFocusEl;
    this.__restoreFocusEl = null;

    if (restoreEl && typeof restoreEl.focus === 'function' && !this.modal.contains(restoreEl)) {
      try {
        restoreEl.focus();
      } catch (e) {}
    } else {
      const active = document.activeElement;

      if (active && this.modal.contains(active) && typeof active.blur === 'function') {
        active.blur();
      }
    }

    this.modal.classList.add('hidden');
    this.modal.setAttribute('aria-hidden', 'true');
  }

  renderRows(items) {
    this.tbody.innerHTML = '';

    if (!items.length) {
      this.tbody.innerHTML = `
        <tr>
          <td colspan="${this.colspan}" class="px-4 py-10 text-center">
            <p class="text-slate-500 font-medium">Inga stängda konton matchade din sökning.</p>
            <p class="mt-2 text-sm text-slate-400">Prova att söka på namn eller e-postadress.</p>
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
      const restoreUrl = this.escapeHtml(u.restore_url || '');

      return `
        <tr class="group hover:bg-emerald-50/30 transition-all duration-200">
          <td class="px-4 py-4 text-xs font-medium text-slate-400 max-md:hidden">
            #${id}
          </td>

          <td class="px-4 py-4">
            <div class="flex flex-col">
              <span class="text-sm font-semibold text-slate-900">${first} ${last}</span>
              <span class="text-xs text-slate-500">${email}</span>
            </div>
          </td>

          <td class="px-4 py-4 max-sm:hidden">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wide bg-slate-100 text-slate-600 border border-slate-200">
              <span class="size-1.5 rounded-full bg-slate-400"></span>
              Stängt
            </span>
          </td>

          <td class="px-4 py-4 max-sm:hidden">
            <div class="flex flex-col text-xs text-slate-500">
              <span class="font-medium italic">Kontot är inaktivt</span>
              <span class="text-[10px] text-slate-400">${deletedAt || 'Datum saknas'}</span>
            </div>
          </td>

          <td class="px-4 py-4 text-right">
            <div class="flex items-center justify-end gap-2">
              <button
                type="button"
                data-action="restore-user"
                data-action-url="${restoreUrl}"
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
