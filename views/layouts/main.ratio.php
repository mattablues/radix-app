<!doctype html>
<html lang="{{ getenv('APP_LANG') }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>{% yield title %}</title>
  <link rel="stylesheet" href="{{ versioned_file('/css/app.css') }}">
  <link rel="icon" type="image/png" href="/favicons/favicon-96x96.png" sizes="96x96" />
  <link rel="icon" type="image/svg+xml" href="/favicons/favicon.svg" />
  <link rel="shortcut icon" href="/favicons/favicon.ico" />
  <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png" />
  <meta name="apple-mobile-web-app-title" content="Radix" />
  <link rel="manifest" href="/favicons/site.webmanifest" />
</head>
<body id="{% yield pageId %}" class="flex flex-col min-h-screen {% yield pageClass %}">
  <header class="sticky top-0 z-50 w-full bg-white shadow-xs [--header-h:60px]">
    {% yield headerContainer %}
    <div class="container-centered h-15 flex items-center justify-between">
    {% include "layouts/partials/header-inner.ratio.php" %}
    </div>
    {% endyield headerContainer %}
  </header>

  {% yield content %}
  <main class="grow">
    {% include "components/flash.ratio.php" %}
    {% include "components/noscript.ratio.php" %}
    {% yield body %}
  </main>
  {% endyield content %}

  <footer class="bg-white border-t border-slate-100 pt-16 pb-8">
    {% yield footerContainer %}
      <div class="container-centered">
        {% include "layouts/partials/footer-inner.ratio.php" %}
      </div>
    {% endyield footerContainer %}
  </footer>

  {% include "components/cookie-consent.ratio.php" %}
  {% yield alpinejs %}
  <script nonce="<?= secure_output(csp_nonce(), true) ?>" src="{{ versioned_file('/js/app.js') }}"></script>
  {% yield script %}
</body>
</html>