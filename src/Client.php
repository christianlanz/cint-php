<?php
namespace Cint;

use GuzzleHttp\Exception\RequestException;

/**
 * Cint Client
 */
class Client {
	private $guzzleClient = null;

	private $panelKey = null;
	private $apiKey = null;
	private $secret = null;

	private $paths = [];

	/*
	 * Accepts configuration via array
	 *
	 * @param array $config Client configuration
	 * 		- panel_key: Key for the panel
	 * 		- guzzle_options: Options passed along to guzzle client
	 *
	 */
	public function __construct(array $config = []) {
		if (empty($config['api_key']))
			throw new CintException("Cint Client requires api_key in configuration");

		if (empty($config['secret']))
			throw new CintException("Cint Client requires secret in configuration");

		if (!empty($config['panel_key']))
			$this->panelKey = $config['panel_key'];

		$this->apiKey = $config['api_key'];
		$this->secret = $config['secret'];

		if (empty($config['guzzle_options']))
			$config['guzzle_options'] = [];

		$base_url = 'https://api.cint.com/';

		if (isset($config['sandbox']) && $config['sandbox'] === true)
			$base_url = 'https://cdp.cintworks.net/';

		$guzzle_opts = array_merge_recursive([
			'base_url' => $base_url,
			'defaults' => [
				'headers' => [
					'User-Agent' => 'cintphp-library/0.1.0',
					'Accept' => 'application/xml',
					'Authorization' => "Basic " . base64_encode($this->apiKey . ":" . $this->secret),
				],
			],
		], $config['guzzle_options']);

		IO::setOptions($guzzle_opts);
	}

	/*
	 * Helper function to parse XML Paths into Indexed array
	 */

	public static function parsePathsXML($xml) {
		$paths = [];

		foreach ($xml->link as $link) {
			$rel = $link->attributes()->rel->__toString();

			$paths[$rel] = [
				'type' => $link->attributes()->type->__toString(),
				'rel' => $rel,
				'href' => $link->attributes()->href->__toString(),
			];
		}

		return $paths;
	}

	public static function parsePathsJSON($json) {
		$paths = [];
		foreach ($json as $link) {
			$rel = $link['rel'];

			$paths[$rel] = [
				'type' => $link['type'],
				'rel' => $rel,
				'href' => $link['href'],
			];
		}
		return $paths;
	}

	public function getPaths($purge = false) {
		if (empty($this->paths) || $purge) {
			$response = IO::request('GET', '/panels/' . $this->apiKey . '/');
			$this->paths = static::parsePathsXML($response->xml());
		}

		return $this->paths;
	}

	/*
	 * Gathers root paths and returns the matching relavent href
	 *
	 * @param string rel The relation/namespace string
	 * @return string|null
	 */

	private function getPath($rel) {
		$paths = $this->getPaths();
		return static::getPathFromDef($paths, $rel);
	}

	public static function getPathFromDef($paths, $rel) {
		if (isset($paths[$rel]))
			return $paths[$rel]['href'];

		throw new CintException("Rel path $rel doesn't exist in root. \n" . var_export($paths, true));
	}

	/*
	 * Request Methods to streamline HATEOASWTFBBQOMGROFL
	 */

	private function requestRel($method, $rel, $options = []) {
		$href = $this->getPath($rel);
		return IO::request($method, $href, $options);
	}

	/*
	 * All the Panel related methods
	 */

	/*
	 * Retrieves panel events
	 */
	public function getEventFeed($limit = 100, $since = null) {
		$query = ['limit' => $limit];
		if ($since !== null) $query['since'] = $since;

		return $this->requestRel('GET', 'panel/events', ['query' => $query])->xml();
	}

	/*
	 * Retrieves the list of available Respondent Quotas
	 */
	public function getRespondentQuotas() {
		$response = $this->requestRel('GET', 'panel/respondent-quotas', [
			'headers' => ['Accept' => 'application/json'],
		]);

		return $response->json();
	}

	/*
	 * Creates panelist object with tie back to panel
	 */

	public function createPanelist(array $data) {
		return new Panelist($this, $data);
	}

	/*
	 * Actually registers the panelist created via the above method
	 */
	public function registerPanelist(Panelist $Panelist) {
		$response = $this->requestRel('POST', 'panelists', [
			'headers' => ['Accept' => 'application/json'],
			'json' => $Panelist->toArray(),
		]);

		return Panelist::fromJSON($this, $response->json());
	}

	/*
	 * Retrieve panelist data from member ID
	 */
	public function getPanelistByMemberId($member_id) {
		try {
			$response = $this->requestRel('GET', 'panelists', [
				'headers' => ['Accept' => 'application/json'],
				'query' => ['member_id' => $member_id],
			]);

			return Panelist::fromJSON($this, $response->json());
		} catch(RequestException $e) {
			return null;
		}
	}

	/*
	 * Retrieves Respondent data
	 *
	 * WTF Cint just broke the HATEOAS style for this method?
	 */
	public function retrieveRespondent($guid) {
		try {
			$url = $this->getPath('self') . "/respondents/$guid";
			$response = IO::request('GET', $url, [
				'headers' => ['Accept' => 'application/json'],
			]);

			return $response->json();
		} catch(RequestException $e) {
			error_log($e);
			return null;
		}
	}
}

class CintException extends \Exception {}
