const mix = require('laravel-mix');
mix.styles([
    'resources/assets/css/bootstrap.css',
    'resources/assets/css/font-awesome.css',
    'resources/assets/css/AdminLTE.css',
    'resources/assets/css/jquery.contextMenu.css',
    'resources/assets/css/jquery-ui.css',
    'resources/assets/css/themes/*.css',
    'resources/assets/css/jstree.css',
    'resources/assets/css/bootstrap-datepicker.css',
    'resources/assets/css/jquery.dataTables.css',
    'resources/assets/css/select.dataTables.css',
    'resources/assets/css/sweetalert2.min.css',
], 'public/css/liman.css').version();
mix.combine([
    'resources/assets/js/jquery.js',
    'resources/assets/js/jquery-ui.js',
    'resources/assets/js/jquery.contextMenu.js',
    'resources/assets/js/bootstrap.js',
    'resources/assets/js/bootstrap-datepicker.js',
    'resources/assets/js/jquery.dataTables.js',
    'resources/assets/js/dataTables.bootstrap.js',
    'resources/assets/js/select.dataTables.js',
    'resources/assets/js/adminlte.js',
    'resources/assets/js/sweetalert2.min.js',
    'resources/assets/js/Chart.js',
    'resources/assets/js/jstree.js',
    'resources/assets/js/liman.js',
], 'public/js/liman.js').version();
