<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AbstractController;
use App\Core\Security;
use App\Core\Session;
use App\Models\User;

class AuthController extends AbstractController
{
    private User $userModel;

    // Error messages
    private string $invalidCSRF = 'Requête invalide';
    private string $invalidCredentials = 'Identifiants incorrects';
    private string $invalidEmail = 'Le format de l\'adresse email n\'est pas valide';
    private string $emailExists = 'Cette adresse email est déjà utilisée';
    private string $tokenExpired = 'Ce lien de réinitialisation est invalide ou a expiré';
    private string $genericError = 'Une erreur est survenue, veuillez réessayer';

    // Success messages
    private string $loginSuccess = 'Connexion réussie';
    private string $registerSuccess = 'Votre inscription a bien été enregistrée';
    private string $resetEmail = 'Si cette adresse existe, vous recevrez un email';
    private string $updated = 'Votre mot de passe a été réinitialisé';

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
                Session::setFlash('csrf', $this->invalidCSRF, 'error');
                $this->redirectToRoute('login');
                die;
            }

            Security::sanitizeInput($_POST);

            $fields = ['email','password','token'];
            $validate_required = Security::validateRequired($_POST, $fields);
            if ($validate_required !== []) {
                Session::setFlash('fields', implode(', ', $validate_required), 'error');
                $this->redirectToRoute('login');
                die;
            }

            if (!Security::validateEmail($_POST['email'])) {
                Session::setFlash('email', $this->invalidCredentials, 'error');
                $this->redirectToRoute('login');
                die;
            }

            $user = $this->userModel->findByEmail($_POST['email']);
            if ($user === null) {
                Session::setFlash('login', $this->invalidCredentials, 'error');
                $this->redirectToRoute('login');
                die;
            }
            if (!Security::verifyPassword($_POST['password'], $user['password'])) {
                Session::setFlash('login', $this->invalidCredentials, 'error');
                $this->redirectToRoute('login');
                die;
            }

            Session::setUser($user);
            Session::setFlash('login', $this->loginSuccess, 'success');
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
                Session::setFlash('csrf', $this->invalidCSRF, 'error');
                $this->redirectToRoute('login');
                die;
            }

            Security::sanitizeInput($_POST);

            $fields = ['nom','prenom','email','gsm','adresse','password','token'];
            $validate_required = Security::validateRequired($_POST, $fields);
            if ($validate_required !== []) {
                Session::setFlash('fields', implode(', ', $validate_required), 'error');
                $this->redirectToRoute('login');
                die;
            }

            if (!Security::validateEmail($_POST['email'])) {
                Session::setFlash('email', $this->invalidEmail, 'error');
                $this->redirectToRoute('register');
                die;
            }

            if ($this->userModel->emailExists($_POST['email'])) {
                Session::setFlash('email', $this->emailExists, 'error');
                $this->redirectToRoute('register');
                die;
            }

            $validate_password = Security::validatePassword($_POST['password']);
            if ($validate_password[0] !== true) {
                Session::setFlash('password', implode(' ', $validate_password[1]), 'error');
                $this->redirectToRoute('register');
                die;
            }

            $_POST['password'] = Security::hashPassword($_POST['password']);

            if (!$this->userModel->create($_POST)) {
                Session::setFlash('register', $this->genericError, 'error');
                $this->redirectToRoute('register');
                die;
            }

            Session::setFlash('register', $this->registerSuccess, 'success');
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
                Session::setFlash('csrf', $this->invalidCSRF, 'error');
                $this->redirectToRoute('reset-password');
                die;
            }

            Security::sanitizeInput($_POST);

            $fields = ['email','token'];
            $validate_required = Security::validateRequired($_POST, $fields);
            if ($validate_required !== []) {
                Session::setFlash('fields', implode(', ', $validate_required), 'error');
                $this->redirectToRoute('reset-password');
                die;
            }

            if (!Security::validateEmail($_POST['email'])) {
                Session::setFlash('email', $this->invalidEmail, 'error');
                $this->redirectToRoute('reset-password');
                die;
            }

            Session::setFlash('email', $this->resetEmail, 'success');

            if (!$this->userModel->emailExists($_POST['email'])) {
                $this->redirectToRoute('login');
                die;
            }

            $token = Security::generateToken();
            $token_hash = Security::hashToken($token);
            $expire = date('Y-m-d H:i:s', time() + 3600);
            if (!$this->userModel->createPasswordResetToken($_POST['email'], $token_hash, $expire)) {
                Session::setFlash('reset-password', $this->genericError, 'error');
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
                Session::setFlash('new-password', $this->tokenExpired, 'error');
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
                Session::setFlash('csrf', $this->invalidCSRF, 'error');
                $this->redirectToRoute('reset-password');
                die;
            }

            $tokenData = $this->userModel->findPasswordResetToken($_POST['reset_token']);
            if ($tokenData === null) {
                Session::setFlash('new-password', $this->invalidCSRF, 'error');
                $this->redirectToRoute('reset-password');
                die;
            }

            Security::sanitizeInput($_POST);

            $fields = ['password','token','reset_token'];
            $validate_required = Security::validateRequired($_POST, $fields);
            if ($validate_required !== []) {
                Session::setFlash('fields', implode(', ', $validate_required), 'error');
                $this->redirectToRoute('reset-password');
                die;
            }

            $validate_password = Security::validatePassword($_POST['password']);
            if ($validate_password[0] !== true) {
                Session::setFlash('password', implode(' ', $validate_password[1]), 'error');
                $this->redirectToRoute('reset-password');
                die;
            }

            $hashedPassword = Security::hashPassword($_POST['password']);

            if (!$this->userModel->updatePassword($tokenData['user_id'], $hashedPassword)) {
                Session::setFlash('new-password', $this->genericError, 'error');
                $this->redirectToRoute('reset-password');
                die;
            }

            $tokenHash = Security::hashToken($_POST['reset_token']);
            $this->userModel->markPasswordResetTokenAsUsed($tokenHash);

            Session::setFlash('new-password', $this->updated, 'success');
            $this->redirectToRoute('login');
            exit;
        }
    }
}
