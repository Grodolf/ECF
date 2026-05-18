<?php
use App\Core\Security;

?>

<div class="container f-col it-center g d:col-5">
    <p class="">Voici le détail de votre commande <strong>#<?= $order['id'] ?></strong>.</p>
    
    <section class="f-col g">
        <h2>Récapitulatif</h2>
        <div class="f-col g">
            <div class="flex ju-between d:g++">
                <p><strong>Statut :</strong></p>
                <p><?= Security::escapeHtml($order['status_name']) ?></p>
            </div>
            <div class="flex ju-between d:g++">
                <p><strong>Menu :</strong></p>
                <p><?= Security::escapeHtml($order['menu_title']) ?></p>
            </div>
            <div class="flex ju-between d:g++">
                <p><strong>Nombre de personnes :</strong></p>
                <p><?= $order['nb_people'] ?></p>
            </div>
            <div class="flex ju-between d:g++">
                <p><strong>Date de livraison :</strong></p>
                <p><?= date('d/m/Y', strtotime($order['delivery_date'])) ?> à <?= Security::escapeHtml($order['delivery_time']) ?></p>
            </div>
            <div class="flex ju-between d:g++">
                <p><strong>Adresse :</strong></p>
                <p><?= Security::escapeHtml($order['delivery_address']) ?>, <?= Security::escapeHtml($order['delivery_city']) ?></p>
            </div>
            <div class="flex ju-between d:g++">
                <p><strong>TOTAL :</strong></p>
                <p><strong><?= number_format($order['total_price'], 2, ',', ' ') ?> €</strong></p>
            </div>
        </div>
    </section>
    
    <section class="f-col g">
        <h2>Suivi de la commande</h2>
        <?php if (empty($history)) : ?>
            <p class="text-muted">Aucun historique disponible.</p>
        <?php else : ?>
            <p>Création de la commande le <?= date('d/m/Y à H:i', strtotime($order['created_at'])) ?></p>
            <ol>
                <?php foreach ($history as $entry) : ?>
                    <li class="ml+">
                        <?= date('d/m/Y à H:i', strtotime($entry['changed_at'])) ?> -
                         <?= Security::escapeHtml($entry['status_name']) ?>
                        <?php if (!empty($entry['comment'])) : ?>
                            <p><?= Security::escapeHtml($entry['comment']) ?></p>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </section>
    <?php if (in_array($user['role'], ['employe', 'admin'])) : ?>
        <div class="f-col g">
            <h2>Mettre à jour le statut</h2>
            <form method="POST" action="/employe/order/update-status/<?= $order['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="input-container">
                    <label for="status_id">Nouveau statut</label>
                    <select name="status_id" id="status_id">
                        <?php foreach ($statuses as $status) : ?>
                            <option value="<?= $status['id'] ?>" <?= $status['id'] == $order['status_id'] ? 'selected' : '' ?>>
                                <?= Security::escapeHtml($status['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="input-container">
                    <label for="comment">Commentaire</label>
                    <input type="text" name="comment" id="comment" placeholder="Commentaire optionnel">
                </div>
                <button type="submit" class="btn">Mettre à jour</button>
            </form>
            <h2>Annuler la commande</h2>
            <form method="POST" action="/employe/order/cancel/<?= $order['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="input-container">
                    <label for="contact_method">Mode de contact avec le client</label>
                    <select name="contact_method" id="contact_method">
                        <option value="">-- Choisir --</option>
                        <option value="email">Email</option>
                        <option value="telephone">Téléphone</option>
                    </select>
                </div>
                <div class="input-container">
                    <label for="cancellation_reason">Motif d'annulation</label>
                    <input type="text" name="cancellation_reason" id="cancellation_reason"
                           placeholder="Motif obligatoire" required>
                </div>
                <button type="submit" class="btn outline">Annuler la commande</button>
            </form>
        </div>
    <?php endif; ?>
    
    <div class="f-col g it-center">
        <?php if (in_array($user['role'], ['employe', 'admin'])) : ?>
            <a href="/employe/orders" class="btn primary">Retour à la liste des commandes.</a>
        <?php endif; ?>
        <?php if ($order['status_name'] === 'En attente' && $user['id'] === $order['user_id']) : ?>
            <a href="/order/edit/<?= $order['id'] ?>" class="btn outline">Modifier la commande</a>
            <form method="POST" action="/order/cancel/<?= $order['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <button type="submit" class="btn outline">Annuler la commande</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<div class="links">
    <a class="btn outline" href="/profile">Retour à mon compte</a>
</div>
