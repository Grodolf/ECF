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
        <link rel="stylesheet" href="./css/style.css">
    </head>
    <body data-theme="auto">
        <header>
            <?php include_once __DIR__ . '/partials/header.php' ?>
        </header>

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
        <main>
            <?= $content ?>
        </main>
        <footer>
            <?php include_once __DIR__ . '/partials/footer.php' ?>
        </footer>
    </body>
</html>
