<?php

use App\Core\Security;

?>

<p><strong>Cette section est réservée aux employés de Vite Et Gourmand!</strong></p>

<div class="flex ju-center it-center">
    <a class="btn primary" href="/employe/menu/create">Ajouter un nouveau menu.</a>
</div>

<h2>Liste des menus existants :</h2>

<table>
    <tr>
        <th>Nom</th>
        <th>Stock</th>
        <th>Ajouter</th>
        <th>Plats</th>
        <th>Allergenes</th>
        <th>Statut</th>
        <th>Modifier</th>
    </tr>
    <?php foreach ($menus as $menu) : ?>
        <tr>
            <td><?= Security::escapeHtml($menu['title']) ?></td>
            <td data-stock><?= $menu['stock'] ?></td>
            <td>
                <form action="/employe/menu/addstock/<?= $menu['id'] ?>" method="post" data-form="menu-addstock">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="number" name="quantity" min="1">
                    <button type="submit" class="btn" data-menu-id="<?= $menu['id'] ?>">➕</button>
                </form>
            </td>
            <td>
                <ul>
                    <?php foreach ($menu['dishes'] as $dish) : ?>
                        <li><?= Security::escapeHtml($dish['name']) ?></li>
                    <?php endforeach; ?>
                </ul>
            </td>
            <td>
                <ul>
                    <?php foreach ($menu['allergenes'] as $allergene) : ?>
                        <li><?= Security::escapeHtml($allergene['name']) ?></li>
                    <?php endforeach; ?>
                </ul>
            </td>
            <td>
                <form action="/employe/menu/toggle/<?= $menu['id'] ?>" method="post" data-form="menu-toggle">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <?php if ($menu['active']) : ?>
                            <button class="btn primary" type="submit" data-menu-id="<?= $menu['id'] ?>">
                                Actif
                            </button>
                        <?php else : ?>
                            <button class="btn outline" type="submit" data-menu-id="<?= $menu['id'] ?>">
                                Inactif
                            </button>
                        <?php endif; ?>
                </form>
            </td>
            <td><a href="/employe/menu/edit/<?= $menu['id'] ?>">Modifier</a></td>
        </tr>
    <?php endforeach; ?>
</table>
