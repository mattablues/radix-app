{% extends "layouts/admin.ratio.php" %}
{% block title %}Redigera konto{% endblock %}
{% block pageId %}user-edit{% endblock %}
{% block searchId %}search-users{% endblock %}
{% block body %}
    <section>
      <div class="mb-8">
        <h1 class="text-3xl font-semibold mb-2">Redigera profil</h1>
        <p class="text-gray-600">Uppdatera personuppgifter och avatar. Lösenord byts på en separat sida.</p>
      </div>

      <form action="{{ route('user.update') }}" method="post" enctype="multipart/form-data" class="w-full max-w-2xl border border-gray-200 p-6 rounded-xl bg-white shadow-sm">
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

        <!-- Personuppgifter Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="relative">
              <label for="firstname" class="block text-sm font-medium text-slate-700 mb-1.5 ml-1">Förnamn</label>
              <input type="text" name="first_name" id="firstname" value="{{ old('first_name') ?: $user->getAttribute('first_name') }}" class="w-full text-sm border-slate-300 rounded-md focus:border-indigo-500 focus:ring-indigo-500 transition shadow-sm">
              {% if (error($errors, 'first_name')) : %}
                <span class="block absolute -bottom-5 left-1 text-xxs text-red-600">{{ error($errors, 'first_name') }}</span>
              {% endif %}
            </div>

            <div class="relative">
              <label for="lastname" class="block text-sm font-medium text-slate-700 mb-1.5 ml-1">Efternamn</label>
              <input type="text" name="last_name" id="lastname" value="{{ old('last_name') ?: $user->getAttribute('last_name') }}" class="w-full text-sm border-slate-300 rounded-md focus:border-indigo-500 focus:ring-indigo-500 transition shadow-sm">
              {% if (error($errors, 'last_name')) : %}
                <span class="block absolute -bottom-5 left-1 text-xxs text-red-600">{{ error($errors, 'last_name') }}</span>
              {% endif %}
            </div>
        </div>

        <div class="relative mb-6">
          <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5 ml-1">E-postadress</label>
          <input type="text" name="email" id="email" value="{{ old('email') ?: $user->getAttribute('email') }}" class="w-full text-sm border-slate-300 rounded-md focus:border-indigo-500 focus:ring-indigo-500 transition shadow-sm">
          {% if (error($errors, 'email')) : %}
            <span class="block absolute -bottom-5 left-1 text-xxs text-red-600">{{ error($errors, 'email') }}</span>
          {% endif %}
        </div>

        <!-- Avatar Sektion -->
        <div class="relative mb-8 pt-4 border-t border-gray-100">
          <label for="avatar" class="block text-sm font-medium text-slate-700 mb-1.5 ml-1">Profilbild <span class="text-gray-400 font-normal">(valfritt)</span></label>
          <input type="file" name="avatar" id="avatar" accept="image/png, image/gif, image/jpeg, image/jpg" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition cursor-pointer">
          {% if (error($errors, 'avatar')) : %}
            <span class="block absolute -bottom-5 left-1 text-xxs text-red-600">{{ error($errors, 'avatar') }}</span>
          {% endif %}
        </div>

        <!-- Knappar -->
        <div class="flex flex-wrap items-center gap-3">
          <button type="submit" class="bg-indigo-600 text-white py-2 px-6 rounded-lg font-semibold hover:bg-indigo-700 transition duration-300 shadow-md shadow-indigo-100">
            Spara ändringar
          </button>

          <a href="{{ route('user.password.edit') }}" class="py-2 px-6 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition duration-300">
            Byt lösenord
          </a>

          <a href="{{ route('user.index') }}" class="py-2 px-6 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition duration-300">
            Avbryt
          </a>

          {% if (error($errors, 'form-error')) : %}
          <div class="w-full mt-4 p-3 bg-red-50 border border-red-100 rounded-lg">
              <p class="text-xxs text-red-600 font-semibold leading-tight text-center">{{ error($errors, 'form-error') }}</p>
          </div>
          {% endif %}
        </div>
      </form>
    </section>
{% endblock %}