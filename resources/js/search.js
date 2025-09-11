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
            // Lägg till eventlistener för inputs med debounce
            this.searchInput.addEventListener('input', this.debounce(async (e) => { // Gör callback-funktionen async
                const term = e.target.value.trim();
                if (term.length > 0) {
                    try {
                        await this.performSearch(term); // Vänta på att sökningen blir klar
                    } catch (error) {
                        console.error('Fel vid sökningen:', error); // Hantera eventuella fel
                    }
                } else {
                    this.clearResults(); // Rensa resultaten om termen är tom
                }
            }, 300));
        } else {
            console.error(`Sökfältet med ID "${this.searchInputId}" hittades inte.`);
        }
    }

    async performSearch(term) {
        try {
            // Visa en laddningsindikator
            this.showLoading();

            // Skicka API-anropet
            const response = await fetch('/api/v1/search/users', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.token}` // Skickar rätt token
                },
                body: JSON.stringify({
                    search: {
                        term: term,
                        current_page: 1
                    }
                })
            });

            // Kontrollera om svaret är OK
            if (!response.ok) {
                throw new Error('Något gick fel med sökningen.');
            }

            // Hämta och logga hela JSON-svaret
            const responseJson = await response.json();
            console.log('Fullständigt svar från API:', responseJson);

            // Tilldela `data` från `body` till `this.results`
            this.results = responseJson.body.data || [];
            console.log('Resultat som tilldelas:', this.results);

            // Rendera resultaten om datan finns
            this.renderResults();

        } catch (error) {
            console.error('Sökfel:', error.message);
            this.mainContent.innerHTML = `<p class="text-red-500">Kunde inte hämta resultaten. Försök igen.</p>`;
        } finally {
            this.hideLoading(); // Dölj laddningsindikatorn oavsett om det lyckades eller ej
        }
    }

    renderResults() {
        console.log('Resultat som ska renderas:', this.results); // Debug-logga resultaten

        if (this.mainContent) {
            // Skapa eller identifiera resultatbehållaren
            let resultContainer = this.mainContent.querySelector('.result-container');
            if (!resultContainer) {
                resultContainer = document.createElement('div');
                resultContainer.classList.add('result-container');
                this.mainContent.appendChild(resultContainer); // Lägg till efter <h1>
            }

            // Rensa tidigare renderat resultat
            resultContainer.innerHTML = '';

            // Rendera sökresultaten
            if (this.results.length > 0) {
                const ul = document.createElement('ul');
                ul.classList.add('search-results'); // Lägg till klass för styling

                // Lägg in resultaten
                this.results.forEach(result => {
                    const li = document.createElement('li');
                    li.classList.add('search-result-item');
                    li.innerHTML = `
                        <div class="flex items-center gap-4">
                            <img src="${result.avatar}" alt="${result.first_name}" class="w-10 h-10 rounded-full object-cover">
                            <div>
                                <p class="font-semibold">${result.first_name} ${result.last_name}</p>
                                <p class="text-sm text-gray-600">${result.email}</p>
                            </div>
                        </div>
                    `;
                    ul.appendChild(li);
                });

                resultContainer.appendChild(ul);

            } else {
                // Om inga resultat hittades
                resultContainer.innerHTML = `<p class="text-gray-500">Inga resultat hittades.</p>`;
            }
        }
    }

    clearResults() {
        if (this.mainContent) {
            this.mainContent.innerHTML = '<p class="text-gray-500">Inga sökord har angetts.</p>';
        }
    }

    showLoading() {
        // Hitta eller skapa ett särskilt laddningselement
        let loadingIndicator = this.mainContent.querySelector('.loading-indicator');
        if (!loadingIndicator) {
            loadingIndicator = document.createElement('p');
            loadingIndicator.classList.add('loading-indicator', 'text-gray-500');
            loadingIndicator.innerText = 'Laddar...';
            this.mainContent.appendChild(loadingIndicator);
        }
        loadingIndicator.style.display = 'block'; // Visa laddningselementet
    }

    hideLoading() {
        const loadingIndicator = this.mainContent.querySelector('.loading-indicator');
        if (loadingIndicator) {
            loadingIndicator.style.display = 'none'; // Dölj laddningselementet
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