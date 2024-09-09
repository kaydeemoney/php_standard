FIle name in the config and databasecall folder must have case the class name have

Utility_Functions ==== Utility_Functions.php

6379

Download Redis from https://github.com/microsoftarchive/redis/releases
Extract the archive and run redis-server.exe.

Download the appropriate php_redis.dll from https://pecl.php.net/package/redis
Place it in the ext directory of your PHP installation.

Add the extension to your php.ini file:
extension=php_redis.dll

 only call classes when you need them


 utility functions are functions that can be used in any project
 personal functions are functions specific to a project