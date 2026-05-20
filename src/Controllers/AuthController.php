<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Security;
use App\Core\Session;
use App\Core\Mailer;
use App\Core\FlashMessage;
use App\Core\RateLimiter;
use App\Models\UserModel;

/**
 * Handles all authentication flows: login, logout, registration,
 * and password reset (request + token-based update).
 */
class AuthController extends AbstractController
{
    private const ROUTE_LOGIN          = 'login';
    private const ROUTE_REGISTER       = 'register';
    private const ROUTE_RESET_PASSWORD = 'reset-password';
    private const ROUTE_HOME           = 'home';

    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    /**
     * Handles login (GET renders the form, POST authenticates the user).
     *
     * On POST: verifies CSRF, validates required fields and e-mail format,
     * checks credentials, stores the user in session, then redirects to the
     * originally requested URL or the home page.
     */
    public function login(): void
    {
        $redirect = isset($_GET['redirect']) ? Security::validateRedirect($_GET['redirect']) : null;

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->renderView('auth/login.php', [
                'csrfToken' => Security::generateCsrfToken(),
                'title'     => 'Page de connexion',
                'headline'  => 'Connexion',
                'redirect'  => $redirect
            ]);
            return;
        }

        $this->verifyCsrf($_POST['csrf_token'] ?? '', self::ROUTE_LOGIN);

        $redirect = isset($_POST['redirect']) ? Security::validateRedirect($_POST['redirect']) : null;
        $password = $_POST['password'] ?? '';
        $ip       = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $_POST    = Security::sanitizeInput($_POST);

        $this->requireFields($_POST, ['email', 'password', 'csrf_token'], self::ROUTE_LOGIN);
        $this->requireValidEmail($_POST['email'], self::ROUTE_LOGIN);

        $loginId = RateLimiter::loginIdentifier($ip, $_POST['email']);

        if (!RateLimiter::check('login', $loginId)) {
            Session::setFlash(FlashMessage::RATE_LIMIT_LOGIN, 'error');
            $this->redirectToRoute(self::ROUTE_LOGIN);
            exit;
        }

        $user = $this->userModel->findByEmail($_POST['email']);

        if ($user === null || !Security::verifyPassword($password, $user['password'])) {
            RateLimiter::hit('login', $loginId);
            Session::setFlash(FlashMessage::INVALID_CREDENTIALS, 'error');
            $this->redirectToRoute(self::ROUTE_LOGIN);
            exit;
        }

        RateLimiter::reset('login', $loginId);
        Session::setUser($user);
        Session::setFlash(FlashMessage::LOGIN_SUCCESS, 'success');
        Session::regenerate();
        Session::delete('csrf_token');

