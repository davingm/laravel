{{--
    <x-welcomepage>

    Animated Laravel starter page with a WebGL particle mountain range.
    Delete this component when you are ready to build your own page.

    Requires davingm-welcome to be installed and loaded:
        npm install davingm-welcome
    Then in your entry JS:
        import 'davingm-welcome';
    Or load the pre-built script:
        <script type="module" src="/vendor/davingm-welcome/welcomepage.js"></script>

    @package davingm-welcome
--}}
<div data-welcomepage>
    <canvas data-welcomepage-canvas aria-hidden="true"></canvas>

    <div data-welcomepage-slot data-welcomepage-content>
        <div data-welcomepage-logo aria-label="Laravel">
            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                 width="128" height="133" viewBox="0 0 128 128" aria-hidden="true">
                <path fill="#f05340" fill-rule="nonzero"
                      d="M26.027 0.137C25.824 0.215 20.086 3.484 13.281 7.395C5.035 12.137 0.801 14.633 0.574 14.867C0.395 15.07 0.191 15.422 0.129 15.645C-0.043 16.219-0.055 100.348 0.117 100.953C0.18 101.188 0.383 101.531 0.566 101.723C1.012 102.191 50.238 130.496 50.836 130.633C51.113 130.699 51.426 130.688 51.734 130.602C52.406 130.434 101.504 102.191 101.941 101.73C102.121 101.531 102.324 101.188 102.391 100.953C102.477 100.676 102.508 96.277 102.508 87.016L102.508 73.48L114.477 66.605C125.762 60.117 126.453 59.711 126.742 59.266L127.039 58.785L127.039 44.207C127.039 28.336 127.07 29.23 126.441 28.656C126.273 28.508 120.523 25.152 113.652 21.195L101.172 14.016L99.785 14.016L87.574 21.027C80.852 24.895 75.137 28.199 74.859 28.379C74.582 28.563 74.25 28.902 74.113 29.148L73.867 29.574L73.813 43.309L73.762 57.047L63.68 62.855C58.133 66.043 53.516 68.684 53.418 68.707C53.238 68.758 53.227 67.449 53.227 42.203L53.227 15.633L52.961 15.176C52.629 14.621 54.121 15.508 39.137 6.895C26.57-0.332 26.871-0.18 26.027 0.137Z"/>
            </svg>
        </div>

        <h1 data-welcomepage-title>
            A clean start<br>for your next idea.
        </h1>

        <p data-welcomepage-description>
            Your application is ready. Make it yours.
        </p>

        <a data-welcomepage-link href="https://laravel.com/docs" target="_blank" rel="noreferrer">
            Read the documentation
            <span data-welcomepage-arrow aria-hidden="true">&#x2197;</span>
        </a>
    </div>
</div>
