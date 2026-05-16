
<form method="POST" action="/new-password/<?= htmlspecialchars($token) ?>">
    <p>Compte : <strong><?= htmlspecialchars($email) ?></strong></p>

    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
    <input type="hidden" name="reset_token" value="<?= htmlspecialchars($token) ?>">
    
    <div class="input-container rel">
        <label for="password">Nouveau mot de passe *</label>
        <input type="password" id="password" name="password" required autofocus>
        <img id="eye" src="./img/eye-off.svg" alt="Afficher le mot de passe" aria-label="Afficher le mot de passe">
    </div>
    <small style="display: block; margin-top: 5px; color: #666;">
        10 caractères minimum, avec majuscule, minuscule, chiffre et caractère spécial
    </small>
    
    <button class="btn outline" type="submit">Réinitialiser mon mot de passe</button>
</form>

<div class="links">
    <a class="btn text" href="/login">Retour à la connexion</a>
    <a class="btn text" href="/home">Retour à l'accueil</a>
</div>
