<section class="d:col-5">
    <h2>Formulaire de contact</h2>

    <form action="/sendmail" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <div class="input-container">
            <label for="nom">Nom complet :</label>
            <input type="text" name="nom" id="nom" required>
        </div>
        <div class="input-container">
            <label for="email">Adresse email :</label>
            <input type="email" name="email" id="email" required>
        </div>
        <div class="input-container">
            <label for="title">Sujet :</label>
            <input type="text" name="title" id="title" required>
        </div>
        <div class="input-container">
            <label for="message">Message :</label>
            <textarea name="message" id="message" cols="30" rows="10" required></textarea>
        </div>
        <button class="btn primary" type="submit">Envoyer</button>
    </form>
</section>
