<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-12 text-left">
    <!-- Kolumn 1: Om Radix -->
<div class="space-y-4">
        <div class="flex items-center gap-3 group">
            <!-- Kub-loggan i dämpat format för footern -->
            <div class="relative size-8 opacity-50 group-hover:opacity-100 transition-opacity duration-500">
                <div class="absolute inset-0 grid grid-cols-2 grid-rows-2 gap-px transform -rotate-12 group-hover:rotate-0 transition-transform duration-500">
                    <div class="bg-blue-500 rounded-tl-sm rounded-br-[1px]"></div>
                    <div class="bg-slate-400 rounded-tr-sm rounded-bl-[1px]"></div>
                    <div class="bg-slate-500 rounded-bl-sm rounded-tr-[1px]"></div>
                    <div class="bg-slate-700 rounded-br-sm rounded-tl-[1px]"></div>
                </div>
            </div>
            <span class="text-lg font-black text-slate-400 tracking-tighter italic group-hover:text-slate-600 transition-colors">Radix</span>
        </div>
        <p class="text-xs text-slate-500 leading-relaxed">
            Ett högpresterande PHP-framework byggt för kontroll, säkerhet och skalbarhet. Utvecklat med fokus på 100% testkvalitet och modern arkitektur.
        </p>
    </div>

    <!-- Kolumn 2: Navigation -->
    <div>
        <h4 class="text-[10px] font-bold text-slate-800 uppercase tracking-widest mb-4">Resurser</h4>
        <ul class="space-y-2">
            <li><a href="{{ route('home.index') }}" class="text-sm text-slate-500 hover:text-blue-600 transition-colors">Hemsida</a></li>
            <li><a href="{{ route('about.index') }}" class="text-sm text-slate-500 hover:text-blue-600 transition-colors">Dokumentation</a></li>
            <li><a href="{{ route('contact.index') }}" class="text-sm text-slate-500 hover:text-blue-600 transition-colors">Support</a></li>
        </ul>
    </div>

    <!-- Kolumn 3: Teknik Stack -->
    <div>
        <h4 class="text-[10px] font-bold text-slate-800 uppercase tracking-widest mb-4">Teknologi</h4>
        <ul class="space-y-2 text-sm text-slate-500">
            <li class="flex items-center gap-2">
                <span class="size-1 bg-blue-400 rounded-full"></span> PHP 8.3 / 8.4
            </li>
            <li class="flex items-center gap-2">
                <span class="size-1 bg-blue-400 rounded-full"></span> Tailwind CSS v4
            </li>
            <li class="flex items-center gap-2">
                <span class="size-1 bg-blue-400 rounded-full"></span> Alpine.js 3.x
            </li>
        </ul>
    </div>

    <!-- Kolumn 4: Säkerhet & Status -->
    <div>
        <h4 class="text-[10px] font-bold text-slate-800 uppercase tracking-widest mb-4">System</h4>
        <ul class="space-y-2">
            <li><a href="{{ route('cookie.index') }}" class="text-sm text-slate-500 hover:text-blue-600 transition-colors">Integritetspolicy</a></li>
            <li>
                <div class="flex items-center gap-2 text-sm text-slate-500">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Radix Engine: Stable
                </div>
            </li>
        </ul>
    </div>
</div>

<div class="mt-12 pt-8 border-t border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4">
    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
        &copy; {{ copyright(getenv('APP_COPY'), getenv('APP_COPY_YEAR')) }} | Radix Framework
    </p>
    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
        Build: {{ $currentVersion }}
    </div>
</div>