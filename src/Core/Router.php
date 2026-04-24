<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Routes;

class Router
{
    private $routes;
    private $availablePaths;
    private $requestedPath;

    public function __construct()
    {
        $this->routes = Routes::ROUTES;
        $this->availablePaths = array_keys($this->routes);
        $this->requestedPath = isset($_GET['path']) ? $_GET['path'] : '/';
        $this->parseRoutes();
    }

    private function parseRoutes(): void
    {
        $explodedRequestedPath = $this->explodePath($this->requestedPath);

        foreach ($this->availablePaths as $candidatePath) {
            $params = $this->matchPath($candidatePath, $explodedRequestedPath);

            if ($params !== null) {
                $route = $this->routes[$candidatePath];
                $controller = new $route['controller']();
                $controller->{$route['method']}(...$this->castParams($params));
                return;
            }
        }

        header('Location: /');
        exit;
    }

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

    private function castParams(array $params): array
    {
        foreach ($params as $key => $value) {
            if (is_numeric($value)) {
                $params[$key] = (int) $value;
            }
        }

        return $params;
    }

    private function explodePath(string $path): array
    {
        return explode("/", rtrim(ltrim($path, '/'), '/'));
    }

    private function isParam(string $candidatePathPart): bool
    {
        return str_contains($candidatePathPart, '{') && str_contains($candidatePathPart, '}');
    }

}
