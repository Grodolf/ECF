<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Routes;

/**
 * Route resolver and controller dispatcher.
 *
 * Reads the path from $_GET['path'], compares it against Routes::ROUTES,
 * extracts dynamic parameters ({param}) and invokes the matching controller method.
 * Redirects to / if no route matches.
 */
class Router
{
    private array $routes;
    private array $availablePaths;
    private string $requestedPath;

    public function __construct()
    {
        $this->routes         = Routes::ROUTES;
        $this->availablePaths = array_keys($this->routes);
        $this->requestedPath  = isset($_GET['path']) ? $_GET['path'] : '/';
        $this->parseRoutes();
    }

    /**
     * Iterates routes and invokes the controller matching the current path.
     * Redirects to / if no match is found.
     */
    private function parseRoutes(): void
    {
        if ($this->requestedPath === '/' || $this->requestedPath === '') {
            header('Location: /home');
            exit;
        }

        $explodedRequestedPath = $this->explodePath($this->requestedPath);

        foreach ($this->availablePaths as $candidatePath) {
            $params = $this->matchPath($candidatePath, $explodedRequestedPath);

            if ($params !== null) {
                $route          = $this->routes[$candidatePath];
                $allowedMethods = (array) $route['http'];

                if (!in_array($_SERVER['REQUEST_METHOD'], $allowedMethods, true)) {
                    http_response_code(405);
                    header('Allow: ' . implode(', ', $allowedMethods));
                    echo "Méthode non autorisée.";
                    exit;
                }

                $controller = new $route['controller']();
                $controller->{$route['method']}(...$this->castParams($params));
                return;
            }
        }

        http_response_code(404);
        echo  "La page demandée n'existe pas.";
        exit;
    }

    /**
     * Compares a candidate route against the request segments and extracts parameters.
     *
     * @param string $candidatePath         Candidate route (e.g. 'order/{menuId}')
     * @param array  $explodedRequestedPath Segments of the current request path
     * @return array|null Named parameters extracted, or null if no match
     */
    private function matchPath(string $candidatePath, array $explodedRequestedPath): ?array
    {
        $explodedCandidatePath = $this->explodePath($candidatePath);

        if (count($explodedCandidatePath) !== count($explodedRequestedPath)) {
            return null;
        }

        $params = [];

        foreach ($explodedRequestedPath as $key => $requestedPathPart) {
            $candidatePathPart = $explodedCandidatePath[$key];

            if ($this->isParam($candidatePathPart)) {
                $params[substr($candidatePathPart, 1, -1)] = $requestedPathPart;
            } elseif ($candidatePathPart !== $requestedPathPart) {
                return null;
            }
        }

        return $params;
    }

    /**
     * Casts numeric parameter values to int.
     *
     * @param array $params Extracted parameters (string values)
     * @return array Parameters with numeric values cast to int
     */
    private function castParams(array $params): array
    {
        foreach ($params as $key => $value) {
            if (is_numeric($value)) {
                $params[$key] = (int) $value;
            }
        }

        return $params;
    }

    /**
     * Splits a URL path into segments, stripping leading and trailing slashes.
     *
     * @param string $path Raw path (e.g. '/order/42/')
     * @return array Segments without slashes (e.g. ['order', '42'])
     */
    private function explodePath(string $path): array
    {
        return explode('/', rtrim(ltrim($path, '/'), '/'));
    }

    /**
     * Returns true if a route segment is a dynamic parameter (e.g. '{id}').
     */
    private function isParam(string $candidatePathPart): bool
    {
        return str_contains($candidatePathPart, '{') && str_contains($candidatePathPart, '}');
    }
}
