<h1>Connexion</h1>

<form method="POST" action="/login">
    <input type="hidden" name="token" value="<?= htmlspecialchars($csrfToken) ?>">
    
    <label for="email">Adresse email</label>
    <input type="email" id="email" name="email" required>
    
    <label for="password">Mot de passe</label>
    <input type="password" id="password" name="password" required>
    
    <button type="submit">Se connecter</button>
</form>

<div class="links">
    <a href="/reset-password">Mot de passe oublié ?</a>
    <a href="/register">Créer un compte</a>
    <a href="/home">Retour à l'accueil</a>
</div>
