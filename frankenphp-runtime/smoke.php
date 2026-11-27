<?php

declare(strict_types=1);

$extensions = ['intl', 'exif', 'zip', 'gd', 'bcmath', 'pdo_mysql', 'imagick', 'redis', 'mbstring', 'xml', 'gmp'];
foreach ($extensions as $extension) {
    if (!extension_loaded($extension)) {
        throw new RuntimeException("Missing PHP extension: {$extension}");
    }
}

if (!is_executable('/usr/bin/convert')) {
    throw new RuntimeException('ImageMagick CLI is unavailable');
}

if (!in_array('mysql', PDO::getAvailableDrivers(), true)) {
    throw new RuntimeException('MySQL PDO driver is unavailable');
}

if (PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION !== $argv[1] || !PHP_ZTS) {
    throw new RuntimeException('Unexpected PHP version or thread safety');
}

$redis = new Redis();
$payload = str_repeat('FrankenPHP Redis compression check ', 100);
foreach ([Redis::COMPRESSION_LZF, Redis::COMPRESSION_LZ4, Redis::COMPRESSION_ZSTD] as $algorithm) {
    $redis->setOption(Redis::OPT_COMPRESSION, $algorithm);
    if ($redis->_uncompress($redis->_compress($payload)) !== $payload) {
        throw new RuntimeException("Redis compression round-trip failed: {$algorithm}");
    }
}

$image = new Imagick();
$image->newImage(2, 3, 'white', 'png');
$decoded = new Imagick();
$decoded->readImageBlob($image->getImageBlob());
if ($decoded->getImageWidth() !== 2 || $decoded->getImageHeight() !== 3) {
    throw new RuntimeException('Imagick PNG round-trip failed');
}

echo "FrankenPHP {$argv[1]} runtime OK\n";
