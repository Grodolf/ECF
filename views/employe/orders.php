<?php

use App\Core\Security;

?>
<div class="flex ju-center px d:col-2">
    <p><strong>Cette section est réservée aux employés de Vite Et Gourmand!</strong></p>
</div>

<section class="f-col g px d:col-3">
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
                    ><?= Security::escapeHtml($status['name']) ?>
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
        <button class="btn primary" type="submit">Appliquer</button>
    </form>
</section>

<section class="d:col-5">
<h2>Aperçu :</h2>
    <div class="over my d:col-5">
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
                    <td><?= Security::escapeHtml($order['menu_title']) ?></td>
                    <td><?= Security::escapeHtml($order['nb_people']) ?></td>
                    <td><?= Security::escapeHtml($order['nom']) .'&nbsp'. Security::escapeHtml($order['prenom']) ?></td>
                    <td><?= Security::escapeHtml($order['email']) ?></td>
                    <td><?= Security::escapeHtml($order['gsm']) ?></td>
                    <td><?= Security::escapeHtml($order['delivery_address']) .',<br />'. Security::escapeHtml($order['delivery_city']) ?></td>
                    <td><?= date('d/m/Y', strtotime($order['delivery_date'])) .'<br />à '. date('H:i', strtotime($order['delivery_time'])) ?></td>
                    <td><?= Security::escapeHtml($order['status_name']) ?></td>
                    <td><a href="/order/detail/<?= $order['id'] ?>" class="btn">Détail</a></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</section>
<div class="links">
    <a class="btn outline" href="/profile">Retour à mon compte</a>
</div>
