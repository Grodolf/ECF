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
        <title><?= Security::escapeHtml($title) ?></title>
        <?php if (isset($description)): ?>
            <meta name="description" content="<?= Security::escapeHtml($description) ?>">
        <?php endif; ?>
        <link rel="stylesheet" href="/css/style.css">
    </head>
    <body data-theme="auto">
        
        <header class="container-full">
            <?php include_once __DIR__ . '/partials/header.php' ?>
        </header>

        <?php $flash = Session::getFlash(); ?>
        <?php if ($flash) : ?>
            <div class="flash-container">
                <div class="flash <?= Security::escapeHtml($flash['type']) ?>">
                    <?= Security::escapeHtml($flash['message']) ?>
                </div>
            </div>
        <?php endif; ?>
        
        <main class="container-full">
            <div class="wrapper grid-1 g d:grid-5">
                <h1 class="mx d:col-5 d:my">
                    <?php if (isset($headline)) : ?>
                        <?= Security::escapeHtml($headline) ?>
                    <?php else : ?>
                        <?= Security::escapeHtml($title) ?>
                    <?php endif; ?>
                </h1>
                <?= $content ?>
            </div>
        </main>
        <footer class="container-full">
            <div class="wrapper f-col it-center">
                <?php include_once __DIR__ . '/partials/footer.php' ?>
            </div>
        </footer>

        <script type="module" src="/js/scripts.js"></script>
        <?php foreach ($scripts ?? [] as $src): ?>
        <script type="module" src="<?= Security::escapeHtml($src) ?>"></script>
        <?php endforeach; ?>
    </body>
</html>
