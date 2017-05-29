<?php

namespace Electro\Configuration\Lib;

use Electro\Exceptions\Fatal\ConfigException;

class DotEnv
{

	/** @var array */
	private $envFiles;

	public function __construct(...$files)
	{
		$this->envFiles = $files;
	}

	public function load()
	{
		global $__ENV; // Used by env() to override the value of getenv()
		$merged = [];
		foreach ($this->envFiles as $file)
		{
			if (file_exists($file))
			{
				$ini = @parse_ini_file($file, false, INI_SCANNER_TYPED);
				if ($ini)
				{
					$partial = $this->buildConfig($ini);
					$merged = array_merge($merged,$partial);

				}
				else
				{
					$e = error_get_last();
					throw new ConfigException(isset($e['message']) ? $e['message'] : "Can't load file $file");
				}
			}
		}
		$__ENV = $merged;
	}

	public function loadConfig(array $config)
	{
		global $__ENV; // Used by env() to override the value of getenv()
		$__ENV = $this->buildConfig($config);
	}
	private function buildConfig(array $config)
	{
		$o = [];
		foreach ($config as $k => $v)
		{
			$r = "REDIRECT_$k";
			if (isset($_SERVER[$r]))
				$e = $_SERVER[$r];
			else
				$e = getenv($k);

			// Environment variables override .env variables
			if ($e !== false)
				$v = $e;
			else
				$v = trim($v); // bevause INI_SCANNER_RAW mode keeps irrelevant whitespace on values

			$o[$k] = $v;
		}
		return $o;
	}

}
