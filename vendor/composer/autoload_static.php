<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitd9df82eb1b78c15c87f76d509a3fd566
{
    public static $prefixLengthsPsr4 = array (
        'V' => 
        array (
            'VotingPhoto\\' => 12,
        ),
        'P' => 
        array (
            'Premmerce\\SDK\\' => 14,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'VotingPhoto\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
        'Premmerce\\SDK\\' => 
        array (
            0 => __DIR__ . '/..' . '/premmerce/wordpress-sdk/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitd9df82eb1b78c15c87f76d509a3fd566::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitd9df82eb1b78c15c87f76d509a3fd566::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
