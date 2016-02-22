# CintPHP

This library creates a simple interface for interacting withe Cint's panelist API.

## Client Methods


### `new $Client($options)`

Creates the `Client` object using the following options:

- `api_key` - API Key given by Cint
- `secret` - API Secret given by Cint
- `panel_key` - The panel key found in the Cint dashboard for the panel you're working with.
- `guzzle_options` - Array of options to be fed directly into the guzzle layer
- `sandbox` - Boolean option to toggle between Cint sandbox and live environment

### `$Client->getPanelistByMemberId($id)`

Pulls panelist data from the API by `member_id` and returns a Panelist object.

### `$Client->createPanelist($Data)`

Creates a new Panelist object with the given data and the necessary reference to the client object.

### `$Client->registerPanelist($Panelist)`

Takes a Panelist object and registers them via the API. This is how you add a user to the panel. Returns a new Panelist object with the data and any other information returned by the api on registration.

### `$Panelist->createRespondentRequest()`

Creates what is called a respondent request, which creates and returns the URL for you to send the panelist to so they can be fed through the Survey system.

## Sample


```php
$Client = new Client([
	'api_key' => 'ABCDDFJDF',
	'secret' => 'a1b2c3d4',
	'panel_key' => 'asdf-asdf-asdf'
]);

$Panelist = $Client->getPanelistByMemberId($user_id);

if ($Panelist === null)
{
	$User = new User;
	$PanelistData = $User->prepUserForCint($user_id);

	// Add user as Cint Panelist
	$Panelist = $Client->createPanelist($PanelistData);
	$Panelist = $Client->registerPanelist($Panelist);
}

// Create respondent
$url = $Panelist->createRespondentRequest();

return redirect($url);
```