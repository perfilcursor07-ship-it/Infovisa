<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Confiar em todos os proxies (necessário quando atrás de load balancer/proxy reverso)
        $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO);
        
        // Força a URL correta em produção
        $middleware->prepend(\App\Http\Middleware\ForceAppUrl::class);
        // Detecta body descarto por post_max_size (evita 405 confuso no salvamento de documentos)
        $middleware->prepend(\App\Http\Middleware\CheckPostSize::class);
        
        $middleware->validateCsrfTokens(except: [
            'api/consultar-cnpj',
            'api/verificar-cnpj/*',
            'admin/ia/chat',
            'admin/ia/chat-edicao-documento',
            'admin/ia/extrair-pdf',
        ]);
        
        // Registrar alias para middleware customizado
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'admin.gestor' => \App\Http\Middleware\EnsureUserIsAdminOrGestor::class,
            'admin.gestor.estadual' => \App\Http\Middleware\EnsureUserIsAdminOrGestorEstadual::class,
            'no-cache-auth' => \App\Http\Middleware\PreventAuthenticatedPageCaching::class,
            'modulo.externo' => \App\Http\Middleware\EnsureModuloExterno::class,
        ]);
        
        // Configurar redirect para usuários não autenticados
        $middleware->redirectGuestsTo(function ($request) {
            return route('login');
        });
        
        // Configurar redirect após autenticação bem-sucedida
        $middleware->redirectUsersTo(function () {
            if (auth('interno')->check()) {
                return route('admin.dashboard');
            }
            if (auth('externo')->check()) {
                return route('company.dashboard');
            }
            return url('/');
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
