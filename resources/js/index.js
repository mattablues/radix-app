import Alpine from "alpinejs";
import Collapse from "@alpinejs/collapse";
import Ajax from "@imacrayon/alpine-ajax";
import Ui from "@alpinejs/ui";
import Focus from "@alpinejs/focus";
import { addTableAria } from "./addTableAria";
import SearchProfiles from './search-profiles';
import SearchSystemEvents from './search-system-events';
import SearchSystemUpdates from './search-system-updates';
import SearchUsers from './search-users';
import SearchDeletedUsers from './search-deleted-users';

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

    const searchProfileInput = document.getElementById('search-profiles');

    if (searchProfileInput && mainContent) {
        new SearchProfiles('search-profiles', 'main');
    }

    // ---------- Header-sök (robust / valfri) ----------
    const btn = document.getElementById('search-toggle');
    const wrap = document.getElementById('search-wrap');

    if (btn && wrap) {
        const open = () => {
            wrap.classList.remove('hidden');
            wrap.style.position = 'absolute';
            wrap.style.left = '0';
            wrap.style.right = '0';
            wrap.style.top = '100%';
            wrap.style.marginTop = '0.5rem';
            wrap.style.zIndex = '70';

            setTimeout(() => {
                if (searchProfileInput) searchProfileInput.focus();
            }, 0);
        };

        const close = () => {
            wrap.classList.add('hidden');
            wrap.removeAttribute('style');

            if (searchProfileInput) searchProfileInput.value = '';

            const dropdown = document.getElementById('search-dropdown');

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
    }
    // ---------- /Header-sök ----------

    // ---------- Tabell-sök (robust / destroy / guards) ----------
    window.__tableSearchInstances = window.__tableSearchInstances || {};

    const getPageId = () =>
        document.querySelector('[data-page-id]')?.getAttribute('data-page-id')
        || (document.body ? document.body.id : null);

    const exists = (id) => !!document.getElementById(id);

    const getEndpointFromForm = (formId, fallback) => {
        const form = document.getElementById(formId);
        const endpoint = form ? (form.getAttribute('data-search-endpoint') || '') : '';
        return endpoint || fallback;
    };

    const getInitialState = () => {
        const params = new URLSearchParams(window.location.search);
        return {
            initialTerm: (params.get('q') || '').trim(),
            initialPage: parseInt(params.get('page') || '1', 10) || 1
        };
    };

    const initTableSearch = (key, requiredIds, createInstance) => {
        const ok = requiredIds.every(exists);
        if (!ok) return null;

        const prev = window.__tableSearchInstances[key];
        if (prev && typeof prev.destroy === 'function') {
            prev.destroy();
        }

        const instance = createInstance();
        window.__tableSearchInstances[key] = instance;
        return instance;
    };

    const pageId = getPageId();

    if (pageId === 'admin-events-index') {
        const { initialTerm, initialPage } = getInitialState();
        const endpoint = getEndpointFromForm('system-events-search-form', '/api/v1/search/system-events');

        initTableSearch(
            'systemEvents',
            ['system-events-search-form', 'system-events-search', 'system-events-tbody'],
            () => new SearchSystemEvents({
                formId: 'system-events-search-form',
                clearBtnId: 'system-events-clear',
                inputId: 'system-events-search',
                tbodyId: 'system-events-tbody',
                pagerId: 'system-events-pager',
                endpoint,
                routeBase: '/admin/events',
                perPage: 20,
                initialTerm,
                initialPage
            })
        );
    }

    if (pageId === 'admin-updates-index') {
        const { initialTerm, initialPage } = getInitialState();
        const endpoint = getEndpointFromForm('system-updates-search-form', '/api/v1/search/system-updates');

        initTableSearch(
            'systemUpdates',
            ['system-updates-search-form', 'system-updates-search', 'system-updates-tbody'],
            () => new SearchSystemUpdates({
                formId: 'system-updates-search-form',
                clearBtnId: 'system-updates-clear',
                inputId: 'system-updates-search',
                tbodyId: 'system-updates-tbody',
                pagerId: 'system-updates-pager',
                endpoint,
                routeBase: '/admin/updates',
                perPage: 10,
                initialTerm,
                initialPage
            })
        );
    }

    if (pageId === 'admin-users-index') {
        const { initialTerm, initialPage } = getInitialState();
        const endpoint = getEndpointFromForm('users-search-form', '/api/v1/search/users');

        initTableSearch(
            'users',
            ['users-search-form', 'users-search', 'users-tbody'],
            () => new SearchUsers({
                formId: 'users-search-form',
                clearBtnId: 'users-clear',
                inputId: 'users-search',
                tbodyId: 'users-tbody',
                pagerId: 'users-pager',
                endpoint,
                routeBase: '/admin/users',
                perPage: 20,
                initialTerm,
                initialPage
            })
        );
    }

    if (pageId === 'admin-user-closed') {
        const { initialTerm, initialPage } = getInitialState();
        const endpoint = getEndpointFromForm('deleted-users-search-form', '/api/v1/search/deleted-users');

        initTableSearch(
            'deletedUsers',
            ['deleted-users-search-form', 'deleted-users-search', 'deleted-users-tbody'],
            () => new SearchDeletedUsers({
                formId: 'deleted-users-search-form',
                clearBtnId: 'deleted-users-clear',
                inputId: 'deleted-users-search',
                tbodyId: 'deleted-users-tbody',
                pagerId: 'deleted-users-pager',
                endpoint,
                routeBase: '/admin/users/closed',
                perPage: 20,
                initialTerm,
                initialPage
            })
        );
    }
    // ---------- /Tabell-sök ----------
});
