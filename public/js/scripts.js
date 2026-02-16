/**
 * Main script
 */

/* --- Constants definition --- */

const mobile = globalThis.matchMedia("(max-width: 1199px)");
const desktop = globalThis.matchMedia("(min-width: 1200px)");

const savedTheme = localStorage.getItem("theme");

const navButton = document.getElementById("nav-button");
const footerButton = document.getElementById("footer-button");
const modeButton = document.getElementById("mode");
const eyeButton = document.getElementById("eye");

const nav = document.getElementById("nav-bar");
const footer = document.querySelectorAll("footer .hidden");
const inputPassword = document.getElementById("password");

/* --- Events listener --- */

modeButton.addEventListener("change", (m) => {
	const value = m.target.value;
	document.body.dataset.theme = value;
	localStorage.setItem("theme", value);
});

if (eyeButton) {
	eyeButton.addEventListener("click", () => {
		if (inputPassword.type === "password") {
			inputPassword.type = "text";
			eyeButton.src = "./img/eye.svg";
			eyeButton.alt = "Masquer le mot de passe";
			eyeButton.setAttribute("aria-label", "Masquer le mot de passe");
		} else {
			inputPassword.type = "password";
			eyeButton.src = "./img/eye-off.svg";
			eyeButton.alt = "Masquer le mot de passe";
			eyeButton.setAttribute("aria-label", "Afficher le mot de passe");
		}
	});
}

if (mobile.matches) {
	navButton.addEventListener("click", () => toggleDisplay(nav));

	footerButton.addEventListener("click", () => {
		footer.forEach((f) => {
			toggleDisplay(f);
			footerButton.classList.toggle("rotate");
		});
	});
}

if (desktop.matches) {
}

/* --- Functions --- */

function toggleDisplay(x) {
	x.classList.toggle("hidden");
}

/* --- On page load --- */

if (savedTheme) {
	document.body.dataset.theme = savedTheme;
	modeButton.value = savedTheme;
}

if (desktop.matches) {
	const hidden = document.querySelectorAll(".hidden");
	hidden.forEach((h) => {
		h.classList.remove("hidden");
	});
}
