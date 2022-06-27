const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel applications. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */

const Public = 'public/';
mix.js('resources/js/app.js', 'public/js')
    .postCss('resources/css/app.css', 'public/css', [
        require('postcss-import'),
        require('tailwindcss'),
    ])
    .combine(
        [
            Public + 'assets/js/jquery-3.6.0.min.js',
            Public + 'assets/js/script.js'
        ], Public + 'js/guest.js')
    .combine(
        [
            Public + 'assets/js/bootstrap.bundle.min.js',
            Public + 'assets/js/offcanvas.js',
        ], Public + 'js/main.js')
    .styles(
        [
            Public + 'assets/css/main.css',
            Public + 'assets/css/join.css',
        ], Public + 'css/main.css')
    .styles(
        [
            Public + 'assets/css/signin.css',
            Public + 'assets/css/join.css',
        ], Public + 'css/guest.css')
    .version();
