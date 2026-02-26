<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<header class="hero-wrapper" style="background-image: url('https://images.unsplash.com/photo-1577106263724-2c8e03bfe9cf?w=1600&q=80');">
    <div class="hero-overlay"></div>
    <div class="container hero-content">
        <h1 class="hero-title">Our Chefs</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active">Chefs</li>
            </ol>
        </nav>
    </div>
</header>

<section class="py-5">
    <div class="container">
        <div class="row g-4 text-center">
            
            <!-- Chef 1 -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <img src="https://images.unsplash.com/photo-1583394293214-28ded15ee548?w=500&q=80" class="card-img-top " style="width:416px; height:624px;" alt="Chef" loading="lazy">

                    <div class="card-body">
                        <h4 class="card-title">John Doe</h4>
                        <p class="text-gold">Head Chef</p>
                    </div>
                </div>
            </div>

            <!-- Chef 2 -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <img src="https://images.unsplash.com/photo-1566554273541-37a9ca77b91f?w=500&q=80" class="card-img-top" alt="Chef" loading="lazy">
                    <div class="card-body">
                        <h4 class="card-title">Sarah Smith</h4>
                        <p class="text-gold">Pastry Chef</p>
                    </div>
                </div>
            </div>

            <!-- Chef 3 -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <img src="https://images.unsplash.com/photo-1581299894007-aaa50297cf16?w=500&q=80" class="card-img-top" alt="Chef" loading="lazy">
                    <div class="card-body">
                        <h4 class="card-title">Michael Lee</h4>
                        <p class="text-gold">Sous Chef</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>