<?php

$env = static function (string $key, string $default = ''): string {
    $value = getenv($key);
    if ($value !== false && $value !== '') {
        return (string)$value;
    }

    $serverValue = $_SERVER[$key] ?? $_ENV[$key] ?? null;
    if (is_string($serverValue) && $serverValue !== '') {
        return $serverValue;
    }

    return $default;
};

return [
  "driver" => $env('DB_DRIVER', 'pgsql'),
  "host" => $env('DB_HOST', '127.0.0.1'),
  "port" => (int)$env('DB_PORT', '5432'),
  "database" => $env('DB_DATABASE', 'phukien'),
  "username" => $env('DB_USERNAME', 'postgres'),
  "password" => $env('DB_PASSWORD', 'tuhoang7444'),
];
