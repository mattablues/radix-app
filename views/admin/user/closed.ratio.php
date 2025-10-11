{% extends "layouts/admin.ratio.php" %}
{% block title %}Admin user index{% endblock %}
{% block pageId %}admin-user{% endblock %}
{% block searchId %}search-users{% endblock %}
{% block body %}
    <section x-data="{ openClosedModal: null, selectedUser: { id: null, email: '' } }">
      <h1 class="text-3xl mb-8">Stängda konton</h1>
{% if($users['data']) : %}
      <table class="w-full">
        <thead>
          <tr class="text-left border-b border-gray-200">
            <th data-cell="id" class="px-1.5 md:px-3 py-2.5 text-sm max-md:hidden">ID</th>
            <th data-cell="namn" class="px-1.5 md:px-3 py-2.5 text-sm max-md:hidden">Namn</th>
            <th data-cell="e-post" class="px-1.5 md:px-3 py-2.5 text-sm max-md:hidden">E-postadress</th>
            <th data-cell="status" class="px-1.5 md:px-3 py-2.5 text-sm max-md:hidden">Status</th>
            <th data-cell="aktiv" class="px-1.5 md:px-3 py-2.5 text-sm max-md:hidden">Aktiv</th>
            <th data-cell="åtgärd" class="px-1.5 md:px-3 py-2.5 text-sm max-md:hidden">Åtgärd</th>
          </tr>
        </thead>
        <tbody>
{% foreach($users['data'] as $user) : %}
          <tr class="text-left border-b border-gray-200 hover:bg-gray-100 even:bg-white odd:bg-gray-50">
            <td data-cell="id" class=" px-1.5 md:px-3 py-2.5 max-md:py-1.5 text-sm max-md:before:content-[attr(data-cell)] max-md:grid max-md:grid-cols-[1fr_2fr] max-md:gap-1 max-md:before:font-semibold max-md:before:text-sm max-md:before:capitalize">{{ $user->getAttribute('id') }}</td>
            <td data-cell="namn" class=" px-1.5 md:px-3 py-2.5 max-md:py-1.5 text-sm max-md:before:content-[attr(data-cell)] max-md:grid max-md:grid-cols-[1fr_2fr] max-md:gap-1 max-md:before:font-semibold max-md:before:text-sm max-md:before:capitalize">{{ $user->getAttribute('first_name') }} {{ $user->getAttribute('last_name') }}</td>
            <td data-cell="e-post" class=" px-1.5 md:px-3 py-2.5 max-md:py-1.5 text-sm max-md:before:content-[attr(data-cell)] max-md:grid max-md:grid-cols-[1fr_2fr] max-md:gap-1 max-md:before:font-semibold max-md:before:text-sm max-md:before:capitalize">{{ $user->getAttribute('email') }}</td>
            <td data-cell="status" class="px-1.5 md:px-3 py-2.5 max-md:py-1.5 text-sm max-md:before:content-[attr(data-cell)] max-md:grid max-md:grid-cols-[1fr_2fr] max-md:gap-1 max-md:before:font-semibold max-md:before:text-sm max-md:before:capitalize"><div class="flex items-center text-xs"><span class="{{ $user->getRelation('status')->getAttribute('status') }} inline-block px-2 rounded-lg">{{ $user->getRelation('status')->getAttribute('status')  }}</span></div></td>
            <td data-cell="aktiv" class=" px-1.5 md:px-3 py-2.5 max-md:py-1.5 text-sm max-md:before:content-[attr(data-cell)] max-md:grid max-md:grid-cols-[1fr_2fr] max-md:gap-1 max-md:before:font-semibold max-md:before:text-sm max-md:before:capitalize"><div class="flex items-center text-xs rounded-lg"><span class="{{ $user->getRelation('status')->getAttribute('active') }} inline-block px-2 rounded-lg">{{ $user->getRelation('status')->getAttribute('active') }}</span></div></td>
            <td data-cell="åtgärd" class=" px-1.5 md:px-3 py-2.5 max-md:py-1.5 text-sm max-md:before:content-[attr(data-cell)] max-md:grid max-md:grid-cols-[1fr_2fr] max-md:gap-1 max-md:before:font-semibold max-md:before:text-sm max-md:before:capitalize">
              <div class="flex items-center gap-1.5">
                <button
                  type="button"
                  x-on:click="selectedUser = { id: {{ $user->getAttribute('id') }}, email: '{{ addslashes($user->getAttribute('email')) }}' }; openClosedModal = true"
                  class="text-xs font-semibold bg-green-600 text-white py-1 px-1.5 rounded-lg cursor-pointer hover:bg-green-700 transition-colors duration-300"
                >
                  Återställ
                </button>
              </div>
            </td>
          </tr>
{% endforeach; %}
        </tbody>
      </table>
      <div
        x-show="openClosedModal"
        x-cloak
        x-on:keydown.escape.window="openClosedModal = false"
        role="dialog"
        aria-modal="true"
        x-id="['modal-title']"
        :aria-labelledby="$id('modal-title')"
        class="fixed inset-0 z-50 overflow-y-auto"
      >
        <div x-show="openClosedModal" x-transition.opacity class="fixed inset-0 bg-black/60"></div>
        <div
          x-show="openClosedModal" x-transition
          x-on:click="openClosedModal = false"
          class="relative flex min-h-screen items-center justify-center p-4"
        >
          <div
            x-on:click.stop
            class="relative min-w-80 max-w-xl rounded-xl bg-white p-6 shadow-lg"
          >
            <h2 class="flex items-center gap-1.5 text-gray-800 mb-2" :id="$id('modal-title')">
              <span class="text-2xl text-gray-700">Återställ konto</span>
            </h2>

            <p class="mt-3 mb-2 px-1 text-[15px] text-gray-700 max-w-sm">
              Detta kommer att återställa kontot <strong x-text="selectedUser.email"></strong> och skicka en aktiveringslänk.
            </p>
            <p class="mt-3 mb-4 px-1 text-[14px] text-gray-700 max-w-sm">Är du säker på att du vill fortsätta?</p>

            <form x-bind:action="'{{ route('admin.user.restore', ['id' => $user->getAttribute('id')]) }}?page={{ $users['pagination']['current_page'] }}'.replace('__ID__', selectedUser.id)" method="post" class="mt-3 flex justify-end space-x-2">
              {{ csrf_field()|raw }}
              <button type="submit" x-on:click="openClosedModal = false" class="relative flex items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-transparent bg-green-600 px-4 py-1.5 text-white hover:bg-green-700 transition-colors duration-300">
                Återställ
              </button>
              <button type="button" x-on:click="openClosedModal = false" class="relative flex items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-gray-800/20 bg-transparent px-4 py-1.5 text-gray-800 hover:bg-gray-800/5 transition-colors duration-300">
                Avbryt
              </button>
            </form>
          </div>
        </div>
      </div>
      <!-- End Modal -->
{% if($users['pagination']['total'] > $users['pagination']['per_page']) : %}
      <p class="mb-10 text-right text-xs font-bold pr-1">sida {{ $users['pagination']['current_page'] }} av {{ $users['pagination']['total'] / $users['pagination']['per_page'] }}</p>
      {{ paginate_links($users['pagination'], 'admin.user.closed', 2)|raw }}
{% endif; %}
{% else : %}
      <p>Inga stängda konton hittades.</p>
{% endif; %}
    </section>
{% endblock %}