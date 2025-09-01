{% extends "layouts/email.ratio.php" %}
{% block title %}Reset Email{% endblock %}

{% block body %}
    <h1>Återställ!</h1>
    <p>Skickat, {{ date('Y-d-m H:i') }}</p>
    <p>{{ $body }}</p>
    <p>{{ $url }}</p>
{% endblock %}