<?php

declare(strict_types=1);

namespace App\Controllers;

use UnexpectedValueException;

/**
 * Base controller extended by all application controllers.
 *
 * Provides view rendering via a shared layout, HTTP redirection,
 * and JSON response helpers.
 */
abstract class AbstractController
{
    /**
     * Renders a view wrapped in the main layout.
     *
     * The `scripts` key in $data accepts an array of absolute JS module paths
     * to be loaded as page-specific <script type="module"> tags.
     *
     * @param string $view Relative path from views/ (e.g. 'auth/login.php')
     * @param array  $data Variables extracted into the view and layout scope
     * @throws UnexpectedValueException If the view file does not exist
     */
    protected function renderView(string $view, array $data = []): void
    {
        $viewPath   = dirname(__DIR__, 2) . '/views/' . $view;
        $layoutPath = dirname(__DIR__, 2) . '/templates/layout.php';

        if (!file_exists($viewPath)) {
            throw new UnexpectedValueException("La vue {$view} n'existe pas.");
        }

        extract($data);

        ob_start();
        require_once $viewPath;
        $content = ob_get_clean();

        require_once $layoutPath;
    }

    /**
     * Redirects to an internal application route.
     *
     * @param string $path   Route path without leading slash (e.g. 'login', 'order/42')
     * @param array  $params Optional query string parameters
     */
    protected function redirectToRoute(string $path, array $params = []): void
    {
        $uri = '/' . $path;

        if (!empty($params)) {
            $strParams = [];
            foreach ($params as $key => $val) {
                $strParams[] = urlencode((string) $key) . '=' . urlencode((string) $val);
            }
            $uri .= '?' . implode('&', $strParams);
        }

        header('Location: ' . $uri);
        exit;
    }

    /**
     * Sends a JSON response with the appropriate HTTP status code.
     *
     * @param array $data       Data to serialize as JSON
     * @param int   $statusCode HTTP response code (defaults to 200)
     */
    protected function returnJson(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
