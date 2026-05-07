<?php
use App\Core\Security;

?>

<div class="container">
    <div class="f-col g">
        <h1>Commande confirmée !</h1>
        
        <p class="">Votre commande <strong>#<?= $order['id'] ?></strong> a été enregistrée avec succès.</p>
        
        <div class="f-col g">
            <h2>Récapitulatif</h2>
            <table class="">
                <tr>
                    <td><strong>Menu :</strong></td>
                    <td><?= Security::escapeHtml($order['menu_title']) ?></td>
                </tr>
                <tr>
                    <td><strong>Nombre de personnes :</strong></td>
                    <td><?= $order['nb_people'] ?></td>
                </tr>
                <tr>
                    <td><strong>Date de livraison :</strong></td>
                    <td><?= date('d/m/Y', strtotime($order['delivery_date'])) ?> à <?= Security::escapeHtml($order['delivery_time']) ?></td>
                </tr>
                <tr>
                    <td><strong>Adresse :</strong></td>
                    <td><?= Security::escapeHtml($order['delivery_address']) ?>, <?= Security::escapeHtml($order['delivery_city']) ?></td>
                </tr>
                <tr class="total-row">
                    <td><strong>TOTAL :</strong></td>
                    <td><strong><?= number_format($order['total_price'], 2, ',', ' ') ?> €</strong></td>
                </tr>
            </table>
        </div>
        
        <div class="info-box">
            <p>Un email de confirmation vous a été envoyé à <strong><?= Security::escapeHtml($order['email']) ?></strong>.</p>
            <p>Votre commande sera traitée dans les plus brefs délais. Vous serez notifié par email à chaque étape.</p>
        </div>
        
        <div class="f-col g">
            <a href="/profile" class="btn primary">Voir mes commandes</a>
            <a href="/menus" class="btn outline">Retour aux menus</a>
        </div>
    </div>
</div>
