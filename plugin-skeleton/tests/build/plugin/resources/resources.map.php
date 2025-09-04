<?php

/**********
	Description: resources mapping.
**********/

// ===== Variable definition.

$resources = [
	'languages' => [
        'pt-br' => [ // Example for Portuguese (Brazil), but you can use any language.
            'name' => 'Português (Brasil)',
            'data' => [
                'layouts' => 'layouts.json',
                'pages' => 'pages.json',
                'components' => 'components.json',
                'variables' => 'variables.json',
            ],
            'version' => '1',
        ],
    ],
];

// ===== Return the variable.

return $resources;