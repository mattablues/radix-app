{% extends "layouts/admin.ratio.php" %}
{% block title %}Admin user index{% endblock %}
{% block pageId %}admin-user{% endblock %}
{% block body %}
    <section>
      <h1 class="text-3xl mb-8">Konton</h1>
{% if($users['data']) : %}
      <table class="w-full">
        <thead>
          <tr class="text-left border-b border-gray-200">
            <th data-cell="id" class="px-1.5 py-2.5 text-sm max-md:hidden">ID</th>
            <th data-cell="namn" class="px-1.5 py-2.5 text-sm max-md:hidden">Namn</th>
            <th data-cell="e-post" class="px-1.5 py-2.5 text-sm max-md:hidden">E-postadress</th>
            <th data-cell="status" class="px-1.5 py-2.5 text-sm max-md:hidden">Status</th>
            <th data-cell="aktiv" class="px-1.5 py-2.5 text-sm max-md:hidden">Aktiv</th>
            <th data-cell="åtgärd" class="px-1.5 py-2.5 text-sm max-md:hidden">Åtgärd</th>
          </tr>
        </thead>
        <tbody>
{% foreach($users['data'] as $user) : %}
          <tr class="text-left border-b border-gray-200 hover:bg-gray-100 even:bg-white odd:bg-gray-50">
            <td data-cell="id" class=" px-1.5 py-2.5 max-md:py-1.5 text-sm max-md:before:content-[attr(data-cell)] max-md:grid max-md:grid-cols-[1fr_2fr] max-md:gap-1 max-md:before:font-semibold max-md:before:text-sm max-md:before:capitalize">{{ $user->getAttribute('id') }}</td>
            <td data-cell="namn" class=" px-1.5 py-2.5 max-md:py-1.5 text-sm max-md:before:content-[attr(data-cell)] max-md:grid max-md:grid-cols-[1fr_2fr] max-md:gap-1 max-md:before:font-semibold max-md:before:text-sm max-md:before:capitalize">{{ $user->getAttribute('first_name') }} {{ $user->getAttribute('last_name') }}</td>
            <td data-cell="e-post" class=" px-1.5 py-2.5 max-md:py-1.5 text-sm max-md:before:content-[attr(data-cell)] max-md:grid max-md:grid-cols-[1fr_2fr] max-md:gap-1 max-md:before:font-semibold max-md:before:text-sm max-md:before:capitalize">{{ $user->getAttribute('email') }}</td>
            <td data-cell="status" class="px-1.5 py-2.5 max-md:py-1.5 text-sm max-md:before:content-[attr(data-cell)] max-md:grid max-md:grid-cols-[1fr_2fr] max-md:gap-1 max-md:before:font-semibold max-md:before:text-sm max-md:before:capitalize"><div class="flex items-center text-xs"><span class="{{ $user->getRelation('status')->getAttribute('status') }} inline-block px-2 rounded-lg">{{ $user->getRelation('status')->getAttribute('status')  }}</span></div></td>
            <td data-cell="aktiv" class=" px-1.5 py-2.5 max-md:py-1.5 text-sm max-md:before:content-[attr(data-cell)] max-md:grid max-md:grid-cols-[1fr_2fr] max-md:gap-1 max-md:before:font-semibold max-md:before:text-sm max-md:before:capitalize"><div class="flex items-center text-xs rounded-lg"><span class="{{ $user->getRelation('status')->getAttribute('active') }} inline-block px-2 rounded-lg">{{ $user->getRelation('status')->getAttribute('active') }}</span></div></td>
            <td data-cell="åtgärd" class=" px-1.5 py-2.5 max-md:py-1.5 text-sm max-md:before:content-[attr(data-cell)] max-md:grid max-md:grid-cols-[1fr_2fr] max-md:gap-1 max-md:before:font-semibold max-md:before:text-sm max-md:before:capitalize">
              <div class="flex items-center gap-1.5">
{% if($user->hasRole('admin')) : %}
                <span class="text-xs font-semibold bg-gray-200/70 text-gray-400 py-1 px-1.5 rounded-lg">Aktivering</span>
                <span class="text-xs font-semibold bg-gray-200/70 text-gray-400 py-1 px-1.5 rounded-lg">Blockera</span>
{% else : %}
                <form action="{{ route('admin.user.send-activation', ['id' => $user->getAttribute('id')]) }}?page={{ $users['pagination']['current_page'] }}" method="post">

                  {{ csrf_field()|raw }}
                  <button class="text-xs font-semibold bg-blue-600 text-white py-1 px-1.5 rounded-lg cursor-pointer hover:bg-blue-700 transition-colors duration-300">Aktivering</button>
                </form>
{% if($user->getRelation('status')->getAttribute('status') !== 'blocked') : %}
                <form action="{{ route('admin.user.block', ['id' => $user->getAttribute('id')]) }}?page={{ $users['pagination']['current_page'] }}" method="post">

                  {{ csrf_field()|raw }}
                  <button class="text-xs font-semibold bg-red-600 text-white py-1 px-1.5 rounded-lg cursor-pointer hover:bg-red-700 transition-colors duration-300">Blockera</button>
                </form>
{% else : %}
                <span class="text-xs font-semibold bg-gray-200/70 text-gray-400 py-1 px-1.5 rounded-lg">Blockera</span>
{% endif; %}
{% endif; %}
              </div>
            </td>
          </tr>
{% endforeach; %}
        </tbody>
      </table>
{% if($users['pagination']['total'] > $users['pagination']['per_page']) : %}
      <p class="mb-10 text-right text-xs font-bold pr-1">sida {{ $users['pagination']['current_page'] }} av {{ calculate_total_pages($users['pagination']['total'], $users['pagination']['per_page']) }}</p>
      {{ paginate_links($users['pagination'], 'admin.user.index', 2)|raw }}
{% endif; %}
{% else : %}
      <p>Inga konton hittades.</p>
{% endif; %}
    </section>
{% endblock %}