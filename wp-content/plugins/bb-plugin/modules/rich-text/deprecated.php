<?php

FLBuilder::register_module_deprecations( 'rich-text', [
	'v1' => [
		'config' => [
			'include_wrapper' => true,
		],
		'files'  => [
			'includes/frontend.php',
			'includes/frontend.css.php',
		],
	],
] );
