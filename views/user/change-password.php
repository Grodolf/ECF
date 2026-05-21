<form method="POST" action="/change-password">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
    
    <div class="input-container rel">
        <label for="old_password">Mot de passe actuel *</label>
        <input type="password" id="old_password" name="old_password" required>
        <img class="eye" src="/img/eye-off.svg" alt="Afficher le mot de passe" aria-label="Afficher le mot de passe">
    </div>
    
    <div class="input-container rel">
        <label for="new_password">Nouveau mot de passe *</label>
        <input type="password" id="new_password" name="new_password" required>
        <img class="eye" src="/img/eye-off.svg" alt="Afficher le mot de passe" aria-label="Afficher le mot de passe">
    </div>

    <small style="display: block; margin-top: 5px; color: #666;">
        10 caractères minimum, avec majuscule, minuscule, chiffre et caractère spécial
    </small>
    
    <button class="btn outline" type="submit">Modifier mon mot de passe</button>
</form>

<div class="links">
    <a class="btn text" href="/profile">Annuler</a>
</div>
