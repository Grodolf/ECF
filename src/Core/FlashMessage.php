<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Centralised registry of application flash messages.
 *
 * Contains only string constants. Controllers pass them directly
 * to Session::setFlash():
 *
 *   Session::setFlash(FlashMessage::LOGIN_SUCCESS, 'success');
 *
 * Dynamic messages (validation errors, computed values…)
 * are built inline in the controllers.
 */
class FlashMessage
{
    // Rate limiting
    public const RATE_LIMIT_LOGIN      = 'Trop de tentatives de connexion. Réessayez dans 15 minutes.';
    public const RATE_LIMIT_RESET      = 'Trop de tentatives. Réessayez dans 1 heure.';
    public const RATE_LIMIT_CHANGE_PWD = 'Trop de tentatives. Réessayez dans 15 minutes.';

    // Generic
    public const INVALID_CSRF  = 'Requête invalide';
    public const GENERIC_ERROR = 'Une erreur est survenue, veuillez réessayer...';
    public const INVALID_MAIL  = "L'adresse Email n'est pas valide";

    // Auth
    public const INVALID_CREDENTIALS  = 'Identifiants incorrects';
    public const EMAIL_ALREADY_EXISTS = 'Cette adresse Email est déjà utilisée.';
    public const TOKEN_EXPIRED        = 'Ce lien de réinitialisation est invalide ou a expiré';
    public const LOGIN_SUCCESS        = 'Connexion réussie.';
    public const REGISTER_SUCCESS     = 'Compte créé avec succès.';
    public const PASSWORD_RESET_SENT  = 'Si cette adresse existe, vous recevrez un email';
    public const PASSWORD_UPDATED     = 'Le mot de passe a été réinitialisé.';
    public const AUTH_REQUIRED        = 'Vous devez être connecté pour accéder à cette page';
    public const SESSION_EXPIRED      = 'Votre session a expiré, veuillez vous reconnecter';
    public const ACCESS_DENIED        = "Vous n'avez pas l'autorisation d'accéder à cette page.";
    public const ADMIN_REQUIRED       = "Réservé à l'administrateur du site.";

    // Profile
    public const PROFILE_UPDATED  = 'Vos informations ont été mises à jour.';
    public const PASSWORD_CHANGED = 'Votre mot de passe a été modifié.';
    public const SAME_PASSWORD    = 'Les deux mots de passe sont identiques.';
    public const WRONG_PASSWORD   = "Le mot de passe n'est pas correct.";

    // Menus
    public const WRONG_MENU            = "Le menu demandé n'existe pas";
    public const MENU_TOGGLE_ERROR     = "Impossible de désactiver le menu";
    public const MENU_TOGGLE_SUCCESS   = "Le menu a bien été activé";
    public const MENU_ADDSTOCK_ERROR   = "Impossible de modifier le stock";
    public const MENU_ADDSTOCK_SUCCESS = "Le stock a bien été modifié";
    public const MENU_IMAGE_ERROR      = "L'image fournie est invalide ou trop volumineuse (5 Mo max, formats : jpg, png, webp, gif).";
    public const MENU_CREATE_ERROR     = "Une erreur est survenue lors de la création du menu.";
    public const MENU_CREATED          = "Le menu a été créé avec succès.";
    public const MENU_EDIT_ERROR       = "Une erreur est survenue lors de la modifcation du menu.";
    public const MENU_EDIT_SUCCESS     = "Le menu a été modifié avec succès.";

    // Order
    public const MENU_UNAVAILABLE         = "Ce menu n'est plus disponible.";
    public const STOCK_INSUFFICIENT       = 'Stock insuffisant pour cette commande.';
    public const ORDER_NOT_FOUND          = 'Commande introuvable.';
    public const GEOCODING_ERROR          = "Impossible de calculer la distance. Vérifiez l'adresse.";
    public const GEOCODING_DISTANCE_ERROR = 'Erreur lors du calcul de la distance.';
    public const WRONG_DATE               = "La date demandée n'est pas valide";
    public const WRONG_TIME               = "L'heure demandée n'est pas valide";
    public const ORDER_SUCCESS            = 'Votre commande a été enregistrée avec succès !';
    public const ORDER_ERROR              = 'Une erreur est survenue lors de la commande.';
    public const CANCEL_ORDER             = 'Commande annulée.';
    public const CANCEL_ORDER_ERROR       = "Erreur lors de l'annulation de la commande";
    public const STATUS_UPDATED           = 'Statut de la commande modifié.';
    public const ORDER_UPDATED            = 'La modification de la commande a été effectuée.';
    public const UPDATE_ERROR             = 'Une erreur est survenue lors de la modification de la commande.';

    // Dishes
    public const DISH_TOGGLE_ERROR   = "La disponibilté du plat n'a pas pu être modifiée.";
    public const DISH_TOGGLE_SUCCESS = "La disponibilté du plat a été modifiée.";
    public const DISH_CREATED        = 'Le plat a été créé avec succès.';
    public const DISH_CREATE_ERROR   = 'Une erreur est survenue lors de la création du plat.';
    public const DISH_IMAGE_ERROR    = "L'image fournie est invalide ou trop volumineuse (5 Mo max, formats : jpg, png, webp, gif).";
    public const DISH_EDIT_ERROR     = "Impossible de modifier le plat.";
    public const DISH_UPDATED        = "Le plat a bien été modifié.";
    public const WRONG_DISH          = "Le plat demandé n'existe pas.";

    //Schedules
    public const SCHEDULES_ERROR   = 'Impossible de mettre à jour les horaires.';
    public const SCHEDULES_SUCCESS = 'La mise à jour des horaires à été effectuée.';

}
