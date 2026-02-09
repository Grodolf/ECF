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
    </head>
    <body data-theme="auto" class="f-col ju-between">
        <header class="grow0 shrink0">
            <?php include_once __DIR__ . '/partials/header.php' ?>
        </header>
        <nav class="grow0 shrink0">
            <?php include_once __DIR__ . '/partials/nav.php' ?>
        </nav>

<?php
$flashKeys = ['generic', 'auth', 'profile'];
foreach ($flashKeys as $key) {
    $flash = Session::getFlash($key);
    if ($flash) {
        echo '<div class="flash ' . htmlspecialchars($flash['type']) . '">';
        echo htmlspecialchars($flash['message']);
        echo '</div>';
    }
}
?>
        <main class="grow1">
            <?= $content ?>
        </main>
        <footer class="grow0 shrink0">
            <?php include_once __DIR__ . '/partials/footer.php' ?>
        </footer>
    </body>
</html>
