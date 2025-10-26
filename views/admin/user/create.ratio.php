{% extends "layouts/admin.ratio.php" %}
{% block title %}Skapa nytt konto{% endblock %}
{% block pageId %}create-user{% endblock %}
{% block searchId %}search-users{% endblock %}
{% block body %}
    <section>
      <h1 class="text-3xl font-semibold mb-8">Skapa nytt konto</h1>

      <form action="{{ route('admin.user.store') }}" method="post" enctype="multipart/form-data" class="w-full bg-white max-w-xl">
         {{ csrf_field()|raw }}

        <div class="relative mb-2">
          <label for="firstname" class="block text-sm text-slate-600 mb-1.5 ml-1">Förnamn</label>
          <input type="text" name="first_name" id="firstname" value="{{ old('first_name') }}" class="w-full text-sm border-slate-300 rounded-md focus:outline-none focus:border-indigo-500 focus:ring-0 focus:ring-indigo-500 transition duration-300 ease-in">
          {% if (error($errors, 'first_name')) : %}
          <span class="block right-1 absolute -bottom-4 text-xs text-red-600">{{ error($errors, 'first_name') }}</span>
          {% endif %}
        </div>

        <div class="relative mb-2">
          <label for="lastname" class="block text-sm text-slate-600 mb-1.5 ml-1">Efternamn</label>
          <input type="text" name="last_name" id="lastname" value="{{ old('last_name') }}" class="w-full text-sm border-slate-300 rounded-md focus:outline-none focus:border-indigo-500 focus:ring-0 focus:ring-indigo-500 transition duration-300 ease-in">
          {% if (error($errors, 'last_name')) : %}
          <span class="block right-1 absolute -bottom-4 text-xs text-red-600">{{ error($errors, 'last_name') }}</span>
          {% endif %}
        </div>

        <div class="relative mb-4">
          <label for="email" class="block text-sm text-slate-600 mb-1.5 ml-1">E-postadress</label>
          <input type="text" name="email" id="email" value="{{ old('email') }}" class="w-full text-sm border-slate-300 rounded-md focus:outline-none focus:border-indigo-500 focus:ring-0 focus:ring-indigo-500 transition duration-300 ease-in">
          {% if (error($errors, 'email')) : %}
          <span class="block right-1 absolute -bottom-4 text-xs text-red-600">{{ error($errors, 'email') }}</span>
          {% endif %}
        </div>

        <div class="relative mb-8">
          <div class="flex gap-2 items-center">
            <button class="whitespace-nowrap py-1.5 px-3 border border-blue-600 bg-blue-600 hover:bg-blue-700  transition-all duration-300 text-white rounded-lg cursor-pointer">Spara</button>
            <a href="{{ route('admin.user.index') }}" class="whitespace-nowrap py-1.5 px-3 bg-transparent text-gray-800  border border-gray-800/20 hover:bg-gray-800/5 transition-colors duration-300 rounded-lg">Avbryt</a>
          </div>
        </div>
      </form>
    </section>
{% endblock %}