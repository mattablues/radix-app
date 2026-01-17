{% extends "layouts/auth.ratio.php" %}
{% block title %}Logga in{% endblock %}
{% block pageId %}login{% endblock %}
{% block body %}
    <div class="w-full max-w-md px-4">
        <!-- Logo / Header ovanför kortet -->
        <div class="flex flex-col items-center mb-8">
            <a href="{{ route('home.index') }}" class="mb-4 transition-transform hover:scale-105">
                <img src="/images/graphics/logo.png" alt="Logo" class="w-auto h-16">
            </a>
            <h1 class="text-2xl font-bold text-gray-900">Välkommen tillbaka</h1>
            <p class="text-sm text-gray-500 mt-1">Logga in på ditt konto för att fortsätta</p>
        </div>

        <form action="{{ route('auth.login.create') }}" method="post" class="bg-white border border-gray-200 p-8 rounded-2xl shadow-xl">
          {{ csrf_field()|raw }}

          <!-- E-postadress -->
          <div class="relative mb-6">
            <label for="email" class="block text-xs font-bold uppercase tracking-widest text-gray-400 mb-2 ml-1">E-postadress</label>
            <div class="relative">
                <input type="text" name="email" id="email" value="{{ old('email') }}"
                       placeholder="namn@exempel.se"
                       class="w-full pl-4 pr-4 py-3 text-sm border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition duration-300">
            </div>
            {% if (error($errors, 'email')) : %}
                <span class="block absolute -bottom-5 left-1 text-xxs text-red-600 font-medium">{{ error($errors, 'email') }}</span>
            {% endif %}
          </div>

          <!-- Lösenord -->
          <div class="relative mb-8">
            <div class="flex justify-between items-center mb-2 ml-1">
                <label for="password" class="block text-xs font-bold uppercase tracking-widest text-gray-400">Lösenord</label>
                <a href="{{ route('auth.password-forgot.index') }}" class="text-xxs font-bold text-indigo-600 hover:text-indigo-800 uppercase tracking-tighter transition-colors">Glömt lösenord?</a>
            </div>
            <input type="password" name="password" id="password"
                   placeholder="••••••••"
                   class="w-full pl-4 pr-4 py-3 text-sm border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition duration-300">
            {% if (error($errors, 'password')) : %}
                <span class="block absolute -bottom-5 left-1 text-xxs text-red-600 font-medium">{{ error($errors, 'password') }}</span>
            {% endif %}
          </div>

          <div class="relative">
            <button type="submit" class="w-full py-3.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-lg shadow-indigo-200 transition-all duration-300 transform active:scale-[0.98] cursor-pointer">
              Logga in
            </button>

            {% if (error($errors, 'form-error')) : %}
                <div class="mt-4 p-3 bg-red-50 border border-red-100 rounded-lg">
                    <p class="text-xxs text-red-600 font-semibold leading-tight text-center">{{ error($errors, 'form-error') }}</p>
                </div>
            {% endif %}
          </div>
        </form>

        <!-- Footer-länk under kortet -->
        {% if(getenv('APP_PRIVATE') === '0') : %}
        <p class="mt-8 text-center text-sm text-gray-600">
            Saknar du ett konto?
            <a href="{{ route('auth.register.index') }}" class="font-bold text-indigo-600 hover:text-indigo-800 transition-colors">Registrera dig här</a>
        </p>
        {% endif; %}
    </div>
{% endblock %}