<?php

use App\Core\Security;

?>

<div class="f-col ju-center g+ it-center d:col-5">
    <p><strong>Cette section est réservée aux employés de Vite Et Gourmand!</strong></p>
</div>

<section class="d:col-5">
    <form action="/employe/schedule/update" method="post">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <?php foreach ($schedules as $schedule) : ?>
                <fieldset class="f-col g it-center d:flex">
                    <legend><?= Security::escapeHtml($schedule['day_of_week']) ?></legend>
                    <input type="hidden" name="id[<?= $schedule['id'] ?>]" value="<?= $schedule['id'] ?>">
                    <label for="opening-<?= $schedule['id'] ?>">Heure d'ouverture :</label>
                    <input type="time" name="opening_time[<?= $schedule['id'] ?>]" id="opening-<?= $schedule['id'] ?>"
                    value="<?= $schedule['opening_time'] ?>" data-opening>
                    <label for="closing-<?= $schedule['id'] ?>">Heure de fermeture :</label>
                    <input type="time" name="closing_time[<?= $schedule['id'] ?>]" id="closing-<?= $schedule['id'] ?>"
                    value="<?= $schedule['closing_time'] ?>" data-closing>
                    <div class="flex g">
                        <label for="closed-<?= $schedule['id'] ?>">Fermé toute la journée</label>
                        <input type="hidden" name="closed[<?= $schedule['id'] ?>]" value="0">
                        <input type="checkbox" name="closed[<?= $schedule['id'] ?>]" id="closed-<?= $schedule['id'] ?>" value="1"
                        <?php if ($schedule['closed']) : ?>
                            checked
                        <?php endif; ?>
                        >
                    </div>
                </fieldset>
            <?php endforeach; ?>
        <button class="btn primary" type="submit">Enregistrer les modifications</button>
    </form>
</section>
<div class="links">
    <a class="btn outline" href="/profile">Retour à mon compte</a>
</div>
