{% extends "layouts/admin.ratio.php" %}
{% block title %}Visa konto{% endblock %}
{% block pageId %}user{% endblock %}
{% block searchId %}search-users{% endblock %}
{% block body %}
    <section>
      <h1 class="text-3xl font-semibold mb-8">Konto</h1>

      <h3 class="text-[20px] font-semibold mb-3">Kontoinformation</h3>

      <div class="w-full md:max-w-[600px]">
        <div class="flex justify-between border border-gray-200 rounded-md">
          <dl class="px-4 py-2 flex-1 bg-gray-50">
            <dt class="text-sm font-medium text-gray-500">Namn</dt>
            <dd class="text-sm text-gray-900 mb-1">{{ $currentUser->getAttribute('first_name') }} {{ $currentUser->getAttribute('last_name') }}</dd>
            <dt class="text-sm font-medium text-gray-500">E-post</dt>
            <dd class="text-sm text-gray-900 mb-1">{{ $currentUser->getAttribute('email') }}</dd>
            <dt class="text-sm font-medium text-gray-500">Behörighet</dt>
            <dd class="text-sm text-gray-900 mb-1">{{ $currentUser->fetchGuardedAttribute('role') }}</dd>
            <dt class="text-sm font-medium text-gray-500">Skapad</dt>
            <dd class="text-sm text-gray-900 mb-1">{{ $currentUser->getAttribute('created_at') }}</dd>
            <dt class="text-sm font-medium text-gray-500">Uppdaterad</dt>
            <dd class="text-sm text-gray-900">{{ $currentUser->getAttribute('updated_at') }}</dd>
          </dl>
          <figure class="flex flex-col items-center justify-center w-[140px] md:w-[200px]">
            <img src="{{ $currentUser->getAttribute('avatar') }}" alt="Avatar" class="w-[100px] sm:w-[140px] h-[100px] sm:h-[140px] rounded-full object-cover">
            <figcaption class="text-xs text-gray-700 font-semibold">Avatar</figcaption>
          </figure>
        </div>
        <div class="flex gap-3 justify-end mt-2 px-1">
          <a href="{{ route('user.edit') }}" class="text-sm self-start block border border-transparent bg-blue-500 px-3 py-0.5 text-white hover:bg-blue-600 transition-colors duration-300 rounded-md cursor-pointer">Redigera</a>
          {% if(!$currentUser->isAdmin()) : %}
          <button class="text-sm border border-transparent bg-red-500 px-3 py-0.5 text-white hover:bg-red-600 transition-colors duration-300 rounded-md cursor-pointer" x-on:click="openCloseModal = true">Stäng</button>
          <button class="text-sm border border-transparent bg-red-500 px-3 py-0.5 text-white hover:bg-red-600 transition-colors duration-300 rounded-md cursor-pointer" x-on:click="openDeleteModal = true">Radera</button>
          {% endif; %}
        </div>
      </div>
    </section>
{% endblock %}