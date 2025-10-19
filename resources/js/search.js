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
        // ... existing code ...
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
        // ... existing code ...
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
        // ... existing code ...
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
        wrapper.className = 'flex items-center justify-between px-3 py-2';

        // Hindra att klick i pager bubblar upp och triggar "klick utanför"
        wrapper.addEventListener('click', (e) => e.stopPropagation());

        const info = document.createElement('div');
        const start = (current_page - 1) * per_page + 1;
        const end = Math.min(current_page * per_page, total);
        info.className = 'text-xs text-gray-600 font-semibold';
        info.textContent = `Visar ${start}–${end} av ${total}`;

        const controls = document.createElement('div');
        controls.className = 'flex items-center gap-2';

        const makeBtn = (label, disabled, targetPage) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = `px-2 py-1 text-xs rounded border ${disabled ? 'text-gray-300 border-gray-200 cursor-not-allowed' : 'text-blue-600 border-blue-200 hover:bg-blue-50'}`;
            btn.textContent = label;
            if (!disabled) {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation(); // säkerställ att klick inte stänger dropdown
                    const t = termOverride ?? term ?? this.searchInput?.value?.trim() ?? '';
                    this.performSearch(t, targetPage).then(() => this.showDropdown());
                });
            }
            return btn;
        };

        controls.appendChild(makeBtn('Första', current_page <= 1, 1));
        controls.appendChild(makeBtn('Föregående', current_page <= 1, current_page - 1));
        controls.appendChild(makeBtn('Nästa', current_page >= last_page, current_page + 1));
        controls.appendChild(makeBtn('Sista', current_page >= last_page, last_page));

        wrapper.appendChild(info);
        wrapper.appendChild(controls);
        return wrapper;
    }
}