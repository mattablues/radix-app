<!DOCTYPE html>
<html lang="<?= getenv('APP_LANG'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{% yield title %}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6;">
  <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd;">
    {% yield body %}
  </div>
</body>
</html>