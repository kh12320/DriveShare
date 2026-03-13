<?php
// ============================================================
// Supabase REST API Client
// Replaces PDO for all database operations
// Works on any network - no port 5432/6543 needed
// ============================================================

// Load .env file if it exists (local dev only)
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#'))
            continue;
        if (str_contains($line, '=')) {
            [$key, $val] = array_map('trim', explode('=', $line, 2));
            if (!getenv($key))
                putenv("{$key}={$val}");
        }
    }
}

define('SUPABASE_URL', getenv('SUPABASE_URL') ?: 'https://khxkphrxwntbcrjfigqu.supabase.co');
define('SUPABASE_ANON_KEY', getenv('SUPABASE_ANON_KEY') ?: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImtoeGtwaHJ4d250YmNyamZpZ3F1Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzMxNjIwMTMsImV4cCI6MjA4ODczODAxM30.R8iHhwyP46SQOwt5reHNX3g1Q0yF-HqEkfsymsLmLrc');
define('SUPABASE_SERVICE_KEY', getenv('SUPABASE_SERVICE_KEY') ?: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImtoeGtwaHJ4d250YmNyamZpZ3F1Iiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc3MzE2MjAxMywiZXhwIjoyMDg4NzM4MDEzfQ.8jiIsn2Q-HVfLOsKHUVhe4ELqv6UgN3i6wK1fm0pspg');
define('SUPABASE_BUCKET', 'car-images');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost:8000');
define('SESSION_LIFETIME', 86400);

// ============================================================
// Supabase REST Client
// ============================================================
class Database
{
    private static string $baseUrl;
    private static string $serviceKey;
    private static string $anonKey;

    public static function init(): void
    {
        self::$baseUrl = SUPABASE_URL . '/rest/v1';
        self::$serviceKey = SUPABASE_SERVICE_KEY;
        self::$anonKey = SUPABASE_ANON_KEY;
    }

    /**
     * Execute a raw SQL query via Supabase RPC (pg_query)
     * Returns array of rows or throws on error
     */
    public static function query(string $sql, array $params = []): array
    {
        self::init();

        // Replace :param with $1, $2 style for PostgreSQL
        $paramValues = [];
        $i = 1;
        $processedSql = preg_replace_callback('/:([a-zA-Z_]+)/', function ($m) use (&$params, &$paramValues, &$i) {
            $key = ':' . $m[1];
            if (array_key_exists($key, $params)) {
                $paramValues[] = $params[$key];
                $i++;
                return '$' . ($i - 1);
            }
            return $m[0];
        }, $sql);

        $payload = json_encode(['query' => $processedSql, 'params' => $paramValues]);

        $ch = curl_init(SUPABASE_URL . '/rest/v1/rpc/execute_sql');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'apikey: ' . self::$serviceKey,
                'Authorization: Bearer ' . self::$serviceKey,
                'Prefer: return=representation',
            ],
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        

        if ($code >= 400) {
            throw new RuntimeException("DB query failed [$code]: $body\nSQL: $processedSql");
        }

        return json_decode($body, true) ?? [];
    }

    /**
     * SELECT rows from a table
     */
    public static function select(string $table, array $filters = [], string $select = '*', ?string $order = null): array
    {
        self::init();
        $url = self::$baseUrl . '/' . $table . '?select=' . urlencode($select);

        foreach ($filters as $col => $val) {
            if (is_null($val)) {
                $url .= '&' . $col . '=is.null';
            } elseif (is_array($val)) {
                // ['op'=>'gte','val'=>5]
                $url .= '&' . $col . '=' . $val['op'] . '.' . urlencode($val['val']);
            } else {
                $url .= '&' . $col . '=eq.' . urlencode($val);
            }
        }

        if ($order)
            $url .= '&order=' . urlencode($order);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'apikey: ' . self::$serviceKey,
                'Authorization: Bearer ' . self::$serviceKey,
            ],
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        

        if ($code >= 400)
            throw new RuntimeException("SELECT $table failed [$code]: $body");
        return json_decode($body, true) ?? [];
    }

    /**
     * INSERT a row, return the inserted row
     */
    public static function insert(string $table, array $data): array
    {
        self::init();
        $ch = curl_init(self::$baseUrl . '/' . $table);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'apikey: ' . self::$serviceKey,
                'Authorization: Bearer ' . self::$serviceKey,
                'Prefer: return=representation',
            ],
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        

        if ($code >= 400)
            throw new RuntimeException("INSERT into $table failed [$code]: $body");
        $rows = json_decode($body, true) ?? [];
        return $rows[0] ?? [];
    }

    /**
     * UPDATE rows matching filters
     */
    public static function update(string $table, array $data, array $filters): int
    {
        self::init();
        $url = self::$baseUrl . '/' . $table . '?';
        $parts = [];
        foreach ($filters as $col => $val) {
            $parts[] = $col . '=eq.' . urlencode($val);
        }
        $url .= implode('&', $parts);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'PATCH',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'apikey: ' . self::$serviceKey,
                'Authorization: Bearer ' . self::$serviceKey,
                'Prefer: return=representation',
            ],
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        

        if ($code >= 400)
            throw new RuntimeException("UPDATE $table failed [$code]: $body");
        return count(json_decode($body, true) ?? []);
    }

    /**
     * DELETE rows matching filters
     */
    public static function delete(string $table, array $filters): bool
    {
        self::init();
        $url = self::$baseUrl . '/' . $table . '?';
        $parts = [];
        foreach ($filters as $col => $val) {
            $parts[] = $col . '=eq.' . urlencode($val);
        }
        $url .= implode('&', $parts);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'apikey: ' . self::$serviceKey,
                'Authorization: Bearer ' . self::$serviceKey,
            ],
        ]);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        return $code < 300;
    }

    /**
     * Generate a UUID v4
     */
    public static function uuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}
