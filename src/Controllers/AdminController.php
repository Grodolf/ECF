<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Security;
use App\Core\Session;
use App\Core\FlashMessage;
use App\Core\Mailer;
use App\Models\UserModel;

class AdminController extends AbstractController
{
    private UserModel $userModel;
    private const ROUTE_LIST    = 'admin/employes';
    private const ROUTE_CREATE  = 'admin/employe/create';

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function list(): void
    {
        $user = Security::requireAdmin();
        $employes = $this->userModel->findAllEmployes();

        $this->renderView('admin/employes.php', [
            'csrfToken'   => Security::generateCsrfToken(),
            'user'        => $user,
            'employes'    => $employes,
            'title'       => 'Gestion des salariés',
            'description' => 'Page de gestion des salarié.',
            'scripts'     => ['/js/modules/EmployeToggle.js']
        ]);
    }

    public function toggle(string $id): void
    {
        header('Content-Type: application/json');

        Security::requireAdmin();

        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Security::verifyCsrfToken($csrfToken)) {
            http_response_code(403);
            echo json_encode(['error' => FlashMessage::INVALID_CSRF]);
            exit;
        }

        if (!$this->userModel->toggleActive($id)) {
            http_response_code(500);
            echo json_encode(['error' => FlashMessage::GENERIC_ERROR]);
            exit;
        }

        echo json_encode(['success' => FlashMessage::EMPLOYE_TOGGLED]);
        exit;
    }

    public function create(): void
    {
        $user = Security::requireAdmin();

        $this->renderView('admin/employe/create.php', [
            'csrfToken'   => Security::generateCsrfToken(),
            'user'        => $user,
            'title'       => 'Nouveau compte employé',
            'description' => "Page de création d'un nouveau compte employé"
        ]);
    }
    public function store(): void
    {
        Security::requireAdmin();

        if (!Security::verifyCsrfToken($_POST['csrf_token'])) {
            Session::setFlash(FlashMessage::INVALID_CSRF, 'error');
            $this->redirectToRoute(self::ROUTE_CREATE);
            exit;
        }

        $_POST = Security::sanitizeInput($_POST);

        $fields = ['nom', 'prenom', 'email'];
        $validate_required = Security::validateRequired($_POST, $fields);
        if ($validate_required !== []) {
            Session::setFlash(implode(', ', $validate_required), 'error');
            $this->redirectToRoute(self::ROUTE_CREATE);
            exit;
        }

        if (!Security::validateEmail($_POST['email'])) {
            Session::setFlash(FlashMessage::INVALID_MAIL, 'error');
            $this->redirectToRoute(self::ROUTE_CREATE);
            exit;
        }

        if ($this->userModel->emailExists($_POST['email'])) {
            Session::setFlash(FlashMessage::EMAIL_ALREADY_EXISTS, 'error');
            $this->redirectToRoute(self::ROUTE_CREATE);
            exit;
        }

        $password = Security::generateToken(16);
        $hash = Security::hashPassword($password);
        $employe = [
            'nom'      => $_POST['nom'],
            'prenom'   => $_POST['prenom'],
            'email'    => $_POST['email'],
            'password' => $hash
        ];

        if (!$this->userModel->createEmploye($employe)) {
            Session::setFlash(FlashMessage::GENERIC_ERROR, 'error');
            $this->redirectToRoute(self::ROUTE_CREATE);
            exit;
        }
        Session::set('temp_password', $password);
        Session::set('email', $_POST['email']);

        $this->sendEmployeMail($employe);
        Session::setFlash(FlashMessage::REGISTER_SUCCESS, 'success');
        $this->redirectToRoute('admin/employe/confirmation');
        exit;
    }

    public function confirmation(): void
    {
        Security::requireAdmin();

        $password = Session::get('temp_password');
        $email = Session::get('email');
        Session::delete('temp_password');
        Session::delete('email');
        if (!$password) {
            Session::setFlash(FlashMessage::GENERIC_ERROR, 'error');
            $this->redirectToRoute(self::ROUTE_LIST);
            exit;
        }

        $this->renderView('admin/employe/confirmation.php', [
            'password' => $password,
            'email'    => $email
        ]);
    }

    private function sendEmployeMail(array $data): void
    {
        try {
            $mailer = new Mailer();

            $mailer->sendWithTemplate(
                $data['email'],
                $data['nom'],
                "Création de l'espace employé",
                'create-employe',
                [
                    'nom'      => $data['nom'],
                    'prenom'   => $data['prenom'],
                    'email'     => $data['email'],
                    'date'     => date('d-m-Y H:i'),
                ]
            );
        } catch (\Exception $e) {
            error_log('Erreur envoi email modification statut de commande : ' . $e->getMessage());
        }
    }
}
