# User Page Design System - Quick Reference Guide

## Setup a New User Page (5 Steps)

### Step 1: Session & Includes
```php
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (empty($_SESSION['customer_id'])) {
    header('Location: customer_login.php?redirect=new_page.php');
    exit();
}

// Include the unified layout
include 'admin/includes/db.php';
include 'includes/user_layout.php';

// Your PHP logic here
$customer_id = (int) $_SESSION['customer_id'];
?>
```

### Step 2: Main Section
```html
<section class="py-5 user-page">
    <div class="container">
        <div class="row g-4">
            <!-- Cards go here -->
        </div>
    </div>
</section>
```

### Step 3: Card Components
```html
<div class="card user-card">
    <div class="card-header user-card-header">
        <h5 class="mb-0">Card Title</h5>
        <span class="text-gold small">Subtitle or description</span>
    </div>
    <div class="card-body">
        <!-- Your content -->
    </div>
</div>
```

### Step 4: Forms (if needed)
```html
<div class="mb-3">
    <label class="form-label">Label Text</label>
    <input type="text" class="form-control" name="field_name">
</div>
```

### Step 5: Footer
```php
<?php include 'includes/footer.php'; ?>
```

---

## CSS Classes Reference

### Layout & Containers
| Class | Purpose |
|-------|---------|
| `.user-page` | Main page wrapper with background gradient |
| `.container` | Bootstrap container for content |
| `.row g-4` | Bootstrap row with 4px gap between columns |

### Cards
| Class | Purpose |
|-------|---------|
| `.card` | Bootstrap card base |
| `.user-card` | Enhanced card with border, shadow, hover effect |
| `.card-header` | Card header (standard Bootstrap) |
| `.user-card-header` | Custom styled card header |
| `.card-body` | Card content area |

### Buttons
| Class | Purpose | Color |
|-------|---------|-------|
| `.btn-gold` | Primary action button | Gold (#c5a059) |
| `.btn-outline-gold` | Secondary action button | Gold outline |
| `.btn-primary` | Works like btn-gold (styled in CSS) | Gold |
| `.btn-sm` | Small button variants | Various |
| `.w-100` | Full width button | - |

### Forms
| Class | Purpose |
|-------|---------|
| `.form-label` | Styled form labels |
| `.form-control` | Text inputs, textareas |
| `.form-select` | Select dropdowns |
| `.invalid-feedback` | Validation error messages |
| `.is-invalid` | Mark field as invalid |

### Tables
| Class | Purpose |
|-------|---------|
| `.table` | Bootstrap table base |
| `.table-hover` | Row hover effect |
| `.table thead th` | Header styling (auto-styled in user-card) |

### Status Badges
| Class | Color | Use Case |
|-------|-------|----------|
| `.status-pending` | Orange/Gold | Pending orders |
| `.status-confirmed` | Blue | Confirmed orders |
| `.status-ready` | Blue | Ready to deliver |
| `.status-preparing` | Purple | Being prepared |
| `.status-completed` | Green | Completed |
| `.status-cancelled` | Red | Cancelled/Rejected |

### Alerts
| Class | Color | Type |
|-------|-------|------|
| `.alert alert-success` | Green | Success messages |
| `.alert alert-danger` | Red | Error messages |
| `.alert alert-warning` | Orange | Warning messages |

### Other Components
| Class | Purpose |
|-------|---------|
| `.text-gold` | Gold text color |
| `.text-muted` | Muted gray text |
| `.summary-line` | Line item in summary |
| `.summary-total` | Total amount (bold, larger) |
| `.order-detail` | Collapsible order detail box |
| `.detail-label` | Label in detail section |
| `.detail-value` | Value in detail section |
| `.checkmark-wrap` | Success animation wrapper |
| `.checkmark` | Success checkmark icon |

---

## Common Patterns

### Alert Messages
```html
<div class="alert alert-success">Success message here</div>
<div class="alert alert-danger">Error message here</div>
<div class="alert alert-warning">Warning message here</div>
```

### Status Display
```html
<span class="badge status-badge status-pending">Pending</span>
<span class="badge status-badge status-completed">Completed</span>
```

### Summary Section
```html
<div class="summary-line d-flex justify-content-between">
    <span>Label</span>
    <span>$123.45</span>
</div>

<div class="d-flex justify-content-between border-top pt-3 mt-3 summary-total">
    <strong>Total</strong>
    <strong>$456.78</strong>
</div>
```

### Collapsible Detail
```html
<button class="btn btn-sm btn-outline-gold" 
        type="button" 
        data-bs-toggle="collapse" 
        data-bs-target="#detail-id">
    View Details
</button>

<tr class="collapse" id="detail-id">
    <td colspan="6">
        <div class="order-detail">
            <!-- Details here -->
        </div>
    </td>
</tr>
```

### Form with Validation
```html
<form id="my-form" novalidate>
    <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" class="form-control" name="email" required>
        <div class="invalid-feedback">Please enter valid email</div>
    </div>
    <button type="submit" class="btn btn-gold w-100">Submit</button>
</form>
```

---

## Color Variables (CSS)

```css
--user-bg-gradient       /* Page background */
--user-gold              /* #c5a059 - Main color */
--user-gold-light        /* Lighter gold for borders */
--user-gold-lighter      /* For backgrounds */
--user-header-bg         /* #1f1f1f - Dark header */
--user-header-color      /* #fff - White text */
--user-card-radius       /* 16px - Card border radius */
```

---

## Responsive Breakpoints

- **Large screens (991px+):** Full sidebar + content layout
- **Medium phones (768-991px):** Stacked layout, adjusted padding
- **Small phones (<576px):** Single column, optimized spacing

---

## File Locations

| File | Purpose |
|------|---------|
| `assets/css/user-design.css` | All user page styles |
| `includes/user_layout.php` | Standard user page setup |
| `includes/header.php` | HTML head with CSS links |
| `includes/navbar.php` | Navigation bar |
| `includes/footer.php` | Footer section |

---

## Example: Complete User Page

```php
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['customer_id'])) {
    header('Location: customer_login.php?redirect=my_page.php');
    exit();
}

include 'admin/includes/db.php';
include 'includes/user_layout.php';

// Your logic here
$customer_id = (int) $_SESSION['customer_id'];
// ... database queries ...
?>

<section class="py-5 user-page">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card user-card">
                    <div class="card-header user-card-header">
                        <h5 class="mb-0">My Section</h5>
                        <span class="text-gold small">Subtitle</span>
                    </div>
                    <div class="card-body">
                        <p>Your content here</p>
                        <button class="btn btn-gold w-100">Action</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
```

---

## Tips & Best Practices

✅ Always use `user-card` class on cards within user pages
✅ Pair with `user-card-header` for consistent headers
✅ Use `.w-100` with buttons for full-width action buttons
✅ Apply `.text-muted` to secondary text
✅ Use `.text-gold` for accent colors
✅ Keep form labels with `.form-label` class
✅ Use `.summary-line` for list-like data
✅ Use status badges with appropriate status class
✅ Wrap alerts in `.alert` + type class (success/danger/warning)
✅ Use `.row g-4` for consistent spacing between columns

---

## Future Improvements

- Add sidebar navigation for user dashboard
- Create reusable component includes (cards, forms)
- Add loading states and spinners
- Expand utility class library
- Create SCSS version for theme variables
