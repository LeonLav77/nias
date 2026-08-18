<?php

declare(strict_types=1);

return [
	// Model that implemetns the isAgeRestricted contract
	'product_model' => null,
	'base_url' => env('NIAS_BASE_URL', 'https://mgradjani-test.gov.hr/idp'),
	'client_id' => env('NIAS_CLIENT_ID'),
	'redirect_uri' => env('NIAS_REDIRECT_URI'),
	'state_ttl' => 900,
	'http_timeout' => 10,
	'signing_keys_ttl' => 3600,
];
