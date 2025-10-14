<footer class="footer">
    <div class="container">
        <div class="row">
            <!-- Left Column -->
            <div class="col-md-5 mb-4">
                <div class="footer-card p-3">
                    <div class="d-flex align-items-center mb-3">
                        <img src="{{ asset('assets/images/logo2.png') }}" alt="logo" class="footer-logo me-2" />
                        <h5 class="text-white mb-0">ZihuBridge</h5>
                    </div>
                    <p class="footer-text">
                        This is amazing individuals around the globe, find a mentor, expand your network
                        and learn from incredible people.
                    </p>
                    <div class="input-wrapper mt-3 d-flex">
                        <input class="effect-2 asd form-control me-2" type="text" placeholder="Enter your email" />
                        <a href="#" class="btn btn-primary-custom d-flex align-items-center text-nowrap">
                            <img src="{{ asset('assets/images/button-image.png') }}" alt="icon" class="btn-icon me-2" />
                            Contact Us
                        </a>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-md-7 mb-4">
                <div class="footer-card p-4 d-flex flex-wrap justify-content-between">
                    <div>
                        <h6 class="text-white">Company</h6>
                        <ul class="footer-links">
                            <li><a href="#">Home</a></li>
                            <li><a href="#">About</a></li>
                            <li><a href="#">Contact</a></li>
                            <li><a href="#">Blog</a></li>
                        </ul>
                    </div>
                    <div>
                        <h6 class="text-white">Feature</h6>
                        <ul class="footer-links">
                            <li><a href="#">Price</a></li>
                            <li><a href="#">Feature</a></li>
                            <li><a href="#">Team</a></li>
                            <li><a href="#">Token</a></li>
                        </ul>
                    </div>
                    <div>
                        <h6 class="text-white">Utility Pages</h6>
                        <ul class="footer-links">
                            <li><a href="#">Licenses</a></li>
                            <li><a href="#">Change log</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Bottom -->
            <div class="col-12">
                <div
                    class="footer-bottom text-center text-md-between d-flex flex-column flex-md-row justify-content-between align-items-center p-3">
                    <div>
                        <button id="scrollToTopBtn" class="btn btn-primary-custom-2">
                            <i class="bx bx-chevrons-up"></i>
                        </button>
                    </div>
                    <p class="mb-2 mb-md-0 text-white small">
                        Copyright © <span class="color-purple">ZihuBridge</span>
                    </p>
                    <div>
                        <a href="#" class="footer-bottom-link">Terms & Condition</a>
                        <a href="#" class="footer-bottom-link">Privacy Policy</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const scrollToTopBtn = document.getElementById('scrollToTopBtn');
        if (scrollToTopBtn) {
            scrollToTopBtn.addEventListener('click', function () {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        }
    });
</script>

<style>
    html {
        scroll-behavior: smooth;
    }

    .footer {
        padding: 3rem 0;
        color: #fff;
    }

    .footer-card {
        background: rgba(19, 19, 27, 1);
        border-radius: 10px;
        box-shadow:
            0px 0.82px 0.82px rgba(255, 255, 255, 0.16) inset,
            0px -0.82px 0.82px rgba(0, 0, 0, 0.2) inset;
    }

    .footer-logo {
        height: 40px;
    }

    .footer-text {
        color: #bbb;
        font-size: 14px;
        line-height: 1.6;
    }

    .input-wrapper {
        position: relative;
        width: 100%;
    }

    .footer input[type="text"],
    .footer input[type="text"]:focus,
    .footer input[type="text"]:active {
        background: rgb(0, 0, 0) !important;
        color: #fff !important;
        border: 0 !important;
        outline: none !important;
        box-shadow: none !important;
        padding: .75rem;
    }

    .footer input::placeholder {
        color: #aaa !important;
        opacity: 0.8;
    }

    .footer-links {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .footer-links li {
        margin-bottom: 6px;
    }

    .footer-links a {
        text-decoration: none;
        color: #bbb;
        font-size: 14px;
    }

    .footer-links a:hover {
        color: #fff;
    }

    .footer-bottom {
        background: rgba(19, 19, 27, 1);
    }

    .footer-bottom-link {
        margin-left: 15px;
        font-size: 13px;
        color: #bbb;
        text-decoration: none;
    }

    .footer-bottom-link:hover {
        color: #fff;
    }

    .color-purple {
        color: rgba(254, 184, 255, 1);
    }

    .btn-primary-custom-2 {
        background: linear-gradient(180deg, #0066FF 0%, #003A92 100%);
        padding: .5rem;
        display: flex;
        justify-content: center;
    }

    .btn-primary-custom-2 i {
        font: 22px;
        color: #FFFF;
    }

    @media (max-width: 768px) {

        .footer-card {
            text-align: center;
        }

        .footer-links {
            margin-top: 1rem;
        }

        input[type="text"] {
            padding: 10px 12px;
        }
    }
</style>