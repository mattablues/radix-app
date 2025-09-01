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
  <header class="sticky top-0 z-50 bg-white shadow-xs">
    <div class="container-centered flex justify-between items-center h-15">
      <a href="{{ route('home.index') }}" class="flex items-center gap-2">
        <img src="/images/graphics/logo.png" alt="Logo" class="w-auto h-10">
        <span class="text-xl">{{ getenv('APP_NAME') }}</span>
      </a>
      <nav class="flex gap-4">
        <a href="{{ route('home.index') }}">Hem</a>
        <a href="{{ route('contact.index') }}">Kontakta oss</a>
{% if($session->isAuthenticated()) : %}
        <a href="{{ route('user.index') }}">Konto</a>
{% else : %}
        <a href="{{ route('auth.login.index') }}">Logga in</a>
{% endif %}
      </nav>
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


