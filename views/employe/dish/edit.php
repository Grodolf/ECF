<?php

use App\Core\Security;

?>
<div class="f-col g it-center d:col-5">
    <p><strong>Cette section est réservée aux employés de Vite Et Gourmand!</strong></p>

    <form action="/employe/dish/update/<?= $dish['id'] ?>" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <input type="hidden" name="current_image" value="<?= $dish['image_url'] ?>">
        <div class="input-container">
            <label for="name">Nom du plat :</label>
            <input type="text" name="name" id="name" value="<?= $dish['name'] ?>" required>
        </div>
        <div class="input-container">
            <label for="description">Description du plat :</label>
            <input type="text" name="description" id="description" value="<?= $dish['description'] ?>" required>
        </div>
        <div class="input-container">
            <label for="type">Type de plat :</label>
            <select name="type" id="type">
                    <option value="<?= $dish['type_id'] ?>" selected><?= Security::escapeHtml($dish['type_name']) ?></option>
            </select>
        </div>
        <div class="input-container">
            <label for="allergenes">Allergènes :</label>
            <select name="allergenes[]" id="allergenes" multiple>
                <?php foreach ($allergenes as $allergene) : ?>
                    <option value="<?=$allergene['id'] ?>"
                        <?php if (in_array($allergene['id'], $dish['allergene_ids'])) : ?>
                            selected
                        <?php endif; ?>
                    ><?= Security::escapeHtml($allergene['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="input-container">
            <label for="image">Photo du plat (jpg, png, webp) :</label>
            <input type="file" id="image" name="image" accept="image/*">
        </div>
        <img src="<?= $dish['image_url'] ?>" alt="">
        <button type="submit" class="btn primary">Modifier</button>
    </form>
</div>
