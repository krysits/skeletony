<?php
namespace Krysits;

use PDO;

class Database {
	public static $db = false;
	public static $config;

	public static function getDb()	{

		if(!self::$db) {
			self::$config = new Config;
			self::$db = new PDO(
				self::$config->getDSN(),
				self::$config->env('DB_USERNAME','postgres'),
				self::$config->env('DB_PASSWORD'),
				[PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4'"]
			);
		}

		return self::$db;
	}
};