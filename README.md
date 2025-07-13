# Dependency

1. Install larvel v12
2. mysql database v8
3. xml extension

# Laravel delete fronend clustor

rm -rf resources/views
rm routes/web.php
rm -f vite.config.js package.json tailwind.config.js postcss.config.js
rm -rf resources/js resources/css public/js public/css


# Mysql
sudo apt update
sudo apt install mysql-server


sudo apt update
sudo apt install php-mysql


sudo apt update
sudo apt install mysql-server

sudo systemctl start mysql
sudo systemctl enable mysql


# JWT token

composer require tymon/jwt-auth
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
php artisan jwt:secret

auth.php
'guards' => [
    'api' => [
        'driver' => 'jwt',
        'provider' => 'users',
    ],
],


# Octane

composer require laravel/octane
php artisan octane:install
Choose swool
npm install -g chokidar-cli
php artisan octane:reload

php artisan octane:start --port=8080 --watch

# Swool
git clone -b v5.1.0 --depth=1 https://github.com/swoole/swoole-src.git
cd swoole-src

phpize
./configure --enable-sockets --enable-openssl --enable-mysqlnd \
  --enable-swoole-curl --enable-cares --enable-brotli --enable-zstd \
  --enable-swoole-thread --enable-iouring

make -j$(nproc)

ls -l modules/

sudo make install

echo "extension=swoole.so" | sudo tee /etc/php/8.3/cli/conf.d/20-swoole.ini

php -m | grep swoole
php --ri swoole

php artisan octane:start --server=swoole --port=8001 --watch

# Install 
  "barryvdh/laravel-dompdf": "*",
  "spatie/laravel-permission": "^6.20",
