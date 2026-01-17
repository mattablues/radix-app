{% extends "layouts/main.ratio.php" %}
{% block title %}Cookies och Systemets Säkerhet{% endblock %}
{% block pageId %}cookies{% endblock %}
{% block body %}
    <section class="py-12 bg-slate-50">
      <div class="container-centered-sm">
        <div class="mb-12 text-center">
            <h1 class="text-4xl font-black text-slate-900 tracking-tight mb-4">Integritet & Säkerhet</h1>
            <div class="h-1.5 w-20 bg-blue-600 rounded-full mx-auto mb-6"></div>
            <p class="text-lg text-slate-500 leading-relaxed">
                Radix Systemet är byggt med din integritet som högsta prioritet. Här beskriver vi hur vi använder modern teknik för att hålla din session säker.
            </p>
        </div>

        <div class="space-y-8">
            <!-- Sektion 1: Systemets motor -->
            <article class="bg-white border border-gray-200 p-8 rounded-3xl shadow-sm">
                <h3 class="text-xl font-bold text-slate-800 mb-4">Hur fungerar Radix-sessions?</h3>
                <p class="text-slate-600 leading-relaxed mb-4">
                    Radix använder cookies uteslutande för att upprätthålla en säker koppling mellan din webbläsare och vår server. Utan dessa små textfiler skulle systemet inte kunna verifiera din identitet vid navigering, vilket krävs för att komma åt administrativa verktyg eller personliga inställningar.
                </p>
                <div class="bg-blue-50 border border-blue-100 p-4 rounded-xl">
                    <p class="text-sm text-blue-900 font-medium italic">
                        <strong>Teknisk notering:</strong> Vi lagrar aldrig känslig information eller lösenord i cookies. Vi använder kryptografiskt säkra sessions-nycklar som genereras unikt för varje användare och session.
                    </p>
                </div>
            </article>

            <!-- Sektion 2: Kategorier -->
            <article class="bg-white border border-gray-200 p-8 rounded-3xl shadow-sm">
                <h3 class="text-xl font-bold text-slate-800 mb-6">Systemkritiska mekanismer</h3>

                <div class="space-y-6">
                    <div>
                        <h4 class="text-sm font-black uppercase tracking-widest text-blue-600 mb-2">Autentisering & Session</h4>
                        <p class="text-sm text-slate-600">
                            Hanterar din inloggningsstatus. Denna cookie är temporär och raderas normalt när du stänger din webbläsare eller loggar ut från systemet.
                        </p>
                    </div>

                    <div class="pt-4 border-t border-gray-50">
                        <h4 class="text-sm font-black uppercase tracking-widest text-blue-600 mb-2">CSRF-skydd</h4>
                        <p class="text-sm text-slate-600">
                            En obligatorisk säkerhetsåtgärd i Radix som förhindrar "Cross-Site Request Forgery". Det garanterar att det faktiskt är du som utför de handlingar (som t.ex. att spara data) som skickas till systemet.
                        </p>
                    </div>

                    <div class="pt-4 border-t border-gray-50">
                        <h4 class="text-sm font-black uppercase tracking-widest text-slate-400 mb-2">Inställningar</h4>
                        <p class="text-sm text-slate-600">
                            Kommer ihåg dina personliga preferenser i gränssnittet, såsom valda filter i listvyer eller om du har accepterat systemets villkor.
                        </p>
                    </div>
                </div>
            </article>

            <!-- Sektion 3: Hantering -->
            <article class="bg-white border border-gray-200 p-8 rounded-3xl shadow-sm">
                <h3 class="text-xl font-bold text-slate-800 mb-4">Webbläsarinställningar</h3>
                <p class="text-sm text-slate-600 mb-6">
                    Radix kräver att cookies är aktiverade för att du ska kunna logga in. Du kan dock rensa din historik och dina cookies när som helst via din webbläsares inställningar.
                </p>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <a href="https://support.google.com/chrome/answer/95647?hl=sv" target="_blank" class="flex items-center justify-center p-3 border border-gray-100 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-50 hover:text-blue-600 transition-all">Chrome</a>
                    <a href="https://support.mozilla.org/sv/kb/kakor-information-webbplatser-lagrar-pa-din-dator" target="_blank" class="flex items-center justify-center p-3 border border-gray-100 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-50 hover:text-blue-600 transition-all">Firefox</a>
                    <a href="https://support.apple.com/sv-se/guide/safari/safari-manage-cookies-and-website-data-sfri11471/mac" target="_blank" class="flex items-center justify-center p-3 border border-gray-100 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-50 hover:text-blue-600 transition-all">Safari</a>
                    <a href="https://support.microsoft.com/sv-se/microsoft-edge/ta-bort-cookies-i-microsoft-edge-63947406-40ac-c3b8-57b9-2a946a29ae09" target="_blank" class="flex items-center justify-center p-3 border border-gray-100 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-50 hover:text-blue-600 transition-all">Edge</a>
                </div>
            </article>

            <div class="text-center pt-8">
                <p class="text-sm text-slate-400 italic">
                    Radix Engine följer gällande regler för elektronisk kommunikation. Läs mer på
                    <a href="https://www.pts.se/" class="text-blue-500 hover:underline" target="_blank">Post- och telestyrelsen</a>.
                </p>
            </div>
        </div>
      </div>
    </section>
{% endblock %}