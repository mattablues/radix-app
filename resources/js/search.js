export default class Search {
    constructor(searchInputId, mainContentSelector, token) {
        this.searchInput = document.getElementById(searchInputId);
        // ... existing code ...
        this.mainContent = document.querySelector(mainContentSelector);
        this.token = token;
        this.results = [];
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
                        await this.performSearch(term);
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

    // ... existing code ...

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
}