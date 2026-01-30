<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AbstractController;
use App\Core\DbConnection;

class MainController extends AbstractController
{
    public function home()
    {
        return $this->renderView('home.php', [
            'title' => '',
            'description' => ''
            ]);
    }

    public function contact()
    {
        return $this->renderView('contact.php', [
            'title' => '',
            'description' => ''
        ]);
    }


}
