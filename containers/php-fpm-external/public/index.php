<?php
declare(strict_types=1);

use App\Kernel;

if (getenv('ALLOW_ENV_OVERRIDE') === 'true') {
    $headers = function_exists('getallheaders') ? array_change_key_case(getallheaders(), \CASE_UPPER) : [];
    if (isset($headers['X-APP-ENV'])) {
        $_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = $headers['X-APP-ENV'];
    }
}

require_once dirname(__DIR__) . '/vendor/autoload_runtime.php';

return static fn (array $context) => new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
