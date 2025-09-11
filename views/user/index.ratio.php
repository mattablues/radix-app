{% extends "layouts/admin.ratio.php" %}
{% block title %}Home index{% endblock %}
{% block pageId %}home{% endblock %}
{% block pageClass %}home{% endblock %}

{% block body %}
    <section>
      <h1 class="text-3xl">Konto</h1>
    </section>
{% endblock %}
{% block script %}
<script>
   fetch('/api/v1/users', {
       headers: {
           'Content-Type': 'application/json',
           'Authorization': 'Bearer {{ $currentToken }}'
       },
   })
    .then(response => {
        if (response.ok) {
            return response.json();
        } else {
            throw new Error('Network response was not ok');
        }
    })
    .then(data => console.log(data))
    .catch(error => console.error('There was a problem with the fetch operation:', error));

</script>

{% endblock %}