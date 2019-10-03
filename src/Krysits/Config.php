<?php
namespace Krysits;

class Config {

	// config vars
	public $type =      'pgsql';
	public $host =      '127.0.0.1';
	public $port =      '5432';
	public $database =  'postgres';
	public $username =  'postgres';
	public $password =  '';

	public $salt = 'skeletony';

	public static $allowedIPs = [
		'127.0.0.1',
		'::1',
		'94.100.3.154',
		'192.168.0.100',
		'192.168.0.101'
	];

	public static $envVars = [];

	/**
	 * Config constructor.
	 */
	public function __construct()
	{
		$this->type =      $this->env('DB_TYPE','pgsql');
		$this->host =      $this->env('DB_HOST','127.0.0.1');
		$this->port =      $this->env('DB_PORT','5432');
		$this->database =  $this->env('DB_DATABASE','postgres');
		$this->username =  $this->env('DB_USERNAME','postgres');
		$this->password =  $this->env('DB_PASSWORD');

		$this->salt =  $this->env('SALT');
	}

	// methods
	public function getDSN()
	{
		return $this->type.":host=".$this->host.";port=".$this->port.";dbname=".$this->database;
	}

	public static function checkIP($ip)
	{
		if(filter_var($ip, FILTER_VALIDATE_IP)) return in_array($ip, self::$allowedIPs);
		return false;
	}

	public static function getEnvVars()
	{
		if(!empty(self::$envVars)) {
			return self::$envVars;
		}

		$envFile = __DIR__ . '/../../.envm';

		if(!is_readable($envFile)) {
			return false;
		}

		$envArr = file($envFile);

		foreach ($envArr as $line) {
			if(strpos($line,'=') == false) continue;
			$tmp = explode('=', $line);
			$varName = trim($tmp[0]);
			$varValue = trim($tmp[1]);
			self::$envVars[$varName] = $varValue;
		}

		return self::$envVars;

	}

	public function env($key, $default = null)
	{
		if(!self::$envVars){
			self::getEnvVars();
		}

		if(!isset(self::$envVars[$key])) {
			return $default;
		}

		$value = self::$envVars[$key];

		if ($value === false) {
			return $default;
		}

		switch (strtolower($value)) {
			case 'true':
			case '(true)':
				return true;
			case 'false':
			case '(false)':
				return false;
			case 'empty':
			case '(empty)':
				return '';
			case 'null':
			case '(null)':
				return;
		}

		if (substr($value, 0, 1) == '"' && substr($value, -1, 1) == '"') {
			return substr($value, 1, -1);
		}

		return $value;
	}
};
