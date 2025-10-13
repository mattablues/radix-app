import Alpine from "alpinejs";
import Collapse from "@alpinejs/collapse";
import Ajax from "@imacrayon/alpine-ajax";
import Ui from "@alpinejs/ui";
import Focus from "@alpinejs/focus";
import { addTableAria } from "./addTableAria";
import { handleCookiesBanner } from "./cookies";
import SearchUsers from './search-users';

window.Alpine = Alpine;
Alpine.plugin(Collapse);
Alpine.plugin(Ui);
Alpine.plugin(Focus);
Alpine.plugin(Ajax);

Alpine.start();

// Kör dina funktioner
addTableAria();
handleCookiesBanner();

document.addEventListener('DOMContentLoaded', () => {
    const tokenMeta = document.querySelector('meta[name="Authorization"]');
    const token = tokenMeta ? (tokenMeta.content || '') : '';
    const mainContent = document.querySelector('main');

    const searchInput = document.getElementById('search-users');

    if (searchInput && mainContent) {
        new SearchUsers('search-users', 'main', token);
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
        setTimeout(() => searchInput && searchInput.focus(), 0);
    };

    const close = () => {
        wrap.classList.add('hidden');
        wrap.removeAttribute('style');
        // Rensa input + dropdown + cache
        if (searchInput) searchInput.value = '';
        const dropdown = document.getElementById('search-dropdown');
        const resultContainer = dropdown ? dropdown.querySelector('.result-container') : null;
        if (resultContainer) resultContainer.innerHTML = '';
        // Trigger clear via event (om SearchUsers lyssnar på input) eller manuellt:
        // Manuell rensning:
        if (dropdown) dropdown.classList.add('hidden');
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
});

