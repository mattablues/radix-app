import SearchTable from './search-table';

export default class SearchUsers extends SearchTable {
  constructor(options) {
    super({
      ...options,
      colspan: 5,
    });

    this.blockModal = document.getElementById('block-user-modal');
    this.blockBackdrop = document.getElementById('block-user-backdrop');
    this.blockCancel = document.getElementById('block-user-cancel');
    this.blockEmail = document.getElementById('block-user-email');
    this.blockForm = document.getElementById('block-user-form');

    this.activationModal = document.getElementById('activation-user-modal');
    this.activationBackdrop = document.getElementById('activation-user-backdrop');
    this.activationCancel = document.getElementById('activation-user-cancel');
    this.activationEmail = document.getElementById('activation-user-email');
    this.activationForm = document.getElementById('activation-user-form');

    this._onTbodyClick = null;
    this._boundEscHandler = null;

    this.bindModalHandlers();
  }

  bindModalHandlers() {
    if (this.blockBackdrop && !this._blockBackdropBound) {
      this._blockBackdropBound = true;
      this._onBlockBackdropClick = () => this.closeBlockModal();
      this.blockBackdrop.addEventListener('click', this._onBlockBackdropClick);
    }

    if (this.blockCancel && !this._blockCancelBound) {
      this._blockCancelBound = true;
      this._onBlockCancelClick = () => this.closeBlockModal();
      this.blockCancel.addEventListener('click', this._onBlockCancelClick);
    }

    if (this.activationBackdrop && !this._activationBackdropBound) {
      this._activationBackdropBound = true;
      this._onActivationBackdropClick = () => this.closeActivationModal();
      this.activationBackdrop.addEventListener('click', this._onActivationBackdropClick);
    }

    if (this.activationCancel && !this._activationCancelBound) {
      this._activationCancelBound = true;
      this._onActivationCancelClick = () => this.closeActivationModal();
      this.activationCancel.addEventListener('click', this._onActivationCancelClick);
    }

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

    if (!this.tbody) {
      return;
    }

    if (!this._onTbodyClick) {
      this._onTbodyClick = (e) => {
        const blockBtn = e.target.closest('button[data-action="block-user"]');

        if (blockBtn) {
          const action = blockBtn.getAttribute('data-action-url') || '';
          const email = blockBtn.getAttribute('data-user-email') || '';

          if (!action) {
            return;
          }

          this.openBlockModal(action, email);
          return;
        }

        const activationBtn = e.target.closest('button[data-action="send-activation"]');

        if (activationBtn) {
          const action = activationBtn.getAttribute('data-action-url') || '';
          const email = activationBtn.getAttribute('data-user-email') || '';

          if (!action) {
            return;
          }

          this.openActivationModal(action, email);
        }
      };

      this.tbody.addEventListener('click', this._onTbodyClick);
    }

    this.bindModalHandlers();
  }

  destroy() {
    if (this.tbody && this._onTbodyClick) {
      this.tbody.removeEventListener('click', this._onTbodyClick);
    }

    if (this.blockBackdrop && this._onBlockBackdropClick) {
      this.blockBackdrop.removeEventListener('click', this._onBlockBackdropClick);
    }

    if (this.blockCancel && this._onBlockCancelClick) {
      this.blockCancel.removeEventListener('click', this._onBlockCancelClick);
    }

    if (this.activationBackdrop && this._onActivationBackdropClick) {
      this.activationBackdrop.removeEventListener('click', this._onActivationBackdropClick);
    }

    if (this.activationCancel && this._onActivationCancelClick) {
      this.activationCancel.removeEventListener('click', this._onActivationCancelClick);
    }

    if (this._boundEscHandler) {
      document.removeEventListener('keydown', this._boundEscHandler);
    }

    this._onTbodyClick = null;
    this._onBlockBackdropClick = null;
    this._onBlockCancelClick = null;
    this._onActivationBackdropClick = null;
    this._onActivationCancelClick = null;
    this._boundEscHandler = null;

    super.destroy();
  }

  openBlockModal(actionUrl, email) {
    if (!this.blockModal || !this.blockForm || !this.blockEmail) {
      return;
    }

    this.blockEmail.textContent = email || '';
    this.blockForm.setAttribute('action', this.withQuerySuffix(actionUrl));

    this.__restoreFocusEl = document.activeElement;

    this.blockModal.classList.remove('hidden');
    this.blockModal.setAttribute('aria-hidden', 'false');

    const cancel = document.getElementById('block-user-cancel');

    if (cancel && typeof cancel.focus === 'function') {
      setTimeout(() => cancel.focus(), 0);
    }
  }

