/**
 * Main script
 */

/* --- Constants definition --- */

const navButton = document.getElementById("nav-button");
const footerButton = document.getElementById("footer-button");
const modeButton = document.getElementById("mode");

const nav = document.getElementById("nav-bar");
const footer = document.querySelectorAll("footer .hidden");

/* --- Events listener --- */

modeButton.addEventListener("change", (m) => {
	const value = m.target.value;
	document.body.dataset.theme = value;
	localStorage.setItem("theme", value);
});

navButton.addEventListener("click", () => toggleDisplay(nav));

footerButton.addEventListener("click", () => {
	footer.forEach((f) => {
		toggleDisplay(f);
		footerButton.classList.toggle("rotate");
	});
});

/* --- Functions --- */

function toggleDisplay(x) {
	x.classList.toggle("hidden");
}

/* --- On page load --- */

const savedTheme = localStorage.getItem("theme");
if (savedTheme) {
	document.body.dataset.theme = savedTheme;
	modeButton.value = savedTheme;
}
