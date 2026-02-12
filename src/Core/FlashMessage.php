<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Session;

class FlashMessage
{
    // Generic
    public static function invalidCsrf(): void
    {
        Session::setFlash('generic', 'Requête invalide', 'error');
    }
    public static function genericError(): void
    {
        Session::setFlash('generic', 'Une erreur est survenue, veuillez réessayer...', 'error');
    }
    public static function invalidMail(): void
    {
        Session::setFlash('generic', 'L\'adresse Email n\'est pas valide', 'error');
    }
    public static function invalidPassword(array $errors): void
    {
        Session::setFlash('generic', implode(', ', $errors), 'error');
    }
    public static function fieldsRequired(array $fields): void
    {
        Session::setFlash('generic', implode(', ', $fields), 'error');
    }

    // Auth
    public static function invalidCredentials(): void
    {
        Session::setFlash('auth', 'Identifiants incorrects', 'error');
    }
    public static function emailAlreadyExists(): void
    {
        Session::setFlash('auth', 'Cette adresse Email est déjà utilisée.', 'error');
    }
    public static function tokenExpired(): void
    {
        Session::setFlash('auth', 'Ce lien de réinitialisation est invalide ou a expiré', 'error');
    }
    public static function loginSuccess(): void
    {
        Session::setFlash('auth', 'Connexion réussie.', 'success');
    }
    public static function registerSuccess(): void
    {
        Session::setFlash('auth', 'Compte créé avec succès.', 'success');
    }
    public static function passwordResetSent(): void
    {
        Session::setFlash('auth', 'Si cette adresse existe, vous recevrez un email', 'success');
    }
    public static function passwordUpdated(): void
    {
        Session::setFlash('auth', 'Le mot de passe a été réinitialisé.', 'success');
    }
    public static function authRequired(): void
    {
        Session::setFlash('auth', 'Vous devez être connecté pour accéder à cette page', 'error');
    }
    public static function sessionExpired(): void
    {
        Session::setFlash('auth', 'Votre session a expiré, veuillez vous reconnecter', 'error');
    }
    public static function accessDenied(): void
    {
        Session::setFlash('auth', 'Vous n\'avez pas l\'autorisation d\'accéder à cette page.', 'error');
    }
    public static function adminRequired(): void
    {
        Session::setFlash('auth', 'Réservé à l\'administrateur du site.', 'error');
    }

    // Profile
    public static function profileUpdated(): void
    {
        Session::setFlash('profile', 'Vos informations ont été mises à jour.', 'success');
    }
    public static function passwordChanged(): void
    {
        Session::setFlash('profile', 'Votre mot de passe a été modifié.', 'success');
    }
    public static function samePassword(): void
    {
        Session::setFlash('profile', 'Les deux mots de passe sont identiques.', 'error');
    }
    public static function wrongPassword(): void
    {
        Session::setFlash('profile', 'Le mot de passe n\'est pas correct.', 'error');
    }
}
