<!doctype html>
<html lang="{{ getenv('APP_LANG') }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>{% yield title %}</title>
  <link rel="stylesheet" href="{{ versioned_file('/css/app.css') }}">

  <link rel="apple-touch-icon" sizes="180x180" href="/icons/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="/icons/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/public/icons/favicon-16x16.png">
  <link rel="manifest" href="/icons/site.webmanifest">
</head>
<body id="{% yield pageId %}" class="flex flex-col min-h-screen {% yield pageClass %}">
  <header class="sticky top-0 z-50 w-full bg-white shadow-md">
    <div class="container-centered h-15 flex items-center justify-between">
      <!-- Logo -->
      <a href="{{ route('home.index') }}" class="flex items-center gap-2">
        <img src="/images/graphics/logo.png" alt="Logo" class="w-auto h-10">
        <span class="text-xl text-gray-900">{{ getenv('APP_NAME') }}</span>
      </a>

      <!-- Desktop Navigation -->
      <nav class="hidden lg:flex gap-4">
        <a href="{{ route('home.index') }}">Hem</a>
        <a href="{{ route('contact.index') }}">Kontakta oss</a>
        {% if ($session->isAuthenticated()) : %}
          <a href="{{ route('user.index') }}">Konto</a>
        {% else : %}
          <a href="{{ route('auth.login.index') }}">Logga in</a>
        {% endif; %}
      </nav>

      <!-- Mobile Navigation (Overlay Menu) -->
      <div class="lg:hidden" x-data="{ open: false }">
        <!-- Hamburger Button -->
        <button
          class="flex items-center text-lg font-medium text-gray-700 focus:outline-none"
          x-on:click="open = true">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M12 17.25h8.25" />
          </svg>
        </button>

        <!-- Fullscreen Overlay Menu -->
        <div
          x-show="open"
          x-on:click.away="open = false"
          class="fixed inset-0 bg-white z-50 flex flex-col items-center justify-center space-y-6 text-xl font-medium text-gray-800"
          style="display: none;"
          x-cloak>
          <!-- Close Button -->
          <button class="absolute top-4 right-4 text-gray-800" x-on:click="open = false">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>

          <!-- Navigation Links -->
          <a href="{{ route('home.index') }}" class="hover:text-gray-600">Hem</a>
          <a href="{{ route('contact.index') }}" class="hover:text-gray-600">Kontakta oss</a>
          {% if ($session->isAuthenticated()) : %}
            <a href="{{ route('user.index') }}" class="hover:text-gray-600">Konto</a>
          {% else : %}
            <a href="{{ route('auth.login.index') }}" class="hover:text-gray-600">Logga in</a>
          {% endif; %}
        </div>
      </div>
    </div>
  </header>

  <main class="flex-grow">
    {% include "components/flash.ratio.php" %}
    {% include "components/noscript.ratio.php" %}
    {% yield body %}
  </main>

  <footer class="text-center">
    <div class="container-centered py-4">
      <p class="text-xs text-slate-500  font-semibold text-center">
        &copy;{{ copyright(getenv('APP_COPY'), getenv('APP_COPY_YEAR')) }}
        | <a href="{{ route('cookie.index') }}" class="underline hover:no-underline transition duration-300">Cookies</a>
      </p>
    </div>
  </footer>
  {% include "components/cookie-consent.ratio.php" %}
  {% yield alpinejs %}
  <script src="{{ versioned_file('/js/app.js') }}"></script>
  {% yield script %}
</body>
</html>