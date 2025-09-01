{% extends "layouts/email.ratio.php" %}
{% block title %}Activation Email{% endblock %}

{% block body %}
    <h1>Meddelande!</h1>
    <p>Skickat, {{ date('Y-d-m H:i') }}</p>
    <p>{{ $body }}</p>
    <p>{{ $url }}</p>
{% endblock %}