<!-- Logo -->
<a href="{{ route('home.index') }}" class="flex items-center gap-3 z-70 group">
  <div class="relative size-10">
    <div class="absolute inset-0 grid grid-cols-2 grid-rows-2 gap-0.5 transform -rotate-12 group-hover:rotate-0 transition-transform duration-500">
      <div class="bg-blue-600 rounded-tl-md rounded-br-[2px] shadow-sm"></div>
      <div class="bg-slate-300 rounded-tr-md rounded-bl-[2px] group-hover:bg-slate-400 transition-colors"></div>
      <div class="bg-slate-400 rounded-bl-md rounded-tr-[2px] group-hover:bg-slate-500 transition-colors"></div>
      <div class="bg-slate-900 rounded-br-md rounded-tl-[2px] shadow-md"></div>
    </div>

    <div class="absolute top-1 left-1 size-1.5 bg-white/30 rounded-full blur-[1px]"></div>
  </div>

  <div class="flex flex-col -space-y-1">
    <div class="flex items-center gap-1">
      <span class="text-xl font-black text-slate-900 tracking-tighter italic">Radix</span>
      <span class="size-1.5 bg-blue-600 rounded-full animate-pulse mt-1"></span>
    </div>
    <span class="text-[9px] font-bold uppercase tracking-[0.3em] text-slate-400 group-hover:text-blue-600 transition-colors">Framework</span>
  </div>
</a>

<!-- Desktop Navigation -->
<nav class="hidden lg:flex items-center gap-8">
  <a href="{{ route('home.index') }}" class="text-[11px] font-bold uppercase tracking-widest transition-colors {{ ($pageId === 'home') ? 'text-blue-600' : 'text-slate-500 hover:text-blue-600' }}">Hem</a>
  <a href="{{ route('about.index') }}" class="text-[11px] font-bold uppercase tracking-widest transition-colors {{ in_array($pageId, ['about', 'changelog']) ? 'text-blue-600' : 'text-slate-500 hover:text-blue-600' }}">Om Radix</a>
  <a href="{{ route('contact.index') }}" class="text-[11px] font-bold uppercase tracking-widest transition-colors {{ ($pageId === 'contact') ? 'text-blue-600' : 'text-slate-500 hover:text-blue-600' }}">Kontakt</a>
</nav>

<!-- Mobile Menu Trigger -->
<div class="lg:hidden flex items-center gap-4" x-data="mobileMenu()">
  <button class="p-2 -mr-2 text-slate-900 focus:outline-none cursor-pointer" x-on:click="open()" type="button" aria-label="Öppna meny">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-7 h-7">
      <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M12 17.25h8.25" />
    </svg>
  </button>

  <template x-teleport="body">
    <div
      x-show="menu"
      x-transition:enter="transition ease-out duration-300"
      x-transition:enter-start="translate-x-full"
      x-transition:enter-end="translate-x-0"
      x-transition:leave="transition ease-in duration-200"
      x-transition:leave-start="translate-x-0"
      x-transition:leave-end="translate-x-full"
      class="fixed inset-0 bg-slate-900 z-[100] flex flex-col p-8"
      x-cloak
    >
      <div class="flex justify-between items-center mb-12">
        <span class="text-xl font-black text-white tracking-tighter italic">Radix</span>
        <button class="p-2 text-white/50 hover:text-white transition-colors" x-on:click="close()" type="button" aria-label="Stäng meny">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-8 h-8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <nav class="flex flex-col space-y-6">
        <a href="{{ route('home.index') }}" x-on:click="close()" class="text-3xl font-black transition-colors {{ ($pageId === 'home') ? 'text-blue-400' : 'text-white hover:text-blue-400' }}">Hem</a>
        <a href="{{ route('about.index') }}" x-on:click="close()" class="text-3xl font-black transition-colors {{ in_array($pageId, ['about', 'changelog']) ? 'text-blue-400' : 'text-white hover:text-blue-400' }}">Om Radix</a>
        <a href="{{ route('contact.index') }}" x-on:click="close()" class="text-3xl font-black transition-colors {{ ($pageId === 'contact') ? 'text-blue-400' : 'text-white hover:text-blue-400' }}">Kontakt</a>
      </nav>
    </div>
  </template>
</div>
