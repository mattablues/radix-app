  <div
    x-show="openDeleteModal"
    x-cloak
    x-on:keydown.escape.window="openDeleteModal = false"
    role="dialog"
    aria-modal="true"
    x-id="['modal-title']"
    :aria-labelledby="$id('modal-title')"
    class="fixed inset-0 z-50 overflow-y-auto"
  >
    <div x-show="openDeleteModal" x-transition.opacity class="fixed inset-0 bg-black/60"></div>
      <div
        x-show="openDeleteModal" x-transition
        x-on:click="openDeleteModal = false"
        class="relative flex min-h-screen items-center justify-center p-4"
      >
      <div
        x-on:click.stop
        class="relative min-w-80 max-w-xl rounded-xl bg-white p-6 shadow-lg"
      >
        <h2 class="flex items-center gap-1.5 text-gray-800 mb-2" :id="$id('modal-title')">
          <span class="text-2xl text-gray-700">Radera konto</span>
        </h2>

        <p class="mt-3 mb-2 text-[15px] text-gray-700 max-w-sm">
          Ditt konto kommer att raderas och all din lagrade information kommer att försvinna, om du inte vill radera ditt innehåll kan du istället stänga kontot, och vid en senare öppna det igen.
        </p>
        <p class="mt-3 mb-4 text-[14px] text-gray-700 max-w-sm">Är du säker på att du vill fortsätta och radera ditt konto?</p>

        <form action="{{ route('user.delete') }}" method="post" class="mt-3 flex justify-end space-x-2">
          {{ csrf_field()|raw }}
          <button type="reset" x-on:click="openDeleteModal = false" class="relative flex items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-gray-800/20 bg-transparent px-3 py-0.5 text-gray-800 hover:bg-gray-800/5 transition-colors duration-300 cursor-pointer">
              Avbryt
          </button>

          <button x-on:click="openDeleteModal = false" class="relative flex items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-transparent bg-red-500 px-3 py-0.5 text-white hover:bg-red-600 transition-colors duration-300 cursor-pointer">
              Radera
          </button>
        </form>
      </div>
    </div>
  </div>