{% extends "layouts/admin.ratio.php" %}
{% block title %}Skapa nytt konto{% endblock %}
{% block pageId %}admin-user-create{% endblock %}
{% block searchId %}search-users{% endblock %}
{% block body %}
    <section>
      <div class="mb-8">
        <h1 class="text-3xl font-semibold mb-2">Skapa nytt konto</h1>
        <p class="text-gray-600">Registrera en ny användare. Lösenord kommer att genereras automatiskt och skickas till användarens e-post.</p>
      </div>

      <form action="{{ route('admin.user.store') }}" method="post" enctype="multipart/form-data" class="w-full max-w-2xl border border-gray-200 p-6 rounded-xl bg-white shadow-sm">
        {{ csrf_field()|raw }}

        {% if (isset($honeypotId) && $honeypotId) : %}
          <input
            type="text"
            name="{{ $honeypotId }}"
            value=""
            tabindex="-1"
            autocomplete="off"
            class="hidden"
            aria-hidden="true"
          >
        {% endif %}

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Förnamn -->
            <div class="relative">
              <label for="firstname" class="block text-sm font-medium text-slate-700 mb-1.5 ml-1">Förnamn</label>
              <input type="text"
                     name="first_name"
                     id="firstname"
                     value="{{ old('first_name') }}"
                     placeholder="T.ex. Johan"
                     class="w-full text-sm border-slate-300 rounded-md focus:border-indigo-500 focus:ring-indigo-500 transition shadow-sm">
              {% if (error($errors, 'first_name')) : %}
                <span class="block absolute -bottom-5 left-1 text-xxs text-red-600">{{ error($errors, 'first_name') }}</span>
              {% endif %}
            </div>

            <!-- Efternamn -->
            <div class="relative">
              <label for="lastname" class="block text-sm font-medium text-slate-700 mb-1.5 ml-1">Efternamn</label>
              <input type="text"
                     name="last_name"
                     id="lastname"
                     value="{{ old('last_name') }}"
                     placeholder="T.ex. Andersson"
                     class="w-full text-sm border-slate-300 rounded-md focus:border-indigo-500 focus:ring-indigo-500 transition shadow-sm">
              {% if (error($errors, 'last_name')) : %}
                <span class="block absolute -bottom-5 left-1 text-xxs text-red-600">{{ error($errors, 'last_name') }}</span>
              {% endif %}
            </div>
        </div>

        <!-- E-postadress -->
        <div class="relative mb-8">
          <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5 ml-1">E-postadress</label>
          <input type="text"
                 name="email"
                 id="email"
                 value="{{ old('email') }}"
                 placeholder="namn@exempel.se"
                 class="w-full text-sm border-slate-300 rounded-md focus:border-indigo-500 focus:ring-indigo-500 transition shadow-sm">
          {% if (error($errors, 'email')) : %}
            <span class="block absolute -bottom-5 left-1 text-xxs text-red-600">{{ error($errors, 'email') }}</span>
          {% endif %}
        </div>

        <!-- Knappar -->
        <div class="flex items-center gap-3 pt-4 border-t border-gray-100">
          <button type="submit" class="bg-indigo-600 text-white py-2.5 px-6 rounded-lg font-semibold hover:bg-indigo-700 transition duration-300 shadow-md shadow-indigo-100">
            Skapa användare
          </button>
          <a href="{{ route('admin.user.index') }}" class="py-2.5 px-6 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition duration-300">
            Avbryt
          </a>
        </div>
      </form>
    </section>
{% endblock %}