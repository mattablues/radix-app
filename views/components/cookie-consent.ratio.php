<div
    x-data="{
        showCookieBanner: !localStorage.getItem('cookies_accepted')
    }"
    x-show="showCookieBanner"
    x-transition:enter="transition ease-out duration-500"
    x-transition:enter-start="translate-y-full opacity-0"
    x-transition:enter-end="translate-y-0 opacity-100"
    x-transition:leave="transition ease-in duration-300"
    x-transition:leave-start="translate-y-0 opacity-100"
    x-transition:leave-end="translate-y-full opacity-0"
    class="fixed bottom-6 left-1/2 -translate-x-1/2 w-[calc(100%-2rem)] max-w-2xl z-[100]"
    style="display: none;"
>
    <div class="bg-slate-900/95 backdrop-blur-md border border-white/10 p-5 md:p-6 rounded-3xl shadow-2xl flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="flex items-start gap-4">
            <div class="p-2 bg-indigo-500/20 rounded-xl text-indigo-400 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <div class="text-left">
                <p class="text-white font-bold text-sm mb-1 text-balance">Vi värnar om din säkerhet</p>
                <p class="text-slate-400 text-xs leading-relaxed">
                    Vi använder nödvändiga cookies för att hantera din inloggning och skydda dina uppgifter.
                    Genom att fortsätta godkänner du detta. <a href="{{ route('cookie.index') }}" class="text-indigo-400 hover:text-indigo-300 underline font-medium">Läs mer här</a>.
                </p>
            </div>
        </div>

        <button
            @click="localStorage.setItem('cookies_accepted', 'true'); showCookieBanner = false"
            class="whitespace-nowrap px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-2xl transition-all shadow-lg shadow-indigo-500/20 transform active:scale-95 cursor-pointer"
        >
            Jag förstår
        </button>
    </div>
</div>