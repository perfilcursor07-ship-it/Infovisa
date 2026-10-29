-- BAIXAR DO PARA GIT
git pull origin main
php artisan migrate
php artisan db:seed


-- SUBIR PARA GIT
git add .
git commit -m "Implementação de questionários dinâmicos e override de competência"
git push -u origin main


ESSE É FUNCIONANDO (SEM SOBRESCREVER .env)
cd /var/www/html/infovisa

# backup rápido do .env antes do pull
cp .env /tmp/infovisa.env.bak

# pull seguro
sudo chown -R $USER:$USER /var/www/html/infovisa .git
git pull --ff-only origin main

# se por algum motivo o .env mudar, restaura automaticamente
cmp -s .env /tmp/infovisa.env.bak || cp /tmp/infovisa.env.bak .env

composer install --no-dev --optimize-autoloader --ignore-platform-req="ext-*"
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan config:true
npm run build

# permissões só onde precisa
sudo chown -R apache:apache storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# garantir link da logo
# se public/storage existir como pasta comum, remove para recriar como symlink
if [ -d public/storage ] && [ ! -L public/storage ]; then
	sudo rm -rf public/storage
fi

# recria o symlink correto caso ele não exista
[ -L public/storage ] || sudo ln -s ../storage/app/public public/storage
ls -ld public/storage
ls -l public/storage/municipios/logomarcas | head

sudo systemctl restart httpd php-fpm

# conferência opcional: .env NÃO deve ser versionado
git ls-files .env
