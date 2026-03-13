import SearchTable from './search-table';

export default class SearchSystemEvents extends SearchTable {
  constructor(options) {
    super({
      ...options,
      colspan: 4,
    });
  }

  renderRows(items) {
    this.tbody.innerHTML = '';

    if (!items.length) {
      this.tbody.innerHTML = `
        <tr>
          <td colspan="${this.colspan}" class="px-4 py-10 text-center">
            <p class="text-slate-500 font-medium">Inga systemhändelser matchade din sökning.</p>
            <p class="mt-2 text-sm text-slate-400">Prova att söka på typ, användare eller innehåll i händelsen.</p>
          </td>
        </tr>
      `;
      return;
    }

    const html = items.map((e) => {
      const userCell = e.user_name
        ? `<span class="text-xs font-medium text-emerald-700">${this.escapeHtml(e.user_name)}</span>`
        : `<span class="text-[10px] font-bold text-slate-300 uppercase tracking-widest">System</span>`;

      return `
        <tr class="group hover:bg-emerald-50/30 transition-all duration-200">
          <td class="px-4 py-4 whitespace-nowrap">
            <span class="text-xs font-medium text-slate-500">${this.escapeHtml(e.created_at || '')}</span>
          </td>
          <td class="px-4 py-4">
            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-black uppercase border ${this.escapeHtml(e.type_badge_class || '')}">
              ${this.escapeHtml(e.type || '')}
            </span>
          </td>
          <td class="px-4 py-4">
            <span class="text-sm font-semibold text-slate-800">${this.escapeHtml(e.message || '')}</span>
          </td>
          <td class="px-4 py-4 max-sm:hidden">
            ${userCell}
          </td>
        </tr>
      `;
    }).join('');

    this.tbody.innerHTML = html;
  }
}
