<?php

use App\Core\Security;

?>

<div class="f-col ju-center g+ px it-center d:col-5">
    <p><strong>Cette section est réservée à l'administrateur de Vite Et Gourmand!</strong></p>
    <a class="btn primary" href="employe/create">Créer un nouveau compte employé</a>
</div>

<section class="d:col-5">
    <h2>Liste des comptes employé :</h2>

    <div class="over">
        <table>
            <tr>
                <th>Nom</th>
                <th>Prenom</th>
                <th>Email</th>
                <th>Status</th>
            </tr>
            <?php foreach ($employes as $employe) : ?>
                <tr>
                    <td><?= Security::escapeHtml($employe['nom']) ?></td>
                    <td><?= Security::escapeHtml($employe['prenom']) ?></td>
                    <td><?= Security::escapeHtml($employe['email']) ?></td>
                    <td>
                        <form data-form="employe-toggle">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                            <?php if ($employe['actif']) : ?>
                                <button class="btn primary" type="submit" data-employe-id="<?= $employe['id'] ?>">
                                    Actif
                                </button>
                            <?php else : ?>
                                <button class="btn outline" type="submit" data-employe-id="<?= $employe['id'] ?>">
                                    Inactif
                                </button>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</section>
<div class="links">
    <a class="btn outline" href="/profile">Retour à mon compte</a>
</div>
