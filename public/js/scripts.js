const mobile = globalThis.matchMedia('(max-width: 1199px)');
const desktop = globalThis.matchMedia('(min-width: 1200px)');

const modeButton = document.getElementById('mode');
const savedTheme = localStorage.getItem('theme');
const navButton = document.getElementById('nav-button');
const nav = document.getElementById('nav-bar');
const eyeButton = document.getElementById('eye');
const inputPassword = document.getElementById('password');
const footerButton = document.getElementById('footer-button');
const footer = document.querySelectorAll('footer .hidden');

/* --- Events --- */

modeButton?.addEventListener('change', (e) => {
    const value = e.target.value;
    document.body.dataset.theme = value;
    localStorage.setItem('theme', value);
});

eyeButton?.addEventListener('click', () => {
    if (inputPassword.type === 'password') {
        inputPassword.type = 'text';
        eyeButton.src = './img/eye.svg';
        eyeButton.alt = 'Masquer le mot de passe';
        eyeButton.setAttribute('aria-label', 'Masquer le mot de passe');
    } else {
        inputPassword.type = 'password';
        eyeButton.src = './img/eye-off.svg';
        eyeButton.alt = 'Afficher le mot de passe';
        eyeButton.setAttribute('aria-label', 'Afficher le mot de passe');
    }
});

if (mobile.matches) {
    navButton?.addEventListener('click', () => nav?.classList.toggle('hidden'));

    footerButton?.addEventListener('click', () => {
        footer.forEach(f => f.classList.toggle('hidden'));
        footerButton.classList.toggle('rotate');
    });

    document.getElementById('menu-button')?.addEventListener('click', () => {
        document.getElementById('menu-filter')?.classList.toggle('hidden');
    });
}

/* --- On load --- */

if (savedTheme) {
    document.body.dataset.theme = savedTheme;
    if (modeButton) modeButton.value = savedTheme;
}

if (desktop.matches) {
    document.querySelectorAll('.hidden').forEach(el => el.classList.remove('hidden'));
}
