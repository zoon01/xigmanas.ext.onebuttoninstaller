<?php
/*
    onebuttoninstaller-stop.php

    Copyright (c) 2015 - 2020 Andreas Schmidhuber
    All rights reserved.

    Redistribution and use in source and binary forms, with or without
    modification, are permitted provided that the following conditions are met:

    1. Redistributions of source code must retain the above copyright notice, this
       list of conditions and the following disclaimer.
    2. Redistributions in binary form must reproduce the above copyright notice,
       this list of conditions and the following disclaimer in the documentation
       and/or other materials provided with the distribution.

    THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
    ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
    WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
    DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
    ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
    (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
    LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
    ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
    (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
    SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

require_once 'config.inc';

$config_name = 'onebuttoninstaller';

if(is_link("/usr/local/share/locale-{$config_name}")):
	unlink("/usr/local/share/locale-{$config_name}");
endif;
if(is_link("/usr/local/www/{$config_name}.php")):
	unlink("/usr/local/www/{$config_name}.php");
endif;
if(is_link("/usr/local/www/{$config_name}-config.php")):
	unlink("/usr/local/www/{$config_name}-config.php");
endif;
if(is_link("/usr/local/www/{$config_name}-update_extension.php")):
	unlink("/usr/local/www/{$config_name}-update_extension.php");
endif;
if(is_link("/usr/local/www/ext/{$config_name}")):
	unlink("/usr/local/www/ext/{$config_name}");
endif;
mwexec('rmdir -p /usr/local/www/ext');
exec('logger onebuttoninstaller-extension: stopped');
