{% extends "layouts/admin.ratio.php" %}
{% block title %}User show{% endblock %}
{% block pageId %}show{% endblock %}
{% block searchId %}search-users{% endblock %}
{% block body %}
    <section x-data="{ openRoleModal: false, selectedRole: '{{ $user->fetchGuardedAttribute('role') }}' }">
      <h1 class="text-3xl mb-8">Konto</h1>
{% if($user) : %}
      <p class="text-gray-700 text-sm">Namn: {{ $user->getAttribute('first_name') }} {{ $currentUser->getAttribute('last_name') }}</p>
      <p class="text-gray-700 text-sm">E-post: {{ $user->getAttribute('email') }}</p>
      {% if($currentUser->hasAtLeast('moderator')) : %}
      <p class="text-gray-700 text-sm">kontostatus: <span class="inline-block text-xs font-semibold py-0.5 px-1.5 rounded {{ $user->getRelation('status')->getAttribute('status') }}">{{ $user->getRelation('status')->translateStatus($user->getRelation('status')->getAttribute('status')) }}</span></p>
      {% endif; %}
      {% if($user->getRelation('status')->getAttribute('active_at')) : %}
      <p class="text-gray-700 text-sm">Senast aktiv: {{ $datetime->frame($user->getRelation('status')->getAttribute('active_at')) }}</p>
      {% else : %}
      <p class="text-gray-700 text-sm">Senast aktiv: aldrig</p>
      {% endif; %}
      {% if($currentUser->isAdmin() && !$user->isAdmin()) : %}
      <div class="mt-6">
        <div class="flex items-center gap-2 mb-4">
          <span class="text-sm text-gray-700">Behörighet:</span>
          <span class="inline-block text-xs font-semibold bg-gray-100 text-gray-800 py-0.5 px-1.5 rounded">{{ $user->fetchGuardedAttribute('role') }}</span>
        </div>
        <button
          type="button"
          x-on:click="openRoleModal = true"
          class="text-sm font-semibold bg-blue-600 text-white py-0.5 px-2 rounded cursor-pointer hover:bg-blue-700 transition-colors duration-300"
        >
          Ändra behörighet
        </button>
      </div>

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
      <p>User not found</p>
{% endif; %}
    </section>
{% endblock %}