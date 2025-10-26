{% extends "layouts/admin.ratio.php" %}
{% block title %}Konto{% endblock %}
{% block pageId %}show-user{% endblock %}
{% block searchId %}search-users{% endblock %}
{% block body %}
    <section x-data="{ openRoleModal: false, selectedRole: '{{ $user ? $user->fetchGuardedAttribute('role') : null }}' }">
      <h1 class="text-3xl font-semibold mb-8">Konto</h1>
{% if($user) : %}
      <h3 class="text-[20px] font-semibold mb-3">Kontoinformation</h3>

      <div class="w-full sm:max-w-[600px]">
        <div class="flex justify-between gap-3  border border-gray-200 rounded-md">
          <div class="sm:flex-1 flex flex-col sm:flex-row sm:gap-14 p-3 sm:px-5">
            <dl>
              <dt class="text-sm font-medium text-gray-500">Namn</dt>
              <dd class="text-sm text-gray-900 mb-1">{{ $user->getAttribute('first_name') }} {{ $currentUser->getAttribute('last_name') }}</dd>
              <dt class="text-sm font-medium text-gray-500">E-post</dt>
              <dd class="text-sm text-gray-900 mb-1">{{ $user->getAttribute('email') }}</dd>
              <dt class="text-sm font-medium text-gray-500">Skapad</dt>
              <dd class="text-sm text-gray-900 mb-1">{{ $user->getAttribute('created_at') }}</dd>
            </dl>
            <dl>
              <dt class="text-sm font-medium text-gray-500">Senast aktiv</dt>
              <dd class="text-sm text-gray-900 mb-1">
              {% if($user->getRelation('status')->getAttribute('active_at')) : %}
                {{ $datetime->frame($user->getRelation('status')->getAttribute('active_at')) }}
              {% else : %}
                aldrig
              {% endif; %}
              </dd>
            {% if($currentUser->hasAtLeast('moderator')) : %}
              <dt class="text-sm font-medium text-gray-500">Kontostatus</dt>
              <dd class="text-sm text-gray-900 mb-1 text-{{ $user->getRelation('status')->getAttribute('status') }} mb-1">{{ $user->getRelation('status')->translateStatus($user->getRelation('status')->getAttribute('status')) }}</dd>
              <dt class="text-sm font-medium text-gray-500">Behörighet</dt>
              <dd class="text-sm text-gray-900 mb-1">{{ $user->fetchGuardedAttribute('role') }}</dd>
            {% endif; %}
            </dl>
          </div>
          <figure class="flex flex-col items-center justify-center px-3 sm:px-5">
            <img src="{{ versioned_file($user->getAttribute('avatar')) }}" alt="Avatar" class="w-[90px] h-[90px] sm:w-[110px] sm:h-[110px] rounded-full object-cover">
            <figcaption class="text-xs text-gray-700 font-semibold">Avatar</figcaption>
          </figure>
        </div>
        {% if($currentUser->isAdmin() && !$user->isAdmin()) : %}
        <div class="flex gap-3 justify-end px-3 sm:px-5 mt-2">
          <button
            type="button"
            x-on:click="openRoleModal = true"
            class="text-sm self-start block border border-transparent bg-blue-500 px-3 py-0.5 text-white hover:bg-blue-600 transition-colors duration-300 rounded-md cursor-pointer"
          >
              Ändra behörighet
          </button>
        </div>
        {% endif; %}
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
            class="relative min-w-80 max-w-xl rounded-xl bg-white px-4 py-4 shadow-lg"
          >
            <h2 class="text-2xl text-gray-700 px-2" :id="$id('modal-title')">
              Ändra behörighet
            </h2>

            <hr class="my-2 border-gray-200" />

            <p class="mb-2 px-2 text-sm text-gray-700 max-w-sm pb-1">
              Välj en ny behörighet för kontot <strong>{{ $user->getAttribute('email') }}</strong>.
            </p>

            <form action="{{ route('admin.user.role', ['id' => $user->getAttribute('id')]) }}" method="post" class="mt-3">
              {{ csrf_field()|raw }}
              <div class="mb-4 px-2">
                <label for="role" class="block text-sm text-slate-600 mb-1.5 ml-1 sr-only">Behörighet</label>
                <select
                  id="role"
                  name="role"
                  x-model="selectedRole"
                  class="block w-full rounded border border-gray-300 px-3 py-1 text-sm focus:outline-none focus:border-indigo-500 focus:ring-0 focus:ring-indigo-500 transition duration-300 ease-in"
                  required
                >
                {% foreach ($roles as $roleCase): %}
                  {% if($roleCase->value !== 'admin') : %}
                  <option value="{{ $roleCase->value }}">{{ $roleCase->value }}</option>
                {% endif; %}
                {% endforeach; %}
                </select>
              </div>

              <hr class="my-2 border-gray-200" />

              <div class="mt-3 flex justify-end space-x-2">
                <button type="submit" x-on:click="openRoleModal = false" class="relative flex items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-transparent bg-blue-600 text-sm px-3 py-1 text-white hover:bg-blue-700 transition-colors duration-300 cursor-pointer">
                  Spara
                </button>
                <button type="button" x-on:click="openRoleModal = false" class="relative flex items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-gray-800/20 bg-transparent text-sm px-3 py-1 text-gray-800 hover:bg-gray-800/5 transition-colors duration-300 cursor-pointer">
                  Avbryt
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