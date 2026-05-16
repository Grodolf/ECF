<?php

use App\Core\Security;
use App\Core\Session;

$stockOk = isset($menu['stock']) && $menu['stock'] >= $menu['min_people'];

?>

<div class="d:col-5">
    <?php if (!empty($images)) : ?>
        <div class="carousel" data-carousel>
            <div class="carousel-track">
                <?php foreach ($images as $index => $image) : ?>
                    <div class="carousel-slide <?= $index === 0 ? 'active' : '' ?>">
                        <img src="<?= Security::escapeHtml($image['image_url']) ?>" alt="<?= Security::escapeHtml($image['alt_text']) ?>">
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="carousel-btn prev hidden" data-mobile data-carousel-prev>⮜</button>
            <button class="carousel-btn next hidden" data-mobile data-carousel-next>⮞</button>
            <div class="carousel-position">
                <?php foreach ($images as $index => $image) : ?>
                    <button class="carousel-dot <?= $index === 0 ? 'active' : '' ?>" data-carousel-dot="<?= $index ?>"></button>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="f-col g mt d:col-3">
    <?php if (!empty($dishesByType)) : ?>
        <p class="px"><?= Security::escapeHtml($menu['description']) ?></p>
    <?php else : ?>
        <p class="px">Ce menu est en cours de création.</p>
    <?php endif; ?>

    <?php foreach ($dishesByType as $typeName => $dishes) : ?>
        <section class="f-col g">
            <h2><?= Security::escapeHtml($typeName) ?></h2>

            <?php foreach ($dishes as $dish) : ?>
                <div class="card inline">
                    <div class="card-header">
                        <img src="<?= Security::escapeHtml($dish['image_url']) ?>" alt="<?= Security::escapeHtml($dish['description']) ?>">
                        <?php foreach ($dish['allergenes'] as $allergene) : ?>
                            <div class="badge danger">
                                <p><?= Security::escapeHtml($allergene['name']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="card-body">
                        <h3 class="card-title"><?= Security::escapeHtml($dish['name']) ?></h3>
                        <p class="card-description"><?= Security::escapeHtml($dish['description']) ?></p>
                        <?php if (!empty($dish['allergenes'])) : ?>
                            <div class="">
                                <strong>Allergènes :</strong>
                                <?php foreach ($dish['allergenes'] as $allergene) : ?>
                                    <p><?= Security::escapeHtml($allergene['description']) ?></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endforeach; ?>
</div>

<div class="f-col g it-center d:col-2">
    <?php if (!empty($menu['conditions'])) : ?>
        <h3>Conditions à respecter</h3>
        <p class="px"><?= Security::escapeHtml($menu['conditions']) ?></p>
    <?php endif; ?>

    <?php if ($stockOk) : ?>
        <p class="px">Il y a actuellement <?= $menu['stock'] ?> menus disponibles à la commande.</p>
    <?php else : ?>
        <p class="px">Ce menu est actuellement indisponible.</p>
    <?php endif; ?>

    <?php if ($stockOk) : ?>
        <?php if (Session::isAuthenticated()) : ?>
            <a class="btn outline max" href="/order/<?= $menu['id'] ?>">Commander le menu</a>
        <?php else : ?>
            <a class="btn outline max" href="/login?redirect=/menu/<?= $menu['id'] ?>">Se connecter</a>
        <?php endif; ?>
    <?php endif; ?>
</div>
