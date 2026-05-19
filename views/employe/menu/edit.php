<?php

use App\Core\Security;

?>

<div class="f-col ju-center g+ it-center d:col-5">
    <p><strong>Cette section est réservée aux employés de Vite Et Gourmand!</strong></p>
</div>

<div class="over d:col-5">
    <form action="/employe/menu/update/<?= $menu['id'] ?>" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <fieldset>
            <legend>Gestion des informations</legend>
            <div class="input-container">
                <label for="title">Intitulé du menu :</label>
                <input type="text" name="title" id="title" value="<?= Security::escapeHtml($menu['title']) ?>" required>
            </div>
            <div class="input-container">
                <label for="description">Description du menu :</label>
                <input type="text" name="description" id="description" value="<?= Security::escapeHtml($menu['description']) ?>" required>
            </div>
            <div class="input-container">
                <label for="theme_id">Thème :</label>
                <select name="theme_id" id="theme_id" required>
                    <?php foreach ($themes as $theme) : ?>
                        <option value="<?= $theme['id'] ?>"
                            <?php if ($theme['id'] === $menu['theme_id']) : ?>
                                selected
                                <?php endif; ?>
                                >
                                <?= Security::escapeHtml($theme['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="input-container">
                            <label for="regime_id">Régime :</label>
                            <select name="regime_id" id="regime_id" required>
                                <?php foreach ($regimes as $regime) : ?>
                                    <option value="<?= $regime['id'] ?>"
                                    <?php if ($regime['id'] === $menu['regime_id']) : ?>
                                selected
                                <?php endif; ?>
                                >
                        <?= Security::escapeHtml($regime['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="input-container">
                <label for="min_people">Nombre de personnes minimum :</label>
                <input type="number" name="min_people" id="min_people" value="<?= $menu['min_people'] ?>" required>
            </div>
            <div class="input-container">
                <label for="base_price">Prix par personne :</label>
                <input type="number" name="base_price" id="base_price" step="0.01" value="<?= $menu['base_price'] ?>" required>
            </div>
            <div class="input-container">
                <label for="conditions">Contraintes spécifiques aux menus :</label>
                <input type="text" name="conditions" id="conditions" value="<?= $menu['conditions'] ?>">
            </div>
        </fieldset>
        <fieldset>
            <legend>Gestion des plats</legend>
            <div class="input-container">
                <label for="entree">Entrée :</label>
                <select name="dishes[]" id="entree" required>
                    <?php foreach ($dishByType[1] as $dish) : ?>
                        <option value="<?= $dish['id'] ?>"
                        <?php if (in_array($dish['id'], $menuDishes)) : ?>
                            selected
                            <?php endif; ?>
                            ><?= Security::escapeHtml($dish['name']) ?>
                        </option>
                        <?php endforeach; ?>
                </select>
            </div>
            <div class="input-container">
                <label for="plat">Plat :</label>
                <select name="dishes[]" id="plat" required>
                    <?php foreach ($dishByType[2] as $dish) : ?>
                        <option value="<?= $dish['id'] ?>"
                            <?php if (in_array($dish['id'], $menuDishes)) : ?>
                                selected
                                <?php endif; ?>
                                ><?= Security::escapeHtml($dish['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="input-container">
                        <label for="dessert">Dessert :</label>
                        <select name="dishes[]" id="dessert" required>
                            <?php foreach ($dishByType[3] as $dish) : ?>
                                <option value="<?= $dish['id'] ?>"
                                <?php if (in_array($dish['id'], $menuDishes)) : ?>
                                    selected
                                    <?php endif; ?>
                                    ><?= Security::escapeHtml($dish['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
        </fieldset>
        <fieldset>
            <legend>Gestion des images</legend>
            <div class="container flex">
                <?php foreach ($images as $image) : ?>
                    <div class="f-col">
                        <input type="hidden" name="image_id[]" value="<?= $image['id'] ?>">
                        <img src="<?= $image['image_url'] ?>" alt="">
                        <label for="alt_text<?= $image['id'] ?>">Alt :</label>
                        <input type="text" name="alt_text[]" id="alt_text<?= $image['id'] ?>" value="<?= Security::escapeHtml($image['alt_text']) ?>" required>
                        <label for="display_order<?= $image['id'] ?>">Ordre d'affichage :</label>
                        <input type="number" name="display_order[]" id="display_order<?= $image['id'] ?>" value="<?= $image['display_order'] ?>" required>
                        <div class="flex ju-around my">
                            <label for="delete_image_<?= $image['id'] ?>">Supprimer l'image :</label>
                            <input type="checkbox" name="delete_images[]" id="delete_image_<?= $image['id'] ?>" value="<?= $image['id'] ?>">
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="input-container">
                    <label for="image">Ajouter des images :</label>
                    <input type="file" name="images[]" id="images" multiple>
                </div>
            </fieldset>
        <button type="submit" class="btn primary">Modifier le menu!</button>
    </form>
</div>
<div class="links">
    <a class="btn outline" href="/profile">Retour à mon compte</a>
</div>
