<?php

use App\Core\Security;

?>

<div class="container">

    <section class="order-summary">
        <h2>Récapitulatif</h2>
        <div class="summary-content">
            <p><strong>Menu :</strong> <?= Security::escapeHtml($menu['title']) ?></p>
            <p><strong>Thème :</strong> <?= Security::escapeHtml($menu['theme_name']) ?></p>
            <p><strong>Régime :</strong> <?= Security::escapeHtml($menu['regime_name']) ?></p>
            <p><strong>Nombre minimum de personnes :</strong> <?= $menu['min_people'] ?></p>
            <p><strong>Prix de base :</strong> <?= number_format($menu['base_price'], 2, ',', ' ') ?>&nbsp;€</p>
        </div>
    </section>

    <form action="/order/edit/<?= $order['id'] ?>" method="post" id="order-form">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <input type="hidden" name="menu_id" value="<?= $order['menu_id'] ?>">

        <fieldset>
            <legend>Nombre de convives</legend>
            
            <div class="input-container">
                <label for="nb_people">Nombre de personnes *</label>
                <input type="number"
                       id="nb_people"
                       name="nb_people"
                       min="<?= $menu['min_people'] ?>"
                       value="<?= $order['nb_people'] ?>"
                       required>
            </div>
        </fieldset>

        <fieldset>
            <legend>Livraison</legend>
            
            <div class="input-container">
                <label for="delivery_address">Adresse de livraison *</label>
                <input type="text"
                       id="delivery_address"
                       name="delivery_address"
                       placeholder="Numéro et rue"
                       value="<?= Security::escapeHtml($order['delivery_address']) ?>"
                       required>
            </div>
            
            <div class="input-container">
                <label for="delivery_city">Ville *</label>
                <input type="text"
                       id="delivery_city"
                       name="delivery_city"
                       placeholder="Ville"
                       value="<?= Security::escapeHtml($order['delivery_city']) ?>"
                       required>
            </div>
            
            <div class="input-container">
                <label for="delivery_date">Date de livraison *</label>
                <input type="date"
                       id="delivery_date"
                       name="delivery_date"
                       min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                       value="<?= $order['delivery_date'] ?>"
                       required>
            </div>
            
            <div class="input-container">
                <label for="delivery_time">Heure souhaitée *</label>
                <input type="time"
                       id="delivery_time"
                       name="delivery_time"
                       value="<?= $order['delivery_time'] ?>"
                       required>
            </div>
        </fieldset>

        <div id="reduction-info" class="hidden"></div>
        <div id="price-details"></div>

        <button type="submit" id="submit-btn" class="btn">Modifier la commande</button>
    </form>

</div>
