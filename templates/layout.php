<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($title ?? 'Mon Application') ?></title>
        <?php if (isset($description)): ?>
        <meta name="description" content="<?= htmlspecialchars($description) ?>">
        <?php endif; ?>
    </head>
    <body>
        <main>
            <?= $content ?>
        </main>
    </body>
</html>
