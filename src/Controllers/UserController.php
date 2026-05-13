<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\FlashMessage;
use App\Core\RateLimiter;
use App\Core\Security;
use App\Core\Session;
use App\Models\UserModel;
use App\Models\OrderModel;

/**
 * Handles authenticated user account actions: profile display, profile editing,
 * and password change.
 */
class UserController extends AbstractController
{
    private const ROUTE_PROFILE         = 'profile';
    private const ROUTE_EDIT_PROFILE    = 'edit-profile';
    private const ROUTE_CHANGE_PASSWORD = 'change-password';

    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    /**
     * Displays the authenticated user's profile page.
     */
    public function profile(): void
    {
        $currentUser = Security::requireAuth();
        $orderModel = new OrderModel();
        $orders = $orderModel->findByUserId($currentUser['id']);

        $this->renderView('user/profile.php', [
            'user'     => $currentUser,
            'orders'   => $orders,
            'title'    => 'Mon espace',
            'headline' => 'Compte'
        ]);
    }

    /**
     * Handles profile editing (GET renders the form, POST processes the update).
     *
     * On POST: verifies the CSRF token, sanitises and validates required fields
     * (nom, prenom, gsm, adresse), updates the user record, refreshes the session,
     * and redirects to the profile page on success.
     */
    public function editProfile(): void
    {
        $currentUser = Security::requireAuth();

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {

            $this->renderView('user/edit-profile.php', [
                'user' => $currentUser,
                'csrfToken' => Security::generateCsrfToken(),
                'title' => 'Modifier mes coordonnées',
                'headline' => 'Compte'
            ]);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if (!Security::verifyCsrfToken($_POST['csrf_token'])) {
                Session::setFlash(FlashMessage::INVALID_CSRF, 'error');
                $this->redirectToRoute(self::ROUTE_PROFILE);
                exit;
            }

            $_POST = Security::sanitizeInput($_POST);

            $fields = ['nom','prenom','gsm','adresse','csrf_token'];
            $validate_required = Security::validateRequired($_POST, $fields);
            if ($validate_required !== []) {
                Session::setFlash(implode(', ', $validate_required), 'error');
                $this->redirectToRoute(self::ROUTE_EDIT_PROFILE);
                exit;
            }

            if (!$this->userModel->update($currentUser['id'], $_POST)) {
                Session::setFlash(FlashMessage::GENERIC_ERROR, 'error');
                $this->redirectToRoute(self::ROUTE_EDIT_PROFILE);
                exit;
            }

            $updatedUser = $this->userModel->findById($currentUser['id']);

            if ($updatedUser === null) {
                Session::setFlash(FlashMessage::GENERIC_ERROR, 'error');
                $this->redirectToRoute(self::ROUTE_PROFILE);
                exit;
            }

            Session::setUser($updatedUser);
            Session::setFlash(FlashMessage::PROFILE_UPDATED, 'success');
            $this->redirectToRoute(self::ROUTE_PROFILE);
            exit;
        }
    }

    /**
     * Handles password change (GET renders the form, POST processes the update).
     *
     * On POST: verifies the CSRF token, validates required fields (old_password,
     * new_password), checks the current password against the stored hash, enforces
     * the password policy via Security::validatePassword(), hashes the new password,
     * and redirects to the profile page on success.
     */
    public function changePassword(): void
    {
        $currentUser = Security::requireAuth();

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->renderView('user/change-password.php', [
                'user'      => $currentUser,
                'csrfToken' => Security::generateCsrfToken(),
                'title'     => 'Modifier mon mot de passe',
                'headline'  => 'Compte'
            ]);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processChangePassword($currentUser);
        }
    }

    private function processChangePassword(array $currentUser): void
    {
        if (!Security::verifyCsrfToken($_POST['csrf_token'])) {
            Session::setFlash(FlashMessage::INVALID_CSRF, 'error');
            $this->redirectToRoute(self::ROUTE_PROFILE);
            exit;
        }

        $pwdId = RateLimiter::ipIdentifier($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

        if (!RateLimiter::check('change_password', $pwdId)) {
            Session::setFlash(FlashMessage::RATE_LIMIT_CHANGE_PWD, 'error');
            $this->redirectToRoute(self::ROUTE_CHANGE_PASSWORD);
            exit;
        }

        $missing = Security::validateRequired($_POST, ['old_password', 'new_password', 'csrf_token']);
        if ($missing !== []) {
            Session::setFlash(implode(', ', $missing), 'error');
            $this->redirectToRoute(self::ROUTE_CHANGE_PASSWORD);
            exit;
        }

        $oldPassword = $_POST['old_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';

        if ($newPassword === $oldPassword) {
            Session::setFlash(FlashMessage::SAME_PASSWORD, 'error');
            $this->redirectToRoute(self::ROUTE_CHANGE_PASSWORD);
            exit;
        }

        $dbData = $this->userModel->findByEmail($currentUser['email']);
        if (!Security::verifyPassword($oldPassword, $dbData['password'])) {
            RateLimiter::hit('change_password', $pwdId);
            Session::setFlash(FlashMessage::WRONG_PASSWORD, 'error');
            $this->redirectToRoute(self::ROUTE_CHANGE_PASSWORD);
            exit;
        }

        [$valid, $errors] = Security::validatePassword($newPassword);
        if (!$valid) {
            Session::setFlash(implode(', ', $errors), 'error');
            $this->redirectToRoute(self::ROUTE_CHANGE_PASSWORD);
            exit;
        }

        if (!$this->userModel->updatePassword($currentUser['id'], Security::hashPassword($newPassword))) {
            Session::setFlash(FlashMessage::GENERIC_ERROR, 'error');
            $this->redirectToRoute(self::ROUTE_CHANGE_PASSWORD);
            exit;
        }

        Session::setFlash(FlashMessage::PASSWORD_CHANGED, 'success');
        $this->redirectToRoute(self::ROUTE_PROFILE);
        exit;
    }
}
