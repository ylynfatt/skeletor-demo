<?php

use NiftyCo\Skeletor\Skeletor;

return function (Skeletor $skeletor) {
    if ($skeletor->confirm('Would you like to install Inertia JS?', true)) {
        $skeletor->spin(
            message: 'Installing Inertia',
            success: 'Inertia successfully installed.',
            error: 'Failed to install Inertia.',
            callback: function () use ($skeletor) {
                $skeletor->exec(['composer', 'require', 'inertiajs/inertia-laravel']);
                $content = <<<HTML
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="utf-8" />
                    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
                    @vite('resources/js/app.js')
                    @inertiaHead
                </head>
                <body>
                    @inertia
                </body>
                </html>
                HTML;

                $skeletor->writeFile('resources/views/app.blade.php', $content);

                $skeletor->exec(['php', 'artisan', 'inertia:middleware']);

                $imports = <<<PHP
                use Illuminate\Foundation\Configuration\Middleware;
                use App\Http\Middleware\HandleInertiaRequests;
                PHP;

                $skeletor->replaceInFile('use Illuminate\Foundation\Configuration\Middleware;', $imports, 'bootstrap/app.php');
                $skeletor->replaceInFile('    ->withMiddleware(function (Middleware $middleware) {
        //
    })', '->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        HandleInertiaRequests::class,
    ]);
})', 'bootstrap/app.php');

                $skeletor->exec(['npm', 'install', '@inertiajs/vue3']);

                $inertiaBootScript = <<<JS
                import { createApp, h } from 'vue'
                import { createInertiaApp } from '@inertiajs/vue3'

                createInertiaApp({
                resolve: name => {
                    const pages = import.meta.glob('./Pages/**/*.vue', { eager: true })
                    return pages[`./Pages/${name}.vue`]
                },
                setup({ el, App, props, plugin }) {
                    createApp({ render: () => h(App, props) })
                    .use(plugin)
                    .mount(el)
                },
                })
                JS;

                $skeletor->replaceInFile("import './bootstrap';", $inertiaBootScript, 'resources/js/app.js');
            }
        );
    }
};
