export default class Search {
    constructor(searchInputId, mainContentSelector, token) {
        this.searchInput = document.getElementById(searchInputId);
        this.mainContent = document.querySelector(mainContentSelector);
        this.token = token;
        this.results = [];
        this.meta = { term: '', total: 0, per_page: 10, current_page: 1, last_page: 0 };
        // Dropdown-stöd
        this.dropdown = document.getElementById('search-dropdown');
        this.resultContainer = this.dropdown ? this.dropdown.querySelector('.result-container') : null;
        this.init();
    }


    init() {
        if (this.searchInput) {
            this.searchInput.addEventListener('input', this.debounce(async (e) => {
                const term = e.target.value.trim();
                if (term.length > 0) {
                    try {
                        // Vid ny term, starta på sida 1
                        await this.performSearch(term, 1);
                        this.showDropdown();
                    } catch (error) {
                        console.error('Fel vid sökningen:', error.message);
                    }
                } else {
                    this.clearResults();
                    this.hideDropdown();
                }
            }, 300));

            // Stäng dropdown vid klick utanför
            document.addEventListener('click', (e) => {
                if (this.dropdown && !this.dropdown.contains(e.target) && e.target !== this.searchInput) {
                    this.hideDropdown();
                }
            });

            this.searchInput.addEventListener('focus', () => {
                if (this.results?.length) this.showDropdown();
            });
        } else {
            console.error(`Sökfältet med ID "${searchInputId}" hittades inte.`);
        }
    }

    clearResults() {
        this.results = []; // Nollställ cachen
        if (this.resultContainer) {
            this.resultContainer.innerHTML = '';
        } else if (this.mainContent) {
            this.mainContent.innerHTML = '<p class="text-gray-500">Inga sökord har angetts.</p>';
        }
    }

    showLoading() {
        if (this.resultContainer) {
            this.resultContainer.innerHTML = '<p class="loading-indicator text-gray-500 p-3">Laddar...</p>';
            this.showDropdown();
            return;
        }
        let loadingIndicator = this.mainContent.querySelector('.loading-indicator');
        if (!loadingIndicator) {
            loadingIndicator = document.createElement('p');
            loadingIndicator.classList.add('loading-indicator', 'text-gray-500');
            loadingIndicator.innerText = 'Laddar...';
            this.mainContent.appendChild(loadingIndicator);
        }
        loadingIndicator.style.display = 'block';
    }

    hideLoading() {
        if (this.resultContainer) return; // inget separat loading-element i dropdown
        const loadingIndicator = this.mainContent.querySelector('.loading-indicator');
        if (loadingIndicator) {
            loadingIndicator.style.display = 'none';
        }
    }

    showDropdown() {
        if (this.dropdown) this.dropdown.classList.remove('hidden');
    }

    hideDropdown() {
        if (this.dropdown) this.dropdown.classList.add('hidden');
        // Se till att rensa också när man stänger
        this.clearResults();
    }

    debounce(func, wait) {
        let timeout;
        return function (...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    renderPager(termOverride) {
        const { current_page, last_page, total, per_page, term } = this.meta || {};
        if (!last_page || last_page <= 1) return null;

        const wrapper = document.createElement('div');
        wrapper.className = 'flex flex-col gap-2 px-3 py-2';
        // Hindra att klick i pager stänger dropdown
        wrapper.addEventListener('click', (e) => e.stopPropagation());

        // Rad 1: info
        const topRow = document.createElement('div');
        topRow.className = 'flex items-center justify-between gap-3';

        const info = document.createElement('div');
        const start = (current_page - 1) * per_page + 1;
        const end = Math.min(current_page * per_page, total);
        info.className = 'text-xs text-gray-600 font-semibold';
        info.textContent = `Visar ${start}–${end} av ${total}`;

        topRow.appendChild(info);

        // Rad 2: pager-knappar + sidnummer
        const bottomRow = document.createElement('div');
        bottomRow.className = 'flex items-center justify-center gap-1.5';

        const makeBtn = (label, disabled, targetPage, title) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = `px-2 py-1 text-xs rounded border ${disabled ? 'text-gray-300 border-gray-200 cursor-not-allowed' : 'text-blue-600 border-blue-200 hover:bg-blue-50'}`;
            btn.textContent = label;
            if (title) btn.title = title;
            if (!disabled) {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const t = termOverride ?? term ?? this.searchInput?.value?.trim() ?? '';
                    this.performSearch(t, targetPage).then(() => this.showDropdown());
                });
            }
            return btn;
        };

        // Sidnummer (kompakt intervall)
        const createPageButtons = () => {
            const frag = document.createDocumentFragment();

            const addPage = (p, isActive = false, isEllipsis = false) => {
                const el = document.createElement('button');
                el.type = 'button';
                if (isEllipsis) {
                    el.className = 'px-2 py-1 text-xs text-gray-400';
                    el.textContent = '…';
                    el.disabled = true;
                    frag.appendChild(el);
                    return;
                }
                el.className = `px-2 py-1 text-xs rounded border ${isActive ? 'bg-blue-600 text-white border-blue-600' : 'text-blue-600 border-blue-200 hover:bg-blue-50'}`;
                el.textContent = String(p);
                if (!isActive) {
                    el.addEventListener('click', (e) => {
                        e.stopPropagation();
                        const t = termOverride ?? term ?? this.searchInput?.value?.trim() ?? '';
                        this.performSearch(t, p).then(() => this.showDropdown());
                    });
                } else {
                    el.setAttribute('aria-current', 'page');
                }
                frag.appendChild(el);
            };

            const windowSize = 2;
            const pages = [];
            if (last_page <= 7) {
                for (let p = 1; p <= last_page; p++) pages.push(p);
            } else {
                const startRange = Math.max(2, current_page - windowSize);
                const endRange = Math.min(last_page - 1, current_page + windowSize);
                pages.push(1);
                if (startRange > 2) pages.push('ellipsis-left');
                for (let p = startRange; p <= endRange; p++) pages.push(p);
                if (endRange < last_page - 1) pages.push('ellipsis-right');
                pages.push(last_page);
            }

            pages.forEach(p => {
                if (p === 'ellipsis-left' || p === 'ellipsis-right') addPage(null, false, true);
                else addPage(p, p === current_page, false);
            });

            return frag;
        };

        bottomRow.appendChild(makeBtn('Första', current_page <= 1, 1, 'Gå till första sidan'));
        bottomRow.appendChild(makeBtn('Föregående', current_page <= 1, current_page - 1, 'Föregående sida'));
        bottomRow.appendChild(createPageButtons());
        bottomRow.appendChild(makeBtn('Nästa', current_page >= last_page, current_page + 1, 'Nästa sida'));
        bottomRow.appendChild(makeBtn('Sista', current_page >= last_page, last_page, 'Gå till sista sidan'));

        wrapper.appendChild(topRow);
        wrapper.appendChild(bottomRow);
        return wrapper;
    }
}