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
