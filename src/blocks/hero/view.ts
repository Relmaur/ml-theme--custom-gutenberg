/**
 * Hero Block - Frontend JavaScript
 * 
 * This script runs ONLY on the frontend (not in the editor).
 * Use this for animations, interactions, or dynamic behavior.
 */
document.addEventListener('DOMContentLoaded', (): void => {
    const heroSections = document.querySelectorAll('.hero-section') as NodeListOf<HTMLElement>;

    heroSections.forEach((hero): void => {
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
        ) as IntersectionObserver;

        observer.observe(hero);
    });
});
