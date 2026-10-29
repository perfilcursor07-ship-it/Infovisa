#!/bin/bash

# Script para corrigir deploy em produção
# Remove arquivos de build do Git e faz deploy limpo

echo "=== Corrigindo deploy em produção ==="

# 1. Fazer stash das alterações locais
echo "1. Salvando alterações locais..."
git stash

# 2. Fazer pull das mudanças
echo "2. Baixando alterações do repositório..."
git pull origin main

# 3. Remover arquivos de build do Git (se ainda estiverem trackeados)
echo "3. Removendo arquivos de build do Git..."
git rm -r --cached public/build 2>/dev/null || true
git rm --cached public/build/manifest.json 2>/dev/null || true

# 4. Commit se houver mudanças
if ! git diff --cached --quiet; then
    echo "4. Commitando remoção dos arquivos de build..."
    git commit -m "Remove arquivos de build do Git (devem ser gerados no deploy)"
    git push origin main
fi

# 5. Instalar dependências PHP
echo "5. Instalando dependências PHP..."
php composer.phar install --no-dev --optimize-autoloader --ignore-platform-req="ext-*"

# 6. Rodar migrações
echo "6. Executando migrações..."
php artisan migrate --force

# 7. Limpar caches
echo "7. Limpando caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 8. Instalar dependências Node
echo "8. Instalando dependências Node..."
npm install

# 9. Build dos assets
echo "9. Compilando assets..."
npm run build

# 10. Configurar cache
echo "10. Configurando cache..."
sudo php artisan config:cache
sudo chown apache:apache bootstrap/cache/config.php

# 11. Ajustar permissões
echo "11. Ajustando permissões..."
sudo chown -R apache:apache /var/www/html/infovisa/
sudo chmod -R 775 /var/www/html/infovisa/storage/ /var/www/html/infovisa/bootstrap/cache/

# 12. Reiniciar serviços
echo "12. Reiniciando serviços..."
sudo systemctl restart httpd php-fpm

echo "=== Deploy concluído com sucesso! ==="
