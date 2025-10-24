{% extends "layouts/admin.ratio.php" %}
{% block title %}Konto{% endblock %}
{% block pageId %}show-user{% endblock %}
{% block searchId %}search-users{% endblock %}
{% block body %}
    <section x-data="{ openRoleModal: false, selectedRole: '{{ $user ? $user->fetchGuardedAttribute('role') : null }}' }">
      <h1 class="text-3xl font-semibold mb-8">Konto</h1>
{% if($user) : %}
      <h3 class="text-[20px] font-semibold mb-3">Kontoinformation</h3>

      <div class="w-full md:max-w-[600px]">
        <div class="flex justify-between border border-gray-200 rounded-md">
          <dl class="px-4 py-2 flex-1 bg-gray-50">
            <dt class="text-sm font-medium text-gray-500">Namn</dt>
            <dd class="text-sm text-gray-900 mb-1">{{ $user->getAttribute('first_name') }} {{ $currentUser->getAttribute('last_name') }}</dd>
            <dt class="text-sm font-medium text-gray-500">E-post</dt>
            <dd class="text-sm text-gray-900 mb-1">{{ $user->getAttribute('email') }}</dd>
            <dt class="text-sm font-medium text-gray-500">Senast aktiv</dt>
            <dd class="text-sm text-gray-900 mb-1">
            {% if($user->getRelation('status')->getAttribute('active_at')) : %}
              {{ $datetime->frame($user->getRelation('status')->getAttribute('active_at')) }}
            {% else : %}
              aldrig
          {% endif; %}
            </dd>
            <dt class="text-sm font-medium text-gray-500">Skapad</dt>
            <dd class="text-sm text-gray-900 mb-1">{{ $user->getAttribute('created_at') }}</dd>
            {% if($currentUser->hasAtLeast('moderator')) : %}
            <dt class="text-sm font-medium text-gray-500">Kontostatus</dt>
            <dd class="inline-block text-xs font-semibold px-1.5 rounded {{ $user->getRelation('status')->getAttribute('status') }} mb-1">{{ $user->getRelation('status')->translateStatus($user->getRelation('status')->getAttribute('status')) }}</dd>
            <dt class="text-sm font-medium text-gray-500">Behörighet</dt>
            <dd class="inline-block text-xs font-semibold px-1.5 bg-gray-700 text-white rounded">{{ $user->fetchGuardedAttribute('role') }}</dd>
            {% endif; %}
          </dl>
          <figure class="flex flex-col items-center justify-center w-[140px] md:w-[200px]">
            <img src="{{ $currentUser->getAttribute('avatar') }}" alt="Avatar" class="w-[100px] sm:w-[140px] h-[100px] sm:h-[140px] rounded-full object-cover">
            <figcaption class="text-xs text-gray-700 font-semibold">Avatar</figcaption>
          </figure>
        </div>
        {% if($currentUser->isAdmin() && !$user->isAdmin()) : %}
        <div class="flex gap-3 justify-end mt-2 px-1">
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