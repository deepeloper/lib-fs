# File system library
[![Latest Stable Version](https://img.shields.io/packagist/v/deepeloper/lib-fs.svg?style=flat-square)](https://packagist.org/packages/deepeloper/lib-fs)
[![Packagist](https://img.shields.io/packagist/dt/deepeloper/lib-fs.svg)](https://packagist.org/packages/deepeloper/lib-fs)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/deepeloper/lib-fs.svg)](http://php.net/)
[![GitHub license](https://img.shields.io/github/license/deepeloper/lib-fs.svg)](https://github.com/deepeloper/lib-fs/blob/master/LICENSE)

[![Code Coverage](https://codecov.io/gh/deepeloper/lib-fs/branch/master/graph/badge.svg)](https://codecov.io/gh/deepeloper/lib-fs)
[![GitHub issues](https://img.shields.io/github/issues-raw/deepeloper/lib-fs.svg)](https://github.com/deepeloper/lib-fs/issues)

[![Donate to liberapay](http://img.shields.io/liberapay/receives/don.bidon.svg?logo=liberapay)](https://liberapay.com/don.bidon/donate)

Look [API documentation](https://deepeloper.github.io/docs/packages/lib-fs/).

## Installation
Run `composer require deepeloper/lib-fs dev-1.0.0`.

## Usage
### Tools: walking directory recursively
```php
\deepeloper\Lib\FileSystem\Tools::walkDir(
    "/path/to/dir",
    function (\SplFileInfo $file, $key, array $args): void
    {
        // $args["path"] contains passed "/path/to/dir" ($path)
        echo sprintf(
            "[%s] %s%s", $file->isDir() ? "DIR " : "file",
            $file->getRealPath(),
            PHP_EOL
        );
    }
);
```

### Tools: removing directory recursively
```php
\deepeloper\Lib\FileSystem\Tools::removeDir("/path/to/dir");
// clearstatcache(...);
```

### Tools: searching & replacing recursively
```php
\deepeloper\Lib\FileSystem\Tools::search(
    "/path/to/dir",
    0,           // Flags (php://glob())
    ["*", ".*"], // File name patterns (php://glob())
    ["*", ".*"], // Subdir name patterns (php://glob())
    "needle",    // String to search in files, if starts with "/" processes like regular expression
    function ($path, array $args)
    {
        // $args["path"] contains passed "/path/to/dir" ($dir)
        // $args["needle"] contains passed "needle" ($needle)
        $contents = file_get_contents($path);
        $contents = preg_replace("/needle/", "replacement", $contents);
        file_put_contents($path, $contents);
    }
);
```

### Logging functionality supporting files rotation
```php
$logger = new \deepeloper\Lib\FileSystem\Logger([
    'path'    => "/path/to/log",
    // 'maxSize' => int maxSize,   // Logger::DEFAULT_MAX_SIZE by default.
    // 'rotation' => int rotation, // Rotating files number, 0 means no rotation.
    // 'rights'    => int rights,  // If set after writing to log file chmod() will be called.
]);
$logger->log("Foo");
```

## Donate
[Yandex.Money, Visa, MasterCard, Maestro](https://money.yandex.ru/to/41001351141494) or visit [Liberapay](https://liberapay.com/don.bidon/donate).
