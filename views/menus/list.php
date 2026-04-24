<?php

use App\Core\Security;

$themes_seen = [];
$theme_options = '';
foreach ($menus as $menu) {
    if (in_array($menu['theme_id'], $themes_seen)) {
        continue;
    }
    array_push($themes_seen, $menu['theme_id']);
    $theme_options .= '<option value="' . $menu['theme_id'] . '">' . $menu['theme'] . '</option>';
}
unset($menu);

$regimes_seen = [];
$regime_options = '';
foreach ($menus as $menu) {
    if (in_array($menu['regime_id'], $regimes_seen)) {
        continue;
    }
    array_push($regimes_seen, $menu['regime_id']);
    $regime_options .= '<option value="' . $menu['regime_id'] . '">' . $menu['regime'] . '</option>';
}
unset($menu);

$minPrice = min(array_column($menus, 'base_price'));

$minPeople = min(array_column($menus, 'min_people'));

$menu_list = '';
foreach ($menus as $menu) {
    $menu_list .= '
    <div class="card">
        <div class="card-header">
            <img src="' . $menu['src'] . '" alt="' . $menu['alt'] . '">
            <div class="badge">
                <p>' . $menu['theme'] . '</p>
            </div>
            <div class="badge">
                <p>' . $menu['regime'] . '</p>
            </div>
        </div>
        <div class="card-body">
            <h3 class="card-title">' . Security::escapeHtml($menu['title']) . '</h3>
            <p class="card-description">' . Security::escapeHtml($menu['description']) . '</p>
            <p>Menu pour ' . $menu['min_people'] . ' personnes minimum, au prix de ' . number_format($menu['base_price'], 2, ',', ' ') . '€ par personnes.</p>
            <div class="btn">
                <a href="/menu/' . $menu['id'] . '">Détails</a>
            </div>
        </div>
    </div>';
}
unset($menu);


?>

<picture>
    <source srcset="/img/buffet_400.webp" media="(max-width: 480px)">
    <source srcset="/img/buffet_600.webp" media="(max-width: 768px)">
    <source srcset="/img/buffet_800.webp" media="(min-width: 769px)">
    <img src="/img/buffet_400.webp" alt="Des menus savoureux avec des produits de saisons">
</picture>

<p class="px">Notre philosophie est simple : proposer des menus savoureux et raffinés, conçus avec des produits frais et de saison, tout en restant à l'écoute de vos besoins et de vos envies.</p>

<button id="menu-button" class="btn outline">Filtrer les menus</button>

<form id="menu-filter" class="hidden">
    <div class="filter price-filter">
        <label>Prix par personnes :</label>
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
    
    <div class="filter">
        <label for="min_people">Nombre de convives minimum :</label>
        <input type="number" name="min_people" id="min_people" placeholder="Nb personnes" min="1">
    </div>
    
    <div class="filter">
        <label for="theme">Thème</label>
        <select name="theme" id="theme">
            <option value="">Tous les thèmes</option>
            <?= $theme_options ?>
        </select>
    </div>
    
    <div class="filter">
        <label for="regime">Régime</label>
        <select name="regime" id="regime">
            <option value="">Tous les régimes</option>
            <?= $regime_options ?>
        </select>
    </div>
    
    <button type="button" id="reset-filters" class="btn outline">Réinitialiser</button>
</form>

<p id="results-count" class="text-muted"></p>
<div id="menus-container">
    <?= $menu_list ?>
</div>
