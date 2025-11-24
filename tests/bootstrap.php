<?php

// Säkerställ stabil REQUEST_METHOD i tester/CI
if (!isset($_SERVER['REQUEST_METHOD']) || !is_string($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] === '') {
    $_SERVER['REQUEST_METHOD'] = 'GET';
}
if (getenv('REQUEST_METHOD') === false) {
    putenv('REQUEST_METHOD=GET');
}

require __DIR__ . '/../vendor/autoload.php';