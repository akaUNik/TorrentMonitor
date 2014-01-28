<?php
class Config
{
    static $confArray;

    public static function read($name)
    {
        return self::$confArray[$name];
    }

    public static function write($name, $value)
    {
        self::$confArray[$name] = $value;
    }
}

#Для MySQL:
Config::write('db.host', 'localhost');
Config::write('db.type', 'mysql');
Config::write('db.charset', 'utf8');
Config::write('db.port', '3306');
Config::write('db.basename', 'torrentmonitor');
Config::write('db.user', 'torrentmonitor');
Config::write('db.password', 'torrentmonitor123');

#Для PostgreSQL:
#Config::write('db.host', 'localhost');
#Config::write('db.type', 'pgsql');
#Config::write('db.port', '5432');
#Config::write('db.basename', 'torrentmonitor');
#Config::write('db.user', 'torrentmonitor');
#Config::write('db.password', 'torrentmonitor');

#Для SQLite:
#Config::write('db.type', 'sqlite');
#Config::write('db.basename', '/var/www/htdocs/TorrentMonitor/torrentmonitor.sqlite'); #Указывайте _абсолютный_ путь до файла БД (расширение рекомендуется использовать .sqlite)
?>