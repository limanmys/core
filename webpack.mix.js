const mix = require('laravel-mix');
mix.combine(['resources/assets/css/*.css'], 'public/css/liman.css');
mix.combine(['resources/assets/js/*.js'], 'public/js/liman.js');
