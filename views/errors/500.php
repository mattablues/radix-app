<!doctype html>
<html lang="<?= secure_output((string) getenv('APP_LANG')); ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>500 - Systemfel | Radix</title>
  <link rel="stylesheet" href="<?= versioned_file('/css/app.css') ?>">
  <link rel="icon" type="image/png" href="/favicons/favicon-96x96.png" sizes="96x96" />
  <link rel="icon" type="image/svg+xml" href="/favicons/favicon.svg" />
  <link rel="shortcut icon" href="/favicons/favicon.ico" />
  <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png" />
  <meta name="apple-mobile-web-app-title" content="Radix" />
  <link rel="manifest" href="/favicons/site.webmanifest" />
</head>
<body class="bg-slate-50 text-slate-600 antialiased min-h-screen flex flex-col font-sans">

  <!-- Header: Enhetlig Radix-profil -->
  <header class="w-full bg-white/80 backdrop-blur-md border-b border-gray-100 sticky top-0 z-50">
    <div class="container-centered mx-auto px-6">
      <div class="h-16 flex justify-between items-center">
        <a href="<?= route('home.index') ?>" class="flex items-center gap-2.5 group">
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

        <a href="<?= route('home.index') ?>" class="p-2 rounded-xl text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-all group" title="Gå hem">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
          </svg>
        </a>
      </div>
    </div>
  </header>

  <!-- Main Content -->
  <main class="grow flex items-center justify-center p-6 relative overflow-hidden">
    <!-- Bakgrundsdekor: Kritisk ton (Röd/Slate) -->
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 size-[500px] bg-red-100/20 rounded-full blur-3xl -z-10"></div>

    <div class="max-w-md w-full text-center">
      <div class="relative mb-8">
        <span class="text-[140px] font-black text-slate-200 select-none leading-none tracking-tighter opacity-50">500</span>
        <div class="absolute inset-0 flex items-center justify-center pt-8">
            <h1 class="text-2xl font-black text-slate-800 uppercase tracking-[0.2em]">Server Error</h1>
        </div>
      </div>

      <div class="bg-white border border-gray-100 p-10 rounded-[2.5rem] shadow-2xl shadow-blue-900/5 relative z-10">
        <h2 class="text-lg font-bold text-gray-900 mb-3 uppercase tracking-wide">Ett oväntat fel uppstod</h2>
        <p class="text-sm text-slate-500 mb-10 leading-relaxed">
          Kärnsystemet har stött på en intern komplikation vid bearbetningen av din begäran. Våra tekniker har informerats automatiskt.
        </p>

        <div class="flex flex-col gap-4">
            <a href="<?= route('home.index') ?>" class="inline-flex items-center justify-center px-8 py-4 bg-red-600 text-white text-xs font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-red-500/20 hover:bg-red-700 transition-all active:scale-[0.98]">
                Återgå till start
            </a>
            <button onclick="location.reload()" class="text-[10px] font-bold text-slate-400 hover:text-blue-600 uppercase tracking-widest transition-colors py-2 cursor-pointer">
                Försök igen
            </button>
        </div>
      </div>
    </div>
  </main>

  <!-- Footer -->
  <footer class="py-10 border-t border-gray-100 bg-white">
    <p class="text-[10px] font-bold text-slate-400 text-center uppercase tracking-[0.3em]">
        &copy; <?= copyright((string) getenv('APP_COPY'), (string) getenv('APP_COPY_YEAR')); ?> | Radix Core
    </p>
  </footer>

</body>
</html>