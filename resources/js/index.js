import Alpine from "alpinejs";
import Collapse from "@alpinejs/collapse";
import Ajax from "@imacrayon/alpine-ajax";
import Ui from "@alpinejs/ui";
import Focus from "@alpinejs/focus";
import { addTableAria } from "./addTableAria";
import SearchUsers from './search-users';
import SearchDeletedUsers from './search-deleted-users';
import SearchSystemEvents from './search-system-events';

window.Alpine = Alpine;
Alpine.plugin(Collapse);
Alpine.plugin(Ui);
Alpine.plugin(Focus);
Alpine.plugin(Ajax);

Alpine.start();

// Kör dina funktioner
addTableAria();

document.addEventListener('DOMContentLoaded', () => {
    const mainContent = document.querySelector('main');

    const searchUserInput = document.getElementById('search-users');
    const searchDeletedInput = document.getElementById('search-deleted-users');

    if (searchUserInput && mainContent) {
        new SearchUsers('search-users', 'main');
    }

    if (searchDeletedInput && mainContent) {
        new SearchDeletedUsers('search-deleted-users', 'main');
    }

    const btn = document.getElementById('search-toggle');
    const wrap = document.getElementById('search-wrap');
    if (!btn || !wrap) return;

    const open = () => {
        wrap.classList.remove('hidden');
        wrap.style.position = 'absolute';
        wrap.style.left = '0';
        wrap.style.right = '0';
        wrap.style.top = '100%';
        wrap.style.marginTop = '0.5rem';
        wrap.style.zIndex = '70';
        // ... existing code ...
        setTimeout(() => {
            if (searchUserInput) {
                searchUserInput.focus();
            } else if (searchDeletedInput) {
                searchDeletedInput.focus();
            }
        }, 0);
    };

    const close = () => {
        wrap.classList.add('hidden');
        wrap.removeAttribute('style');

        if (searchUserInput) searchUserInput.value = '';
        if (searchDeletedInput) searchDeletedInput.value = '';

        // Hitta och rensa första matchande dropdown som finns
        const dropdown =
            document.getElementById('search-dropdown');

        if (dropdown) {
            const resultContainer = dropdown.querySelector('.result-container');
            if (resultContainer) resultContainer.innerHTML = '';
            dropdown.classList.add('hidden');
        }
    };

    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        if (wrap.classList.contains('hidden')) open(); else close();
    });

    document.addEventListener('click', (e) => {
        if (!wrap.contains(e.target) && !btn.contains(e.target)) close();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') close();
    });

    const pageId =
        document.querySelector('[data-page-id]')?.getAttribute('data-page-id')
        || (document.body ? document.body.id : null);

    if (pageId === 'admin-events-index') {
        const params = new URLSearchParams(window.location.search);
        const initialTerm = (params.get('q') || '').trim();
        const initialPage = parseInt(params.get('page') || '1', 10) || 1;

        const form = document.getElementById('system-events-search-form');
        const endpoint = form ? (form.getAttribute('data-search-endpoint') || '') : '';

        new SearchSystemEvents({
            formId: 'system-events-search-form',
            clearBtnId: 'system-events-clear',
            inputId: 'system-events-search',
            tbodyId: 'system-events-tbody',
            pagerId: 'system-events-pager',
            endpoint: endpoint || '/api/v1/search/system-events',
            routeBase: '/admin/events',
            perPage: 20,
            initialTerm,
            initialPage
        });
    }
});