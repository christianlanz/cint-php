<?php
namespace Cint;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;


class IO {
	private static $guzzleClient = null;
	private static $options = [];

	public static function getInstance() {
		if (static::$guzzleClient === null) {
			$guzzle_opts = static::$options;
			static::$guzzleClient = new GuzzleClient($guzzle_opts);
		}

		return static::$guzzleClient;
	}

	public static function setOptions(array $options) {
		static::$options = $options;
	}

	public static function request($method, $url, $options = []) {
		$client = static::getInstance();
		$req = $client->createRequest($method, $url, $options);
		return $req->send();
	}
}


