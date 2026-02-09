<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg fixed-top <?php echo ($current_page != 'index.php') ? 'solid-bg' : ''; ?>">
    <div class="container">
        <a class="navbar-brand text-white d-flex align-items-center gap-2" href="index.php">
            <img src="https://html.pixelfit.agency/bistly/assets/images/innerpage/logo/logo-white.png" alt="Brand Logo">
            <i class="fas fa-hat-chef text-gold fs-2"></i> 
            
        </a>
        
        <button class="navbar-toggler bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                
                <!-- Home -->
                
                <li class="nav-item"><a class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>" href="index.php">Home</a></li>
                </li>

                <!-- About -->
                <li class="nav-item"><a class="nav-link <?php echo ($current_page == 'about.php') ? 'active' : ''; ?>" href="about.php">About Us</a></li>

                <!-- Menu  -->
                <li class="nav-item"><a class="nav-link <?php echo ($current_page == 'about.php') ? 'active' : ''; ?>" href="menugrid.php">Menu</a></li>
                      
                <!-- Pages Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo (in_array($current_page, ['chefs.php', 'gallery.php'])) ? 'active' : ''; ?>" href="#" data-bs-toggle="dropdown">Pages</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="chefs.php">Our Chefs</a></li>
                        <li><a class="dropdown-item" href="gallery.php">Gallery</a></li>
                    </ul>
                </li>

                <!-- Contact -->
                <li class="nav-item"><a class="nav-link <?php echo ($current_page == 'contact.php') ? 'active' : ''; ?>" href="contact.php">Contact</a></li>

                <!-- Admin Management Dropdown -->
                <li class="nav-item dropdown ms-lg-3 admin-menu-wrapper">
     <a class="nav-link dropdown-toggle admin-btn" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fas fa-user-shield me-1"></i> Admin
       </a>
      <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="adminDropdown">
        
        <li><a class="dropdown-item" href="admin/dashboard.php"><i class="fas fa-chart-line me-2"></i> Dashboard</a></li>
        <li><a class="dropdown-item" href="admin/menu.php"><i class="fas fa-utensils me-2"></i> Manage Menu</a></li>
        <li><a class="dropdown-item" href="admin/reservation.php"><i class="fas fa-calendar-check me-2"></i> Reservations</a></li>
        <li><a class="dropdown-item" href="admin/our-chefs.php"><i class="fas fa-user-tie me-2"></i> Our Chefs</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item text-danger" href="admin/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
       </ul>
       </li>


 </div>
</nav>