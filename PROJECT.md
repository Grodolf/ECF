# ECF — Vite & Gourmand · Contexte projet

> Document de référence pour la continuité du développement.
> Stack : PHP 8+ · PDO/MySQL · CSS vanilla · JS ES Modules · PHPMailer

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

---

## 1. Architecture générale

```
ECF/
├── config/                 .env / .env.local / Smtp.php
├── controllers/            AuthController, UserController, MenuController, MainController
├── models/                 UserModel, MenuModel
├── public/
│   ├── index.php           Point d'entrée unique
│   ├── css/                Feuilles de style (voir §7)
│   ├── js/
│   │   ├── scripts.js      Script global (thème, nav, footer, eye)
│   │   └── modules/        Carousel.js, MenuFilter.js
│   └── img/
├── src/Core/               Router, AbstractController, Session, Security,
│                           DatabaseConnection, FlashMessage, Mailer, Env
├── templates/              layout.php, partials/header.php, partials/footer.php
│                           emails/
└── views/                  home, contact, auth/, user/, menus/
```

**Flux d'une requête :**
`index.php` → `Env::loadEnv()` → `Session::start()` → `new Router()` → Controller → `renderView()` → `layout.php`

---

## 2. Système de routes

### `src/Core/Routes.php`

Constante `ROUTES` : tableau associatif `chemin => [Classe, méthode]`.

| Chemin | Contrôleur | Méthode |
|---|---|---|
| `/` | MainController | home |
| `home` | MainController | home |
| `contact` | MainController | contact |
| `login` | AuthController | login |
| `logout` | AuthController | logout |
| `register` | AuthController | register |
| `reset-password` | AuthController | resetPassword |
| `new-password/{token}` | AuthController | newPassword |
| `profile` | UserController | profile |
| `edit-profile` | UserController | editProfile |
| `change-password` | UserController | changePassword |
| `menus` | MenuController | list |
| `menu/{id}` | MenuController | detail |
| `menus/filter` | MenuController | filter |

### `src/Core/Router.php`

| Méthode | Description |
|---|---|
| `__construct()` | Charge les routes, traite la requête courante |
| `parseRoutes(): void` | Fait correspondre le chemin et invoque le contrôleur |
| `matchPath(string, array): ?array` | Compare segments, extrait les `{param}` |
| `castParams(array): array` | Convertit les params numériques en `int` |
| `explodePath(string): array` | Découpe un chemin par `/` |
| `isParam(string): bool` | Détecte la syntaxe `{param}` |

Paramètre inconnu → redirection vers `/`.

---

## 3. Classes Core

### `src/Core/AbstractController.php`

Base de tous les contrôleurs.

| Méthode | Description |
|---|---|
| `renderView(string $view, array $data): void` | Capture la vue avec `ob_*`, extrait `$data`, inclut `layout.php`. **La clé `$scripts` (tableau de chemins) active les `<script type="module">` page-spécifiques dans le layout.** |
| `redirectToRoute(string $path, array $params): void` | Redirige via `header("Location: …")` |
| `returnJson(array $data, int $status): void` | Envoie une réponse JSON avec le bon `Content-Type` |

### `src/Core/Session.php` (statique)

Session sécurisée, cookie HTTPOnly/SameSite, expiration 30 min d'inactivité.

| Méthode | Description |
|---|---|
| `start()` | Init session, vérifie expiration, met à jour `last_activity` |
| `set/get/has/delete($key)` | Accès `$_SESSION` |
| `destroy()` | Détruit la session et le cookie |
| `regenerate()` | `session_regenerate_id(true)` |
| `setFlash($key, $msg, $type)` | Flash message unique (success/error/info) |
| `getFlash($key)` | Lit **et supprime** le flash |
| `isAuthenticated(): bool` | `$_SESSION['user']` non vide |
| `setUser(array $user)` | Stocke l'utilisateur (sans le mot de passe) |
| `getUser(): ?array` | Retourne l'utilisateur authentifié |

### `src/Core/Security.php` (statique)

| Méthode | Description |
|---|---|
| `hashPassword(string): string` | Bcrypt |
| `verifyPassword(string, string): bool` | Vérifie contre le hash bcrypt |
| `generateCsrfToken(): string` | Token random stocké en session |
| `verifyCsrfToken(string): bool` | Comparaison à temps constant |
| `sanitizeInput(string\|array): string\|array` | `trim` + `htmlspecialchars(ENT_QUOTES)` récursif |
| `validateEmail(string): bool` | `FILTER_VALIDATE_EMAIL` |
| `validatePassword(string): array` | Retourne `[bool, array $errors]` : 10+ chars, maj, min, chiffre, spécial |
| `validateRequired(array, array): array` | Champs manquants/vides |
| `generateToken(int): string` | `random_bytes` → hex |
| `hashToken(string): string` | SHA256 pour stockage en BDD |
| `escapeHtml(string): string` | `htmlspecialchars` |
| `requireAuth(): array` | Redirige vers `/login` si non connecté |
| `requireEmploye(): array` | Redirige si rôle `user` |
| `requireAdmin(): array` | Redirige si rôle non `admin` |

### `src/Core/FlashMessage.php` (statique)

Centralise tous les messages flash prédéfinis. Appelle `Session::setFlash()`.

Groupes de méthodes : **Generic** (`invalidCsrf`, `genericError`, `invalidMail`, `invalidPassword`, `fieldsRequired`) · **Auth** (`invalidCredentials`, `emailAlreadyExists`, `tokenExpired`, `loginSuccess`, `registerSuccess`, `passwordResetSent`, `passwordUpdated`, `authRequired`, `sessionExpired`, `accessDenied`, `adminRequired`) · **Profile** (`profileUpdated`, `passwordChanged`, `samePassword`, `wrongPassword`) · **Menus** (`wrongMenu`).

Clés flash lues dans le layout : `generic`, `auth`, `profile`, `menus`.

### `src/Core/DatabaseConnection.php` (Singleton)

| Méthode | Description |
|---|---|
| `getInstance(): PDO` | Retourne (ou crée) l'instance PDO unique. SSL configuré si Aiven (prod). |

### `src/Core/Mailer.php`

Config SMTP depuis `config/Smtp.php`.

| Méthode | Description |
|---|---|
| `configureMail(): PHPMailer` | Instancie PHPMailer avec SMTP, HTML, UTF-8 |
| `loadTemplate(string $name, array $data): string` | Charge `templates/emails/{name}.php`, remplace `{{var}}` |
| `send(string $to, …): bool` | Envoi direct |
| `sendWithTemplate(string $to, string $subject, string $tpl, array $data): bool` | Charge template + génère version texte |

### `src/Core/Env.php` (statique)

| Méthode | Description |
|---|---|
| `loadEnv($path)` | Lit `.env.local` ou `.env`, parse `KEY=VALUE`, injecte dans `$_ENV` et `putenv` |

---

## 4. Contrôleurs

### `controllers/AuthController.php`

Dépendance : `UserModel`.

| Méthode | Description |
|---|---|
| `login()` | GET: form + CSRF · POST: vérifie email/hash, régénère session |
| `logout()` | Détruit la session, redirige vers home |
| `register()` | GET: form · POST: valide tout, vérifie unicité email, hash mdp, insère, envoie email |
| `resetPassword()` | GET: form email · POST: génère token (1h), envoie lien par email |
| `newPassword(string $token)` | GET: valide token + expiration · POST: nouveau mdp, marque token utilisé |

### `controllers/UserController.php`

Dépendance : `UserModel`. Toutes les méthodes appellent `Security::requireAuth()`.

| Méthode | Description |
|---|---|
| `profile()` | Affiche les données de l'utilisateur connecté |
| `editProfile()` | GET: form pré-rempli · POST: met à jour, rafraîchit la session |
| `changePassword()` | GET: form · POST: vérifie ancien mdp, valide nouveau, met à jour |

### `controllers/MenuController.php`

Dépendance : `MenuModel`. Définit la constante `PLACEHOLDER = '/img/placeholder.webp'`.

| Méthode | Description |
|---|---|
| `list()` | Récupère tous les menus + images, passe `scripts: ['/js/modules/MenuFilter.js']` |
| `detail(int $id)` | Menu + plats groupés par type + allergènes, passe `scripts: ['/js/modules/Carousel.js']` |
| `filter()` | Endpoint AJAX POST, valide `X-Requested-With`, retourne JSON `{menus, stats, available_options}` |

### `controllers/MainController.php`

| Méthode | Description |
|---|---|
| `home()` | Vue home.php |
| `contact()` | Vue contact.php |

---

## 5. Modèles

### `models/UserModel.php`

Propriété statique `$db` (PDO).

| Méthode | Description |
|---|---|
| `findById(string $id): ?array` | Sans mot de passe |
| `findByEmail(string $email): ?array` | Avec mot de passe (pour vérification) |
| `emailExists(string $email): bool` | COUNT |
| `create(array $data): bool` | INSERT (nom, prenom, email, gsm, adresse, code_postal, city, password) |
| `update(string $id, array $data): bool` | UPDATE nom, prenom, gsm, adresse |
| `updatePassword(string $id, string $newPassword): bool` | UPDATE password |
| `createPasswordResetToken(…): bool` | INSERT token avec expiration |
| `findPasswordResetToken(string $token): ?array` | Vérifie expiry et `used=0` |
| `markPasswordResetTokenAsUsed(string $tokenHash): bool` | SET `used=1` |

### `models/MenuModel.php`

Propriété statique `$db` (PDO).

| Méthode | Description |
|---|---|
| `findAll(bool $activeOnly): array` | SELECT menus + theme + regime |
| `getListImages(): array` | Image principale (`display_order = 1`) par menu |
| `findById(int $id): array` | Menu complet par ID |
| `getMenuImages(int $id): array` | Toutes les images triées par `display_order` |
| `getMenuDishes(int $id): array` | Plats avec type, triés par `dish_types.display_order` |
| `getDishAllergenes(int $id): array` | Allergènes d'un plat |
| `getAllergenesForMenu(int $id): array` | Allergènes de tous les plats d'un menu |
| `findFiltered(array $filters): array` | WHERE dynamique : min_price, max_price, min_people, theme_id, regime_id |
| `getAvailableOptions(array $filters): array` | Retourne `{themes: int[], regimes: int[]}` disponibles selon filtres prix/personnes uniquement. Utilise `array_values(array_unique(array_map('intval', …)))` pour garantir un tableau JSON d'entiers. |

---

## 6. Vues et templates

### `templates/layout.php`

Variables requises : `$title`, `$content`.
Variables optionnelles : `$description` (meta), `$scripts` (tableau de chemins de modules JS).

- Applique `formatText()` sur `$title` (préserve `&nbsp;`)
- Affiche les flashs (`generic`, `auth`, `profile`, `menus`)
- Charge `/js/scripts.js` en `type="module"` (global)
- Charge chaque entrée de `$scripts` en `type="module"` (page-spécifique)

### `templates/partials/header.php`

Navigation responsive. Sélecteur de thème (`#mode`), bouton nav mobile (`#nav-button`), menu `#nav-bar`. Lien "Mon compte" ou "Connexion" selon `Session::isAuthenticated()`.

### `templates/partials/footer.php`

Breadcrumbs dynamiques depuis `$_GET['path']`. Infos (téléphone, adresse) masquées sur mobile. Section horaires collapsible (`#footer-button`, `.hidden`).

---

### Vues Auth (`views/auth/`)

| Fichier | Variables | Notes |
|---|---|---|
| `login.php` | `$csrfToken`, `$title` | Bouton `#eye` pour visibilité mdp |
| `register.php` | `$csrfToken`, `$title` | Affiche exigences mdp, bouton `#eye` |
| `reset-password.php` | `$csrfToken`, `$title` | Saisie email uniquement |
| `new-password.php` | `$csrfToken`, `$token`, `$email`, `$title` | Token en champ hidden |

### Vues User (`views/user/`)

| Fichier | Variables | Notes |
|---|---|---|
| `profile.php` | `$user`, `$title` | Grille infos, liens edit/change-password/logout |
| `edit-profile.php` | `$user`, `$csrfToken`, `$title` | Email non éditable (disabled) |
| `change-password.php` | `$user`, `$csrfToken`, `$title` | Champs `old_password` + `new_password` |

### Vues Menus (`views/menus/`)

**`list.php`** — Variables : `$menus`, `$title`, `$description`.
Génère les `<option>` theme/regime à partir de `$menus` (PHP). Formulaire filtre `#menu-filter` (caché mobile). Sliders `#price-min-slider` / `#price-max-slider` avec `name="min_price"` / `name="max_price"`, `min=5`, `max=25`. Container `#menus-container` remplacé par AJAX. Compteur `#results-count`.

**`detail.php`** — Variables : `$menu`, `$images`, `$dishesByType`, `$title`, `$description`.
Carousel `[data-carousel]` avec `.carousel-slide`, `[data-carousel-prev/next]`, `[data-carousel-dot]`. Plats groupés par type, badges allergènes. Bouton commande conditionnel (auth + stock).

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

Sélecteur `body[data-theme="auto|light|dark"]` défini dans `scripts.js` et persité dans `localStorage`.

```css
/* Pattern systématique dans chaque composant */
body[data-theme=auto]  .composant { color: light-dark(var(--primary), var(--secondary)); }
body[data-theme=light] .composant { color: var(--primary); }
body[data-theme=dark]  .composant { color: var(--secondary); }
```

**Règle d'or :**
- Mode **light** → accent `--primary` (bordeaux), fonds `--light-1/2`
- Mode **dark** → accent `--secondary` (or), fonds `--dark-1/2`
- Mode **auto** → `light-dark(valeur_light, valeur_dark)` (suit le système OS)

### Fichiers de styles

| Fichier | Rôle |
|---|---|
| `_config.css` | Variables globales, reset, thèmes, typographie responsive |
| `_layout.css` | `.container`, `.wrapper`, `.grid-1` à `.grid-12`, `.col-*` |
| `_space.css` | Utilitaires gap/margin/padding (`.g`, `.m`, `.p` + variantes) |
| `_typo.css` | Tailles h1-h6, line-height, font-weight |
| `_utility.css` | `.hidden`, `.text-muted`, `.rotate`, classes d'aide |
| `_d-layout.css` | Overrides layout desktop (préfixe `d:`) |
| `_d-space.css` | Overrides spacing desktop |
| `_d-utility.css` | Overrides utility desktop |
| `_t-layout.css` | Overrides layout tablette |
| `_t-space.css` | Overrides spacing tablette |
| `_t-utility.css` | Overrides utility tablette |
| `_compoments.css` | `@import` de tous les composants ci-dessous |
| `style.css` | Styles spécifiques aux pages (header, footer, home, auth, profile, menus) |

### Composants (`css/Components/`)

**`buttons.css`** — `.btn` (plein, bordeaux/or selon thème) · `.btn.outline` (bordure) · `.btn.text` (transparent). Hover + focus states.

**`cards.css`** — `.card` (border + background thématisés) · `.card-header` (image + badges, overflow hidden) · `.card-body` (padding, flex colonne) · `.card-title`, `.card-description` (line-clamp 3). Desktop : `flex-direction: row`.

**`forms.css`** — `input`, `select` avec `border-color` thématisée. Focus : outline inversé (secondary/primary selon mode). `.input-container` (label + input vertical). `#eye` (position absolue, right).

**`badges.css`** — `.badge` (inline, bg thématisé) · `.badge.danger` (allergène, rouge/warning).

**`alerts.css`** — `.flash` (alert pleine largeur) · `.flash.success` · `.flash.error`. Positionné après le header.

**`carousel.css`** — `.carousel` (relatif, overflow hidden) · `.carousel-slide` (absolu, `opacity 0` → `opacity 1 + .active`) · `.carousel-btn` (prev/next) · `.carousel-dot` (indicateurs).

**`range-slider.css`** — Double slider prix. Architecture en calques :

```
z-index 0 : .range-slider::before   piste grise complète
z-index 1 : .range-slider::after    zone colorée (left: var(--left), right: var(--right))
z-index 2 : input[type="range"]     transparents, thumbs au-dessus
```

Variables CSS par thème : `--slider-track`, `--slider-accent`, `--slider-thumb-bg`, `--slider-thumb-border`, `--slider-focus`. Thumb centré avec `margin-top: 12px` (webkit) → `(44px - 20px) / 2`.

---

## 8. Modules JavaScript

### `public/js/scripts.js` — Global

Chargé sur toutes les pages (`type="module"`, différé).

Responsabilités :
- **Thème** : `#mode` → `body[data-theme]` + `localStorage`
- **Mot de passe** : `#eye` → toggle `input[type=password/text]` + icône + aria-label
- **Nav mobile** : `#nav-button` → toggle `.hidden` sur `#nav-bar`
- **Footer mobile** : `#footer-button` → toggle `.hidden` sur `footer .hidden` + `.rotate`
- **Filtre mobile** : `#menu-button` → toggle `.hidden` sur `#menu-filter`
- **Restore thème** : lit `localStorage("theme")` au chargement
- **Desktop** : supprime toutes les classes `.hidden` si `min-width: 1200px`

### `public/js/modules/Carousel.js` — Page `menus/detail`

Auto-init : `document.querySelectorAll('[data-carousel]').forEach(el => new Carousel(el))`

**Classe `Carousel`** — Propriétés privées : `#carousel`, `#slides`, `#dots`, `#prevBtn`, `#nextBtn`, `#currentIndex`, `#autoplayInterval`, `#autoplayDelay` (5000ms).

| Méthode privée | Description |
|---|---|
| `#showSlide(index)` | Active slide et dot, met à jour `#currentIndex` |
| `#startAutoplay()` | `setInterval` toutes les 5s |
| `#stopAutoplay()` | `clearInterval` |
| `#init()` | Bind tous les événements |

Événements : clic prev/next · clic dot · swipe tactile (seuil 50px) · mouseenter/leave (pause).

### `public/js/modules/MenuFilter.js` — Page `menus/list`

Auto-init : instancie `MenuFilter` si `#menu-filter` et `#menus-container` présents.

Constantes : `PRICE_MIN = 5`, `PRICE_MAX = 25`.

**Classe `MenuFilter`** — Propriétés privées : `#form`, `#container`, `#priceMinSlider`, `#priceMaxSlider`, `#priceMinDisplay`, `#priceMaxDisplay`, `#rangeSliderContainer`.

| Méthode privée | Description |
|---|---|
| `#init()` | Bind inputs non-range + select (`change` + `input` debounced 500ms), sliders (`input` pour affichage, `change` pour filtre), bouton reset |
| `#updatePriceDisplay()` | Lit sliders, empêche min > max, met à jour affichage + barre |
| `#updateProgressBar(min, max)` | Calcule `--left`/`--right` en % et les injecte sur `.range-slider` |
| `#filter()` | `fetch POST /menus/filter` avec FormData, gère opacité, appelle render + updateStats |
| `#renderMenus(menus)` | Génère le HTML des cards, met à jour `#results-count` |
| `#updateFiltersFromStats(stats, opts)` | Met à jour placeholder min_people, désactive options indisponibles |
| `#updateSelectOptions(id, availableIds)` | `new Set(availableIds.map(Number))` → compare avec `Number(option.value)` pour neutraliser les mismatch string/int PDO |

Fonctions utilitaires (scope module) : `escapeHtml(text)` · `formatPrice(price)` (2 décimales, virgule FR) · `debounce(func, wait)`.

---

## 9. Conventions et sécurité

### Sécurité

- **CSRF** : token sur tous les formulaires POST, vérifié avec `hash_equals()`
- **Mots de passe** : bcrypt via `password_hash(PASSWORD_BCRYPT)` / `password_verify()`
- **Requêtes SQL** : uniquement des `prepare()` + `execute()`, jamais de concaténation
- **XSS** : `Security::escapeHtml()` côté PHP, `escapeHtml()` côté JS pour le HTML généré par AJAX
- **Session** : régénération d'ID après login, expiration 30 min, cookie `HTTPOnly` + `SameSite`
- **Reset password** : token SHA256 stocké (jamais le token brut), expiration 1h, usage unique

### Naming

- Classes PHP : `PascalCase`
- Méthodes/propriétés : `camelCase`
- Fichiers vues : `kebab-case.php`
- Classes CSS : `kebab-case`
- Variables JS : `camelCase`, classes `PascalCase`, constantes `SCREAMING_SNAKE`

### Chargement des scripts

Les scripts page-spécifiques sont déclarés dans le contrôleur via la clé `scripts` passée à `renderView()`. Cela évite de charger `Carousel.js` ou `MenuFilter.js` sur les pages qui n'en ont pas besoin.

```php
$this->renderView('menus/list.php', [
    'scripts' => ['/js/modules/MenuFilter.js']
]);
```

### Responsive

Mobile-first. Trois breakpoints : mobile · 768px (tablette) · 1200px (desktop).
Fichiers `_t-*` pour tablette, `_d-*` pour desktop.
