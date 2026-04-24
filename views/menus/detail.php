<?php

use App\Core\Security;
use App\Core\Session;

// Menu description

$description = '';
if (!empty($dishesByType)) {
    $description .= '<p class="px">'. $menu['description'] .'</p>';
} else {
    $description .= '<p class="px">Ce menu est en cours </p>';
}

// Menu image carousel

$carousel = '';
if (!empty($images)) {
    $carousel .= '
        <div class="carousel" data-carousel>
        <div class="carousel-track">
    ';

    foreach ($images as $index => $image) {
        $carousel .= '<div class="carousel-slide ';
        $carousel .= $index === 0 ? 'active' : '';
        $carousel .= '">
            <img src="' . Security::escapeHtml($image['image_url']) .'" alt="'. Security::escapeHtml($image['alt_text']) .'">
            </div>
        ';
    }
    unset($image);
    unset($index);

    $carousel .= '
        </div>
        <button class="carousel-btn prev hidden" data-carousel-prev>⮜</button>
        <button class="carousel-btn next hidden" data-carousel-next>⮞</button>
        <div class="carousel-position">
    ';

    foreach ($images as $index => $image) {
        $carousel .= '<button class="carousel-dot ';
        $carousel .= $index === 0 ? 'active' : '';
        $carousel .= '" data-carousel-dot="'. $index .'"></button>';
    }
    unset($image);
    unset($index);

    $carousel .= '</div></div>';
}

// Displaying the different dishes with allergenes

$dishes_sections = '';
foreach ($dishesByType as $typeName => $dishes) {
    $dishes_sections .= '<section class="">
        <h2>'. Security::escapeHtml($typeName) .'</h2>';

    foreach ($dishes as $dish) {
        $dishes_sections .= '
        <div class="card">
            <div class="card-header">
                <img src="' . Security::escapeHtml($dish['image_url']) . '" alt="' . Security::escapeHtml($dish['description']) . '">
        ';
        if (!empty($dish['allergenes'])) {
            foreach ($dish['allergenes'] as $allergene) {
                $dishes_sections .= '
                <div class="badge danger">
                    <p>'.Security::escapeHtml($allergene['name']).'</p>
                </div>
            ';
            }
        }
        unset($allergene);
        $dishes_sections .= '
        </div>
        <div class="card-body">
            <h3 class="card-title">' . Security::escapeHtml($dish['name']) . '</h3>
            <p class="card-description">' . Security::escapeHtml($dish['description']) . '</p>
        ';
        if (!empty($dish['allergenes'])) {
            $dishes_sections .= '<div class="">
                    <strong>Allergènes :</strong>';

            foreach ($dish['allergenes'] as $allergene) {
                $dishes_sections .= '<p>'.Security::escapeHtml($allergene['description']).'</p>';
            }
            unset($allergene);
            $dishes_sections .= '</div>';
        }
        $dishes_sections .= '</div>';
    }
    unset($dish);
    $dishes_sections .= '</section>';
}
unset($dishes);

// Display of conditions

$conditions = '';
if (!empty($menu['conditions'])) {
    $conditions .= '
        <aside class="warning">
            <h2>Conditions à respecter</h2>
            <p class="px">'.Security::escapeHtml($menu['conditions']).'</p>
        </aside>
    ';
}

// Sotck

$stock = '';
if (isset($menu['stock']) && $menu['stock'] >= $menu['min_people']) {
    $stockOk = true;
    $stock .= '<p class="px">Il y a actuellement '.$menu['stock'].' menus disponibles à la commande.</p>';
} else {
    $stockOk = false;
    $stock .= '<p class="px">Ce menu est actuellement indisponible.</p>';
}

// Order button

$order = '';
if ($stockOk) {
    if (Session::isAuthenticated()) {
        $order .= '
        <a class="btn outline" href="/order/'.$menu['id'].'">Commander le menu</a>
    ';
    } else {
        $order .= '
        <a class="btn outline" href="/login?redirect=/menu/'. $menu['id'] .'">Se connecter</a>
    ';
    }
}

?>

<?= $carousel ?>
<?= $description ?>
<?= $conditions ?>
<?= $dishes_sections ?>
<?= $stock ?>
<?= $order ?>
