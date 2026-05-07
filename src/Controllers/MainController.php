<?php

declare(strict_types=1);

namespace App\Controllers;

/**
 * Renders the static public pages: home and contact.
 */
class MainController extends AbstractController
{
    /**
     * Renders the home page.
     */
    public function home(): void
    {
        $this->renderView('home.php', [
            'title' => 'Bienvenue&nbsp;chez Vite&nbsp;&&nbsp;Gourmand',
            'description' => 'Traiteur Bordeaux depuis 1999. Menus raffinés sur mesure pour vos événements : mariages, réceptions, repas famille. Commande en ligne. Devis gratuit.',
            'headline' => 'Bienvenue'
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
