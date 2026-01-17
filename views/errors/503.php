<!doctype html>
<html lang="<?= getenv('APP_LANG'); ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>503 - Underhåll pågår</title>
  <link rel="stylesheet" href="/css/app.css">
  <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="/favicons/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/favicons/favicon-16x16.png">
</head>
<body class="bg-slate-50 text-slate-600 antialiased min-h-screen flex flex-col">

  <header class="w-full bg-white border-b border-gray-200">
    <div class="container-centered mx-auto px-4 sm:px-6">
      <div class="h-16 flex justify-between items-center">
        <div class="flex items-center gap-2">
          <img src="/images/graphics/logo.png" alt="Logo" class="w-auto h-10 grayscale opacity-50">
          <span class="text-xl font-bold text-slate-400 tracking-tight"><?= getenv('APP_NAME'); ?></span>
        </div>
      </div>
    </div>
  </header>

  <main class="grow flex items-center justify-center p-6">
    <div class="max-w-md w-full text-center">
      <div class="relative mb-4">
        <span class="text-[120px] font-black text-blue-50 select-none leading-none">503</span>
        <div class="absolute inset-0 flex items-center justify-center">
            <h1 class="text-3xl font-black text-slate-800 uppercase tracking-tight">Strax klar!</h1>
        </div>
      </div>

      <div class="bg-white border border-gray-200 p-8 rounded-2xl shadow-xl relative z-10">
        <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z" />
            </svg>
        </div>
        <h2 class="text-xl font-bold text-gray-900 mb-3">Vi putsar på systemet</h2>
        <p class="text-slate-500 mb-8 leading-relaxed">
          Just nu utför vi planerat underhåll för att göra Åkebrands ännu snabbare. Vi beräknas vara tillbaka online inom kort.
        </p>

        <button onclick="location.reload()" class="inline-flex items-center justify-center px-8 py-3 bg-blue-600 text-white font-bold rounded-xl shadow-lg shadow-blue-100 hover:bg-blue-700 transition-all active:scale-[0.98] cursor-pointer">
            Kontrollera igen
        </button>
      </div>
    </div>
  </main>

  <footer class="py-8 text-center italic text-slate-400 font-medium">
    <p class="text-xs">&copy; <?= copyright(getenv('APP_COPY'), getenv('APP_COPY_YEAR')); ?></p>
  </footer>
</body>
</html>