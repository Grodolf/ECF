<?php

use App\Core\Security;

?>

<section class="f-col g px d:col-3">

    <picture>
        <source srcset="/img/devanture_400.webp" media="(max-width: 480px)">
        <source srcset="/img/devanture_600.webp" media="(max-width: 768px)">
        <source srcset="/img/devanture_800.webp" media="(min-width: 769px)">
        <img src="/img/devanture_400.webp" alt="Vite & Gourmand - Traiteur à Bordeaux depuis 25 ans">
    </picture>

    <p>Vite & Gourmand, c'est l'histoire d'une passion partagée entre Julie et José,  deux amoureux de la gastronomie bordelaise qui ont uni leurs talents il y a  25 ans pour créer une entreprise de traiteur d'exception.</p>
    <p>Installés au cœur de Bordeaux depuis 1999, nous mettons notre savoir-faire  au service de vos événements, qu'il s'agisse d'un repas familial convivial  pour Noël ou Pâques, d'une réception professionnelle, ou de toute autre célébration qui compte pour vous.</p>
    <p> Chaque prestation est unique, pensée et  réalisée sur mesure pour faire de votre événement un moment inoubliable.</p>
</section>

<div id="salad" class="d:col-2">
    <picture>
        <source srcset="/img/salad_400.webp" media="(max-width: 480px)">
        <source srcset="/img/salad_600.webp" media="(max-width: 768px)">
        <source srcset="/img/salad_800.webp" media="(min-width: 769px)">
        <img src="/img/salad_400.webp" alt="Petits plats, grande qualité">
    </picture>
    <p>Menu traditionnel, végétarien, vegan  ou adapté à des régimes spécifiques, nous composons pour vous des plats  qui raviront vos convives.</p>
</div>

<h2 class="d:col-5">Notre équipe</h2>

<section class="f-col g px d:col-3">
    <picture id="team" class="d:col-3">
        <source srcset="/img/equipe_400.webp" media="(max-width: 480px)">
        <source srcset="/img/equipe_600.webp" media="(max-width: 768px)">
        <source srcset="/img/equipe_800.webp" media="(min-width: 769px)">
        <img src="/img/equipe_400.webp" alt="Julie, José et leur équipe devant leur boutique située rue des Trois-Conils à Bordeaux">
    </picture>

    <p>Julie et José se sont rencontrés sur les bancs de l'école hôtelière de Bordeaux. Lui, passionné par les saveurs du terroir aquitain et les accords mets-vins. Elle, créative et perfectionniste, toujours en quête de nouvelles associations gustatives.</p>
    <p>Aujourd'hui, Vite & Gourmand, c'est aussi une petite équipe de cuisiniers et de serveurs passionnés, soigneusement sélectionnés pour leur professionnalisme et leur sens du service. Tous partagent les valeurs qui nous sont chères : respect du produit, créativité et satisfaction client.</p>

</section>


<section class="f-col g px d:col-2">

    <h3>L'excellence&nbsp;au&nbsp;service de&nbsp;vos&nbsp;événements</h3>

    <p>Fort de notre expérience de 25 ans dans le secteur du traiteur, nous avons  développé un savoir-faire reconnu et une expertise qui nous permet de gérer  avec aisance tous types d'événements, des plus intimes aux plus prestigieux. </p>
    <p>Notre équipe vous accompagne à chaque étape de votre projet : de la conception  de votre menu personnalisé jusqu'au service le jour J, en passant par la mise  à disposition de matériel si nécessaire. Nous prenons en charge tous les détails  pour que vous puissiez profiter pleinement de votre événement.</p>
    <p>Ponctualité, présentation soignée et respect de vos attentes sont les maîtres-mots  de notre engagement envers vous. Chaque prestation est l'occasion de prouver que  qualité et réactivité peuvent aller de pair.</p>

</section>

<section class="d:col-5">
    <h2>Vos avis</h2>
    
    <div class="over">
        <div class="flex ju-around max">
            <?php foreach ($reviews as $review) : ?>
                <div class="card review">
                    <div class="card-header flex ju-around">
                        <div class="stars-display">
                            <?php for ($i = 1; $i <= 5; $i++) : ?>
                                <span class="star<?= $i <= $review['rating'] ? ' star--filled' : '' ?>">&#9733;</span>
                            <?php endfor; ?>
                        </div>
                        <h3 class="card-title"><?= $review['rating'] ?> / 5</h3>
                    </div>
                    <div class="card-body">
                      <p class="card-description"><?= Security::escapeHtml($review['comment']) ?></p>
                      <p><?= Security::escapeHtml($review['prenom']) ?> <?= Security::escapeHtml($review['nom']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
