
@if(isset($show))
    <div class="loading">
@else
    <div class="loading" hidden>
@endif

    <div class="loader"></div><br>
    <span class="loading_message"></span>
    <style>
        .loader {
            border: 16px solid #f3f3f3; /* Light grey */
            border-top: 16px solid #3498db; /* Blue */
            border-radius: 50%;
            width: 100px;
            height: 100px;
            animation: spin 2s linear infinite;
            position: absolute;
            left: 50%;
            top : 30%;
        }

        .loading_message{
            margin-left: auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</div>