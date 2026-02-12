<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AbstractController;

class MainController extends AbstractController
{
    public function home()
    {
        return $this->renderView('home.php', [
            'title' => 'Bienvenue&nbsp;chez Vite&nbsp;&&nbsp;Gourmand',
            'description' => 'Traiteur Bordeaux depuis 1999. Menus raffinés sur mesure pour vos événements : mariages, réceptions, repas famille. Commande en ligne. Devis gratuit.',
            'headline' => 'Bienvenue'
            ]);
    }

    public function contact()
    {
        return $this->renderView('contact.php', [
            'title' => 'Prendre contact avec nous.',
            'description' => 'Pour toute question, demandes spéciales ou rensignement vous pouvez utiliser ce formulaire.',
            'headline' => 'Contact'
        ]);
    }

}
