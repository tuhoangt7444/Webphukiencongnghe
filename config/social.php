<?php
return [
	'google' => [
		'client_id' => getenv('GOOGLE_CLIENT_ID') ?: '',
		'client_secret' => getenv('GOOGLE_CLIENT_SECRET') ?: '',
		'redirect_uri' => getenv('GOOGLE_REDIRECT_URI') ?: 'http://localhost/auth/google/callback',
	],
];
