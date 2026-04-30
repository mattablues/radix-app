{% extends "layouts/main.ratio.php" %}
{% block headerContainer %}
    <div class="container-base h-15 flex items-center justify-between">
    {% include "layouts/partials/header-inner.ratio.php" %}
    </div>
{% endblock %}

{% block footerContainer %}
    <div class="transition-[margin] duration-200 lg:ml-(--sidebar-aside-w)">
        <div class="container-sidebar-main">
        {% include "layouts/partials/footer-inner.ratio.php" %}
        </div>
    </div>
{% endblock %}

{% block content %}
  <div class="flex-1">
    <div class="relative h-full">
      <aside x-data="publicSidebar()" class="fixed lg:left-0 left-[-300px] top-[60px] h-[calc(100vh-60px)] w-(--sidebar-aside-w) bg-white border-r border-gray-100 shadow-sm z-40 overflow-y-auto hide-scrollbar transition-all duration-200"
             x-bind:class="sidebarOpen ? 'left-0' : 'left-[-300px]'">
        <div class="py-6 px-4">
          <nav class="space-y-1">
            <h4 class="px-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Huvudmeny</h4>
            <a href="{{ route('home.index') }}" class="flex items-center py-2 px-3 rounded-xl text-sm font-medium transition-all {{ ($pageId === 'home') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-blue-50 hover:text-blue-700' }}">
              Hem
            </a>

            <div class="my-4 border-t border-gray-50"></div>

            <h4 class="px-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Information</h4>
            <a href="{{ route('about.index') }}" class="flex items-center py-2 px-3 rounded-xl text-sm font-medium transition-all {{ in_array($pageId, ['about', 'changelog']) ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-blue-50 hover:text-blue-700' }}">
              Om Radix
            </a>
            <a href="{{ route('contact.index') }}" class="flex items-center py-2 px-3 rounded-xl text-sm font-medium transition-all {{ ($pageId === 'contact') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-blue-50 hover:text-blue-700' }}">
              Support & Kontakt
            </a>
            <a href="{{ route('cookie.index') }}" class="flex items-center py-2 px-3 rounded-xl text-sm font-medium transition-all {{ ($pageId === 'cookies') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-blue-50 hover:text-blue-700' }}">
              Integritet & Säkerhet
            </a>
          </nav>
        </div>
      </aside>

      <main class="transition-[margin] duration-200 lg:ml-(--sidebar-aside-w)">
        <div class="container-sidebar-main pt-4 pb-8">
          {% include "components/flash.ratio.php" %}
          {% include "components/noscript.ratio.php" %}
          {% yield body %}
        </div>
      </main>
    </div>
  </div>
{% endblock %}
