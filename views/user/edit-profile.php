<form method="POST" action="/edit-profile">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
    
    <div class="input-container">
        <label for="nom">Nom *</label>
        <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($user['nom']) ?>" required>
    </div>
    
    <div class="input-container">
        <label for="prenom">Prénom *</label>
        <input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($user['prenom']) ?>" required>
    </div>
    
    <div class="input-container">
        <label for="email">Email (non modifiable)</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
    </div>
    
    <div class="input-container">
        <label for="gsm">Téléphone *</label>
        <input type="tel" id="gsm" name="gsm" value="<?= htmlspecialchars($user['gsm']) ?>" required>
    </div>
    
    <div class="input-container">
        <label for="adresse">Adresse *</label>
        <input type="text" id="adresse" name="adresse" value="<?= htmlspecialchars($user['adresse']) ?>" required>
    </div>
    
    <button class="btn outline" type="submit">Enregistrer les modifications</button>
</form>

<div class="links">
    <div class="btn text">
        <a href="/profile">Annuler</a>
    </div>
</div>
