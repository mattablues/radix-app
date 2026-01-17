<!doctype html>
<html lang="<?= getenv('APP_LANG'); ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>413 - Filen är för stor</title>
  <link rel="stylesheet" href="/css/app.css">
  <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="/favicons/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/favicons/favicon-16x16.png">
</head>
<body class="bg-slate-50 text-slate-600 antialiased min-h-screen flex flex-col">

  <header class="w-full bg-white border-b border-gray-200">
    <div class="container-centered mx-auto px-4 sm:px-6">
      <div class="h-16 flex justify-between items-center">
        <a href="<?= getenv('APP_URL') ?>" class="flex items-center gap-2 transition-opacity hover:opacity-80">
          <img src="/images/graphics/logo.png" alt="Logo" class="w-auto h-10 grayscale opacity-50">
          <span class="text-xl font-bold text-slate-900 tracking-tight"><?= getenv('APP_NAME'); ?></span>
        </a>

        <a href="<?= getenv('APP_URL') ?>" class="p-2 rounded-full text-slate-400 hover:text-indigo-600 hover:bg-slate-50 transition-all" title="Gå hem">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
            <path d="M11.47 3.841a.75.75 0 0 1 1.06 0l8.69 8.69a.75.75 0 1 0 1.06-1.061l-8.689-8.69a2.25 2.25 0 0 0-3.182 0l-8.69 8.69a.75.75 0 1 0 1.061 1.06l8.69-8.689Z" />
            <path d="m12 5.432 8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 0 1-.75-.75v-4.5a.75.75 0 0 0-.75-.75h-3a.75.75 0 0 0-.75.75V21a.75.75 0 0 1-.75.75H5.625a1.875 1.875 0 0 1-1.875-1.875v-6.198a2.29 2.29 0 0 0 .091-.086L12 5.432Z" />
          </svg>
        </a>
      </div>
    </div>
  </header>

  <main class="grow flex items-center justify-center p-6">
    <div class="max-w-md w-full text-center">
      <div class="relative mb-4">
        <span class="text-[120px] font-black text-indigo-50 select-none leading-none">413</span>
        <div class="absolute inset-0 flex items-center justify-center">
            <h1 class="text-3xl font-black text-slate-800 uppercase tracking-tight">Viktväktaren!</h1>
        </div>
      </div>

      <div class="bg-white border border-gray-200 p-8 rounded-2xl shadow-xl relative z-10">
        <h2 class="text-xl font-bold text-gray-900 mb-3">Filen är för stor</h2>
        <p class="text-slate-500 mb-8 leading-relaxed">
          Det du försökte skicka väger lite för mycket för att servern ska orka bära det. Prova med en mindre fil eller komprimera bilden.
        </p>

        <div class="flex flex-col gap-3">
            <a href="javascript:history.back()" class="inline-flex items-center justify-center px-6 py-3 bg-indigo-600 text-white font-bold rounded-xl shadow-lg hover:bg-indigo-700 transition-all active:scale-[0.98]">
                Gå tillbaka och försök igen
            </a>
        </div>
      </div>
    </div>
  </main>

  <footer class="py-8">
    <p class="text-xs text-slate-400 font-medium text-center italic tracking-wide">
        &copy; <?= copyright(getenv('APP_COPY'), getenv('APP_COPY_YEAR')); ?>
    </p>
  </footer>
</body>
</html>