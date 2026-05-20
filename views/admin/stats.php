<?php

use App\Core\Security;

$month = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

?>

<section class="f-col g px it-center d:col-1">
    <h2>Options de filtrage</h2>
    
    <form method="GET" action="/admin/stats">
        <fieldset class="f-col g">
            <legend>Date de début</legend>
            <div class="input-container">
                <label for="year_start">Année :</label>
                <select name="year_start" id="year_start">
                    <?php foreach ($years as $year) : ?>
                        <option value="<?= $year ?>"
                            <?php if ($year === $filters['year_start']) : ?>
                                selected
                            <?php endif; ?>
                        ><?= $year ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="input-container">
                <label for="month_start">Mois :</label>
                <select name="month_start" id="month_start">
                    <?php foreach ($month as $key => $m) : ?>
                        <option value="<?= $key + 1 ?>"
                            <?php if ($key + 1 === $filters['month_start']) : ?>
                                selected
                            <?php endif; ?>
                        ><?= Security::escapeHtml($m) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </fieldset>
        <fieldset class="f-col g">
            <legend>Date de fin</legend>
            <div class="input-container">
                <label for="year_end">Année :</label>
                <select name="year_end" id="year_end">
                    <?php foreach ($years as $year) : ?>
                        <option value="<?= $year ?>"
                            <?php if ($year === $filters['year_end']) : ?>
                                selected
                            <?php endif; ?>
                        ><?= $year ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="input-container">
                <label for="month_end">Mois :</label>
                <select name="month_end" id="month_end">
                    <?php foreach ($month as $key => $m) : ?>
                        <option value="<?= $key + 1 ?>"
                            <?php if ($key + 1 === $filters['month_end']) : ?>
                                selected
                            <?php endif; ?>
                        ><?= Security::escapeHtml($m) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </fieldset>
        <fieldset>
            <legend>Type de menu :</legend>
            <div class="input-container">
                <label for="menu_id">Menu :</label>
                <select name="menu_id" id="menu_id">
                    <option value="">Tous menus</option>
                    <?php foreach ($revenues as $revenue) : ?>
                        <option value="<?= $revenue['_id'] ?>"><?= $revenue['title'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </fieldset>
        <button class="btn primary" type="submit">Appliquer les filtres</button>
    </form>
</section>

<section class="d:col-4">
    <h3>Chiffre d'affaires</h3>
    <div class="charts">
        <div class="px" id="chart-revenues"></div>
    </div>
</section>
<div></div>
<section class="d:col-4">
    <h3>Nombre de ventes</h3>
    <div class="charts">
        <div class="px" id="chart-orders"></div>
    </div>
</section>

<script>
window.ordersData   = <?= json_encode($orders) ?>;
window.revenuesData = <?= json_encode($revenues) ?>;
</script>
