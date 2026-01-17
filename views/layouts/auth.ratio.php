<!doctype html>
<html lang="{{ getenv('APP_LANG') }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{% yield title %} | Radix</title>
  <link rel="stylesheet" href="{{ versioned_file('/css/app.css') }}">
  <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="/favicons/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/favicons/favicon-16x16.png">
  <link rel="manifest" href="/favicons/site.webmanifest">
</head>
<body id="{% yield pageId %}" class="flex flex-col min-h-screen bg-slate-50 text-slate-600 antialiased font-sans">

  <!-- Minimalistisk Auth-Header -->
  <header class="w-full bg-white/80 backdrop-blur-md border-b border-gray-100 fixed top-0 z-50">
    <div class="container-centered mx-auto px-6">
      <div class="h-16 flex justify-between items-center">
        <a href="{{ route('home.index') }}" class="flex items-center gap-2.5 group">
          <div class="relative size-8">
            <div class="absolute inset-0 grid grid-cols-2 grid-rows-2 gap-0.5 transform -rotate-12 group-hover:rotate-0 transition-transform duration-500">
              <div class="bg-blue-600 rounded-tl-sm rounded-br-[1px]"></div>
              <div class="bg-slate-200 rounded-tr-sm rounded-bl-[1px]"></div>
              <div class="bg-slate-300 rounded-bl-sm rounded-tr-[1px]"></div>
              <div class="bg-slate-900 rounded-br-sm rounded-tl-[1px]"></div>
            </div>
          </div>
          <span class="text-lg font-black text-slate-900 tracking-tighter italic">Radix</span>
        </a>

        <a href="{{ route('home.index') }}" class="flex items-center gap-2 text-[10px] font-bold text-slate-400 hover:text-blue-600 uppercase tracking-widest transition-all" title="Tillbaka till start">
          <span>Stäng</span>
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-4">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </a>
      </div>
    </div>
  </header>

  <!-- Huvudinnehåll med centrerat fokus -->
  <div class="grow flex flex-col justify-center relative overflow-hidden pt-16">
    <!-- Dekorativa bakgrundselement för "blueprint"-känsla -->
    <div class="absolute top-1/4 -left-20 size-96 bg-blue-100/30 rounded-full blur-3xl -z-10"></div>
    <div class="absolute bottom-1/4 -right-20 size-96 bg-slate-200/40 rounded-full blur-3xl -z-10"></div>

    <div class="container-centered py-12">
      {% include "components/flash.ratio.php" %}
      {% include "components/noscript.ratio.php" %}

      <main class="flex flex-col items-center justify-center">
        {% yield body %}
      </main>
    </div>
  </div>

  <!-- Kompakt Auth-Footer -->
  <footer class="py-8 border-t border-gray-100 bg-white">
    <div class="container-centered flex flex-col md:flex-row justify-between items-center gap-6 px-6">
      <div class="flex items-center gap-4">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em]">
          &copy; {{ copyright(getenv('APP_COPY'), getenv('APP_COPY_YEAR')) }} | Radix Engine
        </p>
      </div>

      <nav class="flex gap-8">
        <a href="{{ route('cookie.index') }}" class="text-[10px] font-bold text-slate-400 hover:text-blue-600 uppercase tracking-widest transition-all">Säkerhet</a>
        <a href="{{ route('contact.index') }}" class="text-[10px] font-bold text-slate-400 hover:text-blue-600 uppercase tracking-widest transition-all">Systemsupport</a>
      </nav>
    </div>
  </footer>

  {% include "components/cookie-consent.ratio.php" %}
  {% yield alpinejs %}
  <script nonce="<?= secure_output(csp_nonce(), true) ?>" src="{{ versioned_file('/js/app.js') }}"></script>
</body>
</html>