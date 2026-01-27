<div
  x-data="{ restoreFocusEl: null }"
  x-show="openCloseModal"
  x-cloak
  x-on:keydown.escape.window="openCloseModal = false"
  x-trap.noscroll="openCloseModal"
  x-effect="
    if (openCloseModal) {
      if (!restoreFocusEl) restoreFocusEl = document.activeElement;
      $nextTick(() => { $refs.closeCurrentPassword?.focus(); });
    } else {
      const el = restoreFocusEl;
      restoreFocusEl = null;
      if (el && typeof el.focus === 'function') {
        $nextTick(() => { el.focus(); });
      }
    }
  "
  role="dialog"
  aria-modal="true"
  aria-labelledby="close-modal-title"
  aria-describedby="close-modal-description"
  class="fixed inset-0 z-50 overflow-y-auto"
  {% if (isset($modal) && $modal === 'close') : %}x-init="openCloseModal = true"{% endif %}
>
  <!-- Backdrop -->
  <div x-show="openCloseModal" x-transition.opacity class="fixed inset-0 bg-black/60"></div>

  <!-- Modal Container -->
  <div
    x-show="openCloseModal" x-transition
    x-on:click="openCloseModal = false"
    class="relative flex min-h-screen items-center justify-center p-4"
  >
    <div
      x-on:click.stop
      class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-gray-100"
    >
      <h2 id="close-modal-title" class="text-xl font-bold text-gray-900 mb-4">
        Stäng konto
      </h2>

      <div id="close-modal-description" class="space-y-3">
        <p class="text-sm text-gray-600 leading-relaxed">
          Ditt konto kommer att stängas och du kommer inte kunna logga in igen. För att aktivera det senare krävs kontakt med supporten.
        </p>
        <p class="text-sm font-bold text-gray-800">
          Är du helt säker på att du vill stänga ditt konto?
        </p>
      </div>

      <form action="{{ route('user.close') }}" method="post" class="mt-6">
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
          <label for="close-current-password" class="block text-sm font-medium text-slate-700 mb-1.5 ml-1">
            Bekräfta med lösenord
          </label>
          <input
            x-ref="closeCurrentPassword"
            type="password"
            name="current_password"
            id="close-current-password"
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
            x-on:click="openCloseModal = false"
            class="px-4 py-2 text-sm font-semibold text-gray-600 hover:text-gray-800 transition-colors cursor-pointer"
          >
            Avbryt
          </button>

          <button
            type="submit"
            class="inline-flex items-center justify-center rounded-lg bg-red-600 px-6 py-2 text-sm font-bold text-white hover:bg-red-700 shadow-md shadow-red-100 transition-all active:scale-[0.98] cursor-pointer"
          >
            Ja, stäng kontot
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