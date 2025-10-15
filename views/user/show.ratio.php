{% extends "layouts/admin.ratio.php" %}
{% block title %}User show{% endblock %}
{% block pageId %}show{% endblock %}
{% block searchId %}search-users{% endblock %}
{% block body %}
    <section>
      <h1 class="text-3xl mb-8">Konto</h1>
{% if($user) : %}
      <p>Namn {{ $user->getAttribute('first_name') }} {{ $currentUser->getAttribute('last_name') }}</p>
      <p>E-post {{ $user->getAttribute('email') }}</p>
      {% if($currentUser->hasRole('admin')) : %}
      <p>Status {{ $user->getRelation('status')->translateStatus($user->getRelation('status')->getAttribute('status')) }}</p>
      {% endif; %}
      {% if($user->getRelation('status')->getAttribute('active_at')) : %}
      <p>Senast aktiv {{ $datetime->frame($user->getRelation('status')->getAttribute('active_at')) }}</p>
      {% endif; %}
{% else : %}
      <p>User not found</p>
{% endif; %}
    </section>
{% endblock %}