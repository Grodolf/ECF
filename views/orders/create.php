<?php
use App\Core\Security;

$minPeople = $menu['min_people'];
$basePrice = $menu['base_price'];
?>

<div class="container">

    <section class="order-summary">
        <h2>Récapitulatif</h2>
        <div class="summary-content">
            <p><strong>Menu :</strong> <?= Security::escapeHtml($menu['title']) ?></p>
            <p><strong>Thème :</strong> <?= Security::escapeHtml($menu['theme_name']) ?></p>
            <p><strong>Régime :</strong> <?= Security::escapeHtml($menu['regime_name']) ?></p>
            <p><strong>Nombre minimum de personnes :</strong> <?= $minPeople ?></p>
            <p><strong>Prix de base :</strong> <?= number_format($basePrice, 2, ',', ' ') ?>&nbsp;€</p>
        </div>
    </section>
    
    <form id="order-form" method="POST" action="/order/store">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <input type="hidden" name="menu_id" value="<?= $menu['id'] ?>">
        
        <fieldset>
            <legend>Vos informations</legend>
            
            <div class="input-container">
                <label for="nom">Nom</label>
                <input type="text" id="nom" value="<?= Security::escapeHtml($user['nom']) ?>" disabled>
            </div>
            
            <div class="input-container">
                <label for="prenom">Prénom</label>
                <input type="text" id="prenom" value="<?= Security::escapeHtml($user['prenom']) ?>" disabled>
            </div>
            
            <div class="input-container">
                <label for="email">Email</label>
                <input type="email" id="email" value="<?= Security::escapeHtml($user['email']) ?>" disabled>
            </div>
            
            <div class="input-container">
                <label for="gsm">Téléphone</label>
                <input type="tel" id="gsm" value="<?= Security::escapeHtml($user['gsm'] ?? '') ?>" disabled>
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
                       value="<?= Security::escapeHtml($user['adresse'] ?? '') ?>"
                       required>
            </div>
            
            <div class="input-container">
                <label for="delivery_city">Ville *</label>
                <input type="text"
                       id="delivery_city"
                       name="delivery_city"
                       placeholder="Ville"
                       value="<?= Security::escapeHtml($user['city'] ?? 'Bordeaux') ?>"
                       required>
            </div>
            
            <div class="input-container">
                <label for="delivery_date">Date de livraison *</label>
                <input type="date"
                       id="delivery_date"
                       name="delivery_date"
                       min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                       required>
            </div>
            
            <div class="input-container">
                <label for="delivery_time">Heure souhaitée *</label>
                <input type="time"
                       id="delivery_time"
                       name="delivery_time"
                       required>
            </div>
        </fieldset>
        
        <fieldset>
            <legend>Nombre de convives</legend>
            
            <div class="input-container">
                <label for="nb_people">Nombre de personnes *</label>
                <input type="number"
                       id="nb_people"
                       name="nb_people"
                       min="<?= $minPeople ?>"
                       value="<?= $minPeople ?>"
                       required>
                <p class="help-text">Minimum : <?= $minPeople ?> personnes</p>
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
