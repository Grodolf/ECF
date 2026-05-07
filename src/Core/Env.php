<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Environment variable loader from .env files.
 *
 * Priority: `.env.local` (if present) > `.env`.
 * Variables already defined in $_ENV are not overwritten.
 */
class Env
{
    /**
     * Loads environment variables into $_ENV and via putenv().
     *
     * Skips empty lines and comments (lines starting with #).
     *
     * @param string $path Directory containing the .env file (with trailing slash)
     */
    public static function loadEnv(string $path): void
    {
        $file = $path . '.env.local';
        if (!file_exists($file)) {
            $file = $path . '.env';
            if (!file_exists($file)) {
                return;
            }
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            [$name, $value] = explode('=', $line, 2);
            $name  = trim($name);
            $value = trim($value);

            if (!array_key_exists($name, $_ENV)) {
                $_ENV[$name] = $value;
                putenv("$name=$value");
            }
        }
    }
}
