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

    public function __construct()
    {
        $this->userModel = new User();
    }

    // --- Connexion ---

    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {

            $this->renderView('auth/login');
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $login_ko = 'Identifiants incorrects.';

            if (!Security::verifyCsrfToken($_POST['token'])) {
                Session::setFlash('csrf', 'Requête invalide', 'error');
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
                Session::setFlash('email', $login_ko, 'error');
                $this->redirectToRoute('login');
                die;
            }

            $user = $this->userModel->findByEmail($_POST['email']);
            if ($user === null) {
                Session::setFlash('login', $login_ko, 'error');
                $this->redirectToRoute('login');
                die;
            }
            if (!Security::verifyPassword($_POST['password'], $user['password'])) {
                Session::setFlash('login', $login_ko, 'error');
                $this->redirectToRoute('login');
                die;
            }

            Session::set('user_id', $user['id']);
            Session::set('user_role', $user['role']);
            Session::setFlash('login', 'Connexion réussie.', 'success');
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

            $this->renderView('auth/register');
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if (!Security::verifyCsrfToken($_POST['token'])) {
                Session::setFlash('csrf', 'Requête invalide', 'error');
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
                Session::setFlash('email', 'Le format de l\'adresse Email n\'est pas valide', 'error');
                $this->redirectToRoute('register');
                die;
            }

            if ($this->userModel->emailExists($_POST['email'])) {
                Session::setFlash('email', 'Cette adresse Email est déjà utilisée.', 'error');
                $this->redirectToRoute('register');
                die;
            }

            $validate_password = Security::validatePassword($_POST['password']);
            if ($validate_password[0] !== true) {
                Session::setFlash('password', implode(', ', $validate_password[1]), 'error');
                $this->redirectToRoute('register');
                die;
            }

            $_POST['password'] = Security::hashPassword($_POST['password']);

            if (!$this->userModel->create($_POST)) {
                Session::setFlash('register', 'Erreur lors de votre inscription, veuillez réessayer plus tard', 'error');
                $this->redirectToRoute('register');
                die;
            }

            Session::setFlash('register', 'Votre inscription a bien été enregistré', 'success');
            $this->redirectToRoute('login');
            exit;
        }
    }

    // --- Réinitialisation mot de passe ---

    public function resetPassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {

            $this->renderView('auth/reset-password');
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if (!Security::verifyCsrfToken($_POST['token'])) {
                Session::setFlash('csrf', 'Requête invalide', 'error');
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
                Session::setFlash('email', 'Le format de l\'adresse Email n\'est pas valide', 'error');
                $this->redirectToRoute('reset-password');
                die;
            }

            Session::setFlash('email', 'Si cette adresse existe, vous recevrez un email');

            if (!$this->userModel->emailExists($_POST['email'])) {
                $this->redirectToRoute('login');
                die;
            }

            $token = Security::generateToken();
            $token_hash = Security::hashToken($token);
            $expire = date('Y-m-d H:i:s', time() + 3600);
            if (!$this->userModel->createPasswordResetToken($_POST['email'], $token_hash, $expire)) {
                Session::setFlash('reset-password', 'Une erreur est survenue, veuillez réessayez.', 'error');
            }

            /**
             * TODO
             * Mail avec lien /new-password/{$token}
             */

            $this->redirectToRoute('login');
            exit;
        }
    }

    public function newPassword(string $token): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {

            $tokenData = $this->userModel->findPasswordResetToken($token);

            if ($tokenData === null) {
                Session::setFlash('new-password', 'Ce lien de réinitialisation est invalide ou a expiré.', 'error');
                $this->redirectToRoute('reset-password');
                die;
            }

            $this->renderView('auth/new-password', [
                'token' => $token,
                'email' => $tokenData['email']
            ]);
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if (!Security::verifyCsrfToken($_POST['token'])) {
                Session::setFlash('csrf', 'Requête invalide', 'error');
                $this->redirectToRoute('new-password');
                die;
            }

            $tokenData = $this->userModel->findPasswordResetToken($_POST['reset_token']);
            if ($tokenData === null) {
                Session::setFlash('new-password', 'Requête invalide', 'error');
                $this->redirectToRoute('reset-password');
                die;
            }

            Security::sanitizeInput($_POST);

            $fields = ['password','token','reset_token'];
            $validate_required = Security::validateRequired($_POST, $fields);
            if ($validate_required !== []) {
                Session::setFlash('fields', implode(', ', $validate_required), 'error');
                $this->redirectToRoute('new-password');
                die;
            }

            $validate_password = Security::validatePassword($_POST['password']);
            if ($validate_password[0] !== true) {
                Session::setFlash('password', implode(', ', $validate_password[1]), 'error');
                $this->redirectToRoute('new-password');
                die;
            }

            $hashedPassword = Security::hashPassword($_POST['password']);

            if (!$this->userModel->updatePassword($tokenData['user_id'], $hashedPassword)) {
                Session::setFlash('new-password', 'Une erreur est survenue, veuillez réessayez.', 'error');
                $this->redirectToRoute('new-password');
                die;
            }

            $tokenHash = Security::hashToken($_POST['reset_token']);
            $this->userModel->markPasswordResetTokenAsUsed($tokenHash);

            Session::setFlash('new-password', 'Votre mot de passe a été réinitialisé.', 'success');
            $this->redirectToRoute('login');
            exit;
        }
    }
}
