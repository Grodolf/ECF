<?php

use App\Core\Session;

$login = '/login';
$compte = 'Connexion';

if (Session::isAuthenticated()) {
    $login = '/profile';
    $compte = 'Mon compte';
}

?>

<ul class="f-col g p">
    <li class="btn outline"><a href="/home">Accueil</a></li>
    <li class="btn outline"><a href="/menus">Les menus</a></li>
    <li class="btn outline"><a href="<?= $login ?>"><?= $compte ?></a></li>
    <li class="btn outline"><a href="/contact">Contact</a></li>
</ul>

