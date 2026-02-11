<?php

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

<div class="flex ju-between p">
    <p><?= breadcrumbs(); ?></p>
    <div>
        <p>05 56 12 34 56</p>
        <p class="hidden">76 rue des Trois-Conils</p>
        <p class="hidden">Bordeaux</p>
    </div>
</div>
<div class="f-col it-center g--">
    <button id="footer-button" class="btn text">
        <h3>Horaires :</h3>
    </button>
    <div class="grid-2 g- hidden">
        <ul>
            <li>Lundi</li>
            <li>Mardi à Samedi</li>
            <li>Dimanche</li>
        </ul>
        <ul>
            <li>Fermé</li>
            <li>9h00 - 18h00</li>
            <li>9h00 - 12h00</li>
        </ul>
    </div>
</div>
<div class="flex ju-between it-center py px--">
    <div class="btn text"><a href="">Mentions Légales</a></div>
    <div class="btn text"><a href="">Conditions Générales de ventes</a></div>
</div>

