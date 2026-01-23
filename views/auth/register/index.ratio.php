{% extends "layouts/auth.ratio.php" %}
{% block title %}Registrering{% endblock %}
{% block pageId %}register{% endblock %}
{% block body %}
    <div class="w-full max-w-xl px-4 py-8">
        <form action="{{ route('auth.register.create') }}" method="post" class="bg-white border border-gray-200 p-6 md:p-10 rounded-2xl shadow-xl">
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

          <!-- Namn Grid (Sida vid sida) -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-6 mb-6">
            <div class="relative">
              <label for="firstname" class="block text-xs font-bold uppercase tracking-widest text-gray-400 mb-2 ml-1">Förnamn</label>
              <input type="text" name="first_name" id="firstname" value="{{ old('first_name') }}"
                     placeholder="Johan"
                     class="w-full px-4 py-3 text-sm border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition duration-300">
              {% if (error($errors, 'first_name')) : %}
                <span class="block absolute -bottom-5 left-1 text-xxs text-red-600 font-medium">{{ error($errors, 'first_name') }}</span>
              {% endif %}
            </div>

            <div class="relative">
              <label for="lastname" class="block text-xs font-bold uppercase tracking-widest text-gray-400 mb-2 ml-1">Efternamn</label>
              <input type="text" name="last_name" id="lastname" value="{{ old('last_name') }}"
                     placeholder="Andersson"
                     class="w-full px-4 py-3 text-sm border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition duration-300">
              {% if (error($errors, 'last_name')) : %}
                <span class="block absolute -bottom-5 left-1 text-xxs text-red-600 font-medium">{{ error($errors, 'last_name') }}</span>
              {% endif %}
            </div>
          </div>

          <!-- E-post (Full bredd) -->
          <div class="relative mb-6">
            <label for="email" class="block text-xs font-bold uppercase tracking-widest text-gray-400 mb-2 ml-1">E-postadress</label>
            <input type="text" name="email" id="email" value="{{ old('email') }}"
                   placeholder="namn@exempel.se"
                   class="w-full px-4 py-3 text-sm border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition duration-300">
            {% if (error($errors, 'email')) : %}
                <span class="block absolute -bottom-5 left-1 text-xxs text-red-600 font-medium">{{ error($errors, 'email') }}</span>
            {% endif %}
          </div>

          <!-- Lösenord Grid (Sida vid sida) -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-6 mb-10">
            <div class="relative">
              <label for="password" class="block text-xs font-bold uppercase tracking-widest text-gray-400 mb-2 ml-1">Lösenord</label>
              <input type="password" name="password" id="password"
                     placeholder="Minst 8 tecken"
                     class="w-full px-4 py-3 text-sm border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition duration-300">
              {% if (error($errors, 'password')) : %}
                <span class="block absolute -bottom-5 left-1 text-xxs text-red-600 font-medium">{{ error($errors, 'password') }}</span>
              {% endif %}
            </div>

            <div class="relative">
              <label for="password-confirmation" class="block text-xs font-bold uppercase tracking-widest text-gray-400 mb-2 ml-1">Repetera</label>
              <input type="password" name="password_confirmation" id="password-confirmation"
                     placeholder="Repetera"
                     class="w-full px-4 py-3 text-sm border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition duration-300">
              {% if (error($errors, 'password_confirmation')) : %}
                <span class="block absolute -bottom-5 left-1 text-xxs text-red-600 font-medium">{{ error($errors, 'password_confirmation') }}</span>
              {% endif %}
            </div>
          </div>

          <div class="relative pt-2">
            <button type="submit" class="w-full py-3.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-lg shadow-indigo-200 transition-all duration-300 transform active:scale-[0.98] cursor-pointer">
              Registrera konto
            </button>

            {% if (error($errors, 'form-error')) : %}
                <div class="mt-4 p-3 bg-red-50 border border-red-100 rounded-lg">
                    <p class="text-xxs text-red-600 font-semibold leading-tight text-center">{{ error($errors, 'form-error') }}</p>
                </div>
            {% endif %}
          </div>
        </form>

        <p class="mt-8 text-center text-sm text-gray-600">
            Har du redan ett konto?
            <a href="{{ route('auth.login.index') }}" class="font-bold text-indigo-600 hover:text-indigo-800 transition-colors">Logga in här</a>
        </p>
    </div>
{% endblock %}