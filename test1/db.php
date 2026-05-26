<?php
/**
 * db.php — Supabase REST connection (used by document features).
 */

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/supabase.php';

function db(): SupabaseClient
{
    return supabase();
}