        if ($redirect !== null) {
            header("Location: {$redirect}");
        } else {
            if (in_array($user['role'], ['employe', 'admin'])) {
                $this->redirectToRoute('profile');
            } else {
                $this->redirectToRoute(self::ROUTE_HOME);
            }
        }
        exit;
    }

    /**
     * Destroys the current session and redirects to the home page.
     */
    public function logout(): void
    {
        Session::destroy();
        $this->redirectToRoute(self::ROUTE_HOME);
    }

    /**
     * Handles account registration (GET renders the form, POST creates the account).
     *
     * On POST: verifies CSRF, validates required fields, e-mail format and
     * uniqueness, enforces the password policy, creates the user record,
     * and sends a welcome e-mail before redirecting to the login page.
     */
    public function register(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->renderView('auth/register.php', [
                'csrfToken' => Security::generateCsrfToken(),
                'title'     => 'Créer un compte',
                'headline'  => 'Enregistrement'
            ]);
            return;
        }

        $this->verifyCsrf($_POST['csrf_token'] ?? '', self::ROUTE_REGISTER);

        $password = $_POST['password'] ?? '';
        $_POST    = Security::sanitizeInput($_POST);

        $this->requireFields($_POST, ['nom', 'prenom', 'email', 'gsm', 'adresse', 'password', 'csrf_token'], self::ROUTE_LOGIN);
        $this->requireValidEmail($_POST['email'], self::ROUTE_REGISTER);

        if ($this->userModel->emailExists($_POST['email'])) {
            Session::setFlash(FlashMessage::EMAIL_ALREADY_EXISTS, 'error');
            $this->redirectToRoute(self::ROUTE_REGISTER);
            exit;
        }

        $this->requireValidPassword($password, self::ROUTE_REGISTER);

        $_POST['password'] = Security::hashPassword($password);

        if (!$this->userModel->create($_POST)) {
            Session::setFlash(FlashMessage::GENERIC_ERROR, 'error');
            $this->redirectToRoute(self::ROUTE_REGISTER);
            exit;
        }

        $this->sendWelcomeEmail($_POST);

        Session::setFlash(FlashMessage::REGISTER_SUCCESS, 'success');
        $this->redirectToRoute(self::ROUTE_LOGIN);
        exit;
    }

    /**
     * Handles the password-reset request (GET renders the form, POST sends the reset link).
     *
     * On POST: verifies CSRF, validates the e-mail field, then unconditionally
     * flashes a success message before checking whether the address exists — this
     * prevents user enumeration. When the address is known, a hashed token valid
     * for one hour is stored and the reset link is e-mailed to the user.
     */
    public function resetPassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->renderView('auth/reset-password.php', [
                'csrfToken' => Security::generateCsrfToken(),
                'title'     => 'Mot de passe oublié',
                'headline'  => 'Mot de passe'
            ]);
            return;
        }

        $this->verifyCsrf($_POST['csrf_token'] ?? '', self::ROUTE_RESET_PASSWORD);

        $_POST = Security::sanitizeInput($_POST);

        $this->requireFields($_POST, ['email', 'csrf_token'], self::ROUTE_RESET_PASSWORD);
        $this->requireValidEmail($_POST['email'], self::ROUTE_RESET_PASSWORD);

        $resetId = RateLimiter::ipIdentifier($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

        if (!RateLimiter::check('reset_password', $resetId)) {
            Session::setFlash(FlashMessage::RATE_LIMIT_RESET, 'error');
            $this->redirectToRoute(self::ROUTE_RESET_PASSWORD);
            exit;
        }

        // Always incremented before the email-exists check to prevent user enumeration via the rate limiter.
        RateLimiter::hit('reset_password', $resetId);

        // Flash set before the email-exists check intentionally: we never reveal
        // whether an address is registered (user enumeration prevention).
        Session::setFlash(FlashMessage::PASSWORD_RESET_SENT, 'success');

        if (!$this->userModel->emailExists($_POST['email'])) {
            $this->redirectToRoute(self::ROUTE_LOGIN);
            exit;
        }

        $token     = Security::generateToken();
        $tokenHash = Security::hashToken($token);
        $expire    = date('Y-m-d H:i:s', time() + 3600);

        if (!$this->userModel->createPasswordResetToken($_POST['email'], $tokenHash, $expire)) {
            Session::setFlash(FlashMessage::GENERIC_ERROR, 'error');
        }

        $user = $this->userModel->findByEmailPublic($_POST['email']);
        if ($user === null) {
            Session::setFlash(FlashMessage::INVALID_CREDENTIALS, 'error');
            $this->redirectToRoute(self::ROUTE_LOGIN);
            exit;
        }

        $this->sendResetPasswordEmail($user, $_POST['email'], $token);
        $this->redirectToRoute(self::ROUTE_LOGIN);
        exit;
    }

    /**
     * Handles the new-password form reached via a reset link (GET renders the form, POST updates the password).
     *
     * Both GET and POST validate the reset token; an expired or already-used token
     * redirects to the reset-password page. On POST: verifies CSRF, enforces the
     * password policy, hashes and persists the new password, marks the token as
     * used, then redirects to the login page.
     *
     * @param string $token Plain-text reset token from the e-mail link (looked up against its stored hash).
     */
    public function newPassword(string $token): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $tokenData = $this->userModel->findPasswordResetToken($token);
            if ($tokenData === null) {
                Session::setFlash(FlashMessage::TOKEN_EXPIRED, 'error');
                $this->redirectToRoute(self::ROUTE_RESET_PASSWORD);
                exit;
            }
            $this->renderView('auth/new-password.php', [
                'token'     => $token,
                'email'     => $tokenData['email'],
                'csrfToken' => Security::generateCsrfToken(),
                'title'     => 'Réinitialiser mon mot de passe',
                'headline'  => 'Mot de passe'
            ]);
            return;
        }

        $this->verifyCsrf($_POST['csrf_token'] ?? '', self::ROUTE_RESET_PASSWORD);

        $tokenData = $this->userModel->findPasswordResetToken($_POST['reset_token'] ?? '');
        if ($tokenData === null) {
            Session::setFlash(FlashMessage::TOKEN_EXPIRED, 'error');
            $this->redirectToRoute(self::ROUTE_RESET_PASSWORD);
            exit;
        }

        $password = $_POST['password'] ?? '';
        $_POST    = Security::sanitizeInput($_POST);

        $this->requireFields($_POST, ['password', 'csrf_token', 'reset_token'], self::ROUTE_RESET_PASSWORD);
        $this->requireValidPassword($password, self::ROUTE_RESET_PASSWORD);

        if (!$this->userModel->updatePassword((int) $tokenData['user_id'], Security::hashPassword($password))) {
            Session::setFlash(FlashMessage::GENERIC_ERROR, 'error');
            $this->redirectToRoute(self::ROUTE_RESET_PASSWORD);
            exit;
        }

        $this->userModel->markPasswordResetTokenAsUsed(Security::hashToken($_POST['reset_token']));

        Session::setFlash(FlashMessage::PASSWORD_UPDATED, 'success');
        $this->redirectToRoute(self::ROUTE_LOGIN);
        exit;
    }

    /**
     * Guard: aborts with a CSRF error flash and a redirect if the token is invalid.
     *
     * @param string $token         Token value from the request.
     * @param string $redirectRoute Route to redirect to on failure.
     */
    private function verifyCsrf(string $token, string $redirectRoute): void
    {
        if (!Security::verifyCsrfToken($token)) {
            Session::setFlash(FlashMessage::INVALID_CSRF, 'error');
            $this->redirectToRoute($redirectRoute);
            exit;
        }
    }

    /**
     * Guard: aborts with a missing-fields flash and a redirect if any required field is absent or empty.
     *
     * @param array  $post          Sanitised POST data.
     * @param array  $fields        Field names that must be present and non-empty.
     * @param string $redirectRoute Route to redirect to on failure.
     */
    private function requireFields(array $post, array $fields, string $redirectRoute): void
    {
        $missing = Security::validateRequired($post, $fields);
        if ($missing !== []) {
            Session::setFlash(implode(', ', $missing), 'error');
            $this->redirectToRoute($redirectRoute);
            exit;
        }
    }

    /**
     * Guard: aborts with an invalid e-mail flash and a redirect if the address fails format validation.
     *
     * @param string $email         E-mail address to validate.
     * @param string $redirectRoute Route to redirect to on failure.
     */
    private function requireValidEmail(string $email, string $redirectRoute): void
    {
        if (!Security::validateEmail($email)) {
            Session::setFlash(FlashMessage::INVALID_MAIL, 'error');
            $this->redirectToRoute($redirectRoute);
            exit;
        }
    }

    /**
     * Guard: aborts with a policy-violation flash and a redirect if the password does not meet requirements.
     *
     * @param string $password      Plain-text password to validate (before hashing).
     * @param string $redirectRoute Route to redirect to on failure.
     */
    private function requireValidPassword(string $password, string $redirectRoute): void
    {
        $result = Security::validatePassword($password);
        if ($result[0] !== true) {
            Session::setFlash(implode(', ', $result[1]), 'error');
            $this->redirectToRoute($redirectRoute);
            exit;
        }
    }

    /**
     * Sends a welcome e-mail to a newly registered user.
     *
     * Failures are silently caught and logged so they never block registration.
     *
     * @param array $post Sanitised POST data containing email, nom, and prenom.
     */
    private function sendWelcomeEmail(array $post): void
    {
        try {
            (new Mailer())->sendWithTemplate(
                $post['email'],
                $post['prenom'] . ' ' . $post['nom'],
                'Bienvenue',
                'welcome',
                [
                    'nom' => $post['nom'],
                    'prenom' => $post['prenom'],
                    'link' => $_ENV['APP_URL'] . '/menus'
                ],
            );
        } catch (\Exception $e) {
            error_log('Welcome email error: ' . $e->getMessage());
        }
    }

    /**
     * Sends the password-reset e-mail containing the one-time reset link.
     *
     * Failures are silently caught and logged so they never block the reset flow.
     *
     * @param array  $user  User data (nom, prenom) for personalisation.
     * @param string $email Destination e-mail address.
     * @param string $token Plain-text reset token to embed in the link.
     */
    private function sendResetPasswordEmail(array $user, string $email, string $token): void
    {
        try {
            (new Mailer())->sendWithTemplate(
                $email,
                $user['prenom'] . ' ' . $user['nom'],
                'Mot de passe oublié',
                self::ROUTE_RESET_PASSWORD,
                [
                    'nom'       => $user['nom'],
                    'prenom'    => $user['prenom'],
                    'resetLink' => $_ENV['APP_URL'] . '/new-password/' . $token
                ]
            );
        } catch (\Exception $e) {
            error_log('Reset password email error: ' . $e->getMessage());
        }
    }
}
