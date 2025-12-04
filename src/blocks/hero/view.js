/**
 * Hero Block - Frontend JavaScript
 * 
 * This script runs ONLY on the frontend (not in the editor).
 * Use this for animations, interactions, or dynamic behavior.
 */

// Import frontend styles for dev mode (Vite injects CSS via JS during HMR)
// In production, CSS is extracted and registered separately via PHP
import './style.scss';

document.addEventListener('DOMContentLoaded', () => {
    const heroSections = document.querySelectorAll('.hero-section');
    
    heroSections.forEach((hero) => {
        // Example: Add fade-in animation on scroll
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.1 }
        );
        
        observer.observe(hero);
    });
});
