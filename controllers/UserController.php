<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AbstractController;
use App\Core\FlashMessage;
use App\Core\Security;
use App\Core\Session;
use App\Models\User;

class UserController extends AbstractController
{
    private User $userModel;
    private $auth = '/templates/partials/auth-check.php';

    public function __construct()
    {
        $this->userModel = new User();
    }

    public function profile(): void
    {
        require_once dirname(__DIR__, 1) . $this->auth;

        $this->renderView('user/profile.php', ['user' => $currentUser]);
    }

    public function editProfile(): void
    {
        require_once dirname(__DIR__, 1) . $this->auth;

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {

            $this->renderView('user/edit-profile.php', [
                'user' => $currentUser,
                'csrfToken' => Security::generateCsrfToken()
            ]);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if (!Security::verifyCsrfToken($_POST['token'])) {
                FlashMessage::invalidCsrf();
                $this->redirectToRoute('profile');
                die;
            }

            Security::sanitizeInput($_POST);

            $fields = ['nom','prenom','gsm','adresse','token'];
            $validate_required = Security::validateRequired($_POST, $fields);
            if ($validate_required !== []) {
                FlashMessage::fieldsRequired($validate_required);
                $this->redirectToRoute('edit-profile');
                die;
            }

            if (!$this->userModel->update($currentUser['id'], $_POST)) {
                FlashMessage::genericError();
                $this->redirectToRoute('edit-profile');
                die;
            }

            $updatedUser = $this->userModel->findById($currentUser['id']);

            if ($updatedUser === null) {
                FlashMessage::genericError();
                $this->redirectToRoute('profile');
                die;
            }

            Session::setUser($updatedUser);
            FlashMessage::profileUpdated();
            $this->redirectToRoute('profile');
            exit;
        }
    }

    public function changePassword(): void
    {
        require_once dirname(__DIR__, 1) . $this->auth;

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {

            $this->renderView('user/change-password.php', [
                'user' => $currentUser,
                'csrfToken' => Security::generateCsrfToken()
            ]);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if (!Security::verifyCsrfToken($_POST['token'])) {
                FlashMessage::invalidCsrf();
                $this->redirectToRoute('profile');
                die;
            }

            Security::sanitizeInput($_POST);

            $fields = ['old_password','new_password','token'];
            $validate_required = Security::validateRequired($_POST, $fields);
            if ($validate_required !== []) {
                FlashMessage::fieldsRequired($validate_required);
                $this->redirectToRoute('change-password');
                die;
            }


            // New_password != old_password
            if ($_POST['new_password'] === $_POST['old_password']) {
                FlashMessage::samePassword();
                $this->redirectToRoute('change-password');
                die;
            }

            // Old_password verification

            $db_data = $this->userModel->findByEmail($currentUser['email']);
            if (!Security::verifyPassword($_POST['old_password'], $db_data['password'])) {
                FlashMessage::wrongPassword();
                $this->redirectToRoute('change-password');
                die;
            }

            // New_password format verification
            $validate_password = Security::validatePassword($_POST['new_password']);
            if ($validate_password[0] !== true) {
                FlashMessage::invalidPassword($validate_password[1]);
                $this->redirectToRoute('change-password');
                die;
            }

            // Update hashed password in database
            $_POST['new_password'] = Security::hashPassword($_POST['new_password']);
            if (!$this->userModel->updatePassword($currentUser['id'], $_POST['new_password'])) {
                FlashMessage::genericError();
                $this->redirectToRoute('change-password');
                die;
            }

            // Success
            FlashMessage::passwordChanged();
            $this->redirectToRoute('profile');
            exit;
        }
    }
}
