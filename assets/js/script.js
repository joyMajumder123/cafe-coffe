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

    // Cart functionality
    let cart = [];
    const cartItemsDiv = document.getElementById('cart-items');
    const cartTotalDiv = document.getElementById('cart-total');
    const placeOrderBtn = document.getElementById('place-order-btn');
    const orderModal = new bootstrap.Modal(document.getElementById('orderModal'));
    const orderForm = document.getElementById('order-form');

    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function() {
            const name = this.getAttribute('data-name');
            const price = parseFloat(this.getAttribute('data-price'));
            addToCart(name, price);
        });
    });

    function addToCart(name, price) {
        const existingItem = cart.find(item => item.name === name);
        if (existingItem) {
            existingItem.quantity++;
        } else {
            cart.push({ name, price, quantity: 1 });
        }
        updateCartDisplay();
    }

    function updateCartDisplay() {
        cartItemsDiv.innerHTML = '';
        let total = 0;
        cart.forEach(item => {
            const itemTotal = item.price * item.quantity;
            total += itemTotal;
            cartItemsDiv.innerHTML += `<p>${item.name} x${item.quantity} - $${itemTotal.toFixed(2)}</p>`;
        });
        cartTotalDiv.innerHTML = `<h4>Total: $${total.toFixed(2)}</h4>`;
        if (cart.length > 0) {
            placeOrderBtn.style.display = 'block';
        } else {
            placeOrderBtn.style.display = 'none';
        }
    }

    placeOrderBtn.addEventListener('click', function() {
        const items = cart.map(item => `${item.name} x${item.quantity}`).join(', ');
        const total = cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
        document.getElementById('order-items').value = items;
        document.getElementById('order-total').value = total.toFixed(2);
        orderModal.show();
    });

    orderForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('place_order.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            alert(data);
            cart = [];
            updateCartDisplay();
            orderModal.hide();
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

var myCarousel = document.getElementById('heroCarousel')

myCarousel.addEventListener('slide.bs.carousel', function () {
    // Reset animations on all elements
    const animatedElements = document.querySelectorAll('.animate-up');
    animatedElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
    });
})

myCarousel.addEventListener('slid.bs.carousel', function () {
    // Trigger animations on the new active slide
    const activeSlide = document.querySelector('.carousel-item.active');
    const animatedElements = activeSlide.querySelectorAll('.animate-up');
    animatedElements.forEach(el => {
        el.style.opacity = '1';
        el.style.transform = 'translateY(0)';
    });
})
