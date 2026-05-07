<?php

use App\Core\Security;

?>

<p><strong>Cette section est réservée aux employés de Vite Et Gourmand!</strong></p>

<div class="f-col g">
    <h2>Commandes :</h2>
    
    <h3>Filtrer les commandes :</h3>

    <form id="filter_orders" method="GET" action="/employe/orders">
        <div class="input-container">
            <label for="status_id">Statut de la commandes</label>
            <select name="status_id" id="status_id">
                <option value="">Tous les statuts</option>
                <?php foreach ($statuses as $status) : ?>
                    <option
                    value="<?= $status['id'] ?>"
                    <?= (isset($_GET['status_id']) && $_GET['status_id'] == $status['id']) ? 'selected' : '' ?>
                >
                <?= Security::escapeHtml($status['name']) ?>
</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="input-container">
            <label for="search">Recherche :</label>
            <input
                type="text" name="search" id="search"
                placeholder="Nom, Prenom ou Email"
                value="<?= isset($_GET['search']) ? Security::escapeHtml($_GET['search']) : '' ?>"
            >
        </div>
        <button type="submit">Appliquer</button>
    </form>

    <h3>Aperçu :</h3>

    <table>
        <tr>
            <th>ID</th>
            <th>Menu</th>
            <th>Quantité</th>
            <th>Client</th>
            <th>E-mail</th>
            <th>Téléphone</th>
            <th>Adresse de livraison</th>
            <th>Date et heure de livraison</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($orders as $order) : ?>
            <tr>
                <td><?= $order['id'] ?></td>
                <td><?= $order['nb_people'] ?></td>
                <td><?= Security::escapeHtml($order['nb_people']) ?></td>
                <td><?= Security::escapeHtml($order['nom']) .' '. Security::escapeHtml($order['prenom']) ?></td>
                <td><?= Security::escapeHtml($order['email']) ?></td>
                <td><?= Security::escapeHtml($order['gsm']) ?></td>
                <td><?= Security::escapeHtml($order['delivery_address']) .', '. Security::escapeHtml($order['delivery_city']) ?></td>
                <td><?= date('d/m/Y', strtotime($order['delivery_date'])) .' à '. date('H:i', strtotime($order['delivery_time'])) ?></td>
                <td><?= Security::escapeHtml($order['status_name']) ?></td>
                <td><a href="/order/detail/<?= $order['id'] ?>" class="btn">Détail</a></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
