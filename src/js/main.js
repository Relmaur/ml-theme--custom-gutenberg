// src/js/main.js

// Import CSS here so Vite processes it and injects it (in dev) or extracts it (in prod)
import '../scss/main.scss';

console.log('Rigid Hybrid Theme Loaded');

// Global interactions (e.g., Mobile Menu toggle) go here
document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.querySelector('.menu-toggle');
    if (menuToggle) {
        menuToggle.addEventListener('click', () => {
            document.body.classList.toggle('menu-open');
        });
    }
});