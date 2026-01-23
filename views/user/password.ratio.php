{% extends "layouts/admin.ratio.php" %}
{% block title %}Byt lösenord{% endblock %}
{% block pageId %}user-password{% endblock %}
{% block body %}
  <section>
    <div class="mb-8">
      <h1 class="text-3xl font-semibold mb-2">Byt lösenord</h1>
      <p class="text-gray-600">Uppdatera lösenordet för ditt konto.</p>
    </div>

    <form action="{{ route('user.password.update') }}" method="post" class="w-full max-w-2xl border border-gray-200 p-6 rounded-xl bg-white shadow-sm">
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

      <div class="relative mb-6">
        <label for="current_password" class="block text-sm font-medium text-slate-700 mb-1.5 ml-1">Nuvarande lösenord</label>
        <input type="password" name="current_password" id="current_password"
               placeholder="••••••••"
               class="w-full text-sm border-slate-300 rounded-md focus:border-indigo-500 focus:ring-indigo-500 transition shadow-sm bg-white">
        {% if (error($errors, 'current_password')) : %}
          <span class="block absolute -bottom-5 left-1 text-xxs text-red-600">{{ error($errors, 'current_password') }}</span>
        {% endif %}
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="relative">
          <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5 ml-1">Nytt lösenord</label>
          <input type="password" name="password" id="password"
                 placeholder="Minst 8 tecken"
                 class="w-full text-sm border-slate-300 rounded-md focus:border-indigo-500 focus:ring-indigo-500 transition shadow-sm bg-white">
          {% if (error($errors, 'password')) : %}
            <span class="block absolute -bottom-5 left-1 text-xxs text-red-600">{{ error($errors, 'password') }}</span>
          {% endif %}
        </div>

        <div class="relative">
          <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1.5 ml-1">Repetera lösenord</label>
          <input type="password" name="password_confirmation" id="password_confirmation"
                 placeholder="Repetera"
                 class="w-full text-sm border-slate-300 rounded-md focus:border-indigo-500 focus:ring-indigo-500 transition shadow-sm bg-white">
          {% if (error($errors, 'password_confirmation')) : %}
            <span class="block absolute -bottom-5 left-1 text-xxs text-red-600">{{ error($errors, 'password_confirmation') }}</span>
          {% endif %}
        </div>
      </div>

      <div class="flex flex-wrap items-center gap-3">
        <button type="submit" class="bg-indigo-600 text-white py-2 px-6 rounded-lg font-semibold hover:bg-indigo-700 transition duration-300 shadow-md shadow-indigo-100">
          Spara nytt lösenord
        </button>

        <a href="{{ route('user.edit') }}" class="py-2 px-6 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition duration-300">
          Tillbaka till profil
        </a>
      </div>

      {% if (error($errors, 'form-error')) : %}
      <div class="w-full mt-4 p-3 bg-red-50 border border-red-100 rounded-lg">
          <p class="text-xxs text-red-600 font-semibold leading-tight text-center">{{ error($errors, 'form-error') }}</p>
      </div>
      {% endif %}
    </form>
  </section>
{% endblock %}