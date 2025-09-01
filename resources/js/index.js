import Alpine from "alpinejs";
import Collapse from "@alpinejs/collapse";
import Ajax from "@imacrayon/alpine-ajax";
import Ui from "@alpinejs/ui";
import Focus from "@alpinejs/focus";
import { addTableAria } from "./addTableAria";
import { handleCookiesBanner } from "./cookies";

window.Alpine = Alpine;
Alpine.plugin(Collapse);
Alpine.plugin(Ui);
Alpine.plugin(Focus);
Alpine.plugin(Ajax);

Alpine.start();

// Kör dina funktioner
addTableAria();
handleCookiesBanner();