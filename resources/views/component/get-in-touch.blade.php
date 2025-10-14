<div class="container bg-gradient">
    <img src="{{ asset('assets/images/notified-box1.png') }}" alt="" class="notified-1">
    <div class="row gx-3 gy-4">
        <div class="col-md-7 mx-auto">
            <div class="text-center text-white">
                <div class="d-flex gap-3 align-items-center mb-2 justify-content-center">
                    <img src="{{ asset('assets/images/icon2.png') }}" alt="icon" class="img-fluid" />
                    <p>Get In Touch</p>
                </div>
                <div class="how-it-works-text mb-2">
                    Be the first to glide.
                    Get notified when we launch.
                </div>
            </div>
        </div>
        <!-- Input with button -->
        <div class="col-md-7 mx-auto">
            <div class="input-wrapper">
                <input class="effect-2" style="background: none" type="text" placeholder="Enter your email" />
                <button class="subscribe-btn">Subscribe <img src="{{ asset('assets/images/buttonimg3.png') }}" alt="">
                </button>
                <span class="focus-border"></span>
            </div>
        </div>
    </div>
    <img src="{{asset('assets/images/notified-box2.png')}}" alt="" class="notified-2">
</div>

<style>
    .bg-gradient {
        background: linear-gradient(258.36deg, #FFDA79 -3.8%, #3E4AF1 36.51%, #7F1CED 75.51%, #FFDA79 105.52%) !important;
        border-radius: 2rem;
        position: relative;
        top: -15rem;
        padding: 4rem;
    }

    .notified-1 {
        position: absolute;
        top: -5rem;
        left: -5rem;
        height: 200px;
        width: auto;
        z-index: 2;
        animation: floatLeftRight 6s ease-in-out infinite;
    }

    .notified-2 {
        position: absolute;
        bottom: -3rem;
        right: -4rem;
        height: 150px;
        width: auto;
        animation: floatRightLeft 6s ease-in-out infinite;
    }

    @keyframes floatLeftRight {
        0% {
            transform: translateX(0) rotate(0deg);
        }

        50% {
            transform: translateX(20px) rotate(5deg);
        }

        100% {
            transform: translateX(0) rotate(0deg);
        }
    }

    @keyframes floatRightLeft {
        0% {
            transform: translateX(0) rotate(0deg);
        }

        50% {
            transform: translateX(-20px) rotate(-5deg);
        }

        100% {
            transform: translateX(0) rotate(0deg);
        }
    }

    :focus {
        outline: none;
    }

    .input-wrapper {
        position: relative;
        width: 100%;
    }

    .bg-gradient input[type="text"] {
        font: 15px/24px "Lato", Arial, sans-serif;
        color: #FFF;
        width: 100%;
        padding: 12px 120px 12px 12px;
        box-sizing: border-box;
        background: none;
        border: 0;
        border-bottom: 1px solid #ccc;
    }

    .bg-gradient input[type="text"]::placeholder {
        color: #FFF;
        opacity: 0.7;
    }


    .subscribe-btn {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        height: auto;
        padding: 6px 14px;
        border: none;
        border-radius: 6px;
        background: transparent;
        color: #fff;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s ease;
        white-space: nowrap;
    }

    .subscribe-btn:hover {
        background: rgba(255, 255, 255, 0.1);
    }

    .effect-2~.focus-border {
        position: absolute;
        bottom: 0;
        left: 50%;
        width: 0;
        height: 2px;
        background-color: #FFDA79;
        transition: 0.4s;
    }

    .effect-2:focus~.focus-border {
        width: 100%;
        left: 0;
    }

    @media (max-width: 768px) {
        .subscribe-btn {
            font-size: 12px;
            padding: 5px 10px;
            right: 5px;
        }

        input[type="text"] {
            padding: 10px 100px 10px 10px;
            font-size: 14px;
        }

        .notified-1,
        .notified-2 {
            display: none;
        }

        .bg-gradient {
            top: 0rem;
        }

    }

    @media (max-width: 480px) {
        .subscribe-btn {
            font-size: 11px;
            padding: 4px 8px;
        }

        input[type="text"] {
            padding: 8px 90px 8px 8px;
            font-size: 13px;
        }
    }
</style>