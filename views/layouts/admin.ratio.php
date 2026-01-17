<!doctype html>
<html lang="{{ getenv('APP_LANG') }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>{% yield title %}</title>
  <link rel="stylesheet" href="{{ versioned_file('/css/app.css') }}">
  <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="/favicons/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/favicons/favicon-16x16.png">
  <link rel="manifest" href="/favicons/site.webmanifest">
</head>
<body x-data="{ openSidebar: false, openCloseModal: false, openDeleteModal: false }" id="{% yield pageId %}" class="relative min-h-screen bg-slate-50 {% yield pageClass %} text-slate-600 antialiased flex flex-col">

  {% include "components/flash.ratio.php" %}

  <header class="sticky top-0 z-50 w-full bg-gray-900 shadow-lg">
    <div class="container-base relative">
      <div class="h-16 flex items-center justify-between">
<a href="{{ route('home.index') }}" class="flex items-center gap-3 group transition-all">
  <div class="relative size-9">
    <!-- Kub-loggan optimerad för Admin-vyn -->
    <div class="absolute inset-0 grid grid-cols-2 grid-rows-2 gap-0.5 transform -rotate-12 group-hover:rotate-0 transition-transform duration-500">
      <div class="bg-blue-500 rounded-tl-md rounded-br-[1px] shadow-sm"></div>
      <div class="bg-slate-500 rounded-tr-md rounded-bl-[1px] opacity-80 group-hover:opacity-100 transition-opacity"></div>
      <div class="bg-slate-600 rounded-bl-md rounded-tr-[1px] opacity-80 group-hover:opacity-100 transition-opacity"></div>
      <div class="bg-slate-400 rounded-br-md rounded-tl-[1px] shadow-md group-hover:bg-blue-400 transition-colors"></div>
    </div>
    <!-- Liten status-glimt -->
    <div class="absolute top-1 left-1 size-1 bg-white/40 rounded-full"></div>
  </div>

  <div class="flex flex-col -space-y-1">
    <span class="text-xl font-black text-white tracking-tighter italic">Radix</span>
    <span class="text-[8px] font-bold uppercase tracking-[0.4em] text-blue-400/80 group-hover:text-blue-400 transition-colors">Control</span>
  </div>
