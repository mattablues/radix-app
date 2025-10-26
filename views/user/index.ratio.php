{% extends "layouts/admin.ratio.php" %}
{% block title %}Visa konto{% endblock %}
{% block pageId %}user{% endblock %}
{% block searchId %}search-users{% endblock %}
{% block body %}
    <section>
      <h1 class="text-3xl font-semibold mb-8">Konto</h1>

      <h3 class="text-[20px] font-semibold mb-3">Kontoinformation</h3>

      <div class="w-full sm:max-w-[600px]">
        <div class="flex justify-between gap-3  border border-gray-200 rounded-md">
          <div class="sm:flex-1 flex flex-col sm:flex-row sm:gap-14 p-3 sm:px-5">
            <dl>
              <dt class="text-sm font-medium text-gray-500">Namn</dt>
              <dd class="text-sm text-gray-900 mb-1">{{ $currentUser->getAttribute('first_name') }} {{ $currentUser->getAttribute('last_name') }}</dd>
              <dt class="text-sm font-medium text-gray-500">E-post</dt>
              <dd class="text-sm text-gray-900 mb-1">{{ $currentUser->getAttribute('email') }}</dd>
              <dt class="text-sm font-medium text-gray-500">Behörighet</dt>
              <dd class="text-sm text-gray-900 mb-1">{{ $currentUser->fetchGuardedAttribute('role') }}</dd>
            </dl>
            <dl>
              <dt class="text-sm font-medium text-gray-500">Skapat</dt>
              <dd class="text-sm text-gray-900 mb-1">{{ $currentUser->getAttribute('created_at') }}</dd>
              <dt class="text-sm font-medium text-gray-500">Uppdaterat</dt>
              <dd class="text-sm text-gray-900">{{ $currentUser->getAttribute('updated_at') }}</dd>
            </dl>
          </div>
          <figure class="flex flex-col items-center justify-center px-3 sm:px-5">
            <img src="{{ versioned_file($currentUser->getAttribute('avatar')) }}" alt="Avatar" class="w-[90px] h-[90px] sm:w-[110px] sm:h-[110px] rounded-full object-cover">
            <figcaption class="text-xs text-gray-700 font-semibold">Avatar</figcaption>
          </figure>
        </div>
        <div class="flex gap-2 justify-end px-3 sm:px-5 mt-2">
          <a href="{{ route('user.edit') }}" class="text-sm border border-transparent bg-blue-500 px-3 py-0.5 text-white hover:bg-blue-600 transition-colors duration-300 rounded-md cursor-pointer">Redigera</a>
        </div>
      </div>
    </section>
{% endblock %}