<?php

use App\Core\Security;

?>

<div class="f-col ju-center g+ px it-center d:col-5">
    <p><strong>Cette section est réservée aux employés de Vite Et Gourmand!</strong></p>
    <a class="btn primary" href="dish/create">Ajouter un nouveau plat.</a>
</div>

<section d:col-5>
    <h2>Liste des plats existants :</h2>
    <div class="over d:col-5">
        <table>
            <tr>
                <th>Nom</th>
                <th>Type</th>
                <th>Allergènes</th>
                <th>Statut</th>
                <th>Modifier</th>
            </tr>
            <?php foreach ($dishes as $dish) : ?>
                <tr>
                    <td><?= Security::escapeHtml($dish['name']) ?></td>
                    <td><?= Security::escapeHtml($dish['type']) ?></td>
                    <td>
                        <ul>
                            <?php foreach ($dish['allergenes'] as $allergene) : ?>
                                <li><?= $allergene ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </td>
                    <td>
                        <form data-form="dish-toggle">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                <?php if ($dish['active']) : ?>
                                    <button class="btn primary" type="submit" data-dish-id="<?= $dish['id'] ?>">
                                        Actif
                                    </button>
                                <?php else : ?>
                                    <button class="btn outline" type="submit" data-dish-id="<?= $dish['id'] ?>">
                                        Inactif
                                    </button>
                                <?php endif; ?>
                        </form>
                    </td>
                    <td><a href="/employe/dish/edit/<?= $dish['id'] ?>">Modifier</a></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</section>
<div class="links">
    <a class="btn outline" href="/profile">Retour à mon compte</a>
</div>
