<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class PersistentStorage
{
    public static function diskName(): string
    {
        $bucket = config('filesystems.disks.s3.bucket');
        $key = config('filesystems.disks.s3.key');

        if (is_string($bucket) && $bucket !== '' && is_string($key) && $key !== '') {
            return 's3';
        }

        return 'local';
    }

    public static function disk(?string $name = null): Filesystem
    {
        return Storage::disk($name ?: self::diskName());
    }

    public static function persists(): bool
    {
        return self::diskName() === 's3';
    }
}
