<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Lightweight Environment File (.env) Parser and Loader
 */
class Env
{
    private static bool $loaded = false;

    /**
     * Load environment variables from .env file into $_ENV, $_SERVER, and getenv()
     *
     * @param string|null $path Custom path to .env file
     * @return void
     */
    public static function load(?string $path = null): void
    {
        if (self::$loaded && $path === null) {
            return;
        }

        $envFile = $path ?: dirname(__DIR__, 2) . '/.env';

        if (!file_exists($envFile) || !is_readable($envFile)) {
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comment lines
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            // Must have KEY=VALUE
            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $key = trim($parts[0]);
            $val = trim($parts[1]);

            // Handle quoted string values
            if ((str_starts_with($val, '"') && str_ends_with($val, '"')) ||
                (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
                $val = substr($val, 1, -1);
            } else {
                // Strip inline comment if unquoted
                if (str_contains($val, ' #')) {
                    $val = trim(explode(' #', $val, 2)[0]);
                }
            }

            // Convert primitive literal values
            $lower = strtolower($val);
            if ($lower === 'true') {
                $parsedVal = true;
            } elseif ($lower === 'false') {
                $parsedVal = false;
            } elseif ($lower === 'null') {
                $parsedVal = null;
            } else {
                $parsedVal = $val;
            }

            $_ENV[$key] = $parsedVal;
            $_SERVER[$key] = $parsedVal;

            if (is_scalar($parsedVal)) {
                putenv("{$key}={$parsedVal}");
            }
        }

        self::$loaded = true;
    }

    /**
     * Get an environment variable with default fallback
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        self::load();

        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }

        if (array_key_exists($key, $_SERVER)) {
            return $_SERVER[$key];
        }

        $val = getenv($key);
        if ($val !== false) {
            $lower = strtolower($val);
            if ($lower === 'true') return true;
            if ($lower === 'false') return false;
            if ($lower === 'null') return null;
            return $val;
        }

        return $default;
    }

    /**
     * Update or add key-value pairs in the .env file and runtime environment
     *
     * @param array<string, mixed> $values
     * @param string|null $path
     * @return bool
     */
    public static function update(array $values, ?string $path = null): bool
    {
        $envFile = $path ?: dirname(__DIR__, 2) . '/.env';

        if (!file_exists($envFile)) {
            if (@file_put_contents($envFile, '') === false) {
                return false;
            }
        }

        if (!is_writable($envFile)) {
            return false;
        }

        $content = file_get_contents($envFile);
        if ($content === false) {
            return false;
        }

        foreach ($values as $key => $val) {
            $key = trim((string)$key);
            $valStr = (string)$val;

            // Quote value if it contains spaces or quotes
            if (preg_match('/\s|[#"\']/', $valStr) || $valStr === '') {
                $escaped = addcslashes($valStr, '"\\');
                $formattedVal = "\"{$escaped}\"";
            } else {
                $formattedVal = $valStr;
            }

            // Pattern to match existing KEY=...
            $pattern = '/^(' . preg_quote($key, '/') . '\s*=\s*)(.*)$/m';

            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, "{$key}={$formattedVal}", $content);
            } else {
                $content = rtrim($content) . "\n{$key}={$formattedVal}\n";
            }

            // Update in runtime memory
            $_ENV[$key] = $val;
            $_SERVER[$key] = $val;
            if (is_scalar($val)) {
                putenv("{$key}={$val}");
            }
        }

        $result = file_put_contents($envFile, $content);
        return $result !== false;
    }

    /**
     * Set a single key-value in .env and runtime
     *
     * @param string $key
     * @param mixed $value
     * @param string|null $path
     * @return bool
     */
    public static function set(string $key, $value, ?string $path = null): bool
    {
        return self::update([$key => $value], $path);
    }
}

