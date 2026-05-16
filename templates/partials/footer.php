<?php

use App\Core\Security;

function breadcrumbs(string $separator = ' > ', string $home = 'Vite&Gourmand'): string
{
    $path = $_GET['path'] ?? 'home';

    $labels = [
        'home' => 'Accueil',
        'contact' => 'Contact',
        'login' => 'Connexion',
        'register' => 'Inscription',
        'logout' => 'Déconnexion',
        'reset-password' => 'Réinitialisation',
        'new-password' => 'Nouveau mot de passe',
        'profile' => 'Profil',
        'edit-profile' => 'Modifier le profil',
        'change-password' => 'Changer le mot de passe',
    ];

    $segments = array_filter(explode('/', $path));
    $breadcrumbs = ['<a href="/">' . $home . '</a>'];
    $builtPath = '';

    foreach ($segments as $i => $segment) {
        $builtPath .= ($builtPath ? '/' : '') . $segment;
        $label = $labels[$segment] ?? ucfirst(str_replace('-', ' ', $segment));

        if ($i === array_key_last($segments)) {
            $breadcrumbs[] = '<span>' . htmlspecialchars($label) . '</span>';
        } else {
            $breadcrumbs[] = '<a href="/' . htmlspecialchars($builtPath) . '">' . htmlspecialchars($label) . '</a>';
        }
    }

    return implode($separator, $breadcrumbs);
}

?>

<div class="container flex ju-between">
    <div><?= breadcrumbs(); ?></div>
    <div class="f-col g- it-end shrink-0">
        <p>05 56 12 34 56</p>
        <p class="hidden" data-mobile-footer>76 rue des Trois-Conils</p>
        <p class="hidden" data-mobile-footer>Bordeaux</p>
    </div>
</div>
<div class="">
    <button id="footer-button" class="btn text">
        <p>Horaires :</p>
    </button>
    <div class="grid-2 g hidden" data-mobile-footer>
            <?php foreach ($schedules as $schedule) : ?>
                <p><b><?= $schedule['day_of_week'] ?> :</b></p>
                <?php if ($schedule['closed']) : ?>
                    <p>Fermé</p>
                <?php else : ?>
                    <p>Ouvert de <?= $schedule['openning_time'] ?> à <?= $schedule['closing_time'] ?></p>
                <?php endif; ?>
            <?php endforeach; ?>
    </div>
</div>
<div class="container flex ju-between">
    <a class="btn text" href="/mentions-legales">Mentions Légales</a>
    <a class="btn text" href="/cgv">Conditions Générales de Vente</a>
</div>
