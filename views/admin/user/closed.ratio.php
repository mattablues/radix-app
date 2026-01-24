{% extends "layouts/admin.ratio.php" %}
{% block title %}Stängda konton{% endblock %}
{% block pageId %}admin-user-closed{% endblock %}
{% block searchId %}search-deleted-users{% endblock %}
{% block body %}
        <section x-data="{ openClosedModal: null, selectedUser: { id: null, email: '' } }">
          <div class="flex items-start justify-between gap-4 mb-6">
            <h1 class="text-3xl font-semibold">Stängda konton</h1>
            <div class="text-xs text-gray-600 hidden md:block mt-2 font-medium">
              {{ $users['pagination']['total'] ?? 0 }} totalt
            </div>
          </div>
          {% if($users['data']) : %}
          <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
            <div class="overflow-x-auto">
              <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/50 border-b border-gray-100">
                        <th class="px-4 py-4 text-[11px] font-bold uppercase tracking-wider text-gray-500 max-md:hidden">ID</th>
                        <th class="px-4 py-4 text-[11px] font-bold uppercase tracking-wider text-gray-500">Användare</th>
                        <th class="px-4 py-4 text-[11px] font-bold uppercase tracking-wider text-gray-500 max-sm:hidden">Status</th>
                        <th class="px-4 py-4 text-[11px] font-bold uppercase tracking-wider text-gray-500 max-sm:hidden">Senast aktiv</th>
                        <th class="px-4 py-4 text-[11px] font-bold uppercase tracking-wider text-gray-500 text-right">Åtgärder</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                  {% foreach($users['data'] as $user) : %}
                  <tr class="group hover:bg-emerald-50/30 transition-all duration-200">
                    <!-- ID -->
                    <td class="px-4 py-4 text-xs font-medium text-gray-400 max-md:hidden">
                      #{{ $user->getAttribute('id') }}
                    </td>

                    <!-- Namn & E-post -->
                    <td class="px-4 py-4">
                      <div class="flex flex-col">
                        <span class="text-sm font-semibold text-gray-900">
                          {{ $user->getAttribute('first_name') }} {{ $user->getAttribute('last_name') }}
                        </span>
                        <span class="text-xs text-gray-500">{{ $user->getAttribute('email') }}</span>
                      </div>
                    </td>

                    <!-- Status Badge (Visar oftast 'Stängt') -->
                    <td class="px-4 py-4 max-sm:hidden">
                      {% $status = $user->getRelation('status')->getAttribute('status'); %}
                      <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wide bg-gray-100 text-gray-600 border border-gray-200">
                        <span class="size-1.5 rounded-full bg-gray-400"></span>
                        {{ $user->getRelation('status')->translateStatus($status) }}
                      </span>
                    </td>

                    <!-- Aktivitet -->
                    <td class="px-4 py-4 max-sm:hidden">
                      <div class="flex flex-col text-xs text-gray-500">
                        <span class="font-medium italic">
                          Kontot är inaktivt
                        </span>
                        <span class="text-[10px] text-gray-400">
                          {{ $user->getAttribute('deleted_at') ?: 'Datum saknas' }}
                        </span>
                      </div>
                    </td>

                    <!-- Åtgärder -->
                    <td class="px-4 py-4 text-right">
                      <div class="flex items-center justify-end gap-2">
                        <button type="button"
                          x-on:click="selectedUser = { id: {{ $user->getAttribute('id') }}, email: '{{ addslashes($user->getAttribute('email')) }}' }; openClosedModal = true"
                          class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-100 rounded-lg transition-all"
                          title="Återställ konto">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                          </svg>
                          Återställ
                        </button>
                      </div>
                    </td>
                  </tr>
                  {% endforeach; %}
                </tbody>
              </table>
            </div>
          </div>
          <!-- Modal: Återställ konto -->
          <div
            x-show="openClosedModal"
            x-cloak
            x-on:keydown.escape.window="openClosedModal = false"
            role="dialog"
            aria-modal="true"
            class="fixed inset-0 z-50 overflow-y-auto"
          >
            <!-- Backdrop -->
            <div x-show="openClosedModal" x-transition.opacity class="fixed inset-0 bg-black/60"></div>

            <!-- Modal Container -->
            <div
              x-show="openClosedModal" x-transition
              x-on:click="openClosedModal = false"
              class="relative flex min-h-screen items-center justify-center p-4"
            >
              <div
                x-on:click.stop
                class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-gray-100"
              >
                <!-- Header med ikon -->
                <div class="flex items-center gap-3 text-emerald-500 mb-4">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.001 0 01-15.357-2m15.357 2H15" />
                  </svg>
                  <h2 class="text-xl font-bold text-gray-900">
                    Återställ konto
                  </h2>
                </div>

                <div class="space-y-3">
                  <p class="text-sm text-gray-600 leading-relaxed">
                    Detta kommer att återställa kontot för <strong class="text-gray-900" x-text="selectedUser.email"></strong>. En ny aktiveringslänk kommer att skickas till användaren automatiskt.
                  </p>
                  <p class="text-sm font-bold text-gray-800">
                    Vill du gå vidare med återställningen?
                  </p>
                </div>

                <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-50">
                  <button
                    type="button"
                    x-on:click="openClosedModal = false"
                    class="px-4 py-2 text-sm font-semibold text-gray-600 hover:text-gray-800 transition-colors cursor-pointer"
                  >
                    Avbryt
                  </button>

                  <form x-bind:action="'{{ route('admin.user.restore', ['id' => '__ID__']) }}?page={{ $users['pagination']['current_page'] }}'.replace('__ID__', selectedUser.id)" method="post">
                    {{ csrf_field()|raw }}
                    <button
                      type="submit"
                      x-on:click="openClosedModal = false"
                      class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-6 py-2 text-sm font-bold text-white hover:bg-indigo-700 shadow-md shadow-indigo-100 transition-all active:scale-[0.98] cursor-pointer"
                    >
                      Ja, återställ konto
                    </button>
                  </form>
                </div>
              </div>
            </div>
          </div>
          <!-- End Modal -->
{% if($users['pagination']['total'] > $users['pagination']['per_page']) : %}
          <div class="flex flex-wrap items-center justify-between gap-3 mt-4">
            <span class="block text-xs font-medium text-gray-600">{{ $users['pagination']['total'] }} totalt</span>
            <span class="block text-xs font-medium text-gray-600">Sida {{ $users['pagination']['current_page'] }} av {{ calculate_total_pages($users['pagination']['total'], $users['pagination']['per_page']) }}</span>
          </div>
          <div class="mt-2">
            {{ paginate_links($users['pagination'], 'admin.user.closed', 2)|raw }}
          </div>
{% endif; %}
{% else : %}
        <div class="bg-white border border-dashed border-gray-300 rounded-2xl p-12 text-center text-gray-500">
            Inga stängda konton registrerade.
        </div>
{% endif; %}
        </section>
{% endblock %}