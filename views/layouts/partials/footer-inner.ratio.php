<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-12 text-left">
    <!-- Kolumn 1: Om Radix -->
    <div class="space-y-4">
        <div class="flex items-center gap-2">
            <img src="/images/graphics/logo.png" alt="Radix" class="w-auto h-8 grayscale opacity-50">
            <span class="text-lg font-black text-slate-400 tracking-tighter italic">Radix</span>
        </div>
        <p class="text-xs text-slate-500 leading-relaxed">
            Ett högpresterande PHP-framework byggt för kontroll, säkerhet och skalbarhet. Utvecklat med fokus på 100% testkvalitet.
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
        Build: v1.0.0
    </div>
</div>