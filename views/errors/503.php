<!doctype html>
<html lang="<?= secure_output((string) getenv('APP_LANG')); ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>503 - Underhåll pågår | Radix Engine</title>
  <link rel="stylesheet" href="<?= versioned_file('/css/app.css') ?>">
      <link rel="icon" type="image/png" href="/favicons/favicon-96x96.png" sizes="96x96" />
      <link rel="icon" type="image/svg+xml" href="/favicons/favicon.svg" />
      <link rel="shortcut icon" href="/favicons/favicon.ico" />
      <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png" />
      <meta name="apple-mobile-web-app-title" content="Radix" />
      <link rel="manifest" href="/favicons/site.webmanifest" />
</head>
<body class="bg-slate-50 text-slate-600 antialiased min-h-screen flex flex-col font-sans">

  <!-- Header: Enhetlig Radix-profil (utan navigering under maintenance) -->
  <header class="w-full bg-white/80 backdrop-blur-md border-b border-gray-100 sticky top-0 z-50">
    <div class="container-centered mx-auto px-6">
      <div class="h-16 flex items-center">
        <div class="flex items-center gap-2.5 opacity-50">
          <div class="relative size-8">
            <div class="absolute inset-0 grid grid-cols-2 grid-rows-2 gap-0.5">
              <div class="bg-blue-600 rounded-tl-sm rounded-br-[1px]"></div>
              <div class="bg-slate-200 rounded-tr-sm rounded-bl-[1px]"></div>
              <div class="bg-slate-300 rounded-bl-sm rounded-tr-[1px]"></div>
              <div class="bg-slate-900 rounded-br-sm rounded-tl-[1px]"></div>
            </div>
          </div>
          <span class="text-lg font-black text-slate-900 tracking-tighter italic">Radix</span>
        </div>
      </div>
    </div>
  </header>

  <!-- Main Content -->
  <main class="grow flex items-center justify-center p-6 relative overflow-hidden">
    <!-- Bakgrundsdekor -->
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 size-[500px] bg-blue-100/20 rounded-full blur-3xl -z-10"></div>

    <div class="max-w-md w-full text-center">
      <div class="relative mb-8">
        <span class="text-[140px] font-black text-slate-200/60 select-none leading-none tracking-tighter">503</span>
        <div class="absolute inset-0 flex items-center justify-center pt-8">
            <h1 class="text-2xl font-black text-slate-800 uppercase tracking-[0.2em]">Maintenance</h1>
        </div>
      </div>

      <div class="bg-white border border-gray-100 p-10 rounded-[2.5rem] shadow-2xl shadow-blue-900/5 relative z-10">
        <div class="size-20 bg-blue-50 text-blue-600 rounded-[2rem] flex items-center justify-center mx-auto mb-8 shadow-inner relative">
            <svg xmlns="http://www.w3.org/2000/svg" class="size-10 animate-spin-slow" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <div class="absolute -top-1 -right-1 size-4 bg-emerald-500 rounded-full border-4 border-white animate-pulse"></div>
        </div>

        <h2 class="text-lg font-bold text-gray-900 mb-3 uppercase tracking-wide">Systemoptimering pågår</h2>
        <p class="text-sm text-slate-500 mb-10 leading-relaxed">
          Radix Engine genomgår för närvarande en schemalagd uppdatering av kärnkomponenterna. Vi beräknas vara tillbaka online inom kort.
        </p>

        <button onclick="location.reload()" class="inline-flex items-center justify-center px-8 py-4 bg-blue-600 text-white text-xs font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-blue-500/20 hover:bg-blue-700 transition-all active:scale-[0.98] cursor-pointer">
            Verifiera systemstatus
        </button>
      </div>
    </div>
  </main>

  <!-- Footer -->
  <footer class="py-10 border-t border-gray-100 bg-white">
    <p class="text-[10px] font-bold text-slate-400 text-center uppercase tracking-[0.3em]">
        &copy; <?= copyright((string) getenv('APP_COPY'), (string) getenv('APP_COPY_YEAR')); ?> | Radix Deployment
    </p>
  </footer>

</body>
</html>