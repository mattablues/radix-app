{% extends "layouts/main.ratio.php" %}
{% block title %}{{ getenv('APP_NAME') ?: 'Radix System' }}{% endblock %}
{% block pageId %}home{% endblock %}
{% block body %}
    <!-- Hero Section -->
    <section class="relative overflow-hidden bg-slate-900 py-24 sm:py-32 rounded-b-[3rem] shadow-2xl">
      <!-- Dekorativt bakgrundsmönster (Grid) -->
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
            Framtidens Framework är här
          </span>

          <h1 class="text-4xl md:text-6xl lg:text-7xl font-black text-white tracking-tight mb-6 leading-tight">
            Bygg snabbare med
            <span class="text-3xl md:text-5xl lg:text-6xl text-blue-500 block mt-2 italic">
              Radix Engine {{ $latestVersion }}
            </span>
          </h1>

          <p class="text-xl text-slate-400 leading-relaxed mb-10 px-4">
            Ett skräddarsytt PHP-system optimerat för prestanda, säkerhet och total skalbarhet. Den perfekta grunden för dina mest ambitiösa webbprojekt.
          </p>

          <div class="flex flex-col sm:flex-row justify-center gap-4 px-6">
            <a href="{{ route('auth.login.index') }}" class="px-8 py-4 bg-blue-600 text-white font-bold rounded-2xl shadow-lg shadow-blue-500/20 hover:bg-blue-700 hover:-translate-y-1 transition-all">
              Kom igång nu
            </a>
            <a href="{{ route('about.index') }}" class="px-8 py-4 bg-white/10 text-white font-bold rounded-2xl backdrop-blur-md hover:bg-white/20 transition-all border border-white/10">
              Teknisk dokumentation
            </a>
          </div>
        </div>
      </div>
    </section>

    <!-- Funktioner / Fördelar -->
    <section class="py-24">
      <div class="container-centered">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

          <!-- Kort 1: Prestanda -->
          <div class="group p-8 bg-white border border-gray-100 rounded-[2rem] shadow-sm hover:shadow-xl hover:-translate-y-2 transition-all duration-300">
            <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-blue-600 group-hover:text-white transition-colors">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
            </div>
            <h3 class="text-xl font-bold text-slate-900 mb-3">Blixtsnabb Kernal</h3>
            <p class="text-slate-500 leading-relaxed">Radix är byggt med minimal overhead. Varje request hanteras med precision för att leverera svarstider i världsklass.</p>
          </div>

          <!-- Kort 2: Säkerhet -->
          <div class="group p-8 bg-white border border-gray-100 rounded-[2rem] shadow-sm hover:shadow-xl hover:-translate-y-2 transition-all duration-300">
            <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-emerald-600 group-hover:text-white transition-colors">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
            </div>
            <h3 class="text-xl font-bold text-slate-900 mb-3">Säkerhet i Fokus</h3>
            <p class="text-slate-500 leading-relaxed">Med inbyggt CSRF-skydd, säkra sessions-handlers och krypterad datahantering är din applikation trygg från start.</p>
          </div>

          <!-- Kort 3: Struktur -->
          <div class="group p-8 bg-white border border-gray-100 rounded-[2rem] shadow-sm hover:shadow-xl hover:-translate-y-2 transition-all duration-300">
            <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" /></svg>
            </div>
            <h3 class="text-xl font-bold text-slate-900 mb-3">Modern Arkitektur</h3>
            <p class="text-slate-500 leading-relaxed">Använder de senaste standarderna i PHP 8.3 och Tailwind CSS v4 för en utvecklarupplevelse som är både kraftfull och intuitiv.</p>
          </div>

        </div>
      </div>
    </section>

    <!-- Call to Action -->
    <section class="pb-24">
        <div class="container-centered px-4">
            <div class="bg-blue-600 rounded-[3rem] p-12 text-center text-white shadow-2xl shadow-blue-200">
                <h2 class="text-3xl font-black mb-4">Redo att skala upp?</h2>
                <p class="text-blue-100 mb-8 max-w-xl mx-auto text-lg">
                    Radix Systemet är modulärt och anpassningsbart. Oavsett om du bygger ett litet admin-verktyg eller en stor plattform, så är Radix svaret.
                </p>
                <a href="{{ route('contact.index') }}" class="inline-block px-10 py-4 bg-white text-blue-600 font-black rounded-2xl hover:scale-105 transition-transform shadow-lg">
                    Kontakta utvecklaren
                </a>
            </div>
        </div>
    </section>

        <!-- Technical Excellence / Test Results -->
        <section class="py-20 bg-slate-50 relative overflow-hidden">
          <!-- Subtilt mönster för att bryta av -->
          <div class="absolute right-0 top-0 w-64 h-64 bg-blue-100/50 rounded-full -translate-y-1/2 translate-x-1/2 blur-3xl"></div>

          <div class="container-centered relative z-10">
            <div class="bg-white border border-gray-100 rounded-[3rem] p-8 md:p-16 shadow-xl flex flex-col lg:flex-row items-center gap-12">

              <!-- Siffrorna -->
              <div class="flex gap-4 sm:gap-8">
                <div class="text-center">
                  <div class="size-24 sm:size-32 rounded-full border-4 border-emerald-500 flex items-center justify-center mb-4 bg-emerald-50 shadow-inner">
                    <span class="text-2xl sm:text-3xl font-black text-emerald-600">100%</span>
                  </div>
                  <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">MSI Score</span>
                </div>
                <div class="text-center">
                  <div class="size-24 sm:size-32 rounded-full border-4 border-blue-500 flex items-center justify-center mb-4 bg-blue-50 shadow-inner">
                    <span class="text-2xl sm:text-3xl font-black text-blue-600">100%</span>
                  </div>
                  <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Coverage</span>
                </div>
              </div>

              <!-- Texten -->
              <div class="flex-1 text-center lg:text-left">
                <span class="inline-block px-3 py-1 bg-emerald-100 text-emerald-700 text-[10px] font-bold uppercase tracking-widest rounded-full mb-4">
                  Kvalitetssäkrad kod
                </span>
                <h2 class="text-3xl md:text-4xl font-black text-slate-900 mb-6">Testkvalitet i världsklass</h2>
                <p class="text-slate-600 leading-relaxed mb-6">
                  Radix Framework är inte bara byggt för fart – det är byggt för att hålla. Med <strong>100% MSI (Mutation Score Indicator)</strong> på över 4000 mutationer är varje logisk rad kod verifierad.
                </p>
                <div class="flex flex-wrap justify-center lg:justify-start gap-4">
                  <div class="flex items-center gap-2 text-sm font-bold text-slate-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    Noll Deprecations
                  </div>
                  <div class="flex items-center gap-2 text-sm font-bold text-slate-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    Full Typsäkerhet
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>
{% endblock %}