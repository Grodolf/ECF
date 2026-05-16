<?php

use App\Core\Security;

?>
<div class="f-col g it-center d:col-5">
    <p><strong>Cette section est réservée aux employés de Vite Et Gourmand!</strong></p>
    
    <form action="/employe/dish/store" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <div class="input-container">
            <label for="name">Nom du plat :</label>
            <input type="text" name="name" id="name" required>
        </div>
        <div class="input-container">
            <label for="description">Description du plat :</label>
            <input type="text" name="description" id="description" required>
        </div>
        <div class="input-container">
            <label for="type">Type de plat :</label>
            <select name="type" id="type" required>
                <?php foreach ($types as $type) : ?>
                    <option value="<?= $type['id'] ?>"><?= Security::escapeHtml($type['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="input-container">
            <label for="allergenes">Allergènes :</label>
            <select name="allergenes[]" id="allergenes" multiple>
                <?php foreach ($allergenes as $allergene) : ?>
                    <option value="<?= $allergene['id'] ?>"><?= Security::escapeHtml($allergene['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="input-container">
            <label for="image">Photo du plat (jpg, png, webp) :</label>
            <input type="file" id="image" name="image" accept="image/*">
        </div>
        <button type="submit" class="btn primary">Créer le plats!</button>
    </form>
</div>