</a>

        <div class="flex items-center gap-4">
          <!-- Mobil: sökikon -->
          <button type="button" id="search-toggle" class="md:hidden p-2 rounded-full text-gray-400 hover:text-white hover:bg-gray-800 transition-all cursor-pointer" aria-label="Öppna sök">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197M15.803 15.803A7.5 7.5 0 1 1 5.196 5.196a7.5 7.5 0 0 1 10.607 10.607Z"/>
            </svg>
          </button>

          <!-- Namn + avatar -->
          <a href="{{ route('user.index') }}" class="hidden sm:flex items-center text-gray-300 hover:text-white gap-3 transition-all duration-300 group">
            <span class="text-sm font-bold border-r border-gray-700 pr-3 py-1">{{ $currentUser->getAttribute('first_name') }} {{ $currentUser->getAttribute('last_name') }}</span>
            <img src="{{ versioned_file($currentUser->getAttribute('avatar'), '/images/graphics/avatar.png') }}" alt="Avatar" class="w-9 h-9 rounded-full object-cover ring-2 ring-gray-800 group-hover:ring-indigo-500 transition-all shadow-sm">
          </a>

          <!-- Hamburger -->
          <button type="button" class="text-gray-400 lg:hidden hover:text-white p-1 transition-colors cursor-pointer" x-on:click="openSidebar = !openSidebar" aria-label="Öppna meny">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M12 17.25h8.25" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Sök: centrerad på md+, nedfällbar på mobil -->
      <div class="relative md:absolute md:left-1/2 md:-translate-x-1/2 md:top-1/2 md:-translate-y-1/2 z-60 w-full md:w-auto px-4 md:px-0">
        <div id="search-wrap" class="hidden md:block md:static md:mt-0 mt-2">
          <div class="relative">
            <div class="p-2.5 px-4 flex items-center rounded-xl bg-gray-800 border border-white/5 shadow-inner">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 text-gray-500">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
              </svg>
              <label for="{% yield searchId %}" class="sr-only">Sök</label>
              <!-- Ändrat: text-base (16px) på mobil för att stoppa auto-zoom, text-sm på desktop -->
              <input class="text-base md:text-sm ml-3 w-full md:w-[280px] bg-transparent border-none py-0 px-0 focus:ring-0 text-gray-100 placeholder:text-gray-500"
                     id="{% yield searchId %}" placeholder="Sök i systemet..." autocomplete="off">
            </div>

            <!-- Ändrat: Mer robust placering på mobil, dropdownen följer inputfältets bredd -->
            <div id="search-dropdown" class="absolute left-0 right-0 md:left-0 md:right-auto mt-2 md:w-[420px] max-h-[60vh] overflow-auto bg-white text-gray-900 rounded-2xl shadow-2xl border border-gray-200 hidden z-70">
              <div class="result-container"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </header>

  <!-- Sidebar -->
  <div class="sidebar h-screen fixed top-0 lg:left-0 py-20 px-4 w-(--sidebar-w) transition-all duration-300 overflow-y-auto text-left bg-gray-900 shadow-2xl hide-scrollbar z-40 flex flex-col"
    :class="openSidebar ? 'left-0' : 'left-(--sidebar-w-neg)'"
  >
    <div class="flex-1 space-y-4">
      <div class="text-xxs font-black uppercase tracking-[0.2em] text-gray-500 mb-4 px-4">Navigation</div>

      <nav class="space-y-1">
        <!-- Dashboard -->
        <a href="{{ route('dashboard.index') }}" class="group flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ ($pageId === 'dashboard') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/20' : 'text-gray-400 hover:bg-gray-800 hover:text-gray-100' }}">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
          </svg>
          <span class="text-sm font-bold">Dashboard</span>
        </a>

        <!-- Konto Dropdown -->
        <div x-data="{ sidebarDropdown: {{ in_array($pageId, ['user-index', 'user-edit']) ? 'true' : 'false' }} }">
          <button @click="sidebarDropdown = !sidebarDropdown" class="w-full group flex items-center justify-between px-4 py-3 rounded-xl text-gray-400 hover:bg-gray-800 hover:text-gray-100 transition-all cursor-pointer">
            <div class="flex items-center gap-3">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              <span class="text-sm font-bold">Mitt konto</span>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-300" :class="sidebarDropdown ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
          <ul x-show="sidebarDropdown" x-cloak x-collapse class="mt-1 ml-4 border-l border-gray-800 space-y-1">
            <li><a href="{{ route('user.index') }}" class="block py-2 px-8 text-sm {{ ($pageId === 'user-index') ? 'text-indigo-400 font-bold' : 'text-gray-500 hover:text-gray-200' }}">Visa profil</a></li>
            <li><a href="{{ route('user.edit') }}" class="block py-2 px-8 text-sm {{ ($pageId === 'user-edit') ? 'text-indigo-400 font-bold' : 'text-gray-500 hover:text-gray-200' }}">Redigera profil</a></li>

          {% if(!$currentUser->isAdmin()) : %}
            <li class="pt-2 mt-2 border-t border-gray-800/50">
              <button @click="openCloseModal = true" class="w-full text-left py-2 px-8 text-sm text-gray-500 hover:text-red-400 transition-colors cursor-pointer">
                Stäng konto
              </button>
            </li>
            <li>
              <button @click="openDeleteModal = true" class="w-full text-left py-2 px-8 text-sm text-gray-500 hover:text-red-500 font-medium transition-colors cursor-pointer">
                Radera konto
              </button>
            </li>
          {% endif; %}
          </ul>
        </div>
      </nav>

    {% if($currentUser->hasAtLeast('moderator')) : %}
      <div class="pt-4 mt-4 border-t border-gray-800">
        <div class="text-xxs font-black uppercase tracking-[0.2em] text-gray-500 mb-4 px-4">Administration</div>

        <nav class="space-y-1">
          <!-- Användarhantering Dropdown -->
          <div x-data="{ sidebarDropdown: {{ (strpos($pageId, 'admin-user') !== false || $pageId === 'users') ? 'true' : 'false' }} }">
            <button @click="sidebarDropdown = !sidebarDropdown" class="w-full group flex items-center justify-between px-4 py-3 rounded-xl text-gray-400 hover:bg-gray-800 hover:text-gray-100 transition-all cursor-pointer">
              <div class="flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <span class="text-sm font-bold">Användare</span>
              </div>
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-300" :class="sidebarDropdown ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>

            <ul x-show="sidebarDropdown" x-cloak x-collapse class="mt-1 ml-4 border-l border-gray-800 space-y-1">
              <li>
                <a href="{{ route('admin.user.index') }}" class="block py-2 px-8 text-sm {{ ($pageId === 'admin-users-index') ? 'text-indigo-400 font-bold' : 'text-gray-500 hover:text-gray-200' }}">
                  Visa alla
                </a>
              </li>
              <li>
                <a href="{{ route('admin.user.closed') }}" class="block py-2 px-8 text-sm {{ ($pageId === 'admin-user-closed') ? 'text-indigo-400 font-bold' : 'text-gray-500 hover:text-gray-200' }}">
                  Stängda konton
                </a>
              </li>
              {% if($currentUser->isAdmin()) : %}
              <li>
                <a href="{{ route('admin.user.create') }}" class="block py-2 px-8 text-sm {{ ($pageId === 'admin-user-create') ? 'text-indigo-400 font-bold' : 'text-gray-500 hover:text-gray-200' }}">
                  Skapa nytt konto
                </a>
              </li>
              {% endif; %}
            </ul>
          </div>

          <!-- Systemhälsa -->
          <a href="{{ route('admin.health.index') }}" class="group flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ ($pageId === 'health') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/20' : 'text-gray-400 hover:bg-gray-800 hover:text-gray-100' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            <span class="text-sm font-bold">Systemstatus</span>
          </a>
        </nav>
      </div>
    {% endif; %}
    </div>

    <!-- Logout -->
    <div class="pt-4 mt-auto border-t border-gray-800 pb-8">
      <form action="{{ route('auth.logout.index') }}" method="post">
        {{ csrf_field()|raw }}
        <button class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-red-400 hover:bg-red-500/10 transition-all cursor-pointer group">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
          </svg>
          <span class="text-sm font-bold">Logga ut</span>
        </button>
      </form>
    </div>
  </div>

  {% include "components/modal-close.ratio.php" %}
  {% include "components/modal-delete.ratio.php" %}

  <main
    class="max-w-[1180px] flex-1 lg:ml-(--sidebar-w) pt-8 px-4 md:px-8 pb-12 transition-all duration-300"
    x-on:click="if (openSidebar) openSidebar = false"
  >
    {% include "components/noscript.ratio.php" %}
    <div class="max-w-6xl mx-auto">
        {% yield body %}
    </div>
  </main>

  <footer class="py-8 bg-white border-t border-slate-200 mt-auto lg:ml-(--sidebar-w) transition-all duration-300">
    <div class="max-w-[1180px] px-4 md:px-8">
      <div class="flex flex-col md:flex-row justify-between items-center gap-4">
        <p class="text-xxs font-bold text-slate-400 uppercase tracking-widest">
          &copy; {{ copyright(getenv('APP_COPY'), getenv('APP_COPY_YEAR')) }}
        </p>

        <nav class="flex gap-6">
          <a href="{{ route('cookie.index') }}" class="text-xxs font-bold text-slate-400 hover:text-indigo-600 uppercase tracking-widest transition-colors">Cookies</a>
          <a href="{{ route('about.index') }}" class="text-xxs font-bold text-slate-400 hover:text-indigo-600 uppercase tracking-widest transition-colors">Om systemet</a>
        </nav>
      </div>
    </div>
  </footer>

  {% include "components/cookie-consent.ratio.php" %}
  {% yield alpinejs %}
  <script nonce="<?= secure_output(csp_nonce(), true) ?>" src="{{ versioned_file('/js/app.js') }}"></script>
</body>
</html>