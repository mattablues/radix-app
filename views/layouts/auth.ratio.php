<!doctype html>
<html lang="{{ getenv('APP_LANG') }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{% yield title %}</title>
  <link rel="stylesheet" href="{{ versioned_file('/css/app.css') }}">
  <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="/favicons/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/favicons/favicon-16x16.png">
  <link rel="manifest" href="/favicons/site.webmanifest">
</head>
<body id="{% yield pageId %}" class="flex flex-col min-h-screen bg-slate-50 text-slate-600 antialiased">

  <!-- Kompakt Header för Auth -->
  <header class="w-full bg-white border-b border-gray-200">
    <div class="container-centered mx-auto px-4 sm:px-6">
      <div class="h-16 flex justify-between items-center">
        <a href="{{ route('home.index') }}" class="flex items-center gap-2 transition-opacity hover:opacity-80">
          <img src="/images/graphics/logo.png" alt="Logo" class="w-auto h-9 grayscale opacity-50 hover:grayscale-0 transition-all duration-500">
          <span class="text-xl font-black text-slate-900 tracking-tighter italic">{{ getenv('APP_NAME') }}</span>
        </a>

        <a href="{{ route('home.index') }}" class="p-2 rounded-full text-slate-400 hover:text-indigo-600 hover:bg-slate-50 transition-all" title="Gå hem">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
            <path d="M11.47 3.841a.75.75 0 0 1 1.06 0l8.69 8.69a.75.75 0 1 0 1.06-1.061l-8.689-8.69a2.25 2.25 0 0 0-3.182 0l-8.69 8.69a.75.75 0 1 0 1.061 1.06l8.69-8.689Z" />
            <path d="m12 5.432 8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 0 1-.75-.75v-4.5a.75.75 0 0 0-.75-.75h-3a.75.75 0 0 0-.75.75V21a.75.75 0 0 1-.75.75H5.625a1.875 1.875 0 0 1-1.875-1.875v-6.198a2.29 2.29 0 0 0 .091-.086L12 5.432Z" />
          </svg>
        </a>
      </div>
    </div>
  </header>

  <!-- Flash-meddelanden & Innehåll -->
  <div class="grow flex flex-col justify-center py-12">
    {% include "components/flash.ratio.php" %}
    {% include "components/noscript.ratio.php" %}

    <main class="flex items-center justify-center">
      {% yield body %}
    </main>
  </div>

  <!-- Kompakt Footer -->
  <footer class="py-8 bg-white border-t border-slate-100">
    <div class="container-centered flex flex-col md:flex-row justify-between items-center gap-4">
      <p class="text-xxs font-bold text-slate-400 uppercase tracking-widest">
        &copy; {{ copyright(getenv('APP_COPY'), getenv('APP_COPY_YEAR')) }}
      </p>

      <nav class="flex gap-6">
        <a href="{{ route('cookie.index') }}" class="text-xxs font-bold text-slate-400 hover:text-indigo-600 uppercase tracking-widest transition-colors">Cookies</a>
        <a href="{{ route('contact.index') }}" class="text-xxs font-bold text-slate-400 hover:text-indigo-600 uppercase tracking-widest transition-colors">Support</a>
      </nav>
    </div>
  </footer>

  {% include "components/cookie-consent.ratio.php" %}
  {% yield alpinejs %}
  <script nonce="<?= secure_output(csp_nonce(), true) ?>" src="{{ versioned_file('/js/app.js') }}"></script>
</body>
</html>