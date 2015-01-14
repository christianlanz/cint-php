<?php

namespace Cint;

class RespondentQuota {
	public $id;

	// Pricing
	public $CPI;

	// Stats
	public $IR;
	public $LOI;

	// Targeting
	public $targeting;

	// Fulfillment
	public $est_remaining;

	// Devices
	public $required_devices;
	public $blocked_devices;

	public static function fromXML($xml_element)
	{
		$quota = new RespondentQuota();

		$quota->id = $xml_element->id;
		$quota->CPI = $xml_element->pricing->{'indicative-cpi'};

		$quota->IR = $xml_element->statistics->{'incidence-rate'};
		$quota->LOI = $xml_element->statistics->{'length-of-interview'};

		$quota->targeting = $xml_element->{'target-group'};

		$quota->est_remaining = $xml_element->fulfillment->{'estimated-remaining-completes'};

		$quota->required_devices = [];
		$quota->blocked_devices = [];

		return $quota;
	}
}
