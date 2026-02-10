<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AbstractController;

class MainController extends AbstractController
{
    public function home()
    {
        return $this->renderView('home.php', [
            'title' => 'Bienvenue chez Vite & Gourmand',
            'description' => 'Traiteur Bordeaux depuis 1999. Menus raffinés sur mesure pour vos événements : mariages, réceptions, repas famille. Commande en ligne. Devis gratuit.',
            'h1' => 'Bienvenue'
            ]);
    }

    public function contact()
    {
        return $this->renderView('contact.php', [
            'title' => '',
            'description' => '',
            'h1' => ''
        ]);
    }


}
