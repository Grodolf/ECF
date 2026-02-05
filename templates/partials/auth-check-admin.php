<?php

declare(strict_types=1);

/**
 * Partial : User role verification = Admin
 *
 * To be included at the start of any view or controller requiring a connection.
 * Verify that the user is authenticated, otherwise redirect to "/home".
 *
 * Use in a view :
 *   <?php require_once __DIR__ . '/../partials/auth-check-admin.php'; ?>
 *
 * Use in a controller :
 *   require_once __DIR__ . '/../../templates/partials/auth-check-admin.php';
 */

use App\Core\Session;

require_once 'auth-check.php';

if ($currentUser['role'] !== 'admin') {
    Session::setFlash('login', 'Réservé à l\'administrateur du site.', 'error');
    header('Location: /home');
    exit;
}
