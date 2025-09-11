import Alpine from "alpinejs";
import Collapse from "@alpinejs/collapse";
import Ajax from "@imacrayon/alpine-ajax";
import Ui from "@alpinejs/ui";
import Focus from "@alpinejs/focus";
import { addTableAria } from "./addTableAria";
import { handleCookiesBanner } from "./cookies";
import Search from './search';


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
    // Kontrollera om token-meta-taggen existerar
    const tokenMeta = document.querySelector('meta[name="Authorization"]');
    if (!tokenMeta) {
        return;
    }

    const token = tokenMeta.content || ''; // Lägg till token till scriptet
    const searchInput = document.getElementById('search');
    const mainContent = document.querySelector('main');

    // Initiera sök endast om sökfältet och <main> existerar
    if (searchInput && mainContent) {
        new Search('search', 'main', token);
    }
});

