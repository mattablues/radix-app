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
        class="relative min-w-80 max-w-xl rounded-xl bg-white px-4 py-4 shadow-lg"
      >
        <h2 class="text-2xl text-gray-700 px-2" :id="$id('modal-title')">
          Radera konto
        </h2>

        <hr class="my-2 border-gray-200" />

        <p class="mb-2 px-2 text-sm text-gray-700 max-w-sm pt-1">
          Ditt konto kommer att raderas och all din lagrade information kommer att försvinna, om du inte vill radera ditt innehåll kan du istället stänga kontot, och vid en senare öppna det igen.
        </p>
        <p class="mb-2 px-2 text-sm font-medium text-gray-700 max-w-sm pb-1">Är du säker på att du vill fortsätta och radera ditt konto?</p>

        <hr class="my-2 border-gray-200" />

        <form action="{{ route('user.delete') }}" method="post" class="mt-3 flex justify-end px-2 space-x-2">
          {{ csrf_field()|raw }}
          <button type="reset" x-on:click="openDeleteModal = false" class="relative flex items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-gray-800/20 bg-transparent text-sm px-3 py-1 text-gray-800 hover:bg-gray-800/5 transition-colors duration-300 cursor-pointer">
              Avbryt
          </button>

          <button x-on:click="openDeleteModal = false" class="relative flex items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-transparent bg-red-500 text-sm px-3 py-1 text-white hover:bg-red-600 transition-colors duration-300 cursor-pointer">
              Radera
          </button>
        </form>
      </div>
    </div>
  </div>