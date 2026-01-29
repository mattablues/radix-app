{% extends "layouts/admin.ratio.php" %}
{% block title %}API-nyckel{% endblock %}
{% block pageId %}user-api-token{% endblock %}
{% block searchId %}search-users{% endblock %}
{% block body %}
  <section>
    <div class="mb-8">
      <h1 class="text-3xl font-semibold mb-2">API-nyckel</h1>
      <p class="text-gray-600">Används för externa API-anrop (t.ex. curl/PowerShell/integrationer). Admin-UI använder session.</p>
    </div>

    <div class="w-full max-w-3xl bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-black uppercase tracking-widest text-slate-400">Din API-nyckel</h3>

        {% $apiToken = $currentUser->getRelation('token'); %}

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

        <form action="{{ route('user.token.create') }}" method="post" class="mt-4 pt-4 border-t border-gray-50">
          {{ csrf_field()|raw }}
          <button
            type="submit"
            class="text-[9px] font-bold text-gray-400 uppercase tracking-widest hover:text-red-500 transition-colors cursor-pointer"
            onclick="return confirm('Vill du verkligen generera en ny nyckel? Den gamla kommer sluta fungera direkt.')"
          >
            Ersätt befintlig nyckel
          </button>
        </form>
      {% else : %}
        <div class="bg-slate-50 border border-dashed border-gray-200 rounded-xl p-4 text-center">
          <p class="text-xs text-slate-500 italic">Ingen aktiv API-nyckel hittades.</p>
        </div>
      {% endif; %}
    </div>
  </section>
{% endblock %}