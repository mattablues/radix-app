import SearchTable from './search-table';

export default class SearchSystemUpdates extends SearchTable {
  constructor(options) {
    super({
      ...options,
      colspan: 4,
      perPage: options.perPage ?? 10
    });

    // Delete-modal (måste finnas i vyn)
    this.modal = document.getElementById('delete-update-modal');
    this.modalBackdrop = document.getElementById('delete-update-backdrop');
    this.modalCancel = document.getElementById('delete-update-cancel');
    this.modalVersion = document.getElementById('delete-update-version');
    this.modalForm = document.getElementById('delete-update-form');

    // Viktigt: init() kan ha körts innan dessa fanns (pga super()).
    // Bind därför modal-events här också, när vi vet att referenserna är satta.
    this.bindModalHandlers();
  }

  bindModalHandlers() {
    // Stäng modalen
    if (this.modalBackdrop && !this._modalBackdropBound) {
      this._modalBackdropBound = true;
      this.modalBackdrop.addEventListener('click', () => this.closeDeleteModal());
    }
    if (this.modalCancel && !this._modalCancelBound) {
      this._modalCancelBound = true;
      this.modalCancel.addEventListener('click', () => this.closeDeleteModal());
    }

    if (!this._boundEscHandler) {
      this._boundEscHandler = (e) => {
        if (e.key === 'Escape') this.closeDeleteModal();
      };
      document.addEventListener('keydown', this._boundEscHandler);
    }
  }

  init() {
    super.init();

    if (!this.tbody) return;

    // Event delegation: funkar även efter innerHTML-rendering
    this.tbody.addEventListener('click', (e) => {
      const btn = e.target.closest('button[data-action="delete-update"]');
      if (!btn) return;

      const id = btn.getAttribute('data-update-id') || '';
      const version = btn.getAttribute('data-update-version') || '';
      if (!id) return;

      this.openDeleteModal(id, version);
    });

    // Bind igen (om init körts innan constructor hann sätta refs, eller om DOM ändrats)
    this.bindModalHandlers();
  }

  openDeleteModal(id, version) {
    if (!this.modal || !this.modalForm || !this.modalVersion) return;

    this.modalVersion.textContent = version || '';

    const suffix = this.currentQuerySuffix();
    this.modalForm.setAttribute('action', `/admin/updates/${encodeURIComponent(id)}/delete${suffix}`);

    // Spara fokus så vi kan återställa när modalen stängs
    this.__restoreFocusEl = document.activeElement;

    this.modal.classList.remove('hidden');
    this.modal.setAttribute('aria-hidden', 'false');

    // Flytta fokus in i modalen (Avbryt först)
    const cancel = this.modalCancel || document.getElementById('delete-update-cancel');
    if (cancel && typeof cancel.focus === 'function') {
      setTimeout(() => cancel.focus(), 0);
    }
  }

  closeDeleteModal() {
    if (!this.modal) return;

    const restoreEl = this.__restoreFocusEl;
    this.__restoreFocusEl = null;

    // Flytta fokus UT ur modalen innan aria-hidden=true
    if (restoreEl && typeof restoreEl.focus === 'function' && !this.modal.contains(restoreEl)) {
      try { restoreEl.focus(); } catch (e) {}
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
          <td colspan="${this.colspan}" class="px-4 py-10 text-center text-gray-400">
            Inga resultat hittades.
          </td>
        </tr>
      `;
      return;
    }

    const editIcon = `
      <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
      </svg>
    `;

    const deleteIcon = `
      <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
      </svg>
    `;

    const html = items.map((u) => {
      const id = this.escapeHtml(u.id || '');
      const version = this.escapeHtml(u.version || '');
      const title = this.escapeHtml(u.title || '');
      const description = this.escapeHtml(u.description || '');
      const releasedAt = this.escapeHtml(u.released_at_date || u.released_at || '');
      const editUrl = this.escapeHtml(u.edit_url || '#');

      const major = u.is_major
        ? `<span class="inline-flex w-fit px-1.5 py-0.5 rounded text-[9px] font-black uppercase bg-indigo-100 text-indigo-700 border border-indigo-200">Major</span>`
        : '';

      return `
        <tr class="group hover:bg-blue-50/30 transition-all duration-200">
          <td class="px-4 py-4">
            <div class="flex flex-col gap-1">
              <span class="text-sm font-black text-gray-900">${version}</span>
              ${major}
            </div>
          </td>

          <td class="px-4 py-4">
            <div class="flex flex-col">
              <span class="text-sm font-bold text-slate-800">${title}</span>
              <span class="text-xs text-gray-500 line-clamp-1 max-w-md">${description}</span>
            </div>
          </td>

          <td class="px-4 py-4 max-sm:hidden">
            <span class="text-xs font-medium text-gray-600">${releasedAt}</span>
          </td>

          <td class="px-4 py-4 text-right">
            <div class="flex items-center justify-end gap-1">
              <a href="${editUrl}"
                 class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all"
                 title="Redigera">
                ${editIcon}
              </a>

              <button
                type="button"
                class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all"
                title="Radera"
                data-action="delete-update"
                data-update-id="${id}"
                data-update-version="${version}"
              >
                ${deleteIcon}
              </button>
            </div>
          </td>
        </tr>
      `;
    }).join('');

    this.tbody.innerHTML = html;
  }
}
