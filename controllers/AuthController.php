<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AbstractController;
use App\Core\Security;
use App\Core\Session;
use App\Core\FlashMessage;
use App\Models\User;

class AuthController extends AbstractController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    // --- Connexion ---

    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {

            $this->renderView('auth/login.php', ['csrfToken' => Security::generateCsrfToken()]);
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if (!Security::verifyCsrfToken($_POST['token'])) {
                FlashMessage::invalidCsrf();
                $this->redirectToRoute('login');
                die;
            }

            Security::sanitizeInput($_POST);

            $fields = ['email','password','token'];
            $validate_required = Security::validateRequired($_POST, $fields);
            if ($validate_required !== []) {
                FlashMessage::fieldsRequired($validate_required);
                $this->redirectToRoute('login');
                die;
            }

            if (!Security::validateEmail($_POST['email'])) {
                FlashMessage::invalidMail();
                $this->redirectToRoute('login');
                die;
            }

            $user = $this->userModel->findByEmail($_POST['email']);
            if ($user === null) {
                FlashMessage::invalidCredentials();
                $this->redirectToRoute('login');
                die;
            }
            if (!Security::verifyPassword($_POST['password'], $user['password'])) {
                FlashMessage::invalidCredentials();
                $this->redirectToRoute('login');
                die;
            }

            Session::setUser($user);
            FlashMessage::loginSuccess();
            Session::regenerate();
            $this->redirectToRoute('home');
            exit;
        }
    }

    public function logout(): void
    {
        Session::destroy();
        $this->redirectToRoute('home');
    }

    // --- Inscription ---

    public function register(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {

            $this->renderView('auth/register.php', ['csrfToken' => Security::generateCsrfToken()]);
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if (!Security::verifyCsrfToken($_POST['token'])) {
                FlashMessage::invalidCsrf();
                $this->redirectToRoute('login');
                die;
            }

            Security::sanitizeInput($_POST);

            $fields = ['nom','prenom','email','gsm','adresse','password','token'];
            $validate_required = Security::validateRequired($_POST, $fields);
            if ($validate_required !== []) {
                FlashMessage::fieldsRequired($validate_required);
                $this->redirectToRoute('login');
                die;
            }

            if (!Security::validateEmail($_POST['email'])) {
                FlashMessage::invalidMail();
                $this->redirectToRoute('register');
                die;
            }

            if ($this->userModel->emailExists($_POST['email'])) {
                FlashMessage::emailAlreadyExists();
                $this->redirectToRoute('register');
                die;
            }

            $validate_password = Security::validatePassword($_POST['password']);
            if ($validate_password[0] !== true) {
                FlashMessage::invalidPassword($validate_password[1]);
                $this->redirectToRoute('register');
                die;
            }

            $_POST['password'] = Security::hashPassword($_POST['password']);

            if (!$this->userModel->create($_POST)) {
                FlashMessage::genericError();
                $this->redirectToRoute('register');
                die;
            }

            /**
            * TODO
            * Mail de bienvenue
            */

            FlashMessage::registerSuccess();
            $this->redirectToRoute('login');
            exit;
        }
    }

    // --- Réinitialisation mot de passe ---

    public function resetPassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {

            $this->renderView('auth/reset-password.php', ['csrfToken' => Security::generateCsrfToken()]);
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if (!Security::verifyCsrfToken($_POST['token'])) {
                FlashMessage::invalidCsrf();
                $this->redirectToRoute('reset-password');
                die;
            }

            Security::sanitizeInput($_POST);

            $fields = ['email','token'];
            $validate_required = Security::validateRequired($_POST, $fields);
            if ($validate_required !== []) {
                FlashMessage::fieldsRequired($validate_required);
                $this->redirectToRoute('reset-password');
                die;
            }

            if (!Security::validateEmail($_POST['email'])) {
                FlashMessage::invalidMail();
                $this->redirectToRoute('reset-password');
                die;
            }

            FlashMessage::passwordResetSent();

            if (!$this->userModel->emailExists($_POST['email'])) {
                $this->redirectToRoute('login');
                die;
            }

            $token = Security::generateToken();
            $token_hash = Security::hashToken($token);
            $expire = date('Y-m-d H:i:s', time() + 3600);
            if (!$this->userModel->createPasswordResetToken($_POST['email'], $token_hash, $expire)) {
                FlashMessage::genericError();
            }

            /**
             * TODO
             * Mail avec lien /new-password/{$token}
             */

            header('Location: /new-password/' . $token); // pour test
            // $this->redirectToRoute('login');
            exit;
        }
    }

    public function newPassword(string $token): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {

            $tokenData = $this->userModel->findPasswordResetToken($token);

            if ($tokenData === null) {
                FlashMessage::tokenExpired();
                $this->redirectToRoute('reset-password.php');
                die;
            }

            $this->renderView('auth/new-password.php', [
                'token' => $token,
                'email' => $tokenData['email'],
                'csrfToken' => Security::generateCsrfToken()
            ]);
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if (!Security::verifyCsrfToken($_POST['token'])) {
                FlashMessage::invalidCsrf();
                $this->redirectToRoute('reset-password');
                die;
            }

            $tokenData = $this->userModel->findPasswordResetToken($_POST['reset_token']);
            if ($tokenData === null) {
                FlashMessage::tokenExpired();
                $this->redirectToRoute('reset-password');
                die;
            }

            Security::sanitizeInput($_POST);

            $fields = ['password','token','reset_token'];
            $validate_required = Security::validateRequired($_POST, $fields);
            if ($validate_required !== []) {
                FlashMessage::fieldsRequired($validate_required);
                $this->redirectToRoute('reset-password');
                die;
            }

            $validate_password = Security::validatePassword($_POST['password']);
            if ($validate_password[0] !== true) {
                FlashMessage::invalidPassword($validate_password[1]);
                $this->redirectToRoute('reset-password');
                die;
            }

            $hashedPassword = Security::hashPassword($_POST['password']);

            if (!$this->userModel->updatePassword($tokenData['user_id'], $hashedPassword)) {
                FlashMessage::genericError();
                $this->redirectToRoute('reset-password');
                die;
            }

            $tokenHash = Security::hashToken($_POST['reset_token']);
            $this->userModel->markPasswordResetTokenAsUsed($tokenHash);

            FlashMessage::passwordUpdated();
            $this->redirectToRoute('login');
            exit;
        }
    }
}
