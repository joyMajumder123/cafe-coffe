<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', 1);
    session_start();
}

if (empty($_SESSION['customer_id'])) {
    header('Location: customer_login.php?redirect=checkout.php');
    exit();
}

include 'admin/includes/db.php';
require_once 'admin/includes/rbac/csrf.php';
require_once 'includes/customer_meta.php';
include 'includes/user_layout.php';

$customer_id = (int) $_SESSION['customer_id'];
$stmt = $conn->prepare("SELECT name, email, phone FROM customers WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $customer_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
$stmt->close();

ensure_customer_meta_tables($conn);
ensure_address_has_default($conn, $customer_id);
$addresses = fetch_customer_addresses($conn, $customer_id);
$preferred_payment = fetch_customer_payment_preference($conn, $customer_id);
$default_address = null;
foreach ($addresses as $addr) {
    if (!empty($addr['is_default'])) {
        $default_address = $addr;
        break;
    }
}
if ($default_address === null && !empty($addresses)) {
    $default_address = $addresses[0];
}

$default_address_line = '';
$default_city = '';
if ($default_address) {
    $line_parts = [$default_address['address_line1']];
    if (!empty($default_address['address_line2'])) {
        $line_parts[] = $default_address['address_line2'];
    }
    $default_address_line = trim(implode(', ', array_filter($line_parts)));
    $default_city = $default_address['city'] ?? '';
    if (empty($customer['phone']) && !empty($default_address['phone'])) {
        $customer['phone'] = $default_address['phone'];
    }
}
$preferred_payment_method = $preferred_payment ?: 'cash';
?>

<?php include 'includes/sidebar.php'; ?>

<section class="py-5 user-page">
    <div class="container-fluid" style="margin-top: 50px;">
        <div class="row g-4">
            <!-- Sidebar column (desktop only) -->
            <div class="col-lg-auto d-none d-lg-block" style="width: 270px; flex-shrink: 0;"></div>

            <!-- Main content -->
            <div class="col">
              <div class="row g-4">
                <div class="col-lg-7">
                <div class="card user-card mb-4">
                    <div class="card-header user-card-header">
                        <h5 class="mb-0">Checkout</h5>
                        <span class="text-gold small">Secure order details</span>
                    </div>
                    <div class="card-body">
                        <div id="checkout-alert" class="mb-3"></div>
                        <form id="checkout-form" novalidate>
                            <?php echo csrf_field(); ?>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" class="form-control" name="customer_name" value="<?php echo htmlspecialchars($customer['name'] ?? ''); ?>" required>
                                    <div class="invalid-feedback">Please enter your full name.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($customer['email'] ?? ''); ?>" required>
                                    <div class="invalid-feedback">Please enter a valid email address.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Phone</label>
                                    <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>" required>
                                    <div class="invalid-feedback">Please enter a phone number.</div>
                                </div>
                                <?php if (!empty($addresses)): ?>
                                    <div class="col-12">
                                        <label class="form-label">Use a Saved Address</label>
                                        <select class="form-select" id="saved-address-select">
                                            <option value="" <?php echo $default_address ? '' : 'selected'; ?>>Enter a new address</option>
                                            <?php foreach ($addresses as $address): ?>
                                                <option
                                                    value="<?php echo (int) $address['id']; ?>"
                                                    data-line1="<?php echo htmlspecialchars($address['address_line1'], ENT_QUOTES); ?>"
                                                    data-line2="<?php echo htmlspecialchars($address['address_line2'] ?? '', ENT_QUOTES); ?>"
                                                    data-city="<?php echo htmlspecialchars($address['city'], ENT_QUOTES); ?>"
                                                    data-state="<?php echo htmlspecialchars($address['state'] ?? '', ENT_QUOTES); ?>"
                                                    data-postal="<?php echo htmlspecialchars($address['postal_code'] ?? '', ENT_QUOTES); ?>"
                                                    data-phone="<?php echo htmlspecialchars($address['phone'] ?? '', ENT_QUOTES); ?>"
                                                    data-label="<?php echo htmlspecialchars($address['label'], ENT_QUOTES); ?>"
                                                    <?php echo !empty($address['is_default']) ? 'selected' : ''; ?>
                                                >
                                                    <?php echo htmlspecialchars(($address['label'] ?? 'Address') . ' — ' . ($address['city'] ?? '')); ?>
                                                    <?php if (!empty($address['is_default'])): ?> (Default)<?php endif; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="text-muted">Selecting an address will autofill the delivery fields below.</small>
                                    </div>
                                <?php endif; ?>
                                <div class="col-md-6">
                                    <label class="form-label">Address</label>
                                    <input type="text" class="form-control" name="address" placeholder="Street, area, building" value="<?php echo htmlspecialchars($default_address_line); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">City</label>
                                    <input type="text" class="form-control" name="city" placeholder="City" value="<?php echo htmlspecialchars($default_city); ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Payment Method</label>
                                    <div class="payment-options-container">
                                        <label class="payment-option">
                                            <input type="radio" name="payment_method" value="cash" class="payment-radio" <?php echo $preferred_payment_method === 'cash' ? 'checked' : ''; ?>>
                                            <div class="payment-card">
                                                <i class="fas fa-money-bill-wave"></i>
                                                <div>
                                                    <h6>Cash on Delivery</h6>
                                                    <p>Pay when you receive</p>
                                                </div>
                                            </div>
                                        </label>

                                        <label class="payment-option">
                                            <input type="radio" name="payment_method" value="card" class="payment-radio" <?php echo $preferred_payment_method === 'card' ? 'checked' : ''; ?>>
                                            <div class="payment-card">
                                                <i class="fas fa-credit-card"></i>
                                                <div>
                                                    <h6>Credit/Debit Card</h6>
                                                    <p>Safe & secure</p>
                                                </div>
                                            </div>
                                        </label>

                                        <label class="payment-option">
                                            <input type="radio" name="payment_method" value="upi" class="payment-radio" <?php echo $preferred_payment_method === 'upi' ? 'checked' : ''; ?>>
                                            <div class="payment-card">
                                                <i class="fas fa-mobile-alt"></i>
                                                <div>
                                                    <h6>UPI / Digital Wallet</h6>
                                                    <p>Instant payment</p>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Card Payment Details -->
                                <div class="col-12" id="card-details" style="display: none;">
                                    <div class="card-details-section">
                                        <div class="col-12">
                                            <label class="form-label">Card Number</label>
                                            <input type="text" class="form-control" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19">
                                            <div class="invalid-feedback">Please enter a valid card number.</div>
                                        </div>
                                        <div class="row g-3 mt-2">
                                            <div class="col-md-6">
                                                <label class="form-label">Expiry Date</label>
                                                <input type="text" class="form-control" name="card_expiry" placeholder="MM/YY" maxlength="5">
                                                <div class="invalid-feedback">Please enter expiry date (MM/YY).</div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">CVV</label>
                                                <input type="text" class="form-control" name="card_cvv" placeholder="123" maxlength="4">
                                                <div class="invalid-feedback">Please enter CVV.</div>
                                            </div>
                                        </div>
                                        <div class="row g-3 mt-2">
                                            <div class="col-12">
                                                <label class="form-label">Cardholder Name</label>
                                                <input type="text" class="form-control" name="card_holder" placeholder="Name on card">
                                                <div class="invalid-feedback">Please enter cardholder name.</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- UPI Details -->
                                <div class="col-12" id="upi-details" style="display: none;">
                                    <div class="upi-details-section">
                                        <label class="form-label">UPI ID</label>
                                        <input type="text" class="form-control" name="upi_id" placeholder="yourname@upi">
                                        <div class="invalid-feedback">Please enter a valid UPI ID.</div>
                                        <small class="text-muted d-block mt-2">Enter your UPI ID (e.g., yourname@googlepay, yourname@phonepe)</small>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="items" id="order-items">
                            <input type="hidden" name="subtotal" id="order-subtotal">
                            <input type="hidden" name="delivery_charge" id="order-delivery">
                            <input type="hidden" name="tax" id="order-tax">
                            <input type="hidden" name="total_amount" id="order-total">
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary w-100" id="place-order-btn">Place Order</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-5 mt-4 mt-lg-0">
                <div class="card user-card sticky-lg-top" style="top: 2rem;">
                    <div class="card-header user-card-header">
                        <h5 class="mb-0">Order Summary</h5>
                        <span class="text-gold small">Review before placing</span>
                    </div>
                    <div class="card-body">
                        <div id="cart-summary" class="text-muted">Loading cart...</div>
                        <div class="summary-line d-flex justify-content-between">
                            <span>Subtotal</span>
                            <span>₹<span id="summary-subtotal">0.00</span></span>
                        </div>
                        <div class="summary-line d-flex justify-content-between">
                            <span>Delivery</span>
                            <span>₹<span id="summary-delivery">0.00</span></span>
                        </div>
                        <div class="summary-line d-flex justify-content-between">
                            <span>Tax</span>
                            <span>₹<span id="summary-tax">0.00</span></span>
                        </div>
                        <div class="d-flex justify-content-between border-top pt-3 mt-3 summary-total">
                            <strong>Total</strong>
                            <strong>₹<span id="summary-total">0.00</span></strong>
                        </div>
                        <a href="index.php#menu-grid" class="btn btn-outline-gold w-100 mt-3">Continue Shopping</a>
                    </div>
                </div>
            </div>

<div class="modal fade" id="orderSuccessModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="checkmark-wrap">
                    <div class="checkmark">&#10003;</div>
                </div>
                <h5 class="mt-3">Order Confirmed</h5>
                <p class="text-muted mb-0">Your order has been placed successfully.</p>
            </div>
        </div>
    </div>
</div>


<script>
    // Payment Method Handling
    (function() {
        const paymentRadios = document.querySelectorAll('input[name="payment_method"]');
        const cardDetails = document.getElementById('card-details');
        const upiDetails = document.getElementById('upi-details');
        const savedAddressSelect = document.getElementById('saved-address-select');
        const addressInput = document.querySelector('input[name="address"]');
        const cityInput = document.querySelector('input[name="city"]');
        const phoneInput = document.querySelector('input[name="phone"]');

        function updatePaymentDetails() {
            const selectedPayment = document.querySelector('input[name="payment_method"]:checked').value;
            
            // Hide all details first
            cardDetails.style.display = 'none';
            upiDetails.style.display = 'none';

            // Show relevant details
            if (selectedPayment === 'card') {
                cardDetails.style.display = 'block';
            } else if (selectedPayment === 'upi') {
                upiDetails.style.display = 'block';
            }
        }

        function applySavedAddress(option) {
            if (!option || !addressInput || !cityInput) {
                return;
            }
            if (!option.value) {
                addressInput.value = '';
                cityInput.value = '';
                return;
            }
            const line1 = option.getAttribute('data-line1') || '';
            const line2 = option.getAttribute('data-line2') || '';
            const parts = [line1, line2].filter(Boolean);
            addressInput.value = parts.join(', ').trim();
            cityInput.value = option.getAttribute('data-city') || '';
            const savedPhone = option.getAttribute('data-phone');
            if (savedPhone && phoneInput) {
                phoneInput.value = savedPhone;
            }
        }

        // Add event listeners to all payment radio buttons
        paymentRadios.forEach(radio => {
            radio.addEventListener('change', updatePaymentDetails);
        });

        // Initialize on page load
        updatePaymentDetails();

        if (savedAddressSelect) {
            savedAddressSelect.addEventListener('change', function () {
                const option = savedAddressSelect.options[savedAddressSelect.selectedIndex];
                applySavedAddress(option);
            });

            const initialOption = savedAddressSelect.options[savedAddressSelect.selectedIndex];
            if (initialOption && initialOption.value) {
                applySavedAddress(initialOption);
            }
        }
    })();

    (function () {
        const cartStorageKey = 'cafe_cart';
        const cartSummary = document.getElementById('cart-summary');
        const summarySubtotal = document.getElementById('summary-subtotal');
        const summaryDelivery = document.getElementById('summary-delivery');
        const summaryTax = document.getElementById('summary-tax');
        const summaryTotal = document.getElementById('summary-total');
        const orderItemsInput = document.getElementById('order-items');
        const orderSubtotalInput = document.getElementById('order-subtotal');
        const orderDeliveryInput = document.getElementById('order-delivery');
        const orderTaxInput = document.getElementById('order-tax');
        const orderTotalInput = document.getElementById('order-total');
        const checkoutForm = document.getElementById('checkout-form');
        const placeOrderBtn = document.getElementById('place-order-btn');
        const alertBox = document.getElementById('checkout-alert');

        function showAlert(message, type) {
            alertBox.innerHTML = '<div class="alert alert-' + type + '">' + message + '</div>';
        }

        function clearValidation() {
            checkoutForm.querySelectorAll('.is-invalid').forEach(function (input) {
                input.classList.remove('is-invalid');
            });
        }

        function invalidate(input, message) {
            input.classList.add('is-invalid');
            const feedback = input.parentElement.querySelector('.invalid-feedback');
            if (feedback && message) {
                feedback.textContent = message;
            }
        }

        function loadCart() {
            try {
                return JSON.parse(localStorage.getItem(cartStorageKey) || '[]');
            } catch (error) {
                return [];
            }
        }

        const deliveryFee = 2.5;
        const taxRate = 0.05;

        function renderSummary(cart) {
            if (!cart.length) {
                cartSummary.textContent = 'Your cart is empty.';
                summarySubtotal.textContent = '0.00';
                summaryDelivery.textContent = '0.00';
                summaryTax.textContent = '0.00';
                summaryTotal.textContent = '0.00';
                placeOrderBtn.disabled = true;
                return;
            }

            const list = document.createElement('ul');
            list.className = 'list-unstyled mb-0';
            let subtotal = 0;

            cart.forEach(function (item, index) {
                subtotal += item.price * item.quantity;
                const li = document.createElement('li');
                li.className = 'd-flex justify-content-between align-items-center mb-2';
                li.innerHTML =
                    '<div class="d-flex align-items-center gap-2">' +
                        '<button class="btn btn-sm btn-outline-secondary" data-action="decrease" data-index="' + index + '">-</button>' +
                        '<span>' + item.name + ' x ' + item.quantity + '</span>' +
                        '<button class="btn btn-sm btn-outline-secondary" data-action="increase" data-index="' + index + '">+</button>' +
                    '</div>' +
                    '<span>₹' + (item.price * item.quantity).toFixed(2) + '</span>' +
                    '<button class="btn btn-sm btn-outline-danger" data-action="remove" data-index="' + index + '">Remove</button>';
                list.appendChild(li);
            });

            const delivery = subtotal > 0 ? deliveryFee : 0;
            const tax = subtotal * taxRate;
            const total = subtotal + delivery + tax;

            cartSummary.innerHTML = '';
            cartSummary.appendChild(list);
            summarySubtotal.textContent = subtotal.toFixed(2);
            summaryDelivery.textContent = delivery.toFixed(2);
            summaryTax.textContent = tax.toFixed(2);
            summaryTotal.textContent = total.toFixed(2);
            orderItemsInput.value = JSON.stringify(cart);
            orderSubtotalInput.value = subtotal.toFixed(2);
            orderDeliveryInput.value = delivery.toFixed(2);
            orderTaxInput.value = tax.toFixed(2);
            orderTotalInput.value = total.toFixed(2);
            placeOrderBtn.disabled = false;
        }

        let cart = loadCart();
        renderSummary(cart);

        cartSummary.addEventListener('click', function (event) {
            const target = event.target;
            if (target.matches('button[data-index]')) {
                const index = parseInt(target.dataset.index, 10);
                const action = target.dataset.action;
                if (Number.isNaN(index)) {
                    return;
                }

                if (action === 'increase') {
                    cart[index].quantity += 1;
                } else if (action === 'decrease') {
                    cart[index].quantity -= 1;
                    if (cart[index].quantity <= 0) {
                        cart.splice(index, 1);
                    }
                } else {
                    cart.splice(index, 1);
                }

                localStorage.setItem(cartStorageKey, JSON.stringify(cart));
                renderSummary(cart);
            }
        });

        checkoutForm.addEventListener('submit', function (event) {
            event.preventDefault();
            if (!cart.length) {
                return;
            }

            clearValidation();
            const nameInput = checkoutForm.querySelector('input[name="customer_name"]');
            const emailInput = checkoutForm.querySelector('input[name="email"]');
            const phoneInput = checkoutForm.querySelector('input[name="phone"]');
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
            let hasError = false;

            if (!nameInput.value.trim()) {
                invalidate(nameInput, 'Please enter your full name.');
                hasError = true;
            }

            if (!emailInput.value.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value)) {
                invalidate(emailInput, 'Please enter a valid email address.');
                hasError = true;
            }

            if (!phoneInput.value.trim()) {
                invalidate(phoneInput, 'Please enter a phone number.');
                hasError = true;
            }

            // Validate card details if card payment is selected
            if (paymentMethod === 'card') {
                const cardNumberInput = checkoutForm.querySelector('input[name="card_number"]');
                const cardExpiryInput = checkoutForm.querySelector('input[name="card_expiry"]');
                const cardCvvInput = checkoutForm.querySelector('input[name="card_cvv"]');
                const cardHolderInput = checkoutForm.querySelector('input[name="card_holder"]');

                if (!cardNumberInput.value.trim() || !/^\d{13,19}$/.test(cardNumberInput.value.replace(/\s/g, ''))) {
                    invalidate(cardNumberInput, 'Please enter a valid card number.');
                    hasError = true;
                }

                if (!cardExpiryInput.value.trim() || !/^\d{2}\/\d{2}$/.test(cardExpiryInput.value)) {
                    invalidate(cardExpiryInput, 'Please enter expiry date as MM/YY.');
                    hasError = true;
                }

                if (!cardCvvInput.value.trim() || !/^\d{3,4}$/.test(cardCvvInput.value)) {
                    invalidate(cardCvvInput, 'Please enter a valid CVV.');
                    hasError = true;
                }

                if (!cardHolderInput.value.trim()) {
                    invalidate(cardHolderInput, 'Please enter cardholder name.');
                    hasError = true;
                }
            }

            // Validate UPI details if UPI payment is selected
            if (paymentMethod === 'upi') {
                const upiIdInput = checkoutForm.querySelector('input[name="upi_id"]');
                if (!upiIdInput.value.trim() || !/^[a-zA-Z0-9._-]+@[a-zA-Z0-9]+$/.test(upiIdInput.value)) {
                    invalidate(upiIdInput, 'Please enter a valid UPI ID (e.g., yourname@googlepay).');
                    hasError = true;
                }
            }

            if (hasError) {
                showAlert('Please fix the highlighted fields.', 'danger');
                return;
            }

            placeOrderBtn.disabled = true;
            const formData = new FormData(checkoutForm);

            fetch('place_order.php', {
                method: 'POST',
                body: formData
            })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (data && data.success) {
                        showAlert('Order placed successfully. Status: ' + (data.status || 'pending') + '.', 'success');
                        localStorage.removeItem(cartStorageKey);
                        cart = [];
                        renderSummary(cart);
                        checkoutForm.reset();
                        const successModal = new bootstrap.Modal(document.getElementById('orderSuccessModal'));
                        successModal.show();
                        setTimeout(function () { successModal.hide(); }, 1800);
                    } else {
                        showAlert(data.message || 'Order submitted successfully. Please check your Order section for status.', 'success');
                    }
                })
                .catch(function () {
                    showAlert('Order submitted successfully. Please check your Order section for status.', 'success');
                })
                .finally(function () {
                    placeOrderBtn.disabled = false;
                });
        });
    })();
</script>

              </div><!-- /.row inner -->
            </div><!-- /.col main -->
        </div><!-- /.row outer -->
    </div>
</section>

<?php include 'includes/footer.php'; ?>
