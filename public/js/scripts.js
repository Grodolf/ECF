// ========
// GENERAL
// ========

// ---------------
// Theme
// ---------------

const themeToggle = document.querySelector('#mode');
const savedTheme = localStorage.getItem('theme');

if (savedTheme) {
    document.body.dataset.theme = savedTheme;
}

themeToggle?.addEventListener('change', (e) => {
    const theme = e.target.value;
    document.body.dataset.theme = theme;
    localStorage.setItem('theme', theme);
});

// ---------------------------
// VISIBILITÉ MOT DE PASSE
// ---------------------------

const eyeButton = document.querySelector('#eye');
const passwordInput = document.querySelector('input[type="password"]');

eyeButton?.addEventListener('click', () => {
    const type = passwordInput.type === 'password' ? 'text' : 'password';
    passwordInput.type = type;
    
    const isVisible = type === 'text';
    eyeButton.setAttribute('src', isVisible ? './img/eye-off.svg' : './img/eye.svg');
    eyeButton.setAttribute('aria-label', isVisible ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
});

// ---------------
// Messages flash
// ---------------

const flash = document.querySelector('.flash-container');

if (flash) {
    setTimeout(() => {
        flash.remove();
    },5000);
}

// ============================
// 📱 RESPONSIVE - MEDIA QUERY
// ============================

const mobile = window.matchMedia('(max-width: 1199px)');

// ------------------
// Navigation mobile
// ------------------

const navButton = document.querySelector('#nav-button');
const navBar = document.querySelector('#nav-bar');

function toggleNavBar() {
    navBar?.classList.toggle('hidden');
}

function handleMobileNav(e) {
    if (!navButton || !navBar) return;

    if (e.matches) {
        navButton.addEventListener('click', toggleNavBar);
    } else {
        navButton.removeEventListener('click', toggleNavBar);
        navBar.classList.remove('hidden');
    }
}

handleMobileNav(mobile);
mobile.addEventListener('change', handleMobileNav);

// --------------
// Footer mobile
// --------------

const footerButton = document.querySelector('#footer-button');
const footerHidden = document.querySelector('footer .hidden');

function toggleFooterHidden() {
    footerHidden?.classList.toggle('hidden');
    footerButton?.classList.toggle('rotate');
}

function handleMobileFooter(e) {
    if (!footerButton || !footerHidden) return;

    if (e.matches) {
        footerButton.addEventListener('click', toggleFooterHidden);
    } else {
        footerButton.removeEventListener('click', toggleFooterHidden);
        footerHidden.classList.remove('hidden');
    }
}

handleMobileFooter(mobile);
mobile.addEventListener('change', handleMobileFooter);

// -------------------
// Filtre menu mobile
// -------------------

const menuButton = document.querySelector('#menu-button');
const menuFilter = document.querySelector('#menu-filter');

function toggleMenuFilter() {
    menuFilter?.classList.toggle('hidden');
}

function handleMobileFilter(e) {
    if (!menuButton || !menuFilter) return;

    if (e.matches) {
        menuButton.addEventListener('click', toggleMenuFilter);
    } else {
        menuButton.removeEventListener('click', toggleMenuFilter);
        menuFilter.classList.remove('hidden');
    }
}

handleMobileFilter(mobile);
mobile.addEventListener('change', handleMobileFilter);

// -------------------
// Navigation desktop
// -------------------

const desktop = window.matchMedia('(min-width: 1200px)');

if (desktop.matches) {
    document.querySelectorAll('[data-mobile]').forEach(el => el.classList.remove('hidden'));
}
