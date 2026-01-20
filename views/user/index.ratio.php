{% extends "layouts/admin.ratio.php" %}
{% block title %}Visa konto{% endblock %}
{% block pageId %}user-index{% endblock %}
{% block searchId %}search-users{% endblock %}
{% block body %}
    <section>
      <div class="mb-8">
        <h1 class="text-3xl font-semibold mb-2">Profil</h1>
        <p class="text-gray-600">Detaljerad information om användarkontot.</p>
      </div>

      <div class="w-full max-w-3xl">
        <!-- Huvudkort -->
        <div class="overflow-hidden border border-gray-200 rounded-2xl bg-white shadow-sm">
          <div class="flex flex-col md:flex-row items-stretch">

            <!-- Vänster sida: Avatar -->
            <figure class="flex flex-col items-center justify-center p-8 bg-slate-50 border-b md:border-b-0 md:border-r border-gray-100 min-w-[220px]">
              <div class="relative">
                <img src="{{ versioned_file($currentUser->getAttribute('avatar')) }}" alt="Avatar" class="w-32 h-32 rounded-full object-cover ring-4 ring-white shadow-md">
                <div class="absolute bottom-1 right-1 w-5 h-5 border-4 border-white rounded-full {{ $currentUser->isOnline() ? 'bg-green-500' : 'bg-gray-300' }}"></div>
              </div>
              <figcaption class="mt-4 text-center">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider {{ $currentUser->isAdmin() ? 'bg-indigo-100 text-indigo-700' : 'bg-blue-50 text-blue-700' }}">
                  {{ $currentUser->fetchGuardedAttribute('role') }}
                </span>
              </figcaption>
            </figure>

            <!-- Höger sida: Information -->
            <div class="flex-1 p-6 md:p-8">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-12 gap-y-6">
                <div>
                  <dt class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-1">Namn</dt>
                  <dd class="text-base font-semibold text-gray-900">{{ $currentUser->getAttribute('first_name') }} {{ $currentUser->getAttribute('last_name') }}</dd>
                </div>

                <div>
                  <dt class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-1">E‑postadress</dt>
                  <dd class="text-base text-gray-900 break-all">{{ $currentUser->getAttribute('email') }}</dd>
                </div>

                <div>
                  <dt class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-1">Medlem sedan</dt>
                  <dd class="text-sm text-gray-600">{{ $currentUser->getAttribute('created_at') }}</dd>
                </div>

                <div>
                  <dt class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-1">Senast uppdaterad</dt>
                  <dd class="text-sm text-gray-600">{{ $currentUser->getAttribute('updated_at') }}</dd>
                </div>
              </div>

              <!-- Status-indikator -->
              <div class="mt-8 pt-6 border-t border-gray-50 flex items-center gap-4">
                <div class="flex flex-col">
                    <span class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-1">Kontostatus</span>
                    <span class="inline-flex items-center text-sm font-medium">
                        <span class="w-2 h-2 rounded-full mr-2 {{ $currentUser->getRelation('status')->getAttribute('status') === 'activated' ? 'bg-green-500' : 'bg-red-500' }}"></span>
                        {{ $currentUser->getRelation('status')->translateStatus($currentUser->getRelation('status')->getAttribute('status')) }}
                    </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Åtgärder nedanför kortet -->
        <div class="flex items-center justify-between mt-6 px-2">
          {% if($currentUser->hasAtLeast('moderator')) : %}
            <a href="{{ route('admin.user.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700 transition-colors flex items-center gap-1">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
              Tillbaka till konton
            </a>
          {% else : %}
            <a href="{{ route('dashboard.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700 transition-colors flex items-center gap-1">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
              </svg>
              Tillbaka till översikten
            </a>
          {% endif; %}

          <div class="flex gap-3">
            <a href="{{ route('user.edit') }}" class="inline-flex items-center px-6 py-2.5 border border-transparent text-sm font-bold bg-indigo-600 text-white hover:bg-indigo-700 transition-all rounded-lg shadow-md shadow-indigo-100">
              Redigera profil
            </a>
          </div>
        </div>

      <div class="w-full max-w-3xl mt-8 bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-black uppercase tracking-widest text-slate-400">Din API-nyckel</h3>

          {% $apiToken = $currentUser->getRelation('token'); %}

          <!-- Visa "Generera ny" uppe i hörnet om ingen giltig nyckel finns -->
          {% if(!$apiToken instanceof \App\Models\Token || !$apiToken->isValid()) : %}
            <form action="{{ route('user.token.create') }}" method="post">
              {{ csrf_field()|raw }}
              <button type="submit" class="text-xxs font-bold text-blue-600 uppercase tracking-widest hover:text-blue-800 transition-colors cursor-pointer">
                  Skapa ny nyckel
              </button>
            </form>
          {% endif; %}
        </div>

        {% if($apiToken instanceof \App\Models\Token && $apiToken->isValid()) : %}
          <!-- Giltig nyckel finns: Visa nyckeln och kopierings-logiken -->
          <div class="flex items-center gap-3" x-data="{
            copied: false,
            copy() {
              const el = document.createElement('textarea');
              el.value = '{{ $apiToken->value }}';
              document.body.appendChild(el);
              el.select();
              document.execCommand('copy');
              document.body.removeChild(el);
              this.copied = true;
              setTimeout(() => this.copied = false, 2000);
            }
          }">
            <code class="bg-slate-100 px-4 py-2 rounded-lg text-xs font-mono text-indigo-600 flex-1 truncate">
              {{ $apiToken->value }}
            </code>
            <button
              type="button"
              @click="copy()"
              class="text-xs font-bold uppercase tracking-widest transition-all cursor-pointer outline-hidden"
              :class="copied ? 'text-emerald-500 scale-110' : 'text-blue-600 hover:text-blue-800'"
            >
              <span x-text="copied ? 'Klar!' : 'Kopiera'"></span>
            </button>
          </div>
          <p class="mt-2 text-[10px] text-slate-400 italic">Giltig till: {{ $apiToken->expires_at }}</p>

          <!-- Alternativ för att byta ut en fungerande nyckel -->
          <form action="{{ route('user.token.create') }}" method="post" class="mt-4 pt-4 border-t border-gray-50">
              {{ csrf_field()|raw }}
              <button type="submit" class="text-[9px] font-bold text-gray-400 uppercase tracking-widest hover:text-red-500 transition-colors cursor-pointer" onclick="return confirm('Vill du verkligen generera en ny nyckel? Den gamla kommer sluta fungera direkt.')">
                  Ersätt befintlig nyckel
              </button>
          </form>
        {% else : %}
          <!-- Ingen nyckel finns: Visa ett tomt tillstånd -->
          <div class="bg-slate-50 border border-dashed border-gray-200 rounded-xl p-4 text-center">
            <p class="text-xs text-slate-500 italic">Ingen aktiv API-nyckel hittades.</p>
          </div>
        {% endif; %}
      </div>
    </section>
{% endblock %}