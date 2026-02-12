
<form method="POST" action="/reset-password">
    <p>Entrez votre adresse email pour recevoir un lien de réinitialisation.</p>
    <input type="hidden" name="token" value="<?= htmlspecialchars($csrfToken) ?>">
    
    <div class="input-container">
        <label for="email">Adresse email</label>
        <input type="email" id="email" name="email" required autofocus>
    </div>
    
    <button class="btn outline" type="submit">Envoyer le lien</button>
</form>

<div class="links">
    <div class="btn text">
        <a href="/login">Retour à la connexion</a>
    </div>
    <div class="btn text">
        <a href="/home">Retour à l'accueil</a>
    </div>
</div>
