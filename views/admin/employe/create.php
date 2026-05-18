
<div class="f-col ju-center g+ it-center d:col-5">
    <p><strong>Cette section est réservée à l'administrateur de Vite Et Gourmand!</strong></p>
</div>

<section class="f-col d:col-5">
    <h2>Création d'un compte employé</h2>
    <form action="/admin/employe/store" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <div class="input-container">
            <label for="nom">Nom :</label>
            <input type="text" name="nom" id="nom" required>
        </div>
        <div class="input-container">
            <label for="prenom">Prenom :</label>
            <input type="text" name="prenom" id="prenom" required>
        </div>
        <div class="input-container">
            <label for="email">Adresse email :</label>
            <input type="email" name="email" id="email" required>
        </div>
        <button class="btn primary" type="submit">Valider</button>
    </form>
</section>
<div class="links">
    <a class="btn outline" href="/profile">Retour à mon compte</a>
</div>
