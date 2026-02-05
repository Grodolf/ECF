<?php

declare(strict_types=1);

/**
 * Partial : Authentication verification
 *
 * To be included at the start of any view or controller requiring a connection.
 * Verify that the user is authenticated, otherwise redirect to "/login".
 *
 * Use in a view :
 *   <?php require_once __DIR__ . '/../partials/auth-check.php'; ?>
 *
 * Use in a controller :
 *   require_once __DIR__ . '/../../templates/partials/auth-check.php';
 */

use App\Core\Session;

// Check if user is connected
if (!Session::isAuthenticated()) {
    Session::setFlash('login', 'Vous devez être connecté pour accéder à cette page', 'error');
    header('Location: /login');
    die;
}

// Get user infos
$currentUser = Session::getUser();

// If
if ($currentUser === null) {
    Session::destroy();
    Session::setFlash('login', 'Votre session a expiré, veuillez vous reconnecter', 'error');
    header('Location: /login');
    die;
}
