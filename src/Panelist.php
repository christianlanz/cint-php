<?php
namespace Cint;

class Panelist {
	private $id = null;
	private $member_id = null;
	private $data = [];

	private $panel;

	private $paths = [];

	public function __construct($panel, $data, array $links = []) {
		if (!empty($data['member_id']))
			$this->member_id = $data['member_id'];
		if (!empty($data['key']))
			$this->id = $data['key'];

		$required = ['email_address', 'gender', 'postal_code', 'year_of_birth'];

		foreach ($required as $field) {
			if (empty($data[$field])) {
				throw new CintException("`{$field}` is a required field");
			}
		}

		if (!empty($links)) {
			$this->paths = Client::parsePathsJSON($links);
		}

		$this->panel = $panel;
		$this->data = $data;
	}

	/*
	 * Deserializing method
	 */

	public static function fromJSON($panel, array $json) {
		return new Panelist($panel, $json['panelist'], $json['links']);
	}

	/*
	 * Serializing Method
	 */

	public function toArray() {
		$data = $this->data;

		if (!empty($this->member_id))
			$data['member_id'] = $this->member_id;

		if (!empty($this->id))
			$data['key'] = $this->id;

		return $data;
	}

	/*
	 * Test to see if panelist id is set
	 */
	public function isNew() {
		return $this->id === null;
	}

	/*
	 * Request Methods to streamline HATEOASWTFBBQOMGROFL
	 */
	private function getPath($rel) {
		return Client::getPathFromDef($this->paths, $rel);
	}

	private function requestRel($method, $rel, $options = []) {
		$href = $this->getPath($rel);
		return IO::request($method, $href, $options);
	}

	/*
	 * Panelist Related Methods:
	 *
	 */

	/*
	 * Creates respondent request
	 */
	public function createRespondentRequest(array $quota_ids = []) {
		$req = [];

		if (!empty($quota_ids))
		{
			$req = ['candidate_respondent' => [
				'quota_ids' => $quota_ids,
			]];
		}

		$response = $this->requestRel('POST', 'candidate-respondents', [
			'json' => $req,
			'headers' => ['Accept' => 'application/json'],
			'allow_redirects' => false,
		]);

		return $response->getHeader('Location');
	}
}
