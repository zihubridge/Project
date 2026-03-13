<nav class="navbar navbar-expand-lg navbar-light py-2">
    <div class="container">
        <!-- Logo -->
        <a class="navbar-brand" href="#">
            <img src="{{ asset('assets/images/logo2.png') }}" alt="MyLogo" height="70" />
        </a>

        <!-- Hamburger -->
        <button class="navbar-toggler" type="button" id="navbarToggle">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Links -->
        <div class="collapse navbar-collapse" id="navbarMenu">
            <ul class="navbar-nav text-center mx-auto">
                <li class="nav-item my-1"><a class="nav-link" href="#">How It Works</a></li>
                <li class="nav-item my-1"><a class="nav-link" href="#">Why ZihuBridge</a></li>
                <li class="nav-item my-1"><a class="nav-link" href="#">Roadmap</a></li>
                <li class="nav-item my-1"><a class="nav-link" href="#">Community</a></li>
            </ul>
            <div class="d-flex justify-content-center my-1">
                <a href="#" class="btn btn-primary-custom d-flex align-items-center">
                    <img src="{{ asset('assets/images/button-image.png') }}" alt="icon" class="btn-icon me-2" />
                    Contact Us
                </a>
            </div>
        </div>
    </div>
</nav>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const toggleButton = document.getElementById("navbarToggle");
        const navbarMenu = document.getElementById("navbarMenu");

        toggleButton.addEventListener("click", function () {
            navbarMenu.classList.toggle("show");
        });
    });
</script>