<?php
namespace Bantingan;
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
        // creating directories
        $directories = [
            __DIR__."app",
            __DIR__."assets",
            __DIR__."config",
            __DIR__."modules",
        ];

        foreach($directories as $dir) {
            if (!is_dir($dir)) {
                $old = umask(0);
                mkdir($dir, 0775, true);
                umask($old);
            }
        }
        
    }
}