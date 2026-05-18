<?php

use App\Core\Security;

$date = new IntlDateFormatter('fr_FR', IntlDateFormatter::LONG, IntlDateFormatter::NONE);
?>

<section id="infos" class="d:col-2">
    <h2 class="col-2">Mes informations personnelles</h2>
    <p><strong>Nom :</strong></p>
    <p><?= Security::escapeHtml($user['nom']) ?></p>
    <p><strong>Prénom :</strong></p>
    <p><?= Security::escapeHtml($user['prenom']) ?></p>
    <p><strong>Email :</strong></p>
    <p><?= Security::escapeHtml($user['email']) ?></p>
    <p><strong>Téléphone :</strong></p>
    <p><?= Security::escapeHtml($user['gsm']) ?></p>
    <p><strong>Adresse :</strong></p>
    <p><?= Security::escapeHtml($user['adresse']) ?></p>
    <p><strong>Code postal :</strong></p>
    <p><?= Security::escapeHtml($user['code_postal']) ?></p>
    <p><strong>Ville :</strong></p>
    <p><?= Security::escapeHtml($user['city']) ?></p>
    <p><strong>Inscription :</strong></p>
    <p><?= $date->format(new DateTime($user['created_at'])) ?></p>
</section>

<section id="orders" class="f-col g d:col-3">
    <h2>Mes commandes</h2>

    <?php if (empty($orders)) : ?>
        <p class="text-muted">Vous n'avez pas encore passé de commande.</p>
    <?php else : ?>
        <?php foreach ($orders as $order) : ?>
            <div class="card inline">
                <div class="card-header card-order">
                    <h3 class="card-title d:mt+"><?= Security::escapeHtml($order['menu_title']) ?></h3>
                    <div class="badge"><?= Security::escapeHtml($order['status_name']) ?></div>
                </div>
                <div class="card-body">
                    <p class="card-description"><?= $order['nb_people'] ?> personnes — <?= number_format($order['total_price'], 2, ',', ' ') ?> €</p>
                    <p class="card-description"><?= date('d/m/Y', strtotime($order['delivery_date'])) ?></p>
                    <div class="card-actions">
                        <a class="btn" href="/order/detail/<?= $order['id'] ?>">Détails</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<div class="links d:col-5">
    <?php if (in_array($user['role'], ['employe', 'admin'])) : ?>
        <a class="btn primary" href="/employe/orders">Gestion des commandes</a>
        <a class="btn primary" href="/employe/dishes">Gestion des plats</a>
        <a class="btn primary" href="/employe/menus">Gestion des menus</a>
        <a class="btn primary" href="/employe/reviews">Modération des avis</a>
        <a class="btn primary" href="/employe/schedules">Modifier les horaires d'ouvertures.</a>
    <?php endif; ?>
    <a class="btn text" href="/edit-profile">Modifier mes informations</a>
    <a class="btn text" href="/change-password">Changer mon mot de passe</a>
    <a class="btn text" href="/logout">Se déconnecter</a>
    <a class="btn text" href="/home">Retour à l'accueil</a>
</div>

