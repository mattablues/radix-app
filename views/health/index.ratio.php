{% extends "layouts/main.ratio.php" %}
{% block title %}Health{% endblock %}
{% block pageId %}health{% endblock %}
{% block body %}
<section class="py-8">
  <div class="container-centered">
    <h1 class="text-3xl font-semibold mb-6">Systemstatus</h1>

    <div class="grid gap-4 md:grid-cols-2">
      <div class="p-4 rounded border">
        <h2 class="text-xl font-semibold mb-2">Runtime</h2>
        <ul class="space-y-1">
          <li><strong>PHP:</strong> {{ phpversion()|raw }}</li>
          <li><strong>Time:</strong> {{ date('c')|raw }}</li>
          <li><strong>Environment:</strong> {{ getenv('APP_ENV') ?: 'production' }}</li>
        </ul>
      </div>

      <div class="p-4 rounded border">
        <h2 class="text-xl font-semibold mb-2">Kontroller</h2>
        <?php
        $okDb = true;
        $okFs = true;
        try {
            if (function_exists('app')) {
                $dbm = app(\Radix\Database\DatabaseManager::class);
                $conn = $dbm->connection();
                $conn->execute('SELECT 1');
            }
        } catch (\Throwable $e) {
            $okDb = false;
        }

        try {
            $dir = rtrim(defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2), '/\\') . '/cache/health';
            if (!is_dir($dir)) @mkdir($dir, 0755, true);
            $probe = $dir . '/probe_web.txt';
            if (@file_put_contents($probe, (string) time()) === false) throw new \RuntimeException('fs fail');
            @unlink($probe);
        } catch (\Throwable $e) {
            $okFs = false;
        }
        ?>
        <ul class="space-y-1">
          <li><strong>DB:</strong> {{ isset($okDb) && $okDb ? 'ok' : 'fail' }}</li>
          <li><strong>FS:</strong> {{ isset($okFs) && $okFs ? 'ok' : 'fail' }}</li>
        </ul>
      </div>
    </div>

    <div class="mt-6">
      <a href="/api/v1/health" class="text-blue-600 underline">Visa API-health (JSON)</a>
    </div>
  </div>
</section>
{% endblock %}