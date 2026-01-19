{% extends "layouts/admin.ratio.php" %}
{% block title %}Händelselogg{% endblock %}
{% block pageId %}admin-events-index{% endblock %}
{% block searchId %}search-users{% endblock %}
{% block body %}
    <section>
      <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-semibold">Systemhändelser</h1>
            <p class="text-sm text-gray-500 mt-1">Översikt över aktiviteter och händelser i systemet.</p>
        </div>
        <div class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-3">
          {{ $events['pagination']['total'] ?? 0 }} loggar totalt
        </div>
      </div>

      {% if($events['data']) : %}
      <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50/50 border-b border-gray-100">
                    <th class="px-4 py-4 text-[11px] font-bold uppercase tracking-wider text-gray-500">Tidstämpel</th>
                    <th class="px-4 py-4 text-[11px] font-bold uppercase tracking-wider text-gray-500">Typ</th>
                    <th class="px-4 py-4 text-[11px] font-bold uppercase tracking-wider text-gray-500">Händelse</th>
                    <th class="px-4 py-4 text-[11px] font-bold uppercase tracking-wider text-gray-500 max-sm:hidden">Användare</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              {% foreach($events['data'] as $event) : %}
              <tr class="group hover:bg-blue-50/30 transition-all duration-200">
                <td class="px-4 py-4 whitespace-nowrap">
                  <span class="text-xs font-medium text-gray-500">{{ $event->created_at }}</span>
                </td>
                <td class="px-4 py-4">
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-black uppercase border {{ $event->getTypeBadgeClass() }}">
                    {{ $event->type }}
                  </span>
                </td>
                <td class="px-4 py-4">
                  <span class="text-sm font-semibold text-slate-800">{{ $event->message }}</span>
                </td>
                <td class="px-4 py-4 max-sm:hidden">
                  <?php
                    $eventUser = $event->getRelation('user');
                    if (!$eventUser instanceof \App\Models\User && method_exists($event, 'user')) {
                        $eventUser = $event->user()->first();
                    }
                  ?>
                  {% if($eventUser instanceof \App\Models\User) : %}
                    <span class="text-xs font-medium text-indigo-600">{{ $eventUser->first_name }} {{ $eventUser->last_name }}</span>
                  {% else : %}
                    <span class="text-[10px] font-bold text-gray-300 uppercase tracking-widest">System</span>
                  {% endif; %}
                </td>
              </tr>
              {% endforeach; %}
            </tbody>
          </table>
        </div>
      </div>

      {% if($events['pagination']['total'] > $events['pagination']['per_page']) : %}
          <div class="mt-6">
            {{ paginate_links($events['pagination'], 'admin.system-event.index', 2)|raw }}
          </div>
      {% endif; %}
      {% else : %}
        <div class="bg-white border border-dashed border-gray-300 rounded-2xl p-12 text-center text-gray-500">
            Inga händelser har loggats ännu.
        </div>
      {% endif; %}
    </section>
{% endblock %}