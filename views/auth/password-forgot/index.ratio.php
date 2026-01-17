{% extends "layouts/auth.ratio.php" %}
{% block title %}Glömt lösenord?{% endblock %}
{% block pageId %}password-forgot{% endblock %}
{% block body %}
    <div class="w-full max-w-md px-4">
        <form action="{{ route('auth.password-forgot.create') }}" method="post" class="bg-white border border-gray-200 p-8 rounded-2xl shadow-xl">
          {{ csrf_field()|raw }}

          <!-- E-postadress -->
          <div class="relative mb-8">
            <label for="email" class="block text-xs font-bold uppercase tracking-widest text-gray-400 mb-2 ml-1">E-postadress</label>
            <input type="text" name="email" id="email" value="{{ old('email') }}"
                   placeholder="namn@exempel.se"
                   class="w-full pl-4 pr-4 py-3 text-sm border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition duration-300">
            {% if (error($errors, 'email')) : %}
                <span class="block absolute -bottom-5 left-1 text-xxs text-red-600 font-medium">{{ error($errors, 'email') }}</span>
            {% endif %}
          </div>

          <div class="relative">
            <button type="submit" class="w-full py-3.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-lg shadow-indigo-200 transition-all duration-300 transform active:scale-[0.98] cursor-pointer">
              Skicka återställningslänk
            </button>

            {% if (error($errors, 'form-error')) : %}
                <div class="mt-4 p-3 bg-red-50 border border-red-100 rounded-lg">
                    <p class="text-xxs text-red-600 font-semibold leading-tight text-center">{{ error($errors, 'form-error') }}</p>
                </div>
            {% endif %}
          </div>
        </form>

        <!-- Footer-länk under kortet -->
        <p class="mt-8 text-center text-sm text-gray-600">
            Kom du ihåg ditt lösenord?
            <a href="{{ route('auth.login.index') }}" class="font-bold text-indigo-600 hover:text-indigo-800 transition-colors">Gå till logga in</a>
        </p>
    </div>
{% endblock %}