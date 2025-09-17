import Search from './search';

export default class SearchUsers extends Search {
    async performSearch(term) {
        try {
            this.showLoading();

            const response = await fetch('/api/v1/search/users', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.token}`
                },
                body: JSON.stringify({
                    search: {
                        term: term,
                        current_page: 1
                    }
                })
            });

            if (!response.ok) {
                throw new Error('Något gick fel med sökningen.');
            }

            const responseJson = await response.json();
            this.results = responseJson.body.data || [];
            this.renderResults();

        } catch (error) {
            console.error('Sökfel:', error.message);
            this.mainContent.innerHTML = `<p class="text-red-500">Kunde inte hämta resultaten. Försök igen.</p>`;
        } finally {
            this.hideLoading();
        }
    }

    renderResults() {
        if (this.mainContent) {
            let resultContainer = this.mainContent.querySelector('.result-container');
            if (!resultContainer) {
                resultContainer = document.createElement('div');
                resultContainer.classList.add('result-container');
                this.mainContent.appendChild(resultContainer);
            }

            resultContainer.innerHTML = '';

            if (this.results.length > 0) {
                const ul = document.createElement('ul');
                ul.classList.add('search-results');

                this.results.forEach(result => {
                    const li = document.createElement('li');
                    li.classList.add('search-result-item');

                    const userRoute = `/user/${result.id}/show`;

                    li.innerHTML = `
                        <div class="flex items-center gap-4">
                            <img src="${result.avatar}" alt="${result.first_name}" class="w-10 h-10 rounded-full object-cover">
                            <div>
                                <a href="${userRoute}" class="font-semibold text-blue-600 hover:underline">${result.first_name} ${result.last_name}</a>
                                <p class="text-sm text-gray-600">${result.email}</p>
                            </div>
                        </div>
                    `;
                    ul.appendChild(li);
                });

                resultContainer.appendChild(ul);

            } else {
                resultContainer.innerHTML = `<p class="text-gray-500">Inga resultat hittades.</p>`;
            }
        }
    }
}