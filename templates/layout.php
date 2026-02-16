<?php

declare(strict_types=1);

use App\Core\Session;

function formatText($texte)
{
    $texte = str_replace('&nbsp;', 'NBSP', $texte);
    $texte = htmlspecialchars($texte);
    $texte = str_replace('NBSP', '&nbsp;', $texte);

    return $texte;
}

?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= formatText($title) ?></title>
        <?php if (isset($description)): ?>
        <meta name="description" content="<?= htmlspecialchars($description) ?>">
        <?php endif; ?>
        <link rel="stylesheet" href="./css/style.css">
    </head>
    <body data-theme="auto">
        
        <header class="container grid-1 d:grid-5">
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
        <main class="grid-1 g d:grid-5 d:my">
            <h1 class="d:col-5 d:my"><?= formatText($title) ?></h1>
            <?= $content ?>
        </main>
        <footer class="container f-col it-center">
            <?php include_once __DIR__ . '/partials/footer.php' ?>
        </footer>
        <script src="./js/scripts.js"></script>
    </body>
</html>
