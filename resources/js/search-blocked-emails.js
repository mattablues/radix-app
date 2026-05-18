import SearchTable from './search-table';

export default class SearchBlockedEmails extends SearchTable {
  constructor(options) {
    super({
      ...options,
      colspan: 6,
    });

    this._onTbodyClick = null;
    this.bindDeleteHandler();
  }

  init() {
    super.init();
    this.bindDeleteHandler();
  }

  bindDeleteHandler() {
    if (!this.tbody || this._onTbodyClick) {
      return;
    }

    this._onTbodyClick = (e) => {
      const btn = e.target.closest('button[data-action="delete-blocked-email"]');

      if (!btn) {
        return;
      }

      const action = btn.getAttribute('data-delete-action') || '';
      const email = btn.getAttribute('data-delete-email') || '';

      if (!action) {
        return;
      }

      window.dispatchEvent(new CustomEvent('blocked-email-delete', {
        detail: {
          action,
          email,
        },
      }));
    };

    this.tbody.addEventListener('click', this._onTbodyClick);
  }

  destroy() {
    if (this.tbody && this._onTbodyClick) {
      this.tbody.removeEventListener('click', this._onTbodyClick);
    }

    this._onTbodyClick = null;

    super.destroy();
  }

  renderRows(items) {
    this.clearLoadingState();
    this.tbody.innerHTML = '';

    if (!items.length) {
      this.tbody.innerHTML = `
        <tr>
          <td colspan="${this.colspan}" class="px-4 py-10 text-center">
            <p class="text-slate-500 font-medium">Inga blockerade e-postadresser matchade din sökning.</p>
            <p class="mt-2 text-sm text-slate-400">Prova att söka på e-postadress eller anledning.</p>
          </td>
        </tr>
      `;
      return;
    }

    const deleteIcon = `
      <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m2 0H7m3-3h4a1 1 0 011 1v2H9V5a1 1 0 011-1z" />
      </svg>
    `;

    const html = items.map((item) => {
      const id = this.escapeHtml(item.id || '');
      const email = this.escapeHtml(item.email || '');
      const reason = this.escapeHtml(item.reason || '');
      const createdAt = this.escapeHtml(item.created_at || '');
      const deleteUrl = this.escapeHtml(item.delete_url || '');

      const createdByHtml = item.created_by_name
        ? `
          <div class="flex flex-col">
            <span class="text-sm font-medium text-gray-700">${this.escapeHtml(item.created_by_name)}</span>
            <span class="text-xs text-gray-400">${this.escapeHtml(item.created_by_email || '')}</span>
          </div>
        `
        : `<span class="text-sm text-gray-400 italic">System</span>`;

      return `
        <tr class="group hover:bg-red-50/30 transition-all duration-200">
          <td class="px-4 py-4 text-xs font-medium text-gray-400 max-md:hidden">
            #${id}
          </td>

          <td class="px-4 py-4">
            <div class="flex flex-col">
              <span class="text-sm font-semibold text-gray-900 group-hover:text-red-600 transition-colors">
                ${email}
              </span>
              <span class="text-xs text-gray-500 lg:hidden">
                ${reason || 'Ingen anledning angiven'}
              </span>
            </div>
          </td>

          <td class="px-4 py-4 max-lg:hidden">
            <span class="text-sm text-gray-600">
              ${reason || 'Ingen anledning angiven'}
            </span>
          </td>

          <td class="px-4 py-4 max-xl:hidden">
            ${createdByHtml}
          </td>

          <td class="px-4 py-4 max-sm:hidden">
            <span class="text-xs text-gray-500">
              ${createdAt}
            </span>
          </td>

          <td class="px-4 py-4 text-right">
            <div class="flex items-center justify-end gap-2">
              <button
                type="button"
                class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all"
                title="Ta bort blockering"
                data-action="delete-blocked-email"
                data-delete-action="${deleteUrl}"
                data-delete-email="${email}"
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
