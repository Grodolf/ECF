<?php

declare(strict_types=1);

use App\Core\Session;
use App\Core\Security;

?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <?php if (Session::isAuthenticated()): ?>
        <meta name="csrf-token" content="<?= Security::generateCsrfToken() ?>">
        <?php endif; ?>
        <title><?= Security::formatText($title) ?></title>
        <?php if (isset($description)): ?>
        <meta name="description" content="<?= Security::escapeHtml($description) ?>">
        <?php endif; ?>
        <link rel="stylesheet" href="/css/style.css">
    </head>
    <body data-theme="auto">
        
        <header class="container grid-1 d:grid-5">
            <?php include_once __DIR__ . '/partials/header.php' ?>
        </header>

<?php
$flash = Session::getFlash();
if ($flash) {
    echo '<div class="flash-container"><div class="flash ' . Security::escapeHtml($flash['type']) . '">';
    echo Security::escapeHtml($flash['message']);
    echo '</div></div>';
}
?>
        <main class="grid-1 g my- d:grid-5 d:my+">
            <h1 class="d:col-5 d:my"><?= Security::formatText($title) ?></h1>
            <?= $content ?>
        </main>
        <footer class="container f-col it-center">
            <?php include_once __DIR__ . '/partials/footer.php' ?>
        </footer>
        <script type="module" src="/js/scripts.js"></script>
        <?php foreach ($scripts ?? [] as $src): ?>
        <script type="module" src="<?= Security::escapeHtml($src) ?>"></script>
        <?php endforeach; ?>
    </body>
</html>
