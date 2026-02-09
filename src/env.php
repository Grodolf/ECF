<?php

declare(strict_types=1);

function loadEnv($path)
{
    $file = $path . '.env.local';
    if (!file_exists($file)) {

        $file = $path . '.env';

        if (!file_exists($file)) {
            var_dump($file);
            return;
        }
    }


    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignorer les commentaires
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parser la ligne
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        // Définir la variable d'environnement
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }
}
