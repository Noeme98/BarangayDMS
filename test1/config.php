<?php

function load_env_file(string $path): void
{
    if (!is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value, " \t\n\r\0\x0B\"'");

        if ($name !== '') {
            putenv("$name=$value");
            $_ENV[$name] = $value;
        }
    }
}

load_env_file(__DIR__ . '/.env');

function env(string $key, ?string $default = null): ?string
{
    $value = $_ENV[$key] ?? getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }

    return $value;
}

function supabase_project_ref(): string
{
    $url = env('SUPABASE_URL') ?? env('NEXT_PUBLIC_SUPABASE_URL') ?? '';
    if (preg_match('#https?://([^.]+)\.supabase\.co#', $url, $matches)) {
        return $matches[1];
    }

    return 'xiiigzviwwmjapfdladu';
}

define('SUPABASE_URL', env('SUPABASE_URL') ?? env('NEXT_PUBLIC_SUPABASE_URL') ?? '');
define('SUPABASE_PUBLISHABLE_KEY', env('SUPABASE_PUBLISHABLE_KEY') ?? env('NEXT_PUBLIC_SUPABASE_PUBLISHABLE_KEY') ?? '');
define('SUPABASE_DB_HOST', 'db.' . supabase_project_ref() . '.supabase.co');
define('SUPABASE_DB_PORT', env('SUPABASE_DB_PORT', '5432'));
define('SUPABASE_DB_NAME', env('SUPABASE_DB_NAME', 'postgres'));
define('SUPABASE_DB_USER', env('SUPABASE_DB_USER', 'postgres'));
