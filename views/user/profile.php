<h1>Mon profil</h1>

<p><strong>Nom :</strong> <?= htmlspecialchars($user['nom']) ?></p>
<p><strong>Prénom :</strong> <?= htmlspecialchars($user['prenom']) ?></p>
<p><strong>Email :</strong> <?= htmlspecialchars($user['email']) ?></p>
<p><strong>Téléphone :</strong> <?= htmlspecialchars($user['gsm']) ?></p>
<p><strong>Adresse :</strong> <?= htmlspecialchars($user['adresse']) ?></p>
<p><strong>Rôle :</strong> <?= htmlspecialchars($user['role']) ?></p>
<p><strong>Membre depuis :</strong> <?= htmlspecialchars($user['created_at']) ?></p>

<div class="links">
    <a href="/edit-profile">Modifier mes informations</a>
    <a href="/change-password">Changer mon mot de passe</a>
    <a href="/logout">Se déconnecter</a>
    <a href="/home">Retour à l'accueil</a>
</div>
