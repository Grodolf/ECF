<?php

declare(strict_types=1);

use App\Core\Session;

?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($title ?? 'Vite & Gourmand') ?></title>
        <?php if (isset($description)): ?>
        <meta name="description" content="<?= htmlspecialchars($description) ?>">
        <?php endif; ?>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: Arial, sans-serif; line-height: 1.6; padding: 20px; max-width: 800px; margin: 0 auto; }
            h1 { margin-bottom: 20px; }
            form { max-width: 500px; }
            label { display: block; margin-top: 15px; font-weight: bold; }
            input[type="text"], input[type="email"], input[type="password"], input[type="tel"], textarea {
                width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px;
            }
            button { margin-top: 20px; padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
            button:hover { background: #0056b3; }
            .flash { padding: 10px; margin-bottom: 20px; border-radius: 4px; }
            .flash.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
            .flash.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
            .links { margin-top: 20px; }
            .links a { color: #007bff; text-decoration: none; margin-right: 15px; }
            .links a:hover { text-decoration: underline; }
        </style>
    </head>
    <body>
        <nav>
            <a href="/login">Login</a>
            <a href="/register">Register</a>
            <a href="/reset-password">Reset Password</a>
            <a href="/logout">Logout</a>
        </nav>
        <main>
            <?php
                // Affichage des messages flash
                $flashKeys = ['csrf', 'fields', 'email', 'password', 'login', 'register', 'reset-password', 'new-password'];

foreach ($flashKeys as $key) {
    $flash = Session::getFlash($key);
    if ($flash) {
        echo '<div class="flash ' . htmlspecialchars($flash['type']) . '">';
        echo htmlspecialchars($flash['message']);
        echo '</div>';
    }
}
?>
            
            <?= $content ?>
        </main>
    </body>
</html>
