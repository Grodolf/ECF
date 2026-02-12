<?php
$date = new IntlDateFormatter('fr_FR', IntlDateFormatter::LONG, IntlDateFormatter::NONE);
?>

<div id="infos">
    <p><strong>Nom :</strong></p>
    <p><?= htmlspecialchars($user['nom']) ?></p>
    <p><strong>Prénom :</strong></p>
    <p><?= htmlspecialchars($user['prenom']) ?></p>
    <p><strong>Email :</strong></p>
    <p><?= htmlspecialchars($user['email']) ?></p>
    <p><strong>Téléphone :</strong></p>
    <p><?= htmlspecialchars($user['gsm']) ?></p>
    <p><strong>Adresse :</strong></p>
    <p><?= htmlspecialchars($user['adresse']) ?></p>
    <p><strong>Inscription :</strong></p>
    <p><?= $date->format(new DateTime($user['created_at'])) ?></p>
</div>

<div class="links">
    <div class="btn text">
        <a href="/edit-profile">Modifier mes informations</a>
    </div>
    <div class="btn text">
        <a href="/change-password">Changer mon mot de passe</a>
    </div>
    <div class="btn text">
        <a href="/logout">Se déconnecter</a>
    </div>
    <div class="btn text">
        <a href="/home">Retour à l'accueil</a>
    </div>
</div>
