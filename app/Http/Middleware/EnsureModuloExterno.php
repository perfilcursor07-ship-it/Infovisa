<?php

namespace App\Http\Middleware;

use App\Models\Processo;
use App\Models\UsuarioExterno;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe as rotas da área do usuário externo ao módulo liberado para ele
 * (ex.: middleware('modulo.externo:processos')).
 */
class EnsureModuloExterno
{
    public function handle(Request $request, Closure $next, string $modulo): Response
    {
        $usuario = auth('externo')->user();
        if (!$usuario instanceof UsuarioExterno) {
            return $next($request);
        }

        // Processos do cadastro interno do receituário (estabelecimento oculto) pertencem ao módulo receituário
        if ($modulo === 'processos' && $request->routeIs('company.processos.*') && ($processoId = $request->route('id'))) {
            $processoReceituario = Processo::whereKey($processoId)
                ->whereHas('estabelecimento', fn ($q) => $q->where('oculto_receituario', true))
                ->exists();

            if ($processoReceituario) {
                $modulo = 'receituario';
            }
        }

        if ($usuario->temModulo($modulo)) {
            return $next($request);
        }

        $mensagem = 'Seu acesso não inclui o módulo ' . (UsuarioExterno::MODULOS[$modulo] ?? $modulo)
            . '. Para liberar, entre em contato com a Vigilância Sanitária.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $mensagem], 403);
        }

        return redirect()->route($usuario->rotaInicial())->with('error', $mensagem);
    }
}
