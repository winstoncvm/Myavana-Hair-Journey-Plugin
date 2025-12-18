document.addEventListener("DOMContentLoaded", function() {
    // Mobile menu toggle
    document.querySelector('.mobile-menu').addEventListener('click', function() {
        document.querySelector('.nav-links').classList.toggle('active');
    });

    // Animation on scroll
    window.addEventListener('scroll', function() {
        const elements = document.querySelectorAll('.feature-card, .journey-image, .journey-content, .ai-demo');
        
        elements.forEach(element => {
            const elementPosition = element.getBoundingClientRect().top;
            const screenPosition = window.innerHeight / 1.3;
            
            if(elementPosition < screenPosition) {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }
        });
    });

    // Initialize elements with fade-in effect
    document.addEventListener('DOMContentLoaded', function() {
        const elements = document.querySelectorAll('.feature-card, .journey-image, .journey-content, .ai-demo');
        elements.forEach(element => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(20px)';
            element.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        });
        
        // Trigger the hero animation
        setTimeout(() => {
            const heroContent = document.querySelector('.hero-content');
            heroContent.style.opacity = '1';
            heroContent.style.transform = 'translateY(0)';
        }, 300);
    });
});