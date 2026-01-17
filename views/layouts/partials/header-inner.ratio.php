
<!-- Logo -->
<a href="{{ route('home.index') }}" class="flex items-center gap-2.5 z-70 group">
  <img src="/images/graphics/logo.png" alt="Radix Logo" class="w-auto h-9 transition-transform group-hover:scale-105">
  <span class="text-xl font-black text-slate-900 tracking-tighter italic">Radix</span>
</a>

<!-- Desktop Navigation -->
<nav class="hidden lg:flex items-center gap-8">
  <a href="{{ route('home.index') }}" class="text-[11px] font-bold text-slate-500 hover:text-blue-600 transition-colors uppercase tracking-widest">Hem</a>
  <a href="{{ route('about.index') }}" class="text-[11px] font-bold text-slate-500 hover:text-blue-600 transition-colors uppercase tracking-widest">Om Radix</a>
  <a href="{{ route('contact.index') }}" class="text-[11px] font-bold text-slate-500 hover:text-blue-600 transition-colors uppercase tracking-widest">Kontakt</a>

  <div class="h-6 w-px bg-slate-200 mx-2"></div>

  <a href="{{ route('auth.login.index') }}" class="px-5 py-2 bg-blue-600 text-white text-[10px] font-black uppercase tracking-widest rounded-xl shadow-md shadow-blue-100 hover:bg-blue-700 transition-all active:scale-95">
    Logga in
  </a>
</nav>

<!-- Mobile Menu Trigger -->
<div class="lg:hidden flex items-center gap-4" x-data="{ menu: false }">
  <button class="p-2 -mr-2 text-slate-900 focus:outline-none cursor-pointer" x-on:click="menu = true">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-7 h-7">
      <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M12 17.25h8.25" />
    </svg>
  </button>

  <!-- Mobile Menu Overlay -->
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
      x-cloak>

      <div class="flex justify-between items-center mb-12">
        <span class="text-xl font-black text-white tracking-tighter italic">Radix</span>
        <button class="p-2 text-white/50 hover:text-white transition-colors" x-on:click="menu = false">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-8 h-8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <nav class="flex flex-col space-y-6">
        <a href="{{ route('home.index') }}" @click="menu = false" class="text-3xl font-black text-white hover:text-blue-400 transition-colors">Hem</a>
        <a href="{{ route('about.index') }}" @click="menu = false" class="text-3xl font-black text-white hover:text-blue-400 transition-colors">Om Radix</a>
        <a href="{{ route('contact.index') }}" @click="menu = false" class="text-3xl font-black text-white hover:text-blue-400 transition-colors">Kontakt</a>
      </nav>

      <div class="mt-auto">
        <a href="{{ route('auth.login.index') }}" class="w-full inline-flex justify-center items-center py-4 bg-blue-600 text-white font-black uppercase tracking-widest rounded-2xl shadow-xl shadow-blue-500/20">
          Logga in i systemet
        </a>
      </div>
    </div>
  </template>
</div>