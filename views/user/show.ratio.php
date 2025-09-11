{% extends "layouts/admin.ratio.php" %}
{% block title %}User show{% endblock %}
{% block pageId %}show{% endblock %}
{% block pageClass %}show{% endblock %}

{% block body %}
    <section>
      <h1 class="text-3xl">Visa konto</h1>
{% if($user) : %}
      <p>{{ $user->getAttribute('first_name') }} {{ $currentUser->getAttribute('last_name') }}</p>
      <p>Senast aktiv {{ $datetime->frame($user->getRelation('status')->getAttribute('active_at')) }}</p>
{% else : %}
      <p>User not found</p>
{% endif; %}
    </section>
{% endblock %}