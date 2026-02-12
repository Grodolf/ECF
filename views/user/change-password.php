<form method="POST" action="/change-password">
    <input type="hidden" name="token" value="<?= htmlspecialchars($csrfToken) ?>">
    
    <div class="input-container">
        <label for="old_password">Mot de passe actuel *</label>
        <input type="password" id="old_password" name="old_password" required>
    </div>
    
    <div class="input-container">
        <label for="new_password">Nouveau mot de passe *</label>
        <input type="password" id="new_password" name="new_password" required>
    </div>

    <small style="display: block; margin-top: 5px; color: #666;">
        10 caractères minimum, avec majuscule, minuscule, chiffre et caractère spécial
    </small>
    
    <button class="btn outline" type="submit">Modifier mon mot de passe</button>
</form>

<div class="links">
    <div class="btn text">
        <a href="/profile">Annuler</a>
    </div>
</div>