  closeBlockModal() {
    if (!this.blockModal) {
      return;
    }

    const restoreEl = this.__restoreFocusEl;
    this.__restoreFocusEl = null;

    if (restoreEl && typeof restoreEl.focus === 'function' && !this.blockModal.contains(restoreEl)) {
      try {
        restoreEl.focus();
      } catch (e) {}
    } else {
      const active = document.activeElement;

      if (active && this.blockModal.contains(active) && typeof active.blur === 'function') {
        active.blur();
      }
    }

    this.blockModal.classList.add('hidden');
    this.blockModal.setAttribute('aria-hidden', 'true');
  }

  openActivationModal(actionUrl, email) {
    if (!this.activationModal || !this.activationForm || !this.activationEmail) {
      return;
    }

    this.activationEmail.textContent = email || '';
    this.activationForm.setAttribute('action', this.withQuerySuffix(actionUrl));

    this.__restoreFocusElActivation = document.activeElement;

    this.activationModal.classList.remove('hidden');
    this.activationModal.setAttribute('aria-hidden', 'false');

    const cancel = document.getElementById('activation-user-cancel');

    if (cancel && typeof cancel.focus === 'function') {
      setTimeout(() => cancel.focus(), 0);
    }
  }

  closeActivationModal() {
    if (!this.activationModal) {
      return;
    }

    const restoreEl = this.__restoreFocusElActivation;
    this.__restoreFocusElActivation = null;

    if (restoreEl && typeof restoreEl.focus === 'function' && !this.activationModal.contains(restoreEl)) {
      try {
        restoreEl.focus();
      } catch (e) {}
    } else {
      const active = document.activeElement;

      if (active && this.activationModal.contains(active) && typeof active.blur === 'function') {
        active.blur();
      }
    }

    this.activationModal.classList.add('hidden');
    this.activationModal.setAttribute('aria-hidden', 'true');
  }

  statusBadgeHtml(status) {
    const s = String(status || '').toLowerCase();

    const label = s === 'activated'
      ? 'Aktiverad'
      : s === 'blocked'
        ? 'Blockerad'
        : s || 'Okänd';

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
    this.clearLoadingState();
    this.tbody.innerHTML = '';

    if (!items.length) {
      this.tbody.innerHTML = `
        <tr>
          <td colspan="${this.colspan}" class="px-4 py-10 text-center">
            <p class="text-slate-500 font-medium">Inga konton matchade din sökning.</p>
            <p class="mt-2 text-sm text-slate-400">Prova att söka på namn eller e-postadress.</p>
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

      const rawShowUrl = u.admin_show_url || u.show_url || '#';
      const showUrl = this.escapeHtml(rawShowUrl);
      const blockUrl = this.escapeHtml(u.block_url || '');
      const activationUrl = this.escapeHtml(u.send_activation_url || '');

      const status = u.status || '';
      const active = this.escapeHtml(u.active || '');
      const activeAt = this.escapeHtml(u.active_at || 'Aldrig');

      const isAdmin = !!u.is_admin;
      const isBlocked = String(u.status || '').toLowerCase() === 'blocked';

      const actionHtml = isAdmin
        ? `<span class="p-1.5 text-slate-300" title="Admin kan ej ändras här">
             <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
             </svg>
           </span>`
        : `
          <button
            type="button"
            class="p-2 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-all"
            title="Skicka aktivering"
            data-action="send-activation"
            data-action-url="${activationUrl}"
            data-user-id="${id}"
            data-user-email="${email}"
          >
            ${mailIcon}
          </button>

          ${isBlocked ? `
            <span class="p-2 text-slate-400 cursor-not-allowed">
              <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728A9 9 0 115.636 5.636m12.728 12.728L5.636 5.636" />
              </svg>
            </span>
          ` : `
            <button
              type="button"
              class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all"
              title="Blockera användare"
              data-action="block-user"
              data-action-url="${blockUrl}"
              data-user-id="${id}"
              data-user-email="${email}"
            >
              ${blockIcon}
            </button>
          `}
        `;

      return `
        <tr class="group hover:bg-emerald-50/30 transition-all duration-200">
          <td class="px-4 py-4 text-xs font-medium text-slate-400 max-md:hidden">
            #${id}
          </td>

          <td class="px-4 py-4">
            <div class="flex flex-col">
              <a href="${showUrl}" class="text-sm font-semibold text-slate-900 group-hover:text-emerald-600 transition-colors">
                ${first} ${last}
              </a>
              <span class="text-xs text-slate-500">${email}</span>
            </div>
          </td>

          <td class="px-4 py-4 max-sm:hidden">
            ${this.statusBadgeHtml(status)}
          </td>

          <td class="px-4 py-4 max-sm:hidden">
            <div class="flex flex-col">
              <span class="text-xs font-medium ${String(active).toLowerCase() === 'online' ? 'text-emerald-600' : 'text-slate-600'}">
                ${active ? (active.charAt(0).toUpperCase() + active.slice(1)) : 'Offline'}
              </span>
              <span class="text-[10px] text-slate-400 italic">
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
