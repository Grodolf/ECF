<h1>Mot de passe oublié</h1>

<p>Entrez votre adresse email pour recevoir un lien de réinitialisation.</p>

<form method="POST" action="/reset-password">
    <input type="hidden" name="token" value="<?= htmlspecialchars($csrfToken) ?>">
    
    <label for="email">Adresse email</label>
    <input type="email" id="email" name="email" required>
    
    <button type="submit">Envoyer le lien</button>
</form>

<div class="links">
    <a href="/login">Retour à la connexion</a>
    <a href="/home">Retour à l'accueil</a>
</div>
