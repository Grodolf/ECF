<?php

use App\Core\Security;

$date = new IntlDateFormatter('fr_FR', IntlDateFormatter::LONG, IntlDateFormatter::NONE);
?>

<section id="infos">
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

<section id="orders" class="f-col g">
    <h2>Mes commandes</h2>

    <?php if (empty($orders)) : ?>
        <p class="text-muted">Vous n'avez pas encore passé de commande.</p>
    <?php else : ?>
        <?php foreach ($orders as $order) : ?>
            <div class="card">
                <div class="card-header card-order">
                    <div class="badge"><?= Security::escapeHtml($order['status_name']) ?></div>
                </div>
                <div class="card-body">
                    <h3 class="card-title"><?= Security::escapeHtml($order['menu_title']) ?></h3>
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

<div class="links">
    <?php if (in_array($user['role'], ['employe', 'admin'])) : ?>
        <div class="btn primary">
            <a href="/employe/orders">Gestion des commandes</a>
        </div>
    <?php endif; ?>
    <div class="btn text">
        <a href="/edit-profile">Modifier mes informations</a>
    </div>
    <div class="btn text">
        <a href="/change-password">Changer mon mot de passe</a>
    </div>
    <div class="btn text">
        <a href="/logout">Se déconnecter</a>
    </div>
    <div class="btn text">
        <a href="/home">Retour à l'accueil</a>
    </div>
</div>
