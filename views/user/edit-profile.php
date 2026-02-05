<h1>Modifier mon profil</h1>

<form method="POST" action="/edit-profile">
    <input type="hidden" name="token" value="<?= htmlspecialchars($csrfToken) ?>">
    
    <label for="nom">Nom *</label>
    <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($user['nom']) ?>" required>
    
    <label for="prenom">Prénom *</label>
    <input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($user['prenom']) ?>" required>
    
    <label for="email">Email (non modifiable)</label>
    <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
    
    <label for="gsm">Téléphone *</label>
    <input type="tel" id="gsm" name="gsm" value="<?= htmlspecialchars($user['gsm']) ?>" required>
    
    <label for="adresse">Adresse *</label>
    <input type="text" id="adresse" name="adresse" value="<?= htmlspecialchars($user['adresse']) ?>" required>
    
    <button type="submit">Enregistrer les modifications</button>
</form>

<div class="links">
    <a href="/profile">Annuler</a>
</div>
