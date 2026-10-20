<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$cpf = '83033688187';
$email = 'patriciaferreirasobrinho@gmail.com';

echo "=== BUSCANDO CPF: {$cpf} ===\n\n";

// Busca em usuários internos (incluindo soft deleted)
$interno = App\Models\UsuarioInterno::withTrashed()->where('cpf', $cpf)->first();
if ($interno) {
    echo "ENCONTRADO em usuarios_internos:\n";
    echo "ID: {$interno->id}\n";
    echo "Nome: {$interno->nome}\n";
    echo "Email: {$interno->email}\n";
    echo "CPF: {$interno->cpf}\n";
    echo "Status cadastro: ".($interno->cadastro_aprovado ? 'aprovado' : 'pendente')."\n";
    echo "Ativo: ".($interno->ativo ? 'sim' : 'não')."\n";
    echo "Deleted_at: ".($interno->deleted_at ? $interno->deleted_at : 'null (ativo)')."\n";
    echo "Created_at: {$interno->created_at}\n\n";
} else {
    echo "NÃO encontrado em usuarios_internos\n\n";
}

// Busca em usuários externos (incluindo soft deleted)
$externo = App\Models\UsuarioExterno::withTrashed()->where('cpf', $cpf)->first();
if ($externo) {
    echo "ENCONTRADO em usuarios_externos:\n";
    echo "ID: {$externo->id}\n";
    echo "Nome: {$externo->nome}\n";
    echo "Email: {$externo->email}\n";
    echo "CPF: {$externo->cpf}\n";
    echo "Deleted_at: ".($externo->deleted_at ? $externo->deleted_at : 'null (ativo)')."\n";
    echo "Created_at: {$externo->created_at}\n\n";
} else {
    echo "NÃO encontrado em usuarios_externos\n\n";
}

echo "=== BUSCANDO EMAIL: {$email} ===\n\n";

$internoEmail = App\Models\UsuarioInterno::withTrashed()->where('email', $email)->first();
if ($internoEmail) {
    echo "ENCONTRADO em usuarios_internos:\n";
    echo "ID: {$internoEmail->id}\n";
    echo "Nome: {$internoEmail->nome}\n";
    echo "CPF: {$internoEmail->cpf}\n";
    echo "Deleted_at: ".($internoEmail->deleted_at ? $internoEmail->deleted_at : 'null (ativo)')."\n\n";
} else {
    echo "NÃO encontrado em usuarios_internos\n\n";
}

$externoEmail = App\Models\UsuarioExterno::withTrashed()->where('email', $email)->first();
if ($externoEmail) {
    echo "ENCONTRADO em usuarios_externos:\n";
    echo "ID: {$externoEmail->id}\n";
    echo "Nome: {$externoEmail->nome}\n";
    echo "CPF: {$externoEmail->cpf}\n";
    echo "Deleted_at: ".($externoEmail->deleted_at ? $externoEmail->deleted_at : 'null (ativo)')."\n\n";
} else {
    echo "NÃO encontrado em usuarios_externos\n\n";
}
