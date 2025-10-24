{% extends "layouts/admin.ratio.php" %}
{% block title %}Konto{% endblock %}
{% block pageId %}show-user{% endblock %}
{% block searchId %}search-users{% endblock %}
{% block body %}
    <section x-data="{ openRoleModal: false, selectedRole: '{{ $user ? $user->fetchGuardedAttribute('role') : null }}' }">
      <h1 class="text-3xl font-semibold mb-8">Konto</h1>
{% if($user) : %}
      <div class="w-full md:max-w-[600px] flex justify-between border border-gray-200 rounded px-3 sm:px-5 py-3">
        <ul>
          <li class="flex items-start gap-1 sm:gap-2 my-1">
            <span class="shrink-0 inline-block w-24 md:w-28 text-sm font-semibold text-gray-700">Namn:</span>
            <span class="inline-block text-sm text-gray-700">{{ $user->getAttribute('first_name') }} {{ $currentUser->getAttribute('last_name') }}</span>
          </li>
          <li class="flex items-start gap-1 sm:gap-2 my-1">
            <span class="shrink-0 inline-block w-24 md:w-28 text-sm font-semibold text-gray-700">E-post:</span>
            <span class="inline-block text-sm text-gray-700">{{ $user->getAttribute('email') }}</span>
          </li>
          <li class="flex items-start gap-1 sm:gap-2 my-1">
            <span class="shrink-0 inline-block w-24 md:w-28 text-sm font-semibold text-gray-700">Skapat:</span>
            <span class="inline-block text-sm text-gray-700">{{ $user->getAttribute('created_at') }}</span>
          </li>
          <li class="flex items-start gap-1 sm:gap-2 my-1">
            <span class="shrink-0 inline-block w-24 md:w-28 text-sm font-semibold text-gray-700">Senast aktiv:</span>
          {% if($user->getRelation('status')->getAttribute('active_at')) : %}
            <span class="inline-block text-sm text-gray-700">
              {{ $datetime->frame($user->getRelation('status')->getAttribute('active_at')) }}
            </span>
          {% else : %}
            <span class="inline-block text-sm text-gray-700">aldrig</span>
          {% endif; %}
          </li>
          <li class="flex items-start gap-1 sm:gap-2 my-1">
          {% if($currentUser->hasAtLeast('moderator')) : %}
            <span class="shrink-0 inline-block w-24 md:w-28 text-sm font-semibold text-gray-700">Kontostatus:</span>
            <span class="inline-block text-xs font-semibold py-0.5 px-1.5 rounded {{ $user->getRelation('status')->getAttribute('status') }}">{{ $user->getRelation('status')->translateStatus($user->getRelation('status')->getAttribute('status')) }}</span>
          {% endif; %}
          </li>
          {% if($currentUser->isAdmin() && !$user->isAdmin()) : %}
          <li class="flex items-start gap-1 sm:gap-2 my-1">
            <span class="shrink-0 inline-block w-24 md:w-28 text-sm font-semibold text-gray-700">Behörighet:</span>
            <span class="inline-block text-xs font-semibold bg-gray-100 text-gray-800 py-0.5 px-1.5 rounded">{{ $user->fetchGuardedAttribute('role') }}</span>
          </li>
          {% endif; %}
        </ul>
        <div class="flex flex-col items-center justify-between gap-2 mb-1">
          <figure class="text-center">
            <img src="{{ versioned_file($user->getAttribute('avatar'), '/images/graphics/avatar.png') }}" alt="Avatar {{ $user->getAttribute('first_name') }} {{ $user->getAttribute('last_name') }}" class="w-[50px] sm:w-[80px] h-[50px] sm:h-[80px] rounded-full object-cover">
            <figcaption class="text-xs text-gray-700 font-semibold">Avatar</figcaption>
          </figure>
{% if($currentUser->isAdmin() && !$user->isAdmin()) : %}
          <button
              type="button"
              x-on:click="openRoleModal = true"
              class="text-xs font-semibold bg-blue-600 text-white py-0.5 px-1.5 rounded cursor-pointer hover:bg-blue-700 transition-colors duration-300"
            >
              ändra behörighet
            </button>
{% endif; %}
        </div>
      </div>
      {% if($currentUser->isAdmin() && !$user->isAdmin()) : %}
      <div
        x-show="openRoleModal"
        x-cloak
        x-on:keydown.escape.window="openRoleModal = false"
        role="dialog"
        aria-modal="true"
        x-id="['modal-title']"
        :aria-labelledby="$id('modal-title')"
        class="fixed inset-0 z-50 overflow-y-auto"
      >
        <div x-show="openRoleModal" x-transition.opacity class="fixed inset-0 bg-black/60"></div>
        <div
          x-show="openRoleModal" x-transition
          x-on:click="openRoleModal = false"
          class="relative flex min-h-screen items-center justify-center p-4"
        >
          <div
            x-on:click.stop
            class="relative min-w-80 max-w-xl rounded-xl bg-white p-6 shadow-lg"
          >
            <h2 class="flex items-center gap-1.5 text-gray-800 mb-2" :id="$id('modal-title')">
              <span class="text-2xl text-gray-700">Ändra behörighet</span>
            </h2>

            <p class="mt-3 mb-4 text-[15px] text-gray-700 max-w-sm">
              Välj en ny behörighet för kontot <strong>{{ $user->getAttribute('email') }}</strong>.
            </p>

            <form action="{{ route('admin.user.role', ['id' => $user->getAttribute('id')]) }}" method="post" class="mt-3">
              {{ csrf_field()|raw }}
              <div class="mb-4">
                <label for="role" class="block text-sm text-gray-700 mb-1">Behörighet</label>
                <select
                  id="role"
                  name="role"
                  x-model="selectedRole"
                  class="block w-full rounded border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  required
                >
                {% foreach ($roles as $roleCase): %}
                  {% if($roleCase->value !== 'admin') : %}
                  <option value="{{ $roleCase->value }}">{{ $roleCase->value }}</option>
                {% endif; %}
                {% endforeach; %}
                </select>
              </div>

              <div class="mt-3 flex justify-end space-x-2">
                <button type="button" x-on:click="openRoleModal = false" class="relative flex items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-gray-800/20 bg-transparent px-3 py-0.5 text-gray-800 hover:bg-gray-800/5 transition-colors duration-300">
                  Avbryt
                </button>
                <button type="submit" x-on:click="openRoleModal = false" class="relative flex items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-transparent bg-blue-600 px-3 py-0.5 text-white hover:bg-blue-700 transition-colors duration-300">
                  Spara
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
      {% endif; %}
{% else : %}
      <p>Konto hittades inte.</p>
{% endif; %}
    </section>
{% endblock %}