{% extends "layouts/main.ratio.php" %}
{% block headerContainer %}
    <div class="container-base h-15 flex items-center justify-between">
      <a href="{{ route('home.index') }}" class="flex items-center gap-2">
        <img src="/images/graphics/logo.png" alt="Logo" class="w-auto h-10">
        <span class="text-xl text-gray-900">{{ getenv('APP_NAME') }}</span>
      </a>
      <nav class="hidden lg:flex gap-4">
        <a href="{{ route('home.index') }}" class="text-gray-600 hover:text-gray-900">Hem</a>
        <a href="{{ route('contact.index') }}" class="text-gray-600 hover:text-gray-900">Kontakta oss</a>
        <a href="{{ route('about.index') }}" class="text-gray-600 hover:text-gray-900">Om oss</a>
      </nav>

      <div class="lg:hidden" x-data="{ menu:false, sidebar:false }">
        <button class="flex items-center text-lg font-light text-gray-900 focus:outline-none cursor-pointer"
          x-on:click="menu = true">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M12 17.25h8.25" />
          </svg>
        </button>

        <div
          x-show="menu"
          x-transition:enter="transition ease-out duration-300"
          x-transition:enter-start="opacity-0 translate-x-full"
          x-transition:enter-end="opacity-100 translate-x-0"
          x-transition:leave="transition ease-in duration-200"
          x-transition:leave-start="opacity-100 translate-x-0"
          x-transition:leave-end="opacity-0 translate-x-full"
          x-on:click.away="menu = false"
          class="fixed inset-0 bg-white z-[60] flex flex-col items-center justify-center space-y-4 text-xl font-light text-slate-200 transform"
          x-cloak>
          <button class="absolute top-4 right-4 text-gray-900 cursor-pointer" x-on:click="menu = false" aria-label="Stäng meny">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>

          <a href="{{ route('home.index') }}" class="text-gray-600 hover:text-gray-900 white transition duration-300">Hem</a>
          <a href="{{ route('contact.index') }}" class="text-gray-600 hover:text-gray-900 white transition duration-300">Kontakta oss</a>
          <a href="{{ route('about.index') }}" class="text-gray-600 hover:text-gray-900 white transition duration-300">Om oss</a>
        </div>
      </div>
    </div>
{% endblock %}

{% block footerContainer %}
    <div class="container-base py-4">
      <p class="text-xs text-slate-500 font-semibold text-center">
        &copy;{{ copyright(getenv('APP_COPY'), getenv('APP_COPY_YEAR')) }}
        | <a href="{{ route('cookie.index') }}" class="underline hover:no-underline">Cookies</a>
      </p>
    </div>
{% endblock %}

{% block content %}
  <div class="flex-1 min-h-[calc(100vh-108px)]">
    <div class="relative">
      <aside x-data="{ sidebarOpen:false }" class="fixed lg:left-0 left-[-300px] top-[60px] h-[calc(100vh-60px)] w-[220px] bg-white shadow z-40 overflow-y-auto hide-scrollbar transition-all duration-200"
             x-bind:class="sidebarOpen ? 'left-0' : 'left-[-300px]'">
        <div class="container-base py-4">
          <nav class="space-y-1 text-sm">
            <a href="{{ route('home.index') }}" class="block py-1.5 rounded hover:bg-gray-50 text-gray-700">Hem</a>

            <hr class="my-2 border-gray-200" />
            <a href="{{ route('contact.index') }}" class="block py-1.5 rounded hover:bg-gray-50 text-gray-700">Kontakta oss</a>
            <a href="{{ route('about.index') }}" class="block py-1.5 rounded hover:bg-gray-50 text-gray-700">Om oss</a>
          </nav>
        </div>
      </aside>

      <main class="xl:max-w-[1220px] min-h-[calc(100vh-108px)] overflow-y-auto transition-[margin] duration-200 lg:ml-[220px]">
        <div class="container-base pt-4 pb-8">
          {% include "components/flash.ratio.php" %}
          {% include "components/noscript.ratio.php" %}
          {% yield body %}
        </div>
      </main>
    </div>
  </div>
{% endblock %}