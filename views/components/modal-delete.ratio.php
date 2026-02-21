<div
  x-data="modalFocusRestore('deleteCurrentPassword')"
  x-show="$store.modals.deleteAccount"
  x-cloak
  x-on:keydown.escape.window="$store.modals.closeDeleteAccount()"
  x-trap.noscroll="$store.modals.deleteAccount"
  x-effect="onToggle($store.modals.deleteAccount)"
  role="dialog"
  aria-modal="true"
  aria-labelledby="delete-modal-title"
  aria-describedby="delete-modal-description"
  class="fixed inset-0 z-50 overflow-y-auto"
  {% if (isset($modal) && $modal === 'delete') : %}x-init="$store.modals.openDeleteAccount()"{% endif %}
>
  <!-- Backdrop -->
  <div x-show="$store.modals.deleteAccount" x-transition.opacity class="fixed inset-0 bg-black/60"></div>

  <!-- Modal Container -->
  <div
    x-show="$store.modals.deleteAccount" x-transition
    x-on:click="$store.modals.closeDeleteAccount()"
    class="relative flex min-h-screen items-center justify-center p-4"
  >
    <div
      x-on:click.stop
      class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-gray-100"
    >
      <div class="flex items-center gap-3 text-red-600 mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <h2 id="delete-modal-title" class="text-xl font-bold text-gray-900">
          Radera konto permanent
        </h2>
      </div>

      <div id="delete-modal-description" class="space-y-3">
        <p class="text-sm text-gray-600 leading-relaxed">
          Detta raderar all din data permanent. Det går inte att ångra. Om du vill behålla din historik bör du välja att <strong>stänga</strong> kontot istället.
        </p>
        <p class="text-sm font-bold text-red-700 bg-red-50 p-3 rounded-lg border border-red-100">
          Vill du verkligen gå vidare och radera allt?
        </p>
      </div>

      <form action="{{ route('user.delete') }}" method="post" class="mt-6">
        {{ csrf_field()|raw }}

        {% if (isset($honeypotId) && $honeypotId) : %}
          <input
            type="text"
            name="{{ $honeypotId }}"
            value=""
            tabindex="-1"
            autocomplete="off"
            class="hidden"
            aria-hidden="true"
          >
        {% endif %}

        <div class="relative mt-4">
          <label for="delete-current-password" class="block text-sm font-medium text-slate-700 mb-1.5 ml-1">
            Bekräfta med lösenord
          </label>
          <input
            x-ref="deleteCurrentPassword"
            type="password"
            name="current_password"
            id="delete-current-password"
            class="w-full text-sm border-slate-300 rounded-md focus:border-indigo-500 focus:ring-indigo-500 transition shadow-sm bg-white"
            placeholder="••••••••"
            autocomplete="current-password"
          >
          {% if (error($errors, 'current_password')) : %}
            <span class="block absolute -bottom-5 left-1 text-xxs text-red-600">{{ error($errors, 'current_password') }}</span>
          {% endif %}
        </div>

        <div class="mt-4 flex justify-end gap-3 pt-4 border-t border-gray-50">
          <button
            type="button"
            x-on:click="$store.modals.closeDeleteAccount()"
            class="px-4 py-2 text-sm font-semibold text-gray-600 hover:text-gray-800 transition-colors cursor-pointer"
          >
            Avbryt
          </button>

          <button
            type="submit"
            class="inline-flex items-center justify-center rounded-lg bg-red-600 px-6 py-2 text-sm font-bold text-white hover:bg-red-700 shadow-md shadow-red-100 transition-all active:scale-[0.98] cursor-pointer"
          >
            Radera permanent
          </button>
        </div>
        {% if (error($errors, 'form-error')) : %}
        <div class="w-full mt-4 p-3 bg-red-50 border border-red-100 rounded-lg">
            <p class="text-xxs text-red-600 font-semibold leading-tight text-center">{{ error($errors, 'form-error') }}</p>
        </div>
        {% endif %}
      </form>
    </div>
  </div>
</div>
