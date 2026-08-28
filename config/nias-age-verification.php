<?php

declare(strict_types=1);

return [
	'enabled' => env('NIAS_ENABLED', false),

	'country' => env('NIAS_COUNTRY', 'HR'),
	
	'base_url' => env('NIAS_BASE_URL', 'https://mgradjani-test.gov.hr/idp'),
	'client_id' => env('NIAS_CLIENT_ID'),
	'redirect_uri' => env('NIAS_REDIRECT_URI'),
	'callback_approved' => env('NIAS_CALLBACK_APPROVED_URL'),
	'callback_denied' => env('NIAS_CALLBACK_DENIED_URL'),

	'state_ttl' => env('NIAS_STATE_TTL', 900),
	'verification_ttl' => env('NIAS_VERIFICATION_TTL', 30 * 60),
	'http_timeout' => env('NIAS_HTTP_TIMEOUT', 10),
	'signing_keys_ttl' => env('NIAS_SIGNING_KEYS_TTL', 3600),

];
