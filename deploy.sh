#!/bin/bash

# Script de Deploy para Produção - InfoVISA
# Uso: bash deploy.sh

echo "=========================================="
echo "  Deploy InfoVISA - Sistema de Pactuação"
echo "=========================================="
echo ""

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Diretório do projeto
PROJECT_DIR="/var/www/html/infovisa"

echo -e "${YELLOW}1. Navegando para o diretório do projeto...${NC}"
cd $PROJECT_DIR || exit

echo -e "${YELLOW}2. Fazendo backup do banco de dados...${NC}"
BACKUP_FILE="backup_antes_deploy_$(date +%Y%m%d_%H%M%S).sql"
pg_dump -U postgres -d infovisa > $BACKUP_FILE
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Backup criado: $BACKUP_FILE${NC}"
else
    echo -e "${RED}✗ Erro ao criar backup!${NC}"
    exit 1
fi

echo -e "${YELLOW}3. Fazendo pull das alterações do Git...${NC}"
git pull origin main
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Código atualizado${NC}"
else
    echo -e "${RED}✗ Erro ao fazer pull!${NC}"
    exit 1
fi

echo -e "${YELLOW}4. Instalando dependências do Composer...${NC}"
php composer.phar install --no-dev --optimize-autoloader --ignore-platform-req="ext-*"
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Dependências do Composer instaladas${NC}"
else
    echo -e "${RED}✗ Erro ao instalar dependências do Composer!${NC}"
    exit 1
fi

echo -e "${YELLOW}5. Ajustando permissões temporariamente para build do frontend...${NC}"
sudo chown -R $USER:$USER $PROJECT_DIR/node_modules $PROJECT_DIR/public 2>/dev/null || true
echo -e "${GREEN}✓ Permissões ajustadas${NC}"

echo -e "${YELLOW}6. Instalando dependências do Node.js...${NC}"
npm install
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Dependências do Node.js instaladas${NC}"
else
    echo -e "${RED}✗ Erro ao instalar dependências do Node.js!${NC}"
    exit 1
fi

echo -e "${YELLOW}7. Compilando assets do frontend (Vite)...${NC}"
npm run build
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Assets compilados com sucesso${NC}"
else
    echo -e "${RED}✗ Erro ao compilar assets!${NC}"
    exit 1
fi

echo -e "${YELLOW}8. Executando migrations...${NC}"
php artisan migrate --force
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Migrations executadas${NC}"
else
    echo -e "${RED}✗ Erro ao executar migrations!${NC}"
    exit 1
fi

echo -e "${YELLOW}9. Limpando caches...${NC}"
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear
echo -e "${GREEN}✓ Caches limpos${NC}"

echo -e "${YELLOW}10. Cacheando configurações...${NC}"
php artisan config:cache
php artisan route:cache
echo -e "${GREEN}✓ Configurações cacheadas${NC}"

echo -e "${YELLOW}11. Ajustando permissões finais para Apache...${NC}"
sudo chown -R apache:apache $PROJECT_DIR
sudo chmod -R 775 $PROJECT_DIR/storage $PROJECT_DIR/bootstrap/cache
echo -e "${GREEN}✓ Permissões ajustadas${NC}"

echo -e "${YELLOW}12. Reiniciando serviços...${NC}"
sudo systemctl restart php-fpm
sudo systemctl restart httpd
echo -e "${GREEN}✓ Serviços reiniciados${NC}"

echo ""
echo -e "${GREEN}=========================================="
echo "  Deploy concluído com sucesso!"
echo "==========================================${NC}"
echo ""
echo "Próximos passos:"
echo "1. Acesse: https://sistemas.saude.to.gov.br/infovisa"
echo "2. Teste as funcionalidades"
echo "3. Se houver problemas, restaure o backup: $BACKUP_FILE"
echo ""
