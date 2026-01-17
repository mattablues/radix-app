{% extends "layouts/admin.ratio.php" %}
{% block title %}Dashboard | Radix Control{% endblock %}
{% block pageId %}dashboard{% endblock %}
{% block searchId %}search-users{% endblock %}
{% block body %}
    <section>
      <!-- Välkomsthälsning -->
      <div class="mb-8">
        <h1 class="text-3xl font-black text-slate-900 tracking-tight">Systemöversikt 👋</h1>
        <p class="text-slate-500">Välkommen till Radix kontrollpanel. Här ser du aktuell status för din applikation.</p>
      </div>

      <!-- Statistik-översikt (System-KPIs) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-10">
        <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm hover:shadow-md transition-shadow">
          <dt class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Användare</dt>
          <dd class="text-2xl font-black text-slate-900">{{ $userCount }}</dd>
        </div>

        <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm hover:shadow-md transition-shadow">
          <dt class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">PHP Version</dt>
          <dd class="text-2xl font-black text-blue-600">{{ PHP_VERSION }}</dd>
        </div>

        <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm hover:shadow-md transition-shadow">
          <dt class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Memory Limit</dt>
          <dd class="text-2xl font-black text-slate-900">
            {{ ini_get('memory_limit') }}
          </dd>
        </div>

        <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm hover:shadow-md transition-shadow">
          <dt class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Build Status</dt>
          <dd class="flex items-center gap-2 text-2xl font-black text-emerald-600">
            <span class="size-2 bg-emerald-500 rounded-full animate-pulse"></span>
            v1.0.0
          </dd>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Huvudkolumn: Systemlogg / Senaste händelser -->
        <div class="lg:col-span-2">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-slate-800">Senaste händelser</h2>
            {% if($currentUser->hasAtLeast('moderator')) : %}
              <a href="#" class="text-[10px] font-bold text-blue-600 uppercase tracking-wider hover:text-blue-800">Visa alla loggar</a>
            {% endif %}
          </div>

          <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-sm">
            <table class="w-full text-left border-collapse">
              <thead class="bg-gray-50/50 border-b border-gray-100">
                <tr>
                  <th class="px-6 py-3 text-[10px] font-bold text-gray-400 uppercase">Tidstämpel</th>
                  <th class="px-6 py-3 text-[10px] font-bold text-gray-400 uppercase">Händelse</th>
                  <th class="px-6 py-3 text-[10px] font-bold text-gray-400 uppercase text-right">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-50">
                <tr class="group hover:bg-blue-50/30 transition-colors">
                  <td class="px-6 py-4 text-xs text-slate-500">{{ date('Y-m-d H:i') }}</td>
                  <td class="px-6 py-4 text-sm font-semibold text-slate-900 group-hover:text-blue-600 transition-colors">Systemet redo för drift</td>
                  <td class="px-6 py-4 text-xs text-right">
                    <span class="px-2 py-1 bg-emerald-50 text-emerald-600 rounded-md font-bold uppercase text-[9px]">Info</span>
                  </td>
                </tr>
                {% if($currentUser->hasAtLeast('moderator')) : %}
                <tr class="group hover:bg-blue-50/30 transition-colors">
                    <td class="px-6 py-4 text-xs text-slate-500">{{ date('Y-m-d H:i', strtotime('-1 hour')) }}</td>
                    <td class="px-6 py-4 text-sm font-semibold text-slate-900 group-hover:text-blue-600 transition-colors">Cache rensad via CLI</td>
                    <td class="px-6 py-4 text-xs text-right">
                      <span class="px-2 py-1 bg-blue-50 text-blue-600 rounded-md font-bold uppercase text-[9px]">System</span>
                    </td>
                </tr>
                {% endif %}
              </tbody>
            </table>
          </div>
        </div>

        <!-- Sidebar: Verktyg & Hälsa -->
        <div class="space-y-6">
          {% if($currentUser->hasAtLeast('moderator')) : %}
            <h2 class="text-xl font-bold text-slate-800 mb-4">Administration</h2>

            <div class="grid grid-cols-1 gap-3">
              <a href="{{ route('admin.user.index') }}" class="flex items-center gap-3 p-4 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-all shadow-md shadow-blue-100 group">
                <div class="p-2 bg-blue-500 rounded-lg group-hover:scale-110 transition-transform">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                </div>
                <span class="font-bold text-sm">Hantera Konton</span>
              </a>

              <a href="#" class="flex items-center gap-3 p-4 bg-white border border-gray-100 text-slate-700 rounded-xl hover:border-blue-300 hover:bg-blue-50 transition-all group">
                <div class="p-2 bg-slate-100 text-slate-500 rounded-lg group-hover:bg-blue-100 group-hover:text-blue-600 transition-colors">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                </div>
                <span class="font-bold text-sm">Systemkonfiguration</span>
              </a>
            </div>

            <!-- Systemhälsa -->
            <div class="bg-slate-900 rounded-2xl p-6 text-white shadow-xl overflow-hidden relative mt-6">
              <div class="absolute -right-4 -top-4 size-24 bg-blue-500/10 rounded-full"></div>
              <h3 class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-4 relative z-10">Serverhälsa</h3>
              <div class="flex items-center gap-3 mb-6 relative z-10">
                  <div class="size-2 bg-emerald-500 rounded-full animate-pulse"></div>
                  <span class="text-sm font-medium text-slate-200">Systemstatus: OK</span>
              </div>
              <a href="{{ route('admin.health.index') }}" class="inline-flex items-center text-[10px] font-bold text-slate-400 hover:text-white transition-colors uppercase tracking-wider relative z-10">
                  Visa hälsorapport
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-3 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7" /></svg>
              </a>
            </div>
          {% else : %}
            <!-- Information för vanliga användare -->
            <div class="bg-blue-50 border border-blue-100 rounded-2xl p-6 text-blue-900 shadow-sm">
              <h3 class="text-sm font-bold mb-2 uppercase tracking-tight">Välkommen till Radix</h3>
              <p class="text-xs leading-relaxed opacity-80">
                Du är inloggad som standardanvändare. Din roll ger dig tillgång till applikationens grundfunktioner. Om du behöver administrativa behörigheter, vänligen kontakta systemansvarig.
              </p>
            </div>
          {% endif %}
        </div>
      </div>
    </section>
{% endblock %}