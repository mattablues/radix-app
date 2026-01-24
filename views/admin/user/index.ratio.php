{% extends "layouts/admin.ratio.php" %}
{% block title %}Konton{% endblock %}
{% block pageId %}admin-users-index{% endblock %}
{% block searchId %}search-users{% endblock %}
{% block body %}
        <section x-data="{ openBlockModal: null, selectedUser: { id: null, email: '' } }">
          <div class="flex items-start justify-between gap-4 mb-6">
            <h1 class="text-3xl font-semibold">Konton</h1>
            <div class="text-xs text-gray-600 font-medium hidden md:block mt-3">
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
                  <tr class="group hover:bg-blue-50/30 transition-all duration-200">
                    <!-- ID -->
                    <td class="px-4 py-4 text-xs font-medium text-gray-400 max-md:hidden">
                      #{{ $user->getAttribute('id') }}
                    </td>

                    <!-- Namn & E-post -->
                    <td class="px-4 py-4">
                      <div class="flex flex-col">
                        <a href="{{ route('user.show', ['id' => $user->getAttribute('id')]) }}"
                         class="text-sm font-semibold text-gray-900 group-hover:text-blue-600 transition-colors">
                          {{ $user->getAttribute('first_name') }} {{ $user->getAttribute('last_name') }}
                        </a>
                        <span class="text-xs text-gray-500">{{ $user->getAttribute('email') }}</span>
                      </div>
                    </td>

                    <!-- Status Badge -->
                    <td class="px-4 py-4 max-sm:hidden">
                      {% $status = $user->getRelation('status')->getAttribute('status'); %}
                      <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wide
                        {{ $status === 'activated' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : '' }}
                        {{ $status === 'blocked' ? 'bg-red-50 text-red-700 border border-red-100' : '' }}
                        {{ !in_array($status, ['activated', 'blocked']) ? 'bg-amber-50 text-amber-700 border border-amber-100' : '' }}">
                        <span class="size-1.5 rounded-full {{ $status === 'activated' ? 'bg-emerald-500' : ($status === 'blocked' ? 'bg-red-500' : 'bg-amber-500') }}"></span>
                        {{ $user->getRelation('status')->translateStatus($status) }}
                      </span>
                    </td>

                    <!-- Aktivitet -->
                    <td class="px-4 py-4 max-sm:hidden">
                      <div class="flex flex-col">
                        <span class="text-xs font-medium {{ $user->getRelation('status')->getAttribute('active') === 'online' ? 'text-emerald-600' : 'text-gray-600' }}">
                          {{ ucfirst($user->getRelation('status')->getAttribute('active')) }}
                        </span>
                        <span class="text-[10px] text-gray-400 italic">
                          {{ $user->getRelation('status')->getAttribute('active_at') ?: 'Aldrig' }}
                        </span>
                      </div>
                    </td>

                    <!-- Åtgärder -->
                    <td class="px-4 py-4 text-right">
                      <div class="flex items-center justify-end gap-2">
                        {% if($user->isAdmin()) : %}
                          <span class="p-1.5 text-gray-300" title="Admin kan ej ändras här">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                          </span>
                        {% else : %}
                          <form action="{{ route('admin.user.send-activation', ['id' => $user->getAttribute('id')]) }}?page={{ $users['pagination']['current_page'] }}" method="post" class="inline">
                            {{ csrf_field()|raw }}
                            <button class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all" title="Skicka aktivering">
                              <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                            </button>
                          </form>

                          {% if($user->getRelation('status')->getAttribute('status') !== 'blocked') : %}
                            <button type="button"
                              x-on:click="selectedUser = { id: {{ $user->getAttribute('id') }}, email: '{{ addslashes($user->getAttribute('email')) }}' }; openBlockModal = true"
                              class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all" title="Blockera användare">
                              <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728A9 9 0 115.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                            </button>
                          {% endif; %}
                        {% endif; %}
                      </div>
                    </td>
                  </tr>
                  {% endforeach; %}
                </tbody>
              </table>
            </div>
          </div>
          <!-- Modal: Blockera konto -->
          <div
            x-show="openBlockModal"
            x-cloak
            x-on:keydown.escape.window="openBlockModal = false"
            role="dialog"
            aria-modal="true"
            class="fixed inset-0 z-50 overflow-y-auto"
          >
            <!-- Backdrop -->
            <div x-show="openBlockModal" x-transition.opacity class="fixed inset-0 bg-black/60"></div>

            <!-- Modal Container -->
            <div
              x-show="openBlockModal" x-transition
              x-on:click="openBlockModal = false"
              class="relative flex min-h-screen items-center justify-center p-4"
            >
              <div
                x-on:click.stop
                class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-gray-100"
              >
                <!-- Header med ikon -->
                <div class="flex items-center gap-3 text-red-600 mb-4">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728A9 9 0 115.636 5.636m12.728 12.728L5.636 5.636" />
                  </svg>
                  <h2 class="text-xl font-bold text-gray-900">
                    Blockera konto
                  </h2>
                </div>

                <div class="space-y-3">
                  <p class="text-sm text-gray-600 leading-relaxed">
                    Detta kommer att spärra användaren <strong class="text-gray-900" x-text="selectedUser.email"></strong> från att logga in i systemet tills vidare.
                  </p>
                  <p class="text-sm font-bold text-gray-800">
                    Är du säker på att du vill fortsätta?
                  </p>
                </div>

                <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-50">
                  <button
                    type="button"
                    x-on:click="openBlockModal = false"
                    class="px-4 py-2 text-sm font-semibold text-gray-600 hover:text-gray-800 transition-colors cursor-pointer"
                  >
                    Avbryt
                  </button>

                  <form x-bind:action="'{{ route('admin.user.block', ['id' => '__ID__']) }}?page={{ $users['pagination']['current_page'] }}'.replace('__ID__', selectedUser.id)" method="post">
                    {{ csrf_field()|raw }}
                    <button
                      type="submit"
                      x-on:click="openBlockModal = false"
                      class="inline-flex items-center justify-center rounded-lg bg-red-600 px-6 py-2 text-sm font-bold text-white hover:bg-red-700 shadow-md shadow-red-100 transition-all active:scale-[0.98] cursor-pointer"
                    >
                      Ja, blockera konto
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
            {{ paginate_links($users['pagination'], 'admin.user.index', 2)|raw }}
          </div>
{% endif; %}
{% else : %}
        <div class="bg-white border border-dashed border-gray-300 rounded-2xl p-12 text-center text-gray-500">
            Inga konton registrerade.
        </div>
{% endif; %}
        </section>
{% endblock %}