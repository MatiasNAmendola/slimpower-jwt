#SlimPower - Config

[![Latest version][ico-version]][link-packagist]
[comment]: # ([![Total Downloads][ico-downloads]][link-downloads])

[![Latest Stable Version](https://poser.pugx.org/matiasnamendola/slimpower-config/version?format=flat-square)](https://packagist.org/packages/matiasnamendola/slimpower-config) 
[![Latest Unstable Version](https://poser.pugx.org/matiasnamendola/slimpower-config/v/unstable?format=flat-square)](//packagist.org/packages/matiasnamendola/slimpower-config) 
[![Total Downloads](https://poser.pugx.org/matiasnamendola/slimpower-config/downloads?format=flat-square)](https://packagist.org/packages/matiasnamendola/slimpower-config) 
[![Monthly Downloads](https://poser.pugx.org/matiasnamendola/slimpower-config/d/monthly?format=flat-square)](https://packagist.org/packages/matiasnamendola/slimpower-config)
[![Daily Downloads](https://poser.pugx.org/matiasnamendola/slimpower-config/d/daily?format=flat-square)](https://packagist.org/packages/matiasnamendola/slimpower-config)
[![composer.lock available](https://poser.pugx.org/matiasnamendola/slimpower-config/composerlock?format=flat-square)](https://packagist.org/packages/matiasnamendola/slimpower-config)

Config is a file configuration loader that supports PHP, INI, XML, JSON,
and YML files.

##Installation

In terminal:

```sh
    composer require matiasnamendola/slimpower-config
```

Or you can add use this as your composer.json:

```json
    {
        "require": {
            "matiasnamendola/slimpower-config": "dev-master"
        }
    }

```

###Requirements

Config requires PHP 5.3+, and suggests using the [Symfony Yaml component](https://github.com/symfony/Yaml).

Config is designed to be very simple and straightforward to use. All you can do with
it is load, get, and set.

###Loading files

The `Config` object can be created via the factory method `load()`, or
by direct instantiation:

```php

<?php

use SlimPower\Config\Config;

// Load a single file
$conf = Config::load('config.json');
$conf = new Config('config.json');

// Load values from multiple files
$conf = new Config(array('config.json', 'config.xml'));

// Load all supported files in a directory
$conf = new Config(__DIR__ . '/config');

// Load values from optional files
$conf = new Config(array('config.dist.json', '?config.json'));

```

Files are parsed and loaded depending on the file extension. Note that when
loading multiple files, entries with **duplicate keys will take on the value
from the last loaded file**.

When loading a directory, the path is `glob`ed and files are loaded in by
name alphabetically.

###Getting values

Getting values can be done in three ways. One, by using the `get()` method:

```php

// Get value using key
$debug = $conf->get('debug');

// Get value using nested key
$secret = $conf->get('security.secret');

// Get a value with a fallback
$ttl = $conf->get('app.timeout', 3000);

```

The second method, is by using it like an array:

```php

// Get value using a simple key
$debug = $conf['debug'];

// Get value using a nested key
$secret = $conf['security.secret'];

// Get nested value like you would from a nested array
$secret = $conf['security']['secret'];

```

The third method, is by using the `all()` method:

```php

// Get all values
$data = $conf->all();

```

###Setting values

Although Config supports setting values via `set()` or, via the
array syntax, **any changes made this way are NOT reflected back to the
source files**. By design, if you need to make changes to your
configuration files, you have to do it manually.

```php

$conf = Config::load('config.json');

// Sample value from our config file
assert($conf['secret'] == '123');

// Update config value to something else
$conf['secret'] = '456';

// Reload the file
$conf = Config::load('config.json');

// Same value as before
assert($conf['secret'] == '123');

// This will fail
assert($conf['secret'] == '456');

```

###Using with default values

Sometimes in your own projects you may want to use Config for storing
application settings, without needing file I/O. You can do this by extending
the `AbstractConfig` class and populating the `getDefaults()` method:

```php

<?php

namespace ...;

use SlimPower\Config\AbstractConfig;

class MyConfig extends AbstractConfig
{
    protected function getDefaults()
    {
        return array(
            'host' => 'localhost',
            'port'    => 80,
            'servers' => array(
                'host1',
                'host2',
                'host3'
            ),
            'application' => array(
                'name'   => 'configuration',
                'secret' => 's3cr3t'
            )
        );
    }
}

```

##Security

If you discover any security related issues, please email [soporte.esolutions@gmail.com](mailto:soporte.esolutions@gmail.com?subject=[SECURITY] Config Security Issue) instead of using the issue tracker.


##Credits

- [Matías Nahuel Améndola](https://github.com/matiasnamendola)
- [Franco Soto](https://github.com/francosoto)


##License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/MatiasNAmendola/slimpower-config.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/MatiasNAmendola/slimpower-config.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/matiasnamendola/slimpower-config
[link-downloads]: https://packagist.org/packages/matiasnamendola/slimpower-config
