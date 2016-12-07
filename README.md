#SlimPower - JWT

[![Latest version][ico-version]][link-packagist]
[comment]: # ([![Total Downloads][ico-downloads]][link-downloads])

[![Latest Stable Version](https://poser.pugx.org/matiasnamendola/slimpower-jwt/version?format=flat-square)](https://packagist.org/packages/matiasnamendola/slimpower-jwt) 
[![Latest Unstable Version](https://poser.pugx.org/matiasnamendola/slimpower-jwt/v/unstable?format=flat-square)](//packagist.org/packages/matiasnamendola/slimpower-jwt) 
[![Total Downloads](https://poser.pugx.org/matiasnamendola/slimpower-jwt/downloads?format=flat-square)](https://packagist.org/packages/matiasnamendola/slimpower-jwt) 
[![Monthly Downloads](https://poser.pugx.org/matiasnamendola/slimpower-jwt/d/monthly?format=flat-square)](https://packagist.org/packages/matiasnamendola/slimpower-jwt)
[![Daily Downloads](https://poser.pugx.org/matiasnamendola/slimpower-jwt/d/daily?format=flat-square)](https://packagist.org/packages/matiasnamendola/slimpower-jwt)
[![composer.lock available](https://poser.pugx.org/matiasnamendola/slimpower-jwt/composerlock?format=flat-square)](https://packagist.org/packages/matiasnamendola/slimpower-jwt)

A simple library to encode and decode JSON Web Tokens (JWT) in PHP, conforming to [RFC 7519](https://tools.ietf.org/html/rfc7519).

##Installation

In terminal, use composer to manage your dependencies and download 'Slimpower JWT':

```bash

composer require matiasnamendola/slimpower-jwt

```

Or you can add use this as your composer.json:

```json
    {
        "require": {
            "matiasnamendola/slimpower-jwt": "dev-master"
        }
    }

```

##Example

```php

<?php

use \SlimPower\JWT\JWT;

$key = "secret";

$token = array(
    "iss" => "http://example.org",
    "aud" => "http://example.com",
    "iat" => 1356999524,
    "nbf" => 1357000000
);

/**
 * IMPORTANT:
 * You must specify supported algorithms for your application. See
 * https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40
 * for a list of spec-compliant algorithms.
 */
$jwt = JWT::encode($token, $key);
$decoded = JWT::decode($jwt, $key, array('HS256'));

print_r($decoded);

/*
 NOTE: This will now be an object instead of an associative array. To get
 an associative array, you will need to cast it as such:
*/

$decoded_array = (array) $decoded;

/**
 * You can add a leeway to account for when there is a clock skew times between
 * the signing and verifying servers. It is recommended that this leeway should
 * not be bigger than a few minutes.
 *
 * Source: http://self-issued.info/docs/draft-ietf-oauth-json-web-token.html#nbfDef
 */
JWT::$leeway = 60; // $leeway in seconds
$decoded = JWT::decode($jwt, $key, array('HS256'));

?>
```

##Security

If you discover any security related issues, please email [soporte.esolutions@gmail.com](mailto:soporte.esolutions@gmail.com?subject=[SECURITY] Config Security Issue) instead of using the issue tracker.

##Credits

- [Matías Nahuel Améndola](https://github.com/matiasnamendola)
- [Franco Soto](https://github.com/francosoto)

##License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/MatiasNAmendola/slimpower-jwt.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/MatiasNAmendola/slimpower-jwt.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/matiasnamendola/slimpower-jwt
[link-downloads]: https://packagist.org/packages/matiasnamendola/slimpower-jwt