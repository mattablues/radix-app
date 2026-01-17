<div
  x-show="openCloseModal"
  x-cloak
  x-on:keydown.escape.window="openCloseModal = false"
  role="dialog"
  aria-modal="true"
  class="fixed inset-0 z-50 overflow-y-auto"
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
      <h2 class="text-xl font-bold text-gray-900 mb-4">
        Stäng konto
      </h2>

      <div class="space-y-3">
        <p class="text-sm text-gray-600 leading-relaxed">
          Ditt konto kommer att stängas och du kommer inte kunna logga in igen. För att aktivera det senare krävs kontakt med supporten.
        </p>
        <p class="text-sm font-bold text-gray-800">
          Är du helt säker på att du vill stänga ditt konto?
        </p>
      </div>

      <form action="{{ route('user.close') }}" method="post" class="mt-8 flex justify-end gap-3 pt-4 border-t border-gray-50">
        {{ csrf_field()|raw }}
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
      </form>
    </div>
  </div>
</div>