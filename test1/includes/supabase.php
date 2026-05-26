<?php
/**
 * Supabase REST access (no direct PostgreSQL required).
 */

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/supabase_client.php';

function supabase(): SupabaseClient
{
    static $client = null;
    if ($client === null) {
        $client = supabase_client();
        if (!$client->isConfigured()) {
            throw new RuntimeException('Supabase is not configured. Check test1/.env');
        }
    }

    return $client;
}
