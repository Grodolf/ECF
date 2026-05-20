<?php

declare(strict_types=1);
use App\Core\Security;

?>

<form method="POST" action="/login">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

    <?php if (isset($redirect) && $redirect !== null): ?>
        <input type="hidden" name="redirect" value="<?= Security::escapeHtml($redirect) ?>">
    <?php endif; ?>
    
    <div class="input-container">
        <label for="email">Adresse email :</label>
        <input type="email" id="email" name="email" required autofocus>
    </div>
    
    <div class="input-container rel">
        <label for="password">Mot de passe :</label>
        <input type="password" id="password" name="password" required>
        <img id="eye" src="/img/eye-off.svg" alt="Afficher le mot de passe" aria-label="Afficher le mot de passe">
    </div>
    
    <button class="btn outline" type="submit">Se connecter</button>
</form>

<div class="links">
    <a class="btn text" href="/reset-password">Mot de passe oublié ?</a>
    <a class="btn text" href="/register">Créer un compte</a>
    <a class="btn text" href="/home">Retour à l'accueil</a>
</div>
