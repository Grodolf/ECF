<?php

use App\Core\Session;

?>

<div class="wrapper  grid-1 d:grid-5">
    <div class="flex ju-between it-center d:col-2">
        <img src="/img/LogoVG.svg" alt="Logo Vite et gourmand">
        <p id="headline">Vite & Gourmand</p>
        <button id="nav-button"><img src="/img/Menu.svg" alt="Menu de navigation" aria-label="Menu de navigation"></button>
    </div>

    <div id="nav-bar" class="grid-1 g+ hidden d:col-3">
        <div class="grid-1 g+ d:grid-3 d:it-center">
            <div class="grid-1 ju-it-center g- d:col-2">
                <p>Prêt à découvrir nos créations culinaires ?</p>
                <?php if (Session::isAuthenticated()) : ?>
                    <a class="btn" href="/order">Commander un menu</a>
                <?php else : ?>
                    <a class="btn" href="/login?redirect=/order">Commander un menu</a>
                <?php endif; ?>
            </div>
            <div class="d:col-1">
                <label for="mode">🌗</label>
                <select name="mode" id="mode">
                    <option value="auto" selected>Auto</option>
                    <option value="light">Clair</option>
                    <option value="dark">Sombre</option>
                </select>
            </div>
        </div>
        <nav>
            <?php include_once __DIR__ . '/nav.php' ?>
        </nav>
    </div>
</div>
