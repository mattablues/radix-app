export default class Search {
    constructor(searchInputId, mainContentSelector, token) {
        this.searchInput = document.getElementById(searchInputId);
        this.mainContent = document.querySelector(mainContentSelector);
        this.token = token;
        this.results = [];
        this.init();
    }

    init() {
        if (this.searchInput) {
            // Lägg till event listener för inputs med debounce
            this.searchInput.addEventListener('input', this.debounce(async (e) => {
                const term = e.target.value.trim();
                if (term.length > 0) {
                    try {
                        await this.performSearch(term); // Vänta på att sökningen blir klar
                    } catch (error) {
                        console.error('Fel vid sökningen:', error.message);
                    }
                } else {
                    this.clearResults();
                }
            }, 300));
        } else {
            console.error(`Sökfältet med ID "${searchInputId}" hittades inte.`);
        }
    }

    async performSearch(term) {
        throw new Error("Metoden 'performSearch' måste implementeras av underklassen.");
    }

    renderResults() {
        throw new Error("Metoden 'renderResults' måste implementeras av underklassen.");
    }

    clearResults() {
        if (this.mainContent) {
            this.mainContent.innerHTML = '<p class="text-gray-500">Inga sökord har angetts.</p>';
        }
    }

    showLoading() {
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
        const loadingIndicator = this.mainContent.querySelector('.loading-indicator');
        if (loadingIndicator) {
            loadingIndicator.style.display = 'none';
        }
    }

    debounce(func, wait) {
        let timeout;
        return function (...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }
}