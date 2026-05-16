<?php

use App\Core\Security;

?>

<div class="f-col g it-center d:col-5 d:mx++">
    <picture>
        <source srcset="/img/buffet_400.webp" media="(max-width: 480px)">
        <source srcset="/img/buffet_600.webp" media="(max-width: 768px)">
        <source srcset="/img/buffet_800.webp" media="(min-width: 769px)">
        <img src="/img/buffet_400.webp" alt="Des menus savoureux avec des produits de saisons">
    </picture>

    <p class="px">Notre philosophie est simple : proposer des menus savoureux et raffinés, conçus avec des produits frais et de saison, tout en restant à l'écoute de vos besoins et de vos envies.</p>
</div>

<div class="f-col d:col-2">
    <button id="menu-button" class="btn outline">Filtrer les menus</button>

    <form id="menu-filter" class="hidden">
        <div class="flex ju-between price-filter">
            <label for="price-max-slider">Prix par personne :</label>
            <div class="price-display">
                <span id="price-min-display">0€</span>
                <span> - </span>
                <span id="price-max-display">100€</span>
            </div>
            <div class="range-slider">
                <input type="range"
                       id="price-min-slider"
                       name="min_price"
                       min="5"
                       max="25"
                       value="0"
                       step="1">
                <input type="range"
                       id="price-max-slider"
                       name="max_price"
                       min="5"
                       max="25"
                       value="25"
                       step="1">
            </div>
        </div>

        <div class="input-container">
            <label for="min_people">Nombre de convives<br />minimum :</label>
            <input type="number" name="min_people" id="min_people" placeholder="Nb personnes" min="1">
        </div>

        <div class="input-container">
            <label for="theme">Thème</label>
            <select name="theme" id="theme">
                <option value="">Tous les thèmes</option>
                <?php
                $themes_seen = [];
foreach ($menus as $menu) :
    if (in_array($menu['theme_id'], $themes_seen)) {
        continue;
    }
    $themes_seen[] = $menu['theme_id'];
    ?>
                    <option value="<?= $menu['theme_id'] ?>"><?= Security::escapeHtml($menu['theme']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="input-container">
            <label for="regime">Régime</label>
            <select name="regime" id="regime">
                <option value="">Tous les régimes</option>
                <?php
    $regimes_seen = [];
foreach ($menus as $menu) :
    if (in_array($menu['regime_id'], $regimes_seen)) {
        continue;
    }
    $regimes_seen[] = $menu['regime_id'];
    ?>
                    <option value="<?= $menu['regime_id'] ?>"><?= Security::escapeHtml($menu['regime']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="button" id="reset-filters" class="btn outline">Réinitialiser</button>
    </form>

    <p id="results-count" class="text-muted"></p>
</div>

<div id="menus-container" class="f-col g d:col-3">
    <?php foreach ($menus as $menu) : ?>
        <div class="card inline">
            <div class="card-header">
                <img src="<?= $menu['src'] ?>" alt="<?= Security::escapeHtml($menu['alt']) ?>">
                <div class="badge">
                    <p><?= Security::escapeHtml($menu['theme']) ?></p>
                </div>
                <div class="badge">
                    <p><?= Security::escapeHtml($menu['regime']) ?></p>
                </div>
            </div>
            <div class="card-body">
                <h3 class="card-title"><?= Security::escapeHtml($menu['title']) ?></h3>
                <p class="card-description"><?= Security::escapeHtml($menu['description']) ?></p>
                <p>Menu pour <?= $menu['min_people'] ?> personnes minimum, au prix de <?= number_format($menu['base_price'], 2, ',', ' ') ?>€ par personne.</p>
                <a class="btn" href="/menu/<?= $menu['id'] ?>">Détails</a>
            </div>
        </div>
    <?php endforeach; ?>
</div>
