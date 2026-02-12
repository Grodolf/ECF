
<form method="POST" action="/new-password/<?= htmlspecialchars($token) ?>">
    <p>Compte : <strong><?= htmlspecialchars($email) ?></strong></p>

    <input type="hidden" name="token" value="<?= htmlspecialchars($csrfToken) ?>">
    <input type="hidden" name="reset_token" value="<?= htmlspecialchars($token) ?>">
    
    <div class="input-container">
        <label for="password">Nouveau mot de passe *</label>
        <input type="password" id="password" name="password" required autofocus>
        <small style="display: block; margin-top: 5px; color: #666;">
            10 caractères minimum, avec majuscule, minuscule, chiffre et caractère spécial
        </small>
    </div>
    
    <button class="btn outline" type="submit">Réinitialiser mon mot de passe</button>
</form>

<div class="links">
    <div class="btn text">
        <a href="/login">Retour à la connexion</a>
    </div>
    <div class="btn text">
        <a href="/home">Retour à l'accueil</a>
    </div>
</div>
