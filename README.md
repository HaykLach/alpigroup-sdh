<h1>Pim Setup</h1>

<h2>Install</h2>

remote install: connect to remote host
```
ssh c722656_w77_ssh1@k68j64.meinserver.io
cd /var/www/alpigroup.hub.smart-dato.com/web
```
clone repository, install composer packages
```
git clone git@bitbucket.org:smartdato/alpigroup-sdh.git
composer install
composer update smart-dato/sdh-shopware-sdk
```

```
# (optional) publish vendor files 
php artisan vendor:publish

# migrate database
php artisan migrate:fresh

## if needed ##
php artisan migrate --path=vendor/smart-dato/sdh-shopware-sdk/database/migrations

# seed necessary data
php artisan db:seed

# optionally add user; info@smart-dato.com user is already created by db:seed
php artisan make:filament-user

# user role management
php artisan shield:install --fresh

# local setup symlink for public storage
php artisan storage:link
# for timme hosting you will need to create the symlink manually, ex.:
ln -s /var/www/alpigroup.hub.smart-dato.com/web/storage/app/public/ /var/www/alpigroup.hub.smart-dato.com/web/public/storage

cd /var/www/demo.pim.smart-dato.com/web/public
ln -s /var/www/demo.pim.smart-dato.com/web/storage/app/public/ . 
```

optionally configure <code>.env</code> settings

```
APP_NAME=Laravel
APP_ENV=local
APP_DEBUG=true
APP_URL=https://pim.sdh.test

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dbname
DB_USERNAME=root
DB_PASSWORD=password
```

optionally add <code>.env</code> credentials

```
# Ombis API
OMBIS_API_URL="https://shop-alpigroup.pdcloud.eu/rest/smartdato/alpigroup/"
OMBIS_API_USER="theusername"
OMBIS_API_PASSWORD="thepassword"

# openai api - for translation
OPENAI_API_KEY=<your-key-here>
OPENAI_ORGANIZATION=<your-key-here>
```

in shopware:
```
create media folder in shopware gui and store id in .env as SHOPWARE_6_MEDIA_FOLDER_ID
copy language ids from shopware and store in .env as SHOPWARE_6_LANGUAGE_EN_ID and SHOPWARE_6_LANGUAGE_IT_ID
copy sales channel id from shopware and store in .env 
```

optionally add shopware credentials to <code>.env</code>
```
SHOPWARE_6_HOST=https://demo.alpistore.smart-dato.com
SHOPWARE_6_ACCESS_KEY=SWIANG44AZJWZERNDMYWAU5RDA
SHOPWARE_6_SECRET_KEY=Zkw3a2tjeW1QQTNBVzdTVHlSWVVtc2dFb2VxemFJRXp3Rk42VGQ
SHOPWARE_6_MEDIA_FOLDER_ID=01920fa502aa7482bf84ac818e4fa0b0
SHOPWARE_6_SALES_CHANNEL_ID=175a75c1636d59b4a081bcd18880bd1b
SHOPWARE_6_LANGUAGE_EN_ID=5ffd5a21b50347dbaa921f13a77d8bf3
SHOPWARE_6_LANGUAGE_IT_ID=752bdc531af545fab43bd7e6a37aeb9a
```

configure config/pim.php updateable fields
```
    'update' => [
        PimMappingType::PRODUCT->value => [
            PimFormStoreField::PRICES->value,
            'name',
            'active',
        ]
    ]
```

configure config/sdh-shopware-sdk.php shopware config
```
return [
    'credentials' => [
        'host' => env('SHOPWARE_6_HOST'),
        'verifySsl' => env('SHOPWARE_6_SSL_VERIFY'),
        'accessKey' => env('SHOPWARE_6_ACCESS_KEY'),
        'secretKey' => env('SHOPWARE_6_SECRET_KEY'),
    ],
    'defaults' => [
        'mediaFolderId' => env('SHOPWARE_6_MEDIA_FOLDER_ID'),

        'mapping' => [
            'manufacturer' => [
                'Logo-upload' => null,
            ],
            'product' => [
                'Verkaufspreis' => 'price.net',
                'Nettogewicht' => 'weight',
            ],
        ],

        'salesChannels' => [
            [
                'id' => env('SHOPWARE_6_SALES_CHANNEL_ID'),
            ],
        ],
    ],
];


```

start queue worker
```
php artisan queue:work
```

regular import
```
php artisan vc:ombis
```

initial import
vc:ombis also has options available
```
php artisan vc:ombis --truncateTables
```


dispatch thumbnails creation jobs
```
php artisan job:product-preview-images --addJobs
```

trigger image color detection
script stores image colors in pim_products table in custom fields
can be used for missing color filter property
```
php artisan sdh:image-color-determine
```

optional: set color filter property determined by command sdh:image-color-determine
```
php artisan sdh:image-color-set-properties
```

Shopware export
```
php artisan sdh:export:periodic-update
```

Developer tools
```
./vendor/bin/phpstan --memory-limit=2048M
pint: phpStorm - code - reformat
```

System requirements
```
imagick: image manipulation library for creating thumbnails
mysqldump: for database backup
```


<h2>Demo</h2>
https://demo.pim.smart-dato.com/



<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains over 2000 video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the Laravel [Patreon page](https://patreon.com/taylorotwell).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Cubet Techno Labs](https://cubettech.com)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[Many](https://www.many.co.uk)**
- **[Webdock, Fast VPS Hosting](https://www.webdock.io/en)**
- **[DevSquad](https://devsquad.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[OP.GG](https://op.gg)**
- **[WebReinvent](https://webreinvent.com/?utm_source=laravel&utm_medium=github&utm_campaign=patreon-sponsors)**
- **[Lendio](https://lendio.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
