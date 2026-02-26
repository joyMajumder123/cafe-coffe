// Menu Filtering Functionality
document.addEventListener('DOMContentLoaded', function() {
    const filterButtons = document.querySelectorAll('.btn-filter');
    const menuItems = document.querySelectorAll('.menu-item');

    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const filter = this.getAttribute('data-filter');

            // Remove active class from all buttons
            filterButtons.forEach(btn => btn.classList.remove('active'));

            // Add active class to clicked button
            this.classList.add('active');

            // Filter menu items
            menuItems.forEach(item => {
                const category = item.getAttribute('data-category');
                if (filter === 'all' || category === filter) {
                    item.classList.remove('d-none');
                } else {
                    item.classList.add('d-none');
                }
            });
        });
    });
});

// Sticky navbar on scroll
window.addEventListener('scroll', function() {
    const navbar = document.querySelector('.navbar');
    if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
});

// Carousel animations (if present)
(function () {
    var myCarousel = document.getElementById('heroCarousel');
    if (!myCarousel) {
        return;
    }

    myCarousel.addEventListener('slide.bs.carousel', function () {
        var animatedElements = document.querySelectorAll('.animate-up');
        animatedElements.forEach(function (el) {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
        });
    });

    myCarousel.addEventListener('slid.bs.carousel', function () {
        var activeSlide = document.querySelector('.carousel-item.active');
        if (!activeSlide) {
            return;
        }
        var animatedElements = activeSlide.querySelectorAll('.animate-up');
        animatedElements.forEach(function (el) {
            el.style.opacity = '1';
            el.style.transform = 'translateY(0)';
        });
    });
})();
