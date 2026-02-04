<h1>Créer un compte</h1>

<form method="POST" action="/register">
    <input type="hidden" name="token" value="<?= htmlspecialchars($csrfToken) ?>">
    
    <label for="nom">Nom *</label>
    <input type="text" id="nom" name="nom" required>
    
    <label for="prenom">Prénom *</label>
    <input type="text" id="prenom" name="prenom" required>
    
    <label for="email">Adresse email *</label>
    <input type="email" id="email" name="email" required>
    
    <label for="gsm">Numéro de téléphone *</label>
    <input type="tel" id="gsm" name="gsm" required>
    
    <label for="adresse">Adresse postale *</label>
    <input type="text" id="adresse" name="adresse" required>
    
    <label for="password">Mot de passe *</label>
    <input type="password" id="password" name="password" required>
    <small style="display: block; margin-top: 5px; color: #666;">
        10 caractères minimum, avec majuscule, minuscule, chiffre et caractère spécial
    </small>
    
    <button type="submit">Créer mon compte</button>
</form>

<div class="links">
    <a href="/login">Déjà un compte ? Se connecter</a>
    <a href="/home">Retour à l'accueil</a>
</div>
