<?php

use App\Core\Security;

?>

<div class="container flex ju-end">
    <div class="f-col g- it-end shrink-0 mr">
        <p>05 56 12 34 56</p>
        <p class="hidden" data-mobile-footer>76 rue des Trois-Conils</p>
        <p class="hidden" data-mobile-footer>Bordeaux</p>
    </div>
</div>
<div class="f-col my g- it-center">
    <button id="footer-button" class="btn text">
        <p>Horaires :</p>
    </button>
    <div class="grid-2 g-- hidden" data-mobile-footer>
            <?php foreach ($schedules as $schedule) : ?>
                <p class="ml"><b><?= Security::escapeHtml($schedule['day_of_week']) ?> :</b></p>
                <?php if ($schedule['closed']) : ?>
                    <p>Fermé</p>
                <?php else : ?>
                    <p>Ouvert de <?= date('H:i', strtotime($schedule['opening_time'])) ?>
                     à <?= date('H:i', strtotime($schedule['closing_time'])) ?></p>
                <?php endif; ?>
            <?php endforeach; ?>
    </div>
</div>
<div class="container flex ju-between">
    <a class="btn text" href="/mentions-legales">Mentions Légales</a>
    <a class="btn text" href="/cgv">Conditions Générales de Vente</a>
</div>
