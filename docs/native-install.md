SafeWhisper Native Install
==========================


## Requirements:
- HTTP webserver
- PHP 7.3+
- Redis
- Composer
- npm
- git


## Instructions:

```
git clone https://github.com/yani/safewhisper safewhisper
cd safewhisper
composer install --no-dev --optimize-autoloader
npm install --production
```

Rename `.env.example` to `.env` and setup configuration.

Then setup your webserver to route everything to `/public/index.php`
