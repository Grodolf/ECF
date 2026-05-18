<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ReviewModel;

/**
 * Renders the static public pages: home and contact.
 */
class MainController extends AbstractController
{
    private ReviewModel $reviewModel;

    public function __construct()
    {
        $this->reviewModel = new ReviewModel();
    }

    /**
     * Renders the home page.
     */
    public function home(): void
    {
        $reviews = $this->reviewModel->findValidated();
        $this->renderView('home.php', [
            'title'       => 'Bienvenue chez Vite & Gourmand',
            'description' => 'Traiteur Bordeaux depuis 1999. Menus raffinés sur mesure pour vos événements : mariages, réceptions, repas famille. Commande en ligne. Devis gratuit.',
            'headline'    => 'Bienvenue',
            'reviews'     => $reviews
            ]);
    }

    /**
     * Renders the contact page.
     */
    public function contact(): void
    {
        $this->renderView('contact.php', [
            'title' => 'Prendre contact avec nous.',
            'description' => 'Pour toute question, demandes spéciales ou renseignement vous pouvez utiliser ce formulaire.',
            'headline' => 'Contact'
        ]);
    }

}
