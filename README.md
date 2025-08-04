# Laravel Social Follow

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ultratechinnovations/laravel-social-follow.svg?style=flat-square)](https://packagist.org/packages/ultratechinnovations/laravel-social-follow)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/ultratechinnovations/laravel-social-follow/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/ultratechinnovations/laravel-social-follow/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/ultratechinnovations/laravel-social-follow/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/ultratechinnovations/laravel-social-follow/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/ultratechinnovations/laravel-social-follow.svg?style=flat-square)](https://packagist.org/packages/ultratechinnovations/laravel-social-follow)

A complete, flexible follow system for Laravel applications with multi-model support, real-time notifications, and relationship management


## Installation

You can install the package via composer:

```bash
composer require ultratechinnovations/laravel-social-follow
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="social-follow-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="social-follow-config"
```

## Usage
Traits

### CanFollow
Add to any model (e.g., ``User``) that should be able to follow others.

```php
<?php

use Illuminate\Foundation\Auth\User as Authenticatable;
use UltraTechInnovations\SocialFollow\Traits\CanFollow;

class User extends Authenticatable{
    use CanFollow;
}

// Usages
$user->follow($otherUser);
$user->unfollow($otherUser);
$user->toggleFollow($otherUser);
$user->isFollowing($otherUser);
$user->getFollowings();
$user->followingCount();

// If approval_required is true
$user->hasRequestedToFollow($otherUser); 
$otherUser->acceptFollowRequest($user);

```

### CanBeFollowed
Add to any model that can be followed.

```php
<?php

use Illuminate\Foundation\Auth\User as Authenticatable;
use UltraTechInnovations\SocialFollow\Traits\CanBeFollowed;

class User extends Authenticatable{
    use CanBeFollowed;
}

// Usage
$user->isFollowedBy($otherUser);
$user->getFollowers();
$user->pendingFollowers(); // If approval_required is true
```

### CanBlock
Add to any model that should be able to block others.
```php
<?php

use Illuminate\Foundation\Auth\User as Authenticatable;
use UltraTechInnovations\SocialFollow\Traits\CanBlock;

class User extends Authenticatable{
    use CanBlock;
}

// Usage
$user->block($otherUser);
$user->unblock($otherUser);
$user->hasBlocked($otherUser);
```

### CanBeBlocked
Add to any model that can be blocked.

```php
<?php

use Illuminate\Foundation\Auth\User as Authenticatable;
use UltraTechInnovations\SocialFollow\Traits\CanBeBlocked;

class User extends Authenticatable{
    use CanBeBlocked;
}

// Usage
$user->isBlockedBy($otherUser);

```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Oscar Myo Min](https://github.com/ultratechinnovations)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
