{% extends "layouts/main.ratio.php" %}
{% block title %}Om Radix | Framtidens System{% endblock %}
{% block pageId %}about{% endblock %}
{% block body %}
    <section class="py-8">
      <div class="container-centered layout-aside-both [--aside-left-w:250px] [--aside-right-w:250px]">

        <!-- Vänster Sidebar: Filosofi & Fakta -->
        <aside class="area-aside-left sticky-top pt-6 lg:pt-4">
          <div class="space-y-6 lg:space-y-4">
            <div class="bg-white border border-gray-200 p-5 rounded-2xl shadow-sm">
              <h4 class="text-xs font-bold text-blue-600 uppercase tracking-widest mb-3">Vår Filosofi</h4>
              <p class="text-sm text-slate-600 leading-relaxed italic">"Att leverera en minimalistisk men kraftfull grund för webbapplikationer där prestanda och kontroll möts."</p>
            </div>

              <div class="bg-slate-900 p-5 rounded-2xl shadow-xl text-white">
                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Systemfakta</h4>
                <ul class="text-sm space-y-2">
                  <li class="flex justify-between border-b border-slate-800 pb-1">
                    <span class="text-slate-400">Core</span>
                    <span class="font-medium">PHP {{ PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION }}</span>
                  </li>
                  <li class="flex justify-between border-b border-slate-800 pb-1">
                    <span class="text-slate-400">Version</span>
                    <span class="font-medium text-blue-400">
                      {% if(isset($recentUpdates[0])) : %}
                        {{ $recentUpdates[0]->getAttribute('version') }}
                        {{ $recentUpdates[0]->getAttribute('is_major') ? 'Stable' : 'Update' }}
                      {% else : %}
                        v1.0.0
                      {% endif; %}
                    </span>
                  </li>
                  <li class="flex justify-between border-b border-slate-800 pb-1">
                    <span class="text-slate-400">Frontend</span>
                    <span class="font-medium text-blue-400">Tailwind v4</span>
                  </li>
              </ul>
            </div>
          </div>
        </aside>

        <!-- Huvudinnehåll: Berättelsen om Radix -->
        <div class="area-content lg:px-8">
          <div class="mb-10 text-center lg:text-left">
            <h1 class="text-4xl font-black text-slate-900 tracking-tight mb-4">Om Radix Systemet</h1>
            <div class="h-1.5 w-20 bg-blue-600 rounded-full mb-6 mx-auto lg:mx-0"></div>
            <p class="text-xl text-slate-500 leading-relaxed">Ett modernt, lättviktigt och högpresterande framework byggt för professionell webbutveckling.</p>
          </div>

          <div class="prose prose-slate max-w-none lg:space-y-6">
            <div class="bg-white border border-gray-200 p-8 rounded-3xl shadow-sm relative overflow-hidden">
              <!-- Dekorativt element -->
              <div class="absolute -right-10 -top-10 w-40 h-40 bg-blue-50 rounded-full opacity-50"></div>

              <h3 class="text-2xl font-bold text-slate-800 mb-4 relative z-10">Varför Radix?</h3>
              <p class="text-slate-600 relative z-10 leading-relaxed">
                Radix föddes ur behovet av att ha full kontroll över varje rad kod. Istället för att använda tunga, generiska bibliotek skapades en motor som är optimerad för hastighet och säkerhet. Från den egna routing-motorn till det skräddarsydda ORM-lagret är allt designat för att fungera i perfekt harmoni.
              </p>
              <p class="text-slate-600 relative z-10 leading-relaxed mt-4">
                Genom att kombinera styrkan i <strong>PHP {{ PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION }}</strong> med flexibiliteten i <strong>Tailwind CSS v4</strong> och <strong>Alpine.js</strong>, erbjuder Radix en unik miljö där utveckling går snabbt utan att tumma på slutresultatet.
              </p>
            </div>

            <!-- Funktionella Fördelar -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6 lg:mt-8">
                <div class="bg-blue-50 p-6 rounded-2xl border border-blue-100">
                    <h4 class="font-bold text-blue-900 mb-2">Total Kontroll</h4>
                    <p class="text-sm text-blue-800/80">Ingen "magi" i bakgrunden. Du styr din data, dina routes och dina vyer precis som du vill.</p>
                </div>
                <div class="bg-emerald-50 p-6 rounded-2xl border border-emerald-100">
                    <h4 class="font-bold text-emerald-900 mb-2">Prestanda</h4>
                    <p class="text-sm text-emerald-800/80">Byggt för att vara snabbt. Minimal memory usage och optimerad databashantering som standard.</p>
                </div>
            </div>
          </div>
        </div>

        <aside class="area-aside-right sticky-top pt-6 lg:pt-4">
          <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
            <div class="bg-slate-50 px-5 py-3 border-b border-gray-100">
                <h4 class="text-xs font-bold text-slate-500 uppercase tracking-widest">Systemuppdateringar</h4>
            </div>
            <div class="p-5 space-y-6">
              {% $i = 0; %}
              {% foreach($recentUpdates as $update) : %}
              <div class="relative pl-4 border-l-2 {{ ($i === 0) ? 'border-blue-500' : 'border-slate-200' }}">
                <time class="text-xxs font-bold {{ ($i === 0) ? 'text-blue-600' : 'text-slate-400' }} uppercase">
                  {% $dateVal = $update->getAttribute('released_at'); %}
                  {% $dt = $datetime->dateTime($dateVal); %}

                  {% if ($dt->format('Y-m-d') === date('Y-m-d')) : %}
                    Idag
                  {% else : %}
                    {% $months = [1=>'Januari','Februari','Mars','April','Maj','Juni','Juli','Augusti','September','Oktober','November','December']; %}
                    {{ $months[(int)$dt->format('n')] . ' ' . $dt->format('Y') }}
                  {% endif; %}
                </time>
                <h5 class="text-sm font-bold text-slate-800 mt-1">
                  <span class="{{ ($i === 0) ? 'text-blue-600' : 'text-slate-500' }} mr-1">{{ $update->getAttribute('version') }}</span>
                  {{ $update->getAttribute('title') }}
                </h5>
                <p class="text-xs text-slate-500 mt-1 leading-relaxed line-clamp-2">
                  {{ $update->getAttribute('description') }}
                </p>
              </div>
              {% $i++; %}
              {% endforeach; %}

              {% if(count($recentUpdates) > 0) : %}
                <div class="pt-4 mt-2 border-t border-slate-100">
                    <a href="{{ route('about.changelog') }}" class="text-xxs font-bold text-blue-600 hover:text-blue-800 uppercase tracking-widest transition-colors flex items-center gap-1">
                        Visa alla uppdateringar
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7" /></svg>
                    </a>
                </div>
              {% endif; %}
            </div>
          </div>
        </aside>
      </div>
    </section>
{% endblock %}