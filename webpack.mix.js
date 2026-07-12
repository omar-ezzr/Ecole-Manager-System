const mix = require('laravel-mix');

mix.js('resources/js/app.js', 'public/js')
   .autoload({
    chart: ['_']
   })
   .setPublicPath('public');
// webpack.mix.js
// Compile component-scoped Tailwind CSS
mix.postCss('resources/css/component.css', 'public/css', [
    require('postcss-import'),
    require('tailwindcss')(require('./tailwind.component.config.js')), // Component config
  ]);