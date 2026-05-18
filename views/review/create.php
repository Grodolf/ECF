<?php

use App\Core\Security;

?>

<div class="f-col ju-center it-center d:col-5">
    <p>Merci de donner votre avis pour notre prestation. Après vérification par notre équipe, il sera visible sur notre site internet.</p>
    <p><small>La vérification se fait uniquement pour éviter les propos déplacés, haineux ou insultants. Nous publierons tous les avis, même négatifs.</small></p>
</div>

<section class="f-col d:col-5">
    <h2>Détail de la commande :</h2>

    <table>
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
</section>

<section class="f-col d:col-5">
    <h2>Votre avis :</h2>

    <form method="POST" action="/review/store/<?= $order['id'] ?>">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <div class="rating">
            <input value="5" name="rating" id="star5" type="radio">
            <label for="star5"></label>
            <input value="4" name="rating" id="star4" type="radio">
            <label for="star4"></label>
            <input value="3" name="rating" id="star3" type="radio">
            <label for="star3"></label>
            <input value="2" name="rating" id="star2" type="radio">
            <label for="star2"></label>
            <input value="1" name="rating" id="star1" type="radio">
            <label for="star1"></label>
        </div>
        <div class="input-container">
            <label for="comment">Commentaires :</label>
            <textarea name="comment" id="comment" rows="5" cols="15"></textarea>
        </div>
        <button class="btn primary" type="submit">Poster votre avis</button>
    </form>
</section>
<div class="links">
    <a class="btn outline" href="/profile">Retour à mon compte</a>
</div>
