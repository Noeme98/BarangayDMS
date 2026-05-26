<?php

require_once __DIR__ . '/config.php';

/**
 * Supabase REST client (PostgREST). Works over HTTPS when direct Postgres (IPv6) does not.
 */
class SupabaseClient
{
    private string $url;
    private string $key;
    public ?string $last_error = null;
    public int $last_status = 0;

    public function __construct()
    {
        $this->url = rtrim(SUPABASE_URL, '/');
        $this->key = SUPABASE_PUBLISHABLE_KEY;
    }

    public function isConfigured(): bool
    {
        return $this->url !== '' && $this->key !== '';
    }

    /**
     * @param array<string, mixed> $query
     * @return array<int, array<string, mixed>>|null
     */
    public function from(string $table, array $query = [], string $method = 'GET', ?array $body = null): ?array
    {
        $result = $this->request($table, $method, $query, $body);
        return $result['data'] ?? null;
    }

    /**
     * @param array<string, mixed> $query
     * @return array{data: ?array, count: ?int, error: ?string, status: int}
     */
    public function request(string $table, string $method = 'GET', array $query = [], ?array $body = null, bool $wantCount = false): array
    {
        $this->last_error = null;
        $this->last_status = 0;

        if (!$this->isConfigured()) {
            $this->last_error = 'Supabase URL or publishable key is not configured.';
            return ['data' => null, 'count' => null, 'error' => $this->last_error, 'status' => 0];
        }

        $path = '/rest/v1/' . rawurlencode($table);
        $url = $this->url . $path;

        if ($query !== []) {
            $url .= '?' . http_build_query($query);
        }

        $headers = [
            'apikey: ' . $this->key,
            'Authorization: Bearer ' . $this->key,
            'Content-Type: application/json',
            'Prefer: return=representation',
        ];

        if ($wantCount) {
            $headers[] = 'Prefer: count=exact';
            $headers[] = 'Range-Unit: items';
            $headers[] = 'Range: 0-0';
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HEADER => $wantCount,
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response = curl_exec($ch);
        $this->last_status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = $wantCount ? (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE) : 0;
        curl_close($ch);

        if ($response === false) {
            $this->last_error = 'Network request to Supabase failed.';
            return ['data' => null, 'count' => null, 'error' => $this->last_error, 'status' => 0];
        }

        $count = null;
        $jsonBody = $response;

        if ($wantCount && $headerSize > 0) {
            $headerText = substr($response, 0, $headerSize);
            $jsonBody = substr($response, $headerSize);
            if (preg_match('/Content-Range:\s*\d+-\d+\/(\d+|\*)/i', $headerText, $m)) {
                $count = $m[1] === '*' ? 0 : (int) $m[1];
            }
        }

        if ($this->last_status < 200 || $this->last_status >= 300) {
            $decoded = json_decode($jsonBody, true);
            $message = is_array($decoded) ? ($decoded['message'] ?? json_encode($decoded)) : $jsonBody;
            $this->last_error = (string) $message;
            return ['data' => null, 'count' => null, 'error' => $this->last_error, 'status' => $this->last_status];
        }

        if ($jsonBody === '' || $method === 'DELETE') {
            return ['data' => [], 'count' => $count ?? 0, 'error' => null, 'status' => $this->last_status];
        }

        $decoded = json_decode($jsonBody, true);

        return [
            'data' => is_array($decoded) ? $decoded : [],
            'count' => $count,
            'error' => null,
            'status' => $this->last_status,
        ];
    }

    public function count(string $table): ?int
    {
        $result = $this->request($table, 'GET', ['select' => 'id'], null, true);
        return $result['count'];
    }
}

function supabase_client(): SupabaseClient
{
    static $client = null;
    if ($client === null) {
        $client = new SupabaseClient();
    }

    return $client;
}
