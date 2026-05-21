# ECF — Vite & Gourmand · Contexte projet

---

## Sommaire

1. [Architecture générale](#1-architecture-générale)
2. [Système de routes](#2-système-de-routes)
3. [Classes Core](#3-classes-core)
4. [Contrôleurs](#4-contrôleurs)
5. [Modèles](#5-modèles)
6. [Vues et templates](#6-vues-et-templates)
7. [Système CSS](#7-système-css)
8. [Modules JavaScript](#8-modules-javascript)
9. [Conventions et sécurité](#9-conventions-et-sécurité)
10. [MongoDB — statistiques de ventes](#10-mongodb--statistiques-de-ventes)

---

## 1. Architecture générale

```txt
ECF/
├── config/                 .env / .env.local
├── public/
│   ├── index.php           Point d'entrée unique
│   ├── css/                Feuilles de style (voir §7)
│   ├── js/
│   │   ├── scripts.js      Script global (thème, nav, footer, eye)
│   │   └── modules/        Carousel.js, MenuFilter.js, OrderForm.js,
│   │                       MenuToggle.js, MenuRestock.js, DishToggle.js,
│   │                       DisplayOrder.js, ScheduleClosed.js, ReviewReject.js,
│   │                       EmployeToggle.js, StatsCharts.js
│   ├── img/
│   └── uploads/
│       ├── menus/          Images de menus uploadées
│       └── dishes/         Images de plats uploadées
├── src/
│   ├── Controllers/        AbstractController, AuthController, MainController,
│   │                       MenuController, MenuManageController, OrderController,
│   │                       UserController, EmployeController, DishController,
│   │                       ReviewController, AdminController
│   ├── Models/             UserModel, MenuModel, OrderModel, DishModel, ScheduleModel,
│   │                       ReviewModel, ContactModel, StatsModel
│   ├── Core/               Router, Routes, Session, Security, DatabaseConnection,
│   │                       MongoDBConnection, FlashMessage, Mailer, Env,
│   │                       GeocodingService, RateLimiter
│   └── Config/             Database.php, Smtp.php, MongoDB.php
├── templates/              layout.php, partials/header.php, partials/footer.php
│                           emails/layout.php, emails/order-confirmation.php,
│                           emails/order-status-update.php, emails/valid-review.php
└── views/                  home, contact, auth/, user/, menus/, orders/,
                            employe/, review/, ml, cgv
```

**PSR-4 (composer.json) :**

| Namespace | Dossier |
| --- | --- |
| `App\Controllers\` | `src/Controllers/` |
| `App\Models\` | `src/Models/` |
| `App\Core\` | `src/Core/` |
| `App\Config\` | `src/Config/` |

**Flux d'une requête :**
`index.php` → `Env::loadEnv()` → `Session::start()` → `new Router()` → Controller → `renderView()` → `layout.php`

---

## 2. Système de routes

### `src/Core/Routes.php`

Constante `ROUTES` : tableau associatif `chemin => [controller, method, http]`.
Les routes statiques (ex. `order/store`) sont déclarées **avant** les routes dynamiques (ex. `order/{menuId}`) pour éviter les collisions.

| Chemin | Contrôleur | Méthode | HTTP |
| --- | --- | --- | --- |
| `home` | MainController | home | GET |
| `contact` | MainController | contact | GET |
| `sendmail` | MainController | sendmail | POST |
| `mentions-legales` | MainController | legalNotice | GET |
| `cgv` | MainController | cgv | GET |
| `login` | AuthController | login | GET, POST |
| `logout` | AuthController | logout | GET |
| `register` | AuthController | register | GET, POST |
| `reset-password` | AuthController | resetPassword | GET, POST |
| `new-password/{token}` | AuthController | newPassword | GET, POST |
| `profile` | UserController | profile | GET |
| `edit-profile` | UserController | editProfile | GET, POST |
| `change-password` | UserController | changePassword | GET, POST |
| `menus` | MenuController | list | GET |
| `menu/{id}` | MenuController | detail | GET |
| `menus/filter` | MenuController | filter | POST |
| `order/store` | OrderController | store | POST |
| `order/calculate-price` | OrderController | calculatePrice | POST |
| `order/confirmation/{orderId}` | OrderController | confirmation | GET |
| `order/detail/{orderId}` | OrderController | show | GET |
| `order/edit/{orderId}` | OrderController | edit | GET, POST |
| `order/cancel/{orderId}` | OrderController | cancel | POST |
| `orders` | OrderController | list | GET |
| `order` | OrderController | create | GET |
| `order/{menuId}` | OrderController | create | GET |
| `employe/orders` | EmployeController | orders | GET |
| `employe/order/update-status/{orderId}` | EmployeController | updateStatus | POST |
| `employe/order/cancel/{orderId}` | EmployeController | cancelOrder | POST |
| `employe/dishes` | DishController | list | GET |
| `employe/dish/create` | DishController | create | GET |
| `employe/dish/store` | DishController | store | POST |
| `employe/dish/edit/{id}` | DishController | edit | GET |
| `employe/dish/update/{id}` | DishController | update | POST |
| `employe/dish/toggle/{id}` | DishController | toggle | POST |
| `employe/menus` | MenuManageController | list | GET |
| `employe/menu/create` | MenuManageController | create | GET |
| `employe/menu/store` | MenuManageController | store | POST |
| `employe/menu/edit/{id}` | MenuManageController | edit | GET |
| `employe/menu/update/{id}` | MenuManageController | update | POST |
| `employe/menu/toggle/{id}` | MenuManageController | toggle | POST |
| `employe/menu/addstock/{id}` | MenuManageController | addStock | POST |
| `employe/schedules` | EmployeController | schedules | GET |
| `employe/schedule/update` | EmployeController | updateSchedules | POST |
| `employe/reviews` | ReviewController | list | GET |
| `review/{id}` | ReviewController | create | GET |
| `review/store/{id}` | ReviewController | store | POST |
| `review/validate/{id}` | ReviewController | valid | POST |
| `admin/employes` | AdminController | list | GET |
| `admin/employe/create` | AdminController | create | GET |
| `admin/employe/store` | AdminController | store | POST |
| `admin/employe/confirmation` | AdminController | confirmation | GET |
| `admin/employe/toggle/{id}` | AdminController | toggle | POST |
| `admin/stats` | AdminController | stats | GET |

### `src/Core/Router.php`

| Méthode | Description |
| --- | --- |
| `__construct()` | Charge les routes, traite la requête courante |
| `parseRoutes(): void` | Fait correspondre le chemin, vérifie la méthode HTTP (405 si invalide), invoque le contrôleur |
| `matchPath(string, array): ?array` | Compare segments, extrait les `{param}` |
| `castParams(array): array` | Convertit les params numériques en `int` |
| `explodePath(string): array` | Découpe un chemin par `/` |
| `isParam(string): bool` | Détecte la syntaxe `{param}` |

Chemin inconnu → `http_response_code(404)`.
Méthode non autorisée → `http_response_code(405)` + header `Allow:`.

---

## 3. Classes Core

### `src/Controllers/AbstractController.php`

Base de tous les contrôleurs (namespace `App\Controllers`).

| Méthode | Description |
| --- | --- |
| `renderView(string $view, array $data): void` | Capture la vue avec `ob_*`, extrait `$data`, inclut `layout.php`. **La clé `$scripts` (tableau de chemins) active les `<script type="module">` page-spécifiques dans le layout.** |
| `redirectToRoute(string $path, array $params): void` | Redirige via `header("Location: …")` |

### `src/Core/Session.php` (statique)

Session sécurisée, cookie HTTPOnly/SameSite, expiration 30 min d'inactivité.

| Méthode | Description |
| --- | --- |
| `start()` | Init session, vérifie expiration, met à jour `last_activity` |
| `set/get/has/delete($key)` | Accès `$_SESSION` |
| `destroy()` | Détruit la session et le cookie |
| `regenerate()` | `session_regenerate_id(true)` |
| `setFlash(string $msg, string $type)` | Stocke un flash dans `$_SESSION['flash']` (clé unique) |
| `getFlash()` | Lit **et supprime** le flash |
| `isAuthenticated(): bool` | `$_SESSION['user']` non vide |
| `setUser(array $user)` | Stocke l'utilisateur (sans le mot de passe) |
| `getUser(): ?array` | Retourne l'utilisateur authentifié |

### `src/Core/Security.php` (statique)

| Méthode | Description |
| --- | --- |
| `hashPassword(string): string` | Bcrypt |
| `verifyPassword(string, string): bool` | Vérifie contre le hash bcrypt |
| `generateCsrfToken(): string` | Token random stocké en session |
| `verifyCsrfToken(string): bool` | Comparaison à temps constant |
| `sanitizeInput(?string\|array): string\|array` | `trim` + `stripslashes` récursif — **pas** d'encodage HTML (voir §9) |
| `validateEmail(string): bool` | `FILTER_VALIDATE_EMAIL` |
| `validatePassword(string): array` | Retourne `[bool, array $errors]` : 10+ chars, maj, min, chiffre, spécial |
| `validateRequired(array, array): array` | Champs manquants/vides |
| `generateToken(int): string` | `random_bytes` → hex |
| `hashToken(string): string` | SHA256 pour stockage en BDD |
| `escapeHtml(?string): string` | `htmlspecialchars(ENT_QUOTES)` — accepte `null` (retourne `''`). |
| `requireAuth(): array` | Redirige vers `/login` si non connecté |
| `requireEmploye(): array` | Redirige si rôle `user` |
| `requireAdmin(): array` | Redirige si rôle `user` ou `employe` |

### `src/Core/FlashMessage.php`

Registre de constantes `string` pour tous les messages de l'application. Aucune méthode — les contrôleurs appellent directement `Session::setFlash(FlashMessage::CONSTANTE, 'error'|'success')`.

| Groupe | Constantes |
| --- | --- |
| Rate limiting | `RATE_LIMIT_LOGIN`, `RATE_LIMIT_RESET`, `RATE_LIMIT_CHANGE_PWD` |
| Generic | `INVALID_CSRF`, `GENERIC_ERROR`, `INVALID_MAIL`, `EMAIL_SUCCESS` |
| Auth | `INVALID_CREDENTIALS`, `EMAIL_ALREADY_EXISTS`, `TOKEN_EXPIRED`, `LOGIN_SUCCESS`, `REGISTER_SUCCESS`, `PASSWORD_RESET_SENT`, `PASSWORD_UPDATED`, `AUTH_REQUIRED`, `SESSION_EXPIRED`, `ACCESS_DENIED`, `ADMIN_REQUIRED` |
| Profile | `PROFILE_UPDATED`, `PASSWORD_CHANGED`, `SAME_PASSWORD`, `WRONG_PASSWORD` |
| Menus | `WRONG_MENU`, `MENU_TOGGLE_ERROR`, `MENU_TOGGLE_SUCCESS`, `MENU_ADDSTOCK_ERROR`, `MENU_ADDSTOCK_SUCCESS`, `MENU_IMAGE_ERROR`, `MENU_CREATE_ERROR`, `MENU_CREATED`, `MENU_EDIT_ERROR`, `MENU_EDIT_SUCCESS` |
| Order | `MENU_UNAVAILABLE`, `STOCK_INSUFFICIENT`, `ORDER_NOT_FOUND`, `GEOCODING_ERROR`, `GEOCODING_DISTANCE_ERROR`, `WRONG_DATE`, `WRONG_TIME`, `ORDER_SUCCESS`, `ORDER_ERROR`, `CANCEL_ORDER`, `CANCEL_ORDER_ERROR`, `STATUS_UPDATED`, `ORDER_UPDATED`, `UPDATE_ERROR` |
| Dishes | `DISH_TOGGLE_ERROR`, `DISH_TOGGLE_SUCCESS`, `DISH_CREATED`, `DISH_CREATE_ERROR`, `DISH_IMAGE_ERROR`, `DISH_EDIT_ERROR`, `DISH_UPDATED`, `WRONG_DISH` |
| Reviews | `REVIEW_SUCCESS`, `VALID_SUCCESS`, `WRONG_REVIEW`, `REVIEW_EXIST`, `RATING_ERROR`, `STATUS_ERROR`, `COMMENT_ERROR` |

### `src/Core/GeocodingService.php`

Calcul de distance routière via l'API OpenRouteService. Clé API dans `ORS_API_KEY` (`.env`).
**Cache session** : les résultats sont mis en cache dans `$_SESSION['geocode_cache']` (clé = adresse complète) pour éviter des appels API répétés dans la même session.

| Méthode | Description |
| --- | --- |
| `geocode(string $address): ?array` | Géocode une adresse → `{latitude, longitude, label}` |
| `calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): ?float` | Distance routière en km via `/v2/directions/driving-car` |
| `getDistanceFromBordeaux(string $address, string $city): ?float` | Géocode `"$address, $city, France"` puis calcule la distance depuis Bordeaux (44.837789, -0.57918). Résultat mis en cache session. |

### `src/Core/DatabaseConnection.php` (Singleton)

| Méthode | Description |
| --- | --- |
| `getInstance(): PDO` | Retourne (ou crée) l'instance PDO unique. SSL configuré si Aiven (prod). |

### `src/Core/Mailer.php`

Config SMTP depuis variables d'environnement (`.env`).

| Méthode | Description |
| --- | --- |
| `configureMail(): PHPMailer` | Instancie PHPMailer avec SMTP, HTML, UTF-8 |
| `loadTemplate(string $name, array $data): string` | Charge `templates/emails/{name}.php`, remplace `{{var}}` |
| `send(string $to, …): bool` | Envoi direct |
| `sendWithTemplate(string $to, string $subject, string $tpl, array $data): bool` | Charge template + génère version texte |

### `src/Core/Env.php` (statique)

| Méthode | Description |
| --- | --- |
| `loadEnv($path)` | Lit `.env.local` ou `.env`, parse `KEY=VALUE`, injecte dans `$_ENV` et `putenv` |

---

## 4. Contrôleurs

### `src/Controllers/AuthController.php`

Dépendance : `UserModel`.

| Méthode | Description |
| --- | --- |
| `login()` | GET: form + CSRF · POST: vérifie email/hash, régénère session |
| `logout()` | Détruit la session, redirige vers home |
| `register()` | GET: form · POST: valide tout, vérifie unicité email, hash mdp, insère, envoie email |
| `resetPassword()` | GET: form email · POST: génère token (1h), envoie lien par email |
| `newPassword(string $token)` | GET: valide token + expiration · POST: nouveau mdp, marque token utilisé |

### `src/Controllers/UserController.php`

Dépendance : `UserModel`. Toutes les méthodes appellent `Security::requireAuth()`.

| Méthode | Description |
| --- | --- |
| `profile()` | Affiche les données de l'utilisateur connecté + ses commandes |
| `editProfile()` | GET: form pré-rempli · POST: met à jour, rafraîchit la session |
| `changePassword()` | GET: form · POST: vérifie ancien mdp, valide nouveau, met à jour |

### `src/Controllers/MenuController.php`

Dépendance : `MenuModel`. Constante `PLACEHOLDER = '/img/placeholder.webp'`.

| Méthode | Description |
| --- | --- |
| `list()` | Récupère tous les menus actifs + images, passe `scripts: ['/js/modules/MenuFilter.js']` |
| `detail(int $id)` | Menu + plats groupés par type + allergènes, passe `scripts: ['/js/modules/Carousel.js']` |
| `filter()` | Endpoint AJAX POST. Valide le token CSRF via `HTTP_X_CSRF_TOKEN`. Retourne JSON `{menus, stats, available_options}` |

### `src/Controllers/MainController.php`

Dépendances : `ReviewModel`, `ContactModel`.

| Méthode | Description |
| --- | --- |
| `home()` | Vue home.php — charge les avis approuvés via `ReviewModel::findValidated()` |
| `contact()` | Vue contact.php — génère un token CSRF |
| `sendmail()` | POST : CSRF → champs requis (`nom`, `email`, `title`, `message`) → `ContactModel::create()` → email admin → flash `EMAIL_SUCCESS` |
| `legalNotice()` | Vue ml.php (mentions légales) |
| `cgv()` | Vue cgv.php (conditions générales de vente) |

### `src/Controllers/OrderController.php`

Dépendances : `MenuModel`, `OrderModel`, `GeocodingService`, `Mailer`.

| Méthode | Description |
| --- | --- |
| `create(int $menuId)` | GET : vérifie stock, affiche formulaire. Passe `scripts: ['/js/modules/OrderForm.js']` |
| `store()` | POST : CSRF → validation → stock → prix → transaction → email → redirect confirmation |
| `calculatePrice()` | Endpoint AJAX POST. Vérifie session + CSRF (`HTTP_X_CSRF_TOKEN`). Retourne JSON `{menu_price, reduction, menu_price_after_reduction, delivery_cost, distance, total_price}` |
| `confirmation(int $orderId)` | Vérifie appartenance à l'utilisateur, affiche récapitulatif post-commande |
| `show(int $orderId)` | Détail complet d'une commande avec historique de statuts. Vérifie appartenance. |
| `list()` | Liste des commandes de l'utilisateur connecté |
| `edit(int $orderId)` | GET: form de modification · POST: CSRF → ownership (owner ou employe/admin) → validation → recalcul prix/livraison → `OrderModel::update()` |
| `cancel(int $orderId)` | POST : CSRF → existence → appartenance → `OrderModel::cancel()` (status = 1 requis) |

**Méthodes privées :**

| Méthode | Description |
| --- | --- |
| `parseOrderPost(): array` | Extrait et normalise les champs POST |
| `validateOrderFields(array): ?array` | Valide champs requis, format date (Y-m-d, ≥ demain), format heure (H:i) |
| `computeMenuPrice(array, int): array` | Calcule menu_price, reduction, menu_price_after_reduction |
| `computeDeliveryCost(string, string): array` | Retourne `[cost, distance, error]` — Bordeaux = gratuit |
| `isValidDeliveryDate(string): bool` | Format Y-m-d + date ≥ demain |
| `isValidDeliveryTime(string): bool` | Format H:i |
| `sendOrderConfirmationEmail(int, array, array, array): void` | Envoie email via Mailer, échec silencieux (error_log) |

**Calcul du prix** : `menu_price = base_price × nb_people` · réduction 10% si `nb_people ≥ min_people + 5` · frais de livraison : `5€ + 0,59€/km` (distance routière depuis Bordeaux via ORS).

### `src/Controllers/EmployeController.php`

Dépendances : `OrderModel`, `ScheduleModel`. Toutes les méthodes appellent `Security::requireEmploye()`.

| Méthode | Description |
| --- | --- |
| `orders()` | Liste de toutes les commandes avec filtres GET optionnels (`status_id`, `search`). Passe `statuses` pour le dropdown. |
| `updateStatus(int $orderId)` | POST : CSRF → validation `status_id` → `OrderModel::updateStatus()` → email notification → redirect detail |
| `cancelOrder(int $orderId)` | POST : CSRF → validation `cancellation_reason` + `contact_method` (whitelist `email\|telephone`) → `OrderModel::cancelByEmploye()` → email → redirect detail |
| `schedules()` | GET : charge tous les horaires via `ScheduleModel::findAll()`. Passe `scripts: [ScheduleClosed.js]` |
| `updateSchedules()` | POST : CSRF → validation (7 entrées attendues) → `ScheduleModel::updateAll()` |

### `src/Controllers/DishController.php`

Dépendance : `DishModel`. Toutes les méthodes appellent `Security::requireEmploye()`.
Constantes : `ROUTE_LIST = 'employe/dishes'`, `ROUTE_CREATE = 'employe/dish/create'`.
Upload d'image vers `public/uploads/dishes/` (5 Mo max, jpg/png/webp/gif, vérification `getimagesize()`).

| Méthode | Description |
| --- | --- |
| `list()` | Liste tous les plats (incluant inactifs) + lookup types. Passe `scripts: ['/js/modules/DishToggle.js']` |
| `create()` | GET : affiche le formulaire de création avec types et allergènes |
| `store()` | POST : CSRF → champs requis → upload image → `DishModel::create()` + `addAllergenes()` |
| `edit(int $id)` | GET : charge le plat, ses allergènes, tous les types et allergènes disponibles |
| `update(int $id)` | POST : CSRF → `DishModel::update()` → `syncAllergenes()` → upload image si fournie |
| `toggle(int $id)` | POST AJAX : CSRF via `HTTP_X_CSRF_TOKEN` → `DishModel::toggle()`. Retourne JSON. |

### `src/Controllers/ReviewController.php`

Dépendances : `ReviewModel`, `OrderModel`. Route guard : `STATUS_TERMINEE = 7` (commande terminée).

| Méthode | Description |
| --- | --- |
| `create(int $id)` | GET : vérifie auth + guards (ownership, status = 7, pas de doublon) → affiche formulaire de rédaction |
| `store(int $id)` | POST : CSRF → auth → champs requis → rating ∈ [1,5] → guards → `ReviewModel::create()` |
| `list()` | GET : `requireEmploye()` → affiche tous les avis `pending`. Passe `scripts: [ReviewReject.js]` |
| `valid(int $id)` | POST : CSRF → `requireEmploye()` → existence → status whitelist → raison obligatoire si rejet → `ReviewModel::validate()` → email → redirect |

**Guards** (méthode privée `guards()`) : ordre existence → ownership → status = 7 → pas de review existante. Chacun redirige avec flash si non satisfait.

### `src/Controllers/AdminController.php`

Dépendances : `UserModel`, `StatsModel`. Toutes les méthodes appellent `Security::requireAdmin()`.
Constantes : `ROUTE_LIST = 'admin/employes'`, `ROUTE_CREATE = 'admin/employe/create'`.

| Méthode | Description |
| --- | --- |
| `list()` | Affiche la liste de tous les employés. Passe `scripts: [EmployeToggle.js]` |
| `toggle(string $id)` | POST AJAX : CSRF via `HTTP_X_CSRF_TOKEN` → `UserModel::toggleActive()`. Retourne JSON. |
| `create()` | GET : formulaire de création d'un compte employé |
| `store()` | POST : CSRF → champs requis (nom, prenom, email) → unicité email → génère mot de passe aléatoire (16 chars) → `UserModel::createEmploye()` → email de bienvenue → stocke `temp_password` en session → redirect confirmation |
| `confirmation()` | Affiche le mot de passe temporaire une seule fois (lu puis supprimé de la session) |
| `stats()` | Affiche le tableau de bord des ventes. Accepte filtres GET : `year_start`, `year_end`, `month_start`, `month_end`, `menu_id`. Passe `scripts: [StatsCharts.js]` |

**Méthodes privées :**

| Méthode | Description |
| --- | --- |
| `sendEmployeMail(array $data): void` | Envoie l'email de bienvenue au nouvel employé via template `create-employe`. Échec silencieux (error_log). |

### `src/Controllers/MenuManageController.php`

Dépendances : `MenuModel`, `DishModel`. Toutes les méthodes appellent `Security::requireEmploye()`.
Constantes : `ROUTE_LIST = 'employe/menus'`, `ROUTE_CREATE = 'employe/menu/create'`, `ROUTE_EDIT = 'employe/menu/edit/'`.
Upload d'image vers `public/uploads/menus/` (5 Mo max, jpg/png/webp/gif).

| Méthode | Description |
| --- | --- |
| `list()` | Tous les menus (incluant inactifs), lookup plats + allergènes par menu. Passe `scripts: [MenuToggle.js, MenuRestock.js]` |
| `toggle(int $id)` | POST AJAX : CSRF via `HTTP_X_CSRF_TOKEN` → `MenuModel::toggle()`. Retourne JSON. |
| `addStock(int $id)` | POST AJAX : CSRF via `HTTP_X_CSRF_TOKEN` → valide `quantity > 0` → `MenuModel::addStock()` → retourne JSON `{success, stock}` |
| `create()` | GET : dishes groupés par type_id, themes, regimes |
| `store()` | POST : CSRF → champs requis → upload images → `MenuModel::create()` + `addDishes()` + `addImages()` |
| `edit(int $id)` | GET : menu (inactifs inclus), `menuDishes` aplati en tableau d'IDs via `array_column()`, images, themes, regimes, dishes groupés. Passe `scripts: [DisplayOrder.js]` |
| `update(int $id)` | POST : CSRF → `MenuModel::update()` → suppressions images → réordonnancement → upload nouvelles images |

**Méthodes privées :**

| Méthode | Description |
| --- | --- |
| `handleImageDeletions(array): void` | Supprime en BDD + fichier physique |
| `handleImageReorder(array, array, array): void` | Regroupe ids/orders/altTexts et appelle `MenuModel::updateImageOrder()` |
| `handleImageUploads(int, string, array): void` | Valide et déplace les fichiers, appelle `MenuModel::addImages()` |

---

## 5. Modèles

### `src/Models/UserModel.php`

| Méthode | Description |
| --- | --- |
| `findById(string $id): ?array` | Sans mot de passe |
| `findByEmail(string $email): ?array` | Avec mot de passe (pour vérification) |
| `emailExists(string $email): bool` | COUNT |
| `create(array $data): bool` | INSERT (nom, prenom, email, gsm, adresse, code_postal, city, password) |
| `update(string $id, array $data): bool` | UPDATE nom, prenom, gsm, adresse |
| `updatePassword(string $id, string $newPassword): bool` | UPDATE password |
| `createPasswordResetToken(…): bool` | INSERT token avec expiration |
| `findPasswordResetToken(string $token): ?array` | Vérifie expiry et `used=0` |
| `markPasswordResetTokenAsUsed(string $tokenHash): bool` | SET `used=1` |

### `src/Models/OrderModel.php`

| Méthode | Description |
| --- | --- |
| `create(array $orderData): ?int` | INSERT dans `orders`, retourne l'ID. Bas niveau — préférer `createWithTransaction()`. |
| `createWithTransaction(array $orderData, string $userId): ?int` | Transaction : `create()` + `addStatusHistory()` + `decrementStock()`. Rollback en cas d'échec. |
| `cancel(int $orderId, string $userId): bool` | Passe en status 8. Requis : status courant = 1. Transaction + history. |
| `findById(int $id): ?array` | JOIN `users`, `menus`, `order_status`. Retourne null si introuvable. |
| `findByUserId(string $userId): array` | Commandes d'un utilisateur, triées par `created_at DESC`. |
| `findAllFiltered(array $filters): array` | Filtre par `status_id` et/ou `search` (LIKE sur nom/prenom/email). Inclut `gsm`. |
| `addStatusHistory(int, int, string, ?string): bool` | INSERT dans `order_status_history`. |
| `decrementStock(int $menuId, int $quantity): bool` | `UPDATE menus SET stock = stock - ? WHERE stock >= ?` (atomique, anti-négatif). |
| `checkStock(int $menuId, int $quantity): bool` | Vérifie que `stock >= quantity`. |
| `getStatusHistory(int $orderId): array` | Historique complet triée par `changed_at ASC`. JOIN `order_status` (name) + `users` (nom, prenom). |
| `getAllStatus(): array` | Tous les statuts triés par `workflow_order ASC`. |
| `updateStatus(int, int, string, string): bool` | UPDATE + history dans une transaction. |
| `cancelByEmploye(int, array, array): bool` | Status 8 + `cancellation_reason`, `cancelled_by`, `contact_method` + history. Transaction. |
| `update(int $orderId, array $data): bool` | UPDATE champs logistiques/prix uniquement (pas le statut). |

### `src/Models/MenuModel.php`

| Méthode | Description |
| --- | --- |
| `findAll(bool $activeOnly): array` | SELECT menus + theme + regime |
| `getListImages(): array` | Image principale (`display_order = 1`) par menu |
| `findById(int $id, bool $activeOnly): ?array` | Menu complet avec theme_id et regime_id. Filtre `active` conditionnel. |
| `getMenuImages(int $id): array` | Toutes les images triées par `display_order` |
| `getMenuDishes(int $id): array` | Plats avec type_id et dish_type_name, triés par `dish_types.display_order` |
| `getAllergenesForMenu(int $id): array` | Allergènes de tous les plats d'un menu (flat, grouper côté contrôleur) |
| `getAllAllergenes(): array` | Allergènes de tous les menus — lookup pour la vue gestion |
| `getAllDishes(): array` | Tous les plats de tous les menus avec menu_id — lookup pour la vue gestion |
| `getAllThemes(): array` | Tous les thèmes |
| `getAllRegimes(): array` | Tous les régimes |
| `findFiltered(array $filters): array` | WHERE dynamique : min_price, max_price, min_people, theme_id, regime_id |
| `getAvailableOptions(array $filters): array` | `{themes: int[], regimes: int[]}` disponibles selon filtres prix/personnes |
| `create(array $data): int\|false` | INSERT menu, retourne l'ID ou false |
| `update(array $data): bool` | UPDATE champs du menu. Retourne `rowCount() >= 0` (vrai même sans changement) |
| `toggle(int $id): bool` | Bascule le flag `active` |
| `addDishes(int $menuId, array $dishIds): void` | INSERT dans `menu_dishes` |
| `addImages(int $menuId, string $title, array $imageUrls): void` | INSERT dans `menu_images` avec alt auto et display_order séquentiel |
| `deleteImage(int $id): bool` | DELETE dans `menu_images` |
| `updateImageOrder(array $images): void` | UPDATE `display_order` et `alt_text` pour chaque image |
| `addStock(int $id, int $quantity): bool` | `UPDATE menus SET stock = stock + :quantity` |
| `getStock(int $id): ?array` | Retourne `{stock}` pour un menu |

### `src/Models/ReviewModel.php`

Table `reviews` — colonnes : `id`, `order_id`, `user_id`, `rating` (1-5), `comment`, `status` (`pending`/`approved`/`rejected`), `validated_by`, `validated_at`, `reject_reason`.

| Méthode | Description |
| --- | --- |
| `findByOrderId(int $orderId): ?array` | Trouve un avis par `order_id` — vérifie l'unicité avant création |
| `findByReviewId(int $id): ?array` | Trouve un avis par sa PK |
| `findPending(): array` | Tous les avis `status = 'pending'`, JOIN `users` + `menus` |
| `findValidated(): array` | Tous les avis `status = 'approved'`, JOIN `users` + `menus` |
| `create(array $data): bool` | INSERT (order_id, user_id, rating, comment) — status par défaut `pending` |
| `validate(int $id, string $status, string $userId, ?string $rejectReason): bool` | UPDATE status + validated_by + validated_at + reject_reason |

### `src/Models/ContactModel.php`

Table `contacts` — colonnes : `id`, `email`, `title`, `message`, `processed` (0/1), `processed_by` (FK `users.id`).

| Méthode | Description |
| --- | --- |
| `findProcessed(): array` | SELECT contacts traités + JOIN `users` pour récupérer `nom`/`prenom` du traitant |
| `findToProcessed(): array` | SELECT contacts non traités (`processed = 0`) — champs `id`, `email`, `title`, `message` |
| `create(array $data): bool` | INSERT (email, title, message) — retourne `rowCount() === 1` |

### `src/Models/StatsModel.php`

Données de ventes stockées dans **MongoDB**, collection `sales`. Chaque document représente un résumé mensuel : `{ year, month, sales: [{menu_id, title, total_price, …}] }`.

| Méthode | Description |
| --- | --- |
| `getAvailableYears(): array` | `distinct('year')` — liste des années disponibles pour les selects |
| `getOrdersByMenu(): array` | Agrège sur toute la collection, groupe par `menu_id` → `{_id, title, total_sales, total_price}` |
| `getRevenueByMenu(array $filters): array` | Filtre par plage de dates (year_start/end, month_start/end), puis groupe par `menu_id`. Filtre optionnel `menu_id`. Retourne `{_id, title, total_sales, total_price}`. |

**Filtres date :** si `year_start === year_end`, filtre sur `year + month ∈ [month_start, month_end]`. Sinon, `$or` sur le premier et dernier mois/année.

### `src/Models/ScheduleModel.php`

| Méthode | Description |
| --- | --- |
| `findAll(): array` | Tous les horaires triés par `id` |
| `updateAll(array $datas): void` | UPDATE en boucle : `opening_time`, `closing_time`, `closed` pour chaque id. Convertit `''` en `null`. |

---

### `src/Models/DishModel.php`

| Méthode | Description |
| --- | --- |
| `findAll(): array` | Tous les plats avec type_id, type_name, active |
| `findById(int $id): ?array` | Plat avec type |
| `getDishAllergenes(int $id): array` | Allergènes d'un plat |
| `getAllTypes(): array` | Tous les types de plats |
| `getAllAllergenes(): array` | Tous les allergènes |
| `create(array $data): int\|false` | INSERT plat, retourne l'ID |
| `update(int $id, array $data): bool` | UPDATE champs du plat |
| `toggle(int $id): bool` | Bascule le flag `active` |
| `addAllergenes(int $dishId, array $allergeneIds): void` | INSERT dans `dish_allergenes` |
| `syncAllergenes(int $dishId, array $allergeneIds): void` | DELETE puis INSERT (remplacement complet) |

---

## 6. Vues et templates

### `templates/layout.php`

Variables requises : `$title`, `$content`.
Variables optionnelles : `$description` (meta), `$scripts` (tableau de chemins de modules JS).

- Applique `formatText()` sur `$title` (préserve `&nbsp;`)
- Affiche le flash via un seul `Session::getFlash()` (clé unique `$_SESSION['flash']`)
- Charge `/js/scripts.js` en `type="module"` (global)
- Charge chaque entrée de `$scripts` en `type="module"` (page-spécifique)

### `templates/partials/header.php`

Navigation responsive. Sélecteur de thème (`#mode`), bouton nav mobile (`#nav-button`), menu `#nav-bar`. Lien "Mon compte" ou "Connexion" selon `Session::isAuthenticated()`.

### `templates/partials/footer.php`

Breadcrumbs dynamiques depuis `$_GET['path']`. Section horaires collapsible (`#footer-button`, `.hidden`).

---

### Vues Auth (`views/auth/`)

| Fichier | Variables |
| --- | --- |
| `login.php` | `$csrfToken`, `$title` |
| `register.php` | `$csrfToken`, `$title` |
| `reset-password.php` | `$csrfToken`, `$title` |
| `new-password.php` | `$csrfToken`, `$token`, `$email`, `$title` |

### Vues User (`views/user/`)

| Fichier | Variables | Notes |
| --- | --- | --- |
| `profile.php` | `$user`, `$title` | Section commandes avec lien vers `order/detail/{id}` |
| `edit-profile.php` | `$user`, `$csrfToken`, `$title` | Email non éditable |
| `change-password.php` | `$user`, `$csrfToken`, `$title` | `old_password` + `new_password` |

### Vues Menus (`views/menus/`)

**`list.php`** — `$menus`, `$title`, `$description`. Filtres AJAX, sliders prix, container `#menus-container`.

**`detail.php`** — `$menu`, `$images`, `$dishesByType`, `$title`, `$description`. Carousel, plats groupés par type, bouton commande conditionnel.

### Vues Review (`views/review/`)

| Fichier | Variables | Notes |
| --- | --- | --- |
| `create.php` | `$order`, `$csrfToken`, `$title` | Formulaire : note 1-5 (`.rating` interactif via radio) + textarea commentaire. POST vers `review/store/{id}` |

### Vues Orders (`views/orders/`)

| Fichier | Variables | Notes |
| --- | --- | --- |
| `create.php` | `$menu`, `$user`, `$csrfToken` | 3 fieldsets, calcul prix dynamique AJAX |
| `confirmation.php` | `$order` | Récapitulatif post-commande |
| `show.php` | `$order`, `$history` | Détail complet + historique de statuts chronologique |
| `edit.php` | `$order`, `$csrfToken`, `$title` | Formulaire de modification (livraison + nb_people) |

### Vues Admin (`views/admin/`)

| Fichier | Variables | Notes |
| --- | --- | --- |
| `employes.php` | `$employes`, `$csrfToken`, `$title` | Tableau des employés avec toggle actif/inactif AJAX |
| `employe/create.php` | `$csrfToken`, `$title` | Formulaire création compte employé (nom, prénom, email) |
| `employe/confirmation.php` | `$password`, `$email` | Affiche le mot de passe temporaire — visible une seule fois |
| `stats.php` | `$orders`, `$revenues`, `$years`, `$filters`, `$title` | Filtres date/menu (GET), 2 `<div>` cibles Plotly (`#chart-orders`, `#chart-revenues`). Expose `window.ordersData` et `window.revenuesData` pour `StatsCharts.js` |

### Vues Employé (`views/employe/`)

| Fichier | Variables | Notes |
| --- | --- | --- |
| `orders.php` | `$orders`, `$statuses`, `$csrfToken`, `$title` | Liste filtrée GET (`status_id`, `search`), tableau scrollable horizontalement (`.over`) avec lien vers détail |
| `schedules.php` | `$schedules`, `$csrfToken`, `$title` | 7 fieldsets (un par jour) en scroll horizontal mobile, checkbox `closed` remet à zéro les inputs heure via `ScheduleClosed.js` |
| `reviews.php` | `$reviews`, `$csrfToken`, `$title` | Liste des avis `pending` — select approve/reject + textarea raison (toggle via `ReviewReject.js`), soumission POST vers `review/validate/{id}` |
| `dishes.php` | `$dishes`, `$csrfToken`, `$title` | Tableau avec toggle actif/inactif AJAX |
| `dish/create.php` | `$types`, `$allergenes`, `$csrfToken`, `$title` | Formulaire création plat + upload image + sélection allergènes |
| `dish/edit.php` | `$dish`, `$types`, `$allergenes`, `$dishAllergenes`, `$csrfToken`, `$title` | Pré-rempli, allergènes cochés |
| `menus.php` | `$menus`, `$csrfToken`, `$title` | Tableau : toggle actif (AJAX), ajout stock (AJAX), lien modifier |
| `menu/create.php` | `$dishByType`, `$themes`, `$regimes`, `$csrfToken`, `$title` | Création menu, sélection plats par type, upload images multiple |
| `menu/edit.php` | `$menu`, `$menuDishes` (tableau d'IDs), `$images`, `$themes`, `$regimes`, `$dishByType`, `$csrfToken`, `$title` | Édition menu, gestion images (alt, display_order, suppression), upload nouvelles images |

---

## 7. Système CSS

### Variables globales (`_config.css`)

```css
--primary: #6B1F3F     /* bordeaux foncé */
--secondary: #D4AF37   /* or */
--light-1: #faf9f6     /* blanc chaud */
--light-2: #f5f3ee     /* blanc cassé */
--dark-1: #1A1A1A      /* noir */
--dark-2: #2F2F2F      /* gris très sombre */
--border: #3F3F3F
--success: #78ffcb  --error: #C41E3A
--base-unit: 14px / 16px / 18px  (mobile / tablette / desktop)
```

Typographie : `Playfair` (titres) · `Lora` (corps) · `Playwrite` (secondaire).

### Système de thèmes

`body[data-theme="auto|light|dark"]` — persisté dans `localStorage`.

- **light** → accent `--primary` (bordeaux), fonds `--light-1/2`
- **dark** → accent `--secondary` (or), fonds `--dark-1/2`
- **auto** → `light-dark(valeur_light, valeur_dark)`

Chaque règle thémée est triplée : `body[data-theme=auto]`, `body[data-theme=light]`, `body[data-theme=dark]`.

### Fichiers de styles

| Fichier | Rôle |
| --- | --- |
| `_config.css` | Variables globales, reset, thèmes, typographie responsive |
| `_layout.css` | `.container`, `.wrapper`, grille |
| `_space.css` | Utilitaires gap/margin/padding |
| `_typo.css` | Tailles h1-h6 |
| `_utility.css` | `.hidden`, `.text-muted`, `.rotate`, `.over` (scroll horizontal), `.min-max` (min-width: max-content) |
| `_d-layout.css` / `_d-space.css` / `_d-utility.css` | Overrides desktop |
| `_t-layout.css` / `_t-space.css` / `_t-utility.css` | Overrides tablette |
| `_components.css` | `@import` de tous les composants |
| `style.css` | Styles spécifiques aux pages |

### Composants (`css/Components/`)

**`buttons.css`** · **`cards.css`** · **`forms.css`** · **`badges.css`** · **`alerts.css`** · **`carousel.css`** · **`range-slider.css`**

**`rating.css`** — deux usages :

- `.stars-display` + `.star` / `.star--filled` : affichage lecture seule (5 `★`, les N premiers en couleur primaire/secondaire selon thème)
- `.rating` + `input[type="radio"]` + `label` : sélecteur interactif (RTL trick, `float: right`)

**`forms.css`** — points notables :

- `input` global : `appearance: none`, `border-width: 2px` (pas de `border-style` — les checkboxes ont besoin de `border-style: solid` explicite)
- `input[type="checkbox"]` : stylisé from scratch, coche via `::after` (rotation 45°), fond coloré au thème quand coché
- `.input-container` : colonne sur mobile, ligne sur desktop

Double slider prix — architecture calques :

```txt
z-index 0 : ::before   piste grise
z-index 1 : ::after    zone colorée (--left / --right)
z-index 2 : input[type="range"]  thumbs
```

---

## 8. Modules JavaScript

### `public/js/scripts.js` — Global

Thème · visibilité mot de passe · nav mobile · footer collapsible · filtre mobile · restore thème · suppression `.hidden` desktop.

### `public/js/modules/Carousel.js` — `menus/detail`

Classe `Carousel` : autoplay 5s, swipe tactile, prev/next, dots. Auto-init sur `[data-carousel]`.
`#startAutoplay()` appelle `#stopAutoplay()` en tête pour éviter l'accumulation d'intervals lors de survols/clics répétés.

### `public/js/modules/MenuFilter.js` — `menus/list`

Classe `MenuFilter` : fetch POST `/menus/filter` + header `X-CSRF-Token`, debounce 500ms, double slider prix, options disable/enable selon disponibilité.

### `public/js/modules/OrderForm.js` — `orders/create`

Fetch POST `/order/calculate-price` + header `X-CSRF-Token`, debounce 500ms. Affiche détail prix dynamique, désactive submit tant que prix non reçu.

### `public/js/modules/MenuToggle.js` — `employe/menus`

Toggle actif/inactif d'un menu via AJAX POST `/employe/menu/toggle/{id}`. CSRF via header `X-CSRF-Token`. Met à jour le style du bouton selon la réponse JSON.

### `public/js/modules/MenuRestock.js` — `employe/menus`

Ajout de stock via AJAX POST `/employe/menu/addstock/{id}`. Lit la quantité depuis l'input au moment du clic (`FormData` dans le handler), met à jour `[data-stock]` dans la même `<tr>`, remet l'input à zéro. CSRF via header `X-CSRF-Token`.

### `public/js/modules/DishToggle.js` — `employe/dishes`

Toggle actif/inactif d'un plat via AJAX POST `/employe/dish/toggle/{id}`. Même pattern que `MenuToggle.js`.

### `public/js/modules/DisplayOrder.js` — `employe/menu/edit`

Évite les doublons dans les inputs `name="display_order[]"`. Au `focus` mémorise la valeur (`data-prev`), au `change` trouve un éventuel conflit et fait un swap automatique.

### `public/js/modules/ReviewReject.js` — `employe/reviews`

Écoute le `change` sur chaque `[data-status]`. Affiche/masque `[data-comment]` (textarea raison de refus) selon que la valeur sélectionnée est `'rejected'` ou non.

### `public/js/modules/ScheduleClosed.js` — `employe/schedules`

Au chargement et au `change` de chaque `input[type="checkbox"]` dans un fieldset : si coché, vide et passe en `readonly` les inputs `[data-opening]` et `[data-closing]` du même fieldset. Décoche → réactive les inputs.

### `public/js/modules/EmployeToggle.js` — `admin/employes`

Toggle actif/inactif d'un employé via AJAX POST `/admin/employe/toggle/{id}`. Intercepte le clic sur `[data-employe-id]` dans chaque `[data-form="employe-toggle"]`. CSRF via header `X-CSRF-Token`. Met à jour le bouton (`primary` ↔ `outline`, texte `Actif` ↔ `Inactif`) selon la réponse JSON.

### `public/js/modules/StatsCharts.js` — `admin/stats`

Module ES. Importe Plotly via CDN ESM : `import Plotly from 'https://esm.sh/plotly.js-dist-min'`.

Lit `globalThis.ordersData` et `globalThis.revenuesData` (exposés par `admin/stats.php` dans des balises `<script>` classiques avec `window.*`).

- Rend deux graphiques à barres groupées (un par menu) dans `#chart-orders` et `#chart-revenues`.
- Axes : X = menus, Y = volume de ventes ou chiffre d'affaires.
- Thème adaptatif : lit `getComputedStyle(document.body)` pour obtenir `--primary`, `--secondary`, etc. Un `MutationObserver` sur `body[data-theme]` redessine les graphiques au changement de thème.

---

## 9. Conventions et sécurité

### Sécurité

- **CSRF** : champ `csrf_token` sur tous les formulaires POST, vérifié avec `hash_equals()`. Endpoints AJAX : header `X-CSRF-Token` (lu via `$_SERVER['HTTP_X_CSRF_TOKEN']`)
- **HTTP methods** : le Router vérifie la méthode avant d'appeler le contrôleur (405 si invalide)
- **Mots de passe** : bcrypt via `password_hash(PASSWORD_BCRYPT)` / `password_verify()`
- **SQL** : `prepare()` + `execute()` exclusivement. Construire la requête complète avant `prepare()` (évite mismatch placeholders PDO)
- **XSS** : `Security::escapeHtml(?string)` côté PHP (null-safe, `ENT_COMPAT`), `escapeHtml()` côté JS. **Ne pas appliquer `htmlspecialchars` à l'entrée** — la donnée brute est stockée en base, l'encodage se fait uniquement à l'affichage.
- **Session** : régénération d'ID après login, expiration 30 min, cookie `HTTPOnly` + `SameSite`
- **Reset password** : token SHA256 stocké, expiration 1h, usage unique
- **Annulation employé** : `contact_method` validé contre whitelist `['email', 'telephone']`
- **Upload images** : vérification `getimagesize()` (pas seulement l'extension), MIME whitelist, taille max 5 Mo, nom aléatoire `uniqid()`

### Pièges PDO connus

- `rowCount() === 1` sur un UPDATE retourne `false` si aucune valeur n'a changé (même données). Utiliser `rowCount() >= 0` pour les updates où "aucun changement" est un succès valide.

### Scroll horizontal (`.over`)

Pour qu'`overflow-x: auto` fonctionne dans un contexte grid/flex :

1. Le conteneur `.over` doit avoir `width: 100%` et `min-width: 0` (sinon la colonne `1fr` se force à `min-content` du contenu)
2. L'enfant direct doit avoir `min-width: max-content` (classe `.min-max`) pour dépasser la largeur de `.over`

### Naming

- Classes PHP : `PascalCase` · méthodes/propriétés : `camelCase`
- Fichiers vues : `kebab-case.php` · classes CSS : `kebab-case`
- JS : variables `camelCase`, classes `PascalCase`, constantes `SCREAMING_SNAKE`
- Docblocks et commentaires : **en anglais**

### Responsive

Mobile-first. Breakpoints : 768px (tablette) · 1200px (desktop).

---

## 10. MongoDB — statistiques de ventes

### Connexion (`src/Core/MongoDBConnection.php`)

Singleton suivant le même pattern que `DatabaseConnection.php`.

```php
MongoDBConnection::getInstance() // retourne MongoDB\Database
```

- L'URI Atlas est lue directement depuis `MONGO_URI` et passée telle quelle au `Client`.
- Erreur de connexion → `503` + `die()` (même comportement que PDO).
- Config lue depuis `src/Config/MongoDB.php` → retourne `['uri', 'database']`.

### Variables d'environnement requises

| Variable | Description |
| --- | --- |
| `MONGO_URI` | URI de connexion complète (format Atlas : `mongodb+srv://user:pass@cluster.mongodb.net/?…`) |
| `MONGO_DATABASE` | Nom de la base de données cible |

### Structure de la collection `sales`

```json
{
  "year": 2025,
  "month": 12,
  "sales": [
    { "menu_id": 1, "title": "Menu Noël", "total_price": 15.00 },
    …
  ]
}
```
