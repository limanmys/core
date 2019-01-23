<div class="loading" style="visibility: hidden">
    <div class="wrapper">

    </div>
    <div class="loader"></div>
    <style>
        .wrapper {
            top: 0;
            left: 0;
            position: absolute;
            width: 100%;
            height: 100%;
            background-color: rgba(201, 76, 76, 0.5);
            filter: grayscale(1) blur(1.5rem);
            -webkit-filter: grayscale(1) blur(1.5rem);
            z-index: 999989;
        }

        .loader {
            border: 16px solid #f3f3f3; /* Light grey */
            border-top: 16px solid #3498db; /* Blue */
            border-radius: 50%;
            width: 20em;
            height: 20em;
            animation: spin 2s linear infinite;
            position: absolute;
            left: 40%;
            top: 40%;
            z-index: 999999;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</div>