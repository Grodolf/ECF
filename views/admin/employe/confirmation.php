<?php

use App\Core\Security;

?>

<section class="d:col-5">

<h1>Compte Créé</h1>

<p>Le compte lié à <?= Security::escapeHtml($email) ?> a bien été créé.</p>
<p>Le mot de passe est affiché maintenant, prenez soin de le noter, il ne sera plus affiché et ne pourra pas être récupéré.</p>

<p class="password"><?= Security::escapeHtml($password) ?></p>

<p class="warning">Assurez-vous d'avoir bien noté le mot de passe avant de cliquer sur le lien ci-dessous !</p>

<a class="btn primary" href="/admin/employes">Retour à la liste</a>

</section>
