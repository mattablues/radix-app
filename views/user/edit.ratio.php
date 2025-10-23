{% extends "layouts/admin.ratio.php" %}
{% block title %}Redigera konto{% endblock %}
{% block pageId %}home{% endblock %}
{% block searchId %}search-users{% endblock %}
{% block body %}
    <section>
      <h1 class="text-3xl mb-8">Redigera konto</h1>

      <form action="{{ route('user.update') }}" method="post" enctype="multipart/form-data" class="w-full bg-white max-w-xl">
         {{ csrf_field()|raw }}

        <div class="relative mb-2">
          <label for="firstname" class="block text-sm text-slate-600 mb-1.5 ml-1">Förnamn</label>
          <input type="text" name="first_name" id="firstname" value="{{ old('first_name') ?: $user->getAttribute('first_name') }}" class="w-full text-sm border-slate-300 rounded-md focus:outline-none focus:border-indigo-500 focus:ring-0 focus:ring-indigo-500 transition duration-300 ease-in">
          {% if (error($errors, 'first_name')) : %}
          <span class="block right-1 absolute -bottom-4 text-xs text-red-600">{{ error($errors, 'first_name') }}</span>
          {% endif %}
        </div>

        <div class="relative mb-2">
          <label for="lastname" class="block text-sm text-slate-600 mb-1.5 ml-1">Efternamn</label>
          <input type="text" name="last_name" id="lastname" value="{{ old('last_name') ?: $user->getAttribute('last_name') }}" class="w-full text-sm border-slate-300 rounded-md focus:outline-none focus:border-indigo-500 focus:ring-0 focus:ring-indigo-500 transition duration-300 ease-in">
          {% if (error($errors, 'last_name')) : %}
          <span class="block right-1 absolute -bottom-4 text-xs text-red-600">{{ error($errors, 'last_name') }}</span>
          {% endif %}
        </div>

        <div class="relative mb-2">
          <label for="email" class="block text-sm text-slate-600 mb-1.5 ml-1">E-postadress</label>
          <input type="text" name="email" id="email" value="{{ old('email') ?: $user->getAttribute('email') }}" class="w-full text-sm border-slate-300 rounded-md focus:outline-none focus:border-indigo-500 focus:ring-0 focus:ring-indigo-500 transition duration-300 ease-in">
          {% if (error($errors, 'email')) : %}
          <span class="block right-1 absolute -bottom-4 text-xs text-red-600">{{ error($errors, 'email') }}</span>
          {% endif %}
        </div>

        <div class="relative mb-2">
          <div class="form-upload-btn">
            <label for="avatar" class="block text-sm text-slate-600 mb-1.5 ml-1">Avatar</label>
            <input type="file" name="avatar" id="avatar" accept="image/png, image/gif, image/jpeg, image/jpg" class="w-full text-sm border border-slate-300 rounded-md focus:outline-none focus:border-indigo-500 focus:ring-0 focus:ring-indigo-500 transition duration-300 ease-in">
          </div>
          {% if (error($errors, 'avatar')) : %}
          <span class="block right-1 absolute -bottom-4 text-xs text-red-600">{{ error($errors, 'avatar') }}</span>
          {% endif %}
        </div>

        <div class="relative mb-2">
          <label for="password" class="block text-sm text-slate-600 mb-1.5 ml-1">Lösenord</label>
          <input type="password" name="password" id="password" class="w-full text-sm border-slate-300 rounded-md focus:outline-none focus:border-indigo-500 focus:ring-0 focus:ring-indigo-500 transition duration-300 ease-in">
          {% if (error($errors, 'password')) : %}
          <span class="block right-1 absolute -bottom-4.5 text-xs text-red-600">{{ error($errors, 'password') }}</span>
          {% endif %}
        </div>

        <div class="relative mb-2">
          <label for="password-confirmation" class="block text-sm text-slate-600 mb-1.5 ml-1">Repetera lösenord</label>
          <input type="password" name="password_confirmation" id="password-confirmation" class="w-full text-sm border-slate-300 rounded-md focus:outline-none focus:border-indigo-500 focus:ring-0 focus:ring-indigo-500 transition duration-300 ease-in">
          {% if (error($errors, 'password_confirmation')) : %}
          <span class="block right-1 absolute -bottom-4 text-xs text-red-600">{{ error($errors, 'password_confirmation') }}</span>
          {% endif %}
        </div>
        <div class="relative mt-6 mb-8">
          <div class="flex gap-2 items-center">
            <button class="whitespace-nowrap py-1.5 px-3 border border-blue-600 bg-blue-600 hover:bg-blue-700  transition-all duration-300 text-white rounded-lg cursor-pointer">Spara</button>
            <a href="{{ route('user.index') }}" class="whitespace-nowrap py-1.5 px-3 bg-transparent text-gray-800  border border-gray-800/20 hover:bg-gray-800/5 transition-colors duration-300 rounded-lg">Avbryt</a>
          </div>
        </div>
      </form>
    </section>
{% endblock %}