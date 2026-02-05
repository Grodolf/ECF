<?php

declare(strict_types=1);

/**
 * Partial : User role verification = Employe
 *
 * To be included at the start of any view or controller requiring a connection.
 * Verify that the user is authenticated, otherwise redirect to "/home".
 *
 * Use in a view :
 *   <?php require_once __DIR__ . '/../partials/auth-check-employe.php'; ?>
 *
 * Use in a controller :
 *   require_once __DIR__ . '/../../templates/partials/auth-check-employe.php';
 */

use App\Core\Session;

require_once 'auth-check.php';

if ($currentUser['role'] === 'user') {
    Session::setFlash('login', 'Vous n\'avez pas l\'autorisation d\'accéder à cette page.', 'error');
    header('Location: /home');
    exit;
}
