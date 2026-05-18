<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Security;
use App\Core\Session;
use App\Core\FlashMessage;
use App\Core\Mailer;
use App\Models\ReviewModel;
use App\Models\ContactModel;

/**
 * Renders the static public pages: home and contact.
 */
class MainController extends AbstractController
{
    private ReviewModel $reviewModel;
    private ContactModel $contactModel;
    private const ROUTE = 'contact';

    public function __construct()
    {
        $this->reviewModel = new ReviewModel();
        $this->contactModel = new ContactModel();
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
            'csrfToken'   => Security::generateCsrfToken(),
            'title'       => 'Prendre contact avec nous.',
            'description' => 'Pour toute question, demandes spéciales ou renseignement vous pouvez utiliser ce formulaire.',
            'headline'    => 'Contact'
        ]);
    }

    public function sendmail(): void
    {
        if (!isset($_POST['csrf_token']) || !Security::verifyCsrfToken($_POST['csrf_token'])) {
            Session::setFlash(FlashMessage::INVALID_CSRF, 'error');
            $this->redirectToRoute(self::ROUTE);
            exit;
        }

        $_POST = Security::sanitizeInput($_POST);

        $fields = ['nom', 'email', 'title', 'message'];
        $validate_required = Security::validateRequired($_POST, $fields);
        if ($validate_required !== []) {
            Session::setFlash(implode(', ', $validate_required), 'error');
            $this->redirectToRoute(self::ROUTE);
            exit;
        }

        $this->contactModel->create($_POST);

        $this->sendContactMail($_POST);
        Session::setFlash(FlashMessage::EMAIL_SUCCESS, 'success');
        $this->redirectToRoute(self::ROUTE);
        exit;
    }

    public function legalNotice(): void
    {
        $this->renderView('ml.php', [
            'title'       => 'Mentions Légales',
            'description' => 'Mentions légales obligatoires',
        ]);
    }

    public function cgv(): void
    {
        $this->renderView('cgv.php', [
            'title'       => 'CGV',
            'description' => 'Conditions générales de ventes',
        ]);
    }

    private function sendContactMail(array $data): void
    {
        try {
            $mailer = new Mailer();

            $mailer->sendWithTemplate(
                'admin@vite-et-gourmand.fr',
                'José Vite&Gourmand',
                'Contact via le formulaire',
                'contact',
                [
                    'nom'      => $data['nom'],
                    'mail'     => $data['email'],
                    'title'    => $data['title'],
                    'message'  => nl2br(htmlspecialchars($data['message'])),
                    'date'     => date('d-m-Y H:i'),
                ]
            );
        } catch (\Exception $e) {
            error_log('Erreur envoi email modification statut de commande : ' . $e->getMessage());
        }
    }
}
