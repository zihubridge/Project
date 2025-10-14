<section class="hero-section py-5 text-white mb-5"
    style="background-image: url('{{ asset('assets/images/bg-image.png') }}');">
    <div class="container">
        <div class="row">
            <div class="col-lg-6">
                <div class="d-flex gap-3 align-items-center mb-2 hero-section-top">
                    <img src="{{ asset('assets/images/icon.png') }}" alt="icon" class="img-fluid" />
                    <p class="">A New Smart Block Chain</p>
                </div>

                <div class="hero-section-text mb-4">
                    Cross-Chain Swaps Made Effortless.
                </div>

                <p class="hero-section-discription mb-4">
                    Swap tokens seamlessly across Stellar, Ripple (XRPL), Solana — <br>
                    and more coming soon.
                </p>

                <div class="d-flex flex-wrap align-items-center gap-3 hero-section-top">
                    <button class="btn btn-primary-custom">
                        <img src="{{ asset('assets/images/button-image.png') }}" alt="icon" class="btn-icon" />
                        Get Early Access
                    </button>
                    <button class="btn btn-secondary-custom">
                        <img src="{{ asset('assets/images/button-image2.png') }}" alt="icon" class="btn-icon" />
                        Join Community
                    </button>
                </div>
            </div>

            <div class="col-lg-6 text-end d-none-responsive">
                <div class="d-flex justify-content-end">
                    <div id="lottie-container" class="animated-image"></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Include Lottie Web Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const lottieContainer = document.getElementById('lottie-container');

        if (lottieContainer) {
            lottie.loadAnimation({
                container: lottieContainer,
                renderer: 'svg',
                loop: true,
                autoplay: true,
                path: '{{ asset('assets/json/hero.json') }}',
            });
        }
    });

</script>