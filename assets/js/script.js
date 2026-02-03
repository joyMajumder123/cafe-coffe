document.addEventListener('DOMContentLoaded', function() {
    
    // Navbar Scroll Effect
    const navbar = document.querySelector('.navbar');
    
    function checkScroll() {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            // Only remove solid bg if it's NOT a page that forces solid bg (like contact.php)
            if(!navbar.classList.contains('solid-bg')) {
                navbar.classList.remove('scrolled');
            }
        }
    }
    // Function to handle scroll
    function handleScroll() {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            // Only remove the class if we aren't on a page that requires a solid background
            if (!navbar.classList.contains('solid-bg')) {
                navbar.classList.remove('scrolled');
            }
        }
    }

    // Listen for scroll events
    window.addEventListener('scroll', handleScroll);
    window.addEventListener('scroll', checkScroll);
    checkScroll(); // Run on load

    // Menu Filter Logic (Only runs if elements exist)
    const filterBtns = document.querySelectorAll('.btn-filter');
    const menuItems = document.querySelectorAll('.menu-item');

    if(filterBtns.length > 0) {
        filterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                
                filterBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                const filterValue = this.getAttribute('data-filter');

                menuItems.forEach(item => {
                    if (filterValue === 'all' || item.getAttribute('data-category') === filterValue) {
                        item.classList.remove('hide');
                        item.classList.add('show');
                    } else {
                        item.classList.remove('show');
                        item.classList.add('hide');
                    }
                });
            });
        });
    }
});

    
    