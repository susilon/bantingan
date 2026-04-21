<?php
namespace Susilon\Bantingan;
use Composer\Script\Event;
use Composer\Util\Filesystem;
/*      
    This is the application installer

    Bantingan Framework v3
    Copyright (C) 2023 by Susilo Nurcahyo
    susilonurcahyo@gmail.comm    

    Bantingan Framework is free, open source, and GPL friendly. You can use it for commercial projects, open source projects, or really almost whatever you want.   

    This application is provided to you “as is” without warranty of any kind, either express or implied, including, but not limited to, the implied warranties of merchantability, fitness for a particular purpose or non-infringement.
*/

class Installer
{
    public static function postInstall(Event $event)
    {
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
        $baseDir = dirname($vendorDir);
        
        $filesystem = new Filesystem();
        
        $dirs = [
            $baseDir . '/app',
            $baseDir . '/app/Controllers',
            $baseDir . '/app/Models',
            $baseDir . '/app/Views',
            $baseDir . '/public',
            $baseDir . '/config',
            $baseDir . '/modules',
            $baseDir . '/templates_c',
        ];
        
        foreach ($dirs as $dir) {
            if (!$filesystem->isDirectory($dir)) {
                $filesystem->ensureDirectoryExists($dir);
            }
        }
        
        // Copy config file example
        //copy(
        //    $vendorDir . '/susilon/bantingan/config.example.php',
        //    $baseDir . '/config/bantingan.php'
        //);        
    }

    public static function postUpdate(Event $event)
    {
        self::postInstall($event);
    }
}