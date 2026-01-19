{% extends "layouts/main.ratio.php" %}
{% block title %}Kontakt | Radix System{% endblock %}
{% block pageId %}contact{% endblock %}
{% block body %}
    <section class="py-8">
      <div class="container-centered layout-aside-both [--aside-left-w:250px] [--aside-right-w:250px]">

        <!-- Vänster Sidebar: Systemstatus & Info -->
        <aside class="area-aside-left sticky-top pt-6 lg:pt-4">
          <div class="space-y-6 lg:space-y-4">
            <div class="bg-white border border-gray-200 p-4 rounded-xl shadow-sm">
              <h4 class="font-bold text-slate-800 mb-2 uppercase text-[10px] tracking-widest text-blue-600">Teknisk Support</h4>
              <p class="text-xs text-slate-500 leading-relaxed">Ärenden rörande frameworket eller databasen hanteras prioriterat under vardagar.</p>
            </div>
            <div class="bg-white border border-gray-200 p-4 rounded-xl shadow-sm border-l-4 border-l-blue-500">
              <h4 class="font-bold text-slate-800 mb-2 uppercase text-[10px] tracking-widest">Utvecklar-tips</h4>
              <p class="text-xs text-slate-500 leading-relaxed">Bifoga gärna miljövariabler eller PHP-version om ditt ärende gäller en specifik miljö.</p>
            </div>
          </div>
        </aside>

        <!-- Huvudinnehåll (Formulär) -->
        <div class="area-content lg:px-8">
          <div class="mb-8">
            <h1 class="text-3xl font-black text-slate-900 tracking-tight mb-2">Kontakta Radix</h1>
            <p class="text-slate-500">Frågor om licensering, partnerskap eller teknisk assistans? Vi är bara ett meddelande bort.</p>
          </div>

          <form action="{{ route('contact.create') }}" method="post" class="bg-white border border-gray-200 p-6 md:p-8 rounded-[2rem] shadow-xl">
            {{ csrf_field()|raw }}
            {{ honeypot_field()|raw }}

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
              <div class="relative">
                <label for="first-name" class="block text-[11px] font-bold uppercase tracking-widest text-gray-400 mb-2 ml-1">Förnamn</label>
                <input type="text" name="first_name" id="first-name" value="{{ old('first_name') }}"
                       placeholder="Mats"
                       class="w-full px-4 py-3 text-sm border-slate-200 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition duration-300 shadow-sm">
                {% if (error($errors, 'first_name')) : %}
                  <span class="block absolute -bottom-5 left-1 text-xxs text-red-600 font-medium">{{ error($errors, 'first_name') }}</span>
                {% endif %}
              </div>

              <div class="relative">
                <label for="last-name" class="block text-[11px] font-bold uppercase tracking-widest text-gray-400 mb-2 ml-1">Efternamn</label>
                <input type="text" name="last_name" id="last-name" value="{{ old('last_name') }}"
                       placeholder="Åkebrand"
                       class="w-full px-4 py-3 text-sm border-slate-200 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition duration-300 shadow-sm">
                {% if (error($errors, 'last_name')) : %}
                  <span class="block absolute -bottom-5 left-1 text-xxs text-red-600 font-medium">{{ error($errors, 'last_name') }}</span>
                {% endif %}
              </div>
            </div>

            <div class="relative mb-6">
              <label for="email" class="block text-[11px] font-bold uppercase tracking-widest text-gray-400 mb-2 ml-1">E-postadress</label>
              <input type="text" name="email" id="email" value="{{ old('email') }}"
                     placeholder="mats@radix.se"
                     class="w-full px-4 py-3 text-sm border-slate-200 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition duration-300 shadow-sm">
              {% if (error($errors, 'email')) : %}
                <span class="block absolute -bottom-5 left-1 text-xxs text-red-600 font-medium">{{ error($errors, 'email') }}</span>
              {% endif %}
            </div>

            <div class="relative mb-8">
              <label for="message" class="block text-[11px] font-bold uppercase tracking-widest text-gray-400 mb-2 ml-1">Meddelande</label>
              <textarea name="message" id="message" rows="6"
                        placeholder="Beskriv ditt ärende här..."
                        class="w-full px-4 py-3 text-sm border-slate-200 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition duration-300 shadow-sm">{{ old('message') }}</textarea>
              {% if (error($errors, 'message')) : %}
                <span class="block absolute -bottom-5 left-1 text-xxs text-red-600 font-medium">{{ error($errors, 'message') }}</span>
              {% endif %}
            </div>

            <div class="relative pt-2">
              <button type="submit" class="w-full md:w-auto px-10 py-3.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-lg shadow-blue-100 transition-all duration-300 transform active:scale-[0.98] cursor-pointer">
                Skicka förfrågan
              </button>

              {% if (error($errors, 'form-error')) : %}
                <div class="mt-4 p-3 bg-red-50 border border-red-100 rounded-lg">
                  <p class="text-xxs text-red-600 font-semibold leading-tight text-center">{{ error($errors, 'form-error') }}</p>
                </div>
              {% endif %}
            </div>
          </form>
        </div>

        <!-- Höger Sidebar: Tech Stack & Privacy -->
        <aside class="area-aside-right sticky-top  pt-6 lg:pt-4">
          <div class="bg-slate-900 rounded-2xl p-6 text-white shadow-xl">
            <h4 class="text-xs font-bold uppercase tracking-widest text-blue-400 mb-4">Om Radix Support</h4>
            <ul class="space-y-4 text-xs">
              <li class="flex flex-col">
                <span class="text-blue-300 text-[10px] font-bold uppercase mb-1">Partnerskap</span>
                <p class="text-slate-400">Är du intresserad av att använda Radix i ditt nästa kommersiella projekt? Vi erbjuder skräddarsydda lösningar.</p>
              </li>
              <li class="flex flex-col pt-4 border-t border-slate-800">
                <span class="text-blue-300 text-[10px] font-bold uppercase mb-1">Säker hantering</span>
                <p class="text-slate-400">Din data hanteras via krypterade kanaler och sparas endast så länge ärendet kräver det.</p>
              </li>
            </ul>
          </div>
        </aside>

      </div>
    </section>
{% endblock %}