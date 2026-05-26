<?php
/**
 * Application bootstrap: session, config, shared helpers.
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/config.php';
require_once __DIR__ . '/logger.php';
require_once __DIR__ . '/flash.php';
require_once __DIR__ . '/csrf.php';

date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Manila') ?? 'Asia/Manila');

const APP_UPLOAD_MAX_BYTES = 50 * 1024 * 1024;
const APP_ALLOWED_MIMES = [
    'application/pdf' => 'pdf',
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
];

const DOCUMENT_CATEGORIES = [
    'ordinance' => 'Ordinances',
    'permit' => 'Permits',
    'report' => 'Reports',
    'resolution' => 'Resolutions',
    'certificate' => 'Certificates',
    'historical' => 'Historical',
];

const DOCUMENT_STATUSES = [
    'pending' => 'Pending',
    'in_review' => 'In Review',
    'approved' => 'Approved',
    'returned' => 'Returned',
    'digitized' => 'Digitized',
];

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function format_date(?string $date): string
{
    if ($date === null || $date === '') {
        return '—';
    }

    $ts = strtotime($date);

    return $ts ? date('M j, Y', $ts) : e($date);
}

function status_badge_class(string $status): string
{
    return match ($status) {
        'approved' => 'status-approved',
        'pending' => 'status-pending',
        'in_review' => 'status-review',
        'digitized' => 'status-digitized',
        'returned' => 'status-returned',
        default => 'status-pending',
    };
}

function category_icon(string $category): string
{
    return match ($category) {
        'ordinance' => 'ti-scale',
        'permit' => 'ti-id-badge',
        'report' => 'ti-report',
        'resolution' => 'ti-gavel',
        'certificate' => 'ti-certificate',
        'historical' => 'ti-scan',
        default => 'ti-file',
    };
}

function category_icon_bg(string $category): string
{
    return match ($category) {
        'ordinance', 'historical' => 'rgba(99, 102, 241, 0.15)',
        'permit' => 'rgba(45, 212, 191, 0.12)',
        'report', 'certificate' => 'rgba(52, 211, 153, 0.1)',
        'resolution' => 'rgba(251, 113, 133, 0.12)',
        default => 'var(--dms-surface-raised)',
    };
}

function category_icon_color(string $category): string
{
    return match ($category) {
        'ordinance', 'historical' => '#a5b4fc',
        'permit' => '#5eead4',
        'report', 'certificate' => 'var(--color-text-success)',
        'resolution' => 'var(--color-text-danger)',
        default => 'var(--color-text-secondary)',
    };
}

function status_icon(string $status): string
{
    return match ($status) {
        'approved' => 'ti-check',
        'pending' => 'ti-clock',
        'in_review' => 'ti-eye',
        'digitized' => 'ti-database',
        'returned' => 'ti-arrow-back-up',
        default => 'ti-point',
    };
}
