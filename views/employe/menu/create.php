<?php

use App\Core\Security;

?>

<p><strong>Cette section est réservée aux employés de Vite Et Gourmand!</strong></p>

<form action="/employe/menu/store" method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
    <div class="input-container">
        <label for="title">Intitulé du menu :</label>
        <input type="text" name="title" id="title" required>
    </div>
    <div class="input-container">
        <label for="description">Description du menu :</label>
        <input type="text" name="description" id="description" required>
    </div>
    <div class="inpt-container">
        <label for="theme">Thème :</label>
        <select name="theme" id="theme" required>
            <?php foreach ($themes as $theme) : ?>
                <option value="<?= $theme['id'] ?>"><?= Security::escapeHtml($theme['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="inpt-container">
        <label for="regime">Régime :</label>
        <select name="regime" id="regime" required>
            <?php foreach ($regimes as $regime) : ?>
                <option value="<?= $regime['id'] ?>"><?= Security::escapeHtml($regime['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="input-container">
        <label for="entree">Entrée :</label>
        <select name="dishes[]" id="entree" required>
            <?php foreach ($dishByType[1] as $entree) : ?>
                <option value="<?= $entree['id'] ?>"><?= Security::escapeHtml($entree['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="input-container">
        <label for="plat">Plat :</label>
        <select name="dishes[]" id="plat" required>
            <?php foreach ($dishByType[2] as $plat) : ?>
                <option value="<?= $plat['id'] ?>"><?= Security::escapeHtml($plat['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="input-container">
        <label for="dessert">Dessert :</label>
        <select name="dishes[]" id="dessert" required>
            <?php foreach ($dishByType[3] as $dessert) : ?>
                <option value="<?= $dessert['id'] ?>"><?= Security::escapeHtml($dessert['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="input-container">
        <label for="price">Prix à l'unité :</label>
        <input type="number" name="price" id="price" step="0.01" required>
    </div>
    <div class="input-container">
        <label for="conditions">Contraintes spécifiques au menus :</label>
        <input type="text" name="conditions" id="conditions">
    </div>
    <div class="input-container">
        <label for="min_people">Nombre de personnes minimum :</label>
        <input type="number" name="min_people" id="min_people" required>
    </div>
    <div class="input-container">
        <label for="image">Photo du Menu (jpg, png, webp) :</label>
        <input type="file" id="image" name="image[]" accept="image/*" multiple>
    </div>
    <button type="submit" class="btn primary">Créer le menu!</button>
</form>
