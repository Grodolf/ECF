<h1>Nouveau mot de passe</h1>

<p>Compte : <strong><?= htmlspecialchars($email) ?></strong></p>

<form method="POST" action="/new-password/<?= htmlspecialchars($token) ?>">
    <input type="hidden" name="token" value="<?= htmlspecialchars($csrfToken) ?>">
    <input type="hidden" name="reset_token" value="<?= htmlspecialchars($token) ?>">
    
    <label for="password">Nouveau mot de passe *</label>
    <input type="password" id="password" name="password" required>
    <small style="display: block; margin-top: 5px; color: #666;">
        10 caractères minimum, avec majuscule, minuscule, chiffre et caractère spécial
    </small>
    
    <button type="submit">Réinitialiser mon mot de passe</button>
</form>

<div class="links">
    <a href="/login">Retour à la connexion</a>
</div>
