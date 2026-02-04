<?php

declare(strict_types=1);

namespace App\Core;

use UnexpectedValueException;

abstract class AbstractController
{
    protected function renderView(string $view, array $data = []): void
    {
        // Chemin vers la vue
        $viewPath = dirname(__DIR__, 2) . '/views/' . $view;

        // Chemin vers le layout
        $layoutPath = dirname(__DIR__, 2) . '/templates/layout.php';

        // Vérifier que la vue existe
        if (!file_exists($viewPath)) {
            var_dump($viewPath);
            throw new UnexpectedValueException("La vue {$view} n'existe pas.");
        }

        // Rendre les données disponibles dans la vue et le layout
        extract($data);

        // Capturer le contenu de la vue
        ob_start();
        require_once $viewPath;
        $content = ob_get_clean();

        // Inclure le layout qui utilisera $content et $data
        require_once $layoutPath;
    }

    protected function redirectToRoute(string $path, array $params = []): void
    {
        $uri = '/' . $path;

        if (!empty($params)) {
            $strParams = [];
            foreach ($params as $key => $val) {
                array_push($strParams, urlencode((string) $key) . '=' . urlencode((string) $val));
            }
            $uri .= '&' . implode('&', $strParams);
        }

        header("Location: " . $uri);
        die;
    }

    protected function returnJson(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
