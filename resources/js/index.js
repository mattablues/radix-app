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
    if (!tokenMeta) {
        return;
    }

    const token = tokenMeta.content || '';
    const searchUsers = document.getElementById('search-users');
    const mainContent = document.querySelector('main');

    if (searchUsers && mainContent) {
        new SearchUsers('search-users', 'main', token); // Använder SearchUsers här
    }
});

