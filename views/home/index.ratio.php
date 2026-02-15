{% extends "layouts/main.ratio.php" %}
{% block title %}{{ getenv('APP_NAME') ?: 'Radix System' }}{% endblock %}
{% block pageId %}home{% endblock %}
{% block body %}
    <!-- Hero: neutral starter-info -->
    <section class="relative overflow-hidden bg-slate-900 py-20 sm:py-28 rounded-b-[3rem] shadow-2xl">
        <div class="absolute inset-0 opacity-10 pointer-events-none">
            <svg class="h-full w-full" fill="none" viewBox="0 0 400 400" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                        <path d="M 40 0 L 0 0 0 40" fill="none" stroke="white" stroke-width="1"/>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#grid)" />
            </svg>
        </div>

        <div class="container-centered relative z-10">
            <div class="text-center max-w-3xl mx-auto">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-widest bg-blue-500/20 text-blue-300 border border-blue-500/30 mb-6">
                    Minimal starter för Radix
                </span>

                <h1 class="text-4xl md:text-6xl font-black text-white tracking-tight mb-4 leading-tight">
                    Välkommen till
                    <span class="block mt-2 italic text-blue-400">
                        {{ getenv('APP_NAME') ?: 'Radix App Starter' }}
                    </span>
                </h1>

                <p class="text-sm font-mono text-slate-400 mb-6">
                    Version: {{ $latestVersion ?? 'v1.0.0' }}
                </p>

                <p class="text-lg md:text-xl text-slate-300 leading-relaxed mb-10 px-4">
                    Den här installationen är avskalad som standard – bara publika sidor och core‑funktioner.
                    Lägg till autentisering, användare och admin-panel när du vill, via CLI‑kommandon.
                </p>

                <div class="flex flex-col sm:flex-row justify-center gap-4 px-6">
                    <a href="{{ route('about.index') }}" class="px-8 py-3 bg-blue-600 text-white font-bold rounded-2xl shadow-lg shadow-blue-500/20 hover:bg-blue-700 hover:-translate-y-0.5 transition-all">
                        Läs om systemet
                    </a>
                    <a href="{{ route('contact.index') }}" class="px-8 py-3 bg-white/10 text-white font-bold rounded-2xl backdrop-blur-md hover:bg-white/20 transition-all border border-white/10">
                        Kontakta utvecklaren
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Kort beskrivning: hur man aktiverar features -->
    <section class="py-16">
        <div class="container-centered">
            <div class="max-w-3xl mx-auto text-center mb-10">
                <h2 class="text-2xl md:text-3xl font-black text-slate-900 mb-4">Aktivera fler features när du vill</h2>
                <p class="text-slate-600 leading-relaxed">
                    Radix‑appen är tänkt att vara minimal när du klonar den. När du är redo kan du lägga till
                    autentisering, användarprofiler och admin‑gränssnitt med färdiga scaffold‑kommandon.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="p-6 bg-white rounded-2xl border border-gray-100 shadow-sm">
                    <h3 class="text-sm font-bold text-slate-900 mb-2 uppercase tracking-widest">Auth</h3>
                    <p class="text-sm text-slate-600 mb-4">
                        Lägg till login, registrering och återställning av lösenord med ett enkelt kommando.
                    </p>
                    <pre class="text-[11px] bg-slate-900 text-slate-100 rounded-xl p-3 text-left overflow-x-auto"><code>php radix scaffold:install routes/auth</code></pre>
                </div>

                <div class="p-6 bg-white rounded-2xl border border-gray-100 shadow-sm">
                    <h3 class="text-sm font-bold text-slate-900 mb-2 uppercase tracking-widest">User</h3>
                    <p class="text-sm text-slate-600 mb-4">
                        Aktivera användarprofil, dashboard och kontohantering för inloggade användare.
                    </p>
                    <pre class="text-[11px] bg-slate-900 text-slate-100 rounded-xl p-3 text-left overflow-x-auto"><code>php radix scaffold:install routes/user</code></pre>
                </div>

                <div class="p-6 bg-white rounded-2xl border border-gray-100 shadow-sm">
                    <h3 class="text-sm font-bold text-slate-900 mb-2 uppercase tracking-widest">Admin</h3>
                    <p class="text-sm text-slate-600 mb-4">
                        Lägg till admin‑panel med användarhantering, systemloggar och uppdateringar.
                    </p>
                    <pre class="text-[11px] bg-slate-900 text-slate-100 rounded-xl p-3 text-left overflow-x-auto"><code>php radix scaffold:install routes/admin</code></pre>
                </div>
            </div>
        </div>
    </section>

    <!-- Liten CTA i slutet -->
    <section class="pb-20">
        <div class="container-centered">
            <div class="bg-slate-900 text-white rounded-[2.5rem] p-10 md:p-12 text-center shadow-2xl">
                <h2 class="text-2xl md:text-3xl font-black mb-3">Redo att bygga vidare?</h2>
                <p class="text-sm md:text-base text-slate-200 mb-6 max-w-xl mx-auto">
                    Börja med de publika sidorna, lägg sedan till auth, user och admin när applikationen kräver det.
                    Radix‑startert är gjort för att växa i takt med ditt projekt.
                </p>
                <a href="{{ route('about.index') }}" class="inline-block px-8 py-3 bg-white text-slate-900 font-bold rounded-2xl hover:scale-105 transition-transform shadow-lg">
                    Läs mer i dokumentationen
                </a>
            </div>
        </div>
    </section>
{% endblock %}
