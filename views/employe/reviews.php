<?php

use App\Core\Security;

?>

<div class="flex ju-center d:col-2">
    <p><strong>Cette section est réservée aux employés de Vite Et Gourmand!</strong></p>
</div>

<section class="f-col g px d:col-5">
    <h2>Avis en attentes de validation :</h2>

    <table>
        <tr>
            <th>Client</th>
            <th>Commande</th>
            <th>Note</th>
            <th>Commentaire</th>
            <th>Validation</th>
        </tr>
        <?php foreach ($reviews as $review) : ?>
            <tr>
                <td><?= Security::escapeHtml($review['nom']) . '<br />' . Security::escapeHtml($review['prenom']) ?></td>
                <td><a href="order/detail/<?= $review['order_id'] ?>">Commande N° <?= $review['order_id'] ?></a></td>
                <td><?= $review['rating'] ?> / 5</td>
                <td><?= Security::escapeHtml($review['comment']) ?></td>
                <td class="rel">
                    <form class="flex" method="POST" action="/review/validate/<?= $review['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <select name="status" id="status-<?= $review['id'] ?>" data-status>
                            <option value="approved">Accepté</option>
                            <option value="rejected">Refusé</option>
                        </select>
                        <div class="reject" data-comment>
                            <label class="mb+" for="comment-<?= $review['id'] ?>">Raison du refus</label>
                            <textarea name="comment" id="comment-<?= $review['id'] ?>"
                            rows="3" cols="35"></textarea>
                        </div>
                        <button class="btn primary" type="submit">Valider</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</section>
<div class="links">
    <a class="btn outline" href="/profile">Retour à mon compte</a>
</div>
