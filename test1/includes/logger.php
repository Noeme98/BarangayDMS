<?php
/**
 * Appends errors to logs/app.log (not shown to users).
 */

function app_log(string $message, string $level = 'ERROR'): void
{
    $dir = dirname(__DIR__) . '/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    $line = sprintf(
        "[%s] %s: %s%s",
        date('Y-m-d H:i:s'),
        $level,
        $message,
        PHP_EOL
    );

    @file_put_contents($dir . '/app.log', $line, FILE_APPEND | LOCK_EX);
}

function app_log_exception(Throwable $e, string $context = ''): void
{
    $msg = $context !== '' ? "{$context} — " : '';
    $msg .= $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
    app_log($msg, 'EXCEPTION');
}
