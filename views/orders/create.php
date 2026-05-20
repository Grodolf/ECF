<?php
use App\Core\Security;

if ($menu) {
    $menuPrice = $menu['base_price'];
    $minPeople = $menu['min_people'];
} else {
    $menuPrice = 0;
    $minpeople = 0;
}
?>

<div class="container">
    <?php if ($menu) : ?>

        <section class="f-col g px d:col-5">
            <h2>Récapitulatif</h2>
            <div class="f-col g-">
                <p><strong>Menu :</strong> <?= Security::escapeHtml($menu['title']) ?></p>
                <p><strong>Thème :</strong> <?= Security::escapeHtml($menu['theme_name']) ?></p>
                <p><strong>Régime :</strong> <?= Security::escapeHtml($menu['regime_name']) ?></p>
                <p><strong>Nombre minimum de personnes :</strong> <?= $menu['min_people'] ?></p>
                <p><strong>Prix de base :</strong> <?= number_format($menuPrice, 2, ',', ' ') ?>&nbsp;€</p>
            </div>
        </section>
        
        <form class="my" id="order-form" method="POST" action="/order/store">
            <input type="hidden" name="menu_id" value="<?= $menu['id'] ?>">

    <?php else : ?>

        <form id="order-form" method="POST" action="/order/store">
            <div class="input-container">
                <label for="menu_id">Choix du menu</label>
                <select class="mr" name="menu_id" id="menu_id">
                    <?php foreach ($list as $l) : ?>
                        <?php if ($l['min_people'] > $l['stock']) {
                            continue;
                        } ?>
                        <option value="<?= $l['id'] ?>"><?= $l['title'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <p id="base-price"><strong>Prix de base :</strong> <?= number_format($menuPrice, 2, ',', ' ') ?>&nbsp;€</p>
    <?php endif; ?>

        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <fieldset>
            <legend>Vos informations</legend>
            
            <div class="input-container">
                <label for="nom">Nom</label>
                <input type="text" id="nom" value="<?= Security::escapeHtml($user['nom']) ?>"readonly>
            </div>
            
            <div class="input-container">
                <label for="prenom">Prénom</label>
                <input type="text" id="prenom" value="<?= Security::escapeHtml($user['prenom']) ?>"readonly>
            </div>
            
            <div class="input-container">
                <label for="email">Email</label>
                <input type="email" id="email" value="<?= Security::escapeHtml($user['email']) ?>"readonly>
            </div>
            
            <div class="input-container">
                <label for="gsm">Téléphone</label>
                <input type="tel" id="gsm" value="<?= Security::escapeHtml($user['gsm'] ?? '') ?>"readonly>
            </div>
        </fieldset>
        
        <fieldset>
            <legend>Livraison</legend>
            
            <div class="input-container">
                <label for="delivery_address">Adresse de livraison :</label>
                <input type="text"
                       id="delivery_address"
                       name="delivery_address"
                       placeholder="Numéro et rue"
                       value="<?= Security::escapeHtml($user['adresse'] ?? '') ?>"
                       required>
            </div>

            <div class="input-container">
                <label for="delivery_postal_code">Code postale :</label>
                <input type="text"
                       id="delivery_postal_code"
                       name="delivery_postal_code"
                       placeholder="Code postale"
                       value="<?= Security::escapeHtml($user['code_postal'] ?? '') ?>"
                       required>
            </div>
            
            <div class="input-container">
                <label for="delivery_city">Ville :</label>
                <input type="text"
                       id="delivery_city"
                       name="delivery_city"
                       placeholder="Ville"
                       value="<?= Security::escapeHtml($user['city'] ?? 'Bordeaux') ?>"
                       required>
            </div>
            
            <div class="input-container">
                <label for="delivery_date">Date de livraison :</label>
                <input type="date"
                       id="delivery_date"
                       name="delivery_date"
                       min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                       required>
            </div>
            
            <div class="input-container">
                <label for="delivery_time">Heure souhaitée :</label>
                <input type="time"
                       id="delivery_time"
                       name="delivery_time"
                       required>
            </div>
        </fieldset>
        
        <fieldset>
            <legend>Nombre de convives</legend>
            
            <div class="input-container">
                <label for="nb_people">Nombre de personnes :</label>
                <input type="number"
                       id="nb_people"
                       name="nb_people"
                       min="<?= $minPeople ?>"
                       value="<?= $minPeople ?>"
                       required>
                <p class="help-text" id="min-people-info">Minimum : <?= $minPeople ?> personnes</p>
                <p class="help-text success hidden" id="reduction-info">
                    Réduction de 10% applicable !
                </p>
            </div>
        </fieldset>
        
        <section class="price-summary">
            <h2>Détail du prix</h2>
            <div id="price-details">
                <p class="calculating">Calcul en cours...</p>
            </div>
        </section>
        
        <div class="form-actions">
            <button type="submit" class="btn primary" id="submit-btn" disabled>
                Commander
            </button>
            <a href="/menu/<?= $menu['id'] ?>" class="btn outline">
                Annuler
            </a>
        </div>
    </form>
</div>

<script>
window.menusList = <?= json_encode(array_map(fn ($l) => [
    'id'         => $l['id'],
    'base_price' => $l['base_price'],
    'min_people' => $l['min_people'],
], $list ?? [])) ?>;
</script>
