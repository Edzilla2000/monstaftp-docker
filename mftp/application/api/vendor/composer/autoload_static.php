<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit8357991fef199ec251d2d4eb2b427244
{
    public static $files = array (
        'fd419dbaef6da3b0a9513930c6ff3624' => __DIR__ . '/..' . '/wapmorgan/unified-archive/src/function.gzip_stat.php',
        '9e1b3d8b1d64afff01a1869b5f98e4e3' => __DIR__ . '/..' . '/wapmorgan/unified-archive/src/REGISTER_LZW_STREAM_WRAPPER.php',
    );

    public static $prefixLengthsPsr4 = array (
        'w' => 
        array (
            'wapmorgan\\UnifiedArchive\\' => 25,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'wapmorgan\\UnifiedArchive\\' => 
        array (
            0 => __DIR__ . '/..' . '/wapmorgan/unified-archive/src',
        ),
    );

    public static $prefixesPsr0 = array (
        'P' => 
        array (
            'PEAR' => 
            array (
                0 => __DIR__ . '/..' . '/pear/pear_exception',
            ),
        ),
        'C' => 
        array (
            'Console' => 
            array (
                0 => __DIR__ . '/..' . '/pear/console_getopt',
            ),
        ),
        'A' => 
        array (
            'Archive_Tar' => 
            array (
                0 => __DIR__ . '/..' . '/pear/archive_tar',
            ),
        ),
    );

    public static $fallbackDirsPsr0 = array (
        0 => __DIR__ . '/..' . '/pear/pear-core-minimal/src',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit8357991fef199ec251d2d4eb2b427244::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit8357991fef199ec251d2d4eb2b427244::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInit8357991fef199ec251d2d4eb2b427244::$prefixesPsr0;
            $loader->fallbackDirsPsr0 = ComposerStaticInit8357991fef199ec251d2d4eb2b427244::$fallbackDirsPsr0;

        }, null, ClassLoader::class);
    }
}
