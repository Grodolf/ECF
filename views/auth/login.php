
<form method="POST" action="/login">
    <input type="hidden" name="token" value="<?= htmlspecialchars($csrfToken) ?>">
    
    <div class="input-container">
        <label for="email">Adresse email :</label>
        <input type="email" id="email" name="email" required autofocus>
    </div>
    
    <div class="input-container">
        <label for="password">Mot de passe :</label>
        <input type="password" id="password" name="password" required>
    </div>
    
    <button class="btn outline" type="submit">Se connecter</button>
</form>

<div class="links">
    <div class="btn text">
        <a href="/reset-password">Mot de passe oublié ?</a>
    </div>
    <div class="btn text">
        <a href="/register">Créer un compte</a>
    </div>
    <div class="btn text">
        <a href="/home">Retour à l'accueil</a>
    </div>
</div>
