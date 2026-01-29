<?php

declare(strict_types=1);

spl_autoload_register(function (string $class): void {

    // Répertoire de base pour les classes
    $baseDir = dirname(__DIR__, 1).'/';

    // Vérifier si la classe utilise le namespace de base
    $baseNamespace = 'App\\';
    $len = strlen($baseNamespace);
    if (strncmp($baseNamespace, $class, $len) !== 0) {
        throw new UnexpectedValueException('Le namespace doit obligatoirement commencer par "App".');
    }

    // Mapper les $filePath à partir des namespaces
    $relativeClass = substr($class, $len);
    // D'abord construire le chemin dans src/, puis appliquer les remplacements spéciaux
    $file = $baseDir . 'src/' . str_replace('\\', '/', $relativeClass) . '.php';

    $file = str_replace('/src/Controllers/', '/controllers/', $file);
    $file = str_replace('/src/Models/', '/models/', $file);
    $file = str_replace('/src/Config/', '/config/', $file);

    // Charger le fichier si il existe
    if (file_exists($file)) {
        require_once $file;
    }

});
