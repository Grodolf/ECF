
<form method="POST" action="/register">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
    <p>Tous les champs sont requis.</p>
    
    <div class="input-container">
        <label for="nom">Nom :</label>
        <input type="text" id="nom" name="nom" required autofocus>
    </div>
    
    <div class="input-container">
        <label for="prenom">Prénom :</label>
        <input type="text" id="prenom" name="prenom" required>
    </div>
    
    <div class="input-container">
        <label for="email">Adresse email :</label>
        <input type="email" id="email" name="email" required>
    </div>
    
    <div class="input-container">
        <label for="gsm">Numéro de téléphone :</label>
        <input type="tel" id="gsm" name="gsm" required>
    </div>
    
    <div class="input-container">
        <label for="adresse">Adresse :</label>
        <input type="text" id="adresse" name="adresse" required>
    </div>

    <div class="input-container">
        <label for="code_postal">Code postal :</label>
        <input type="text" id="code_postal" name="code_postal" required>
    </div>

    <div class="input-container">
        <label for="city">Ville :</label>
        <input type="text" id="city" name="city" required>
    </div>
    
    <div class="input-container rel">
        <label for="password">Mot de passe :</label>
        <input type="password" id="password" name="password" required>
        <img id="eye" src="./img/eye-off.svg" alt="Afficher le mot de passe" aria-label="Afficher le mot de passe">
    </div>

    <small>10 caractères minimum, avec majuscule, minuscule, chiffre et caractère spécial</small>
    
    <button class="btn outline" type="submit">Créer mon compte</button>
</form>

<div class="links">
    <div class="btn text">
        <a href="/login">Déjà un compte ? Se connecter</a>
    </div>
    <div class="btn text">
        <a href="/home">Retour à l'accueil</a>
    </div>
</div>
