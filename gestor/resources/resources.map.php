<?php

/**********
	Description: resources mapping.
**********/

// ===== Variable definition.

$resources = [
	'languages' => [
        'pt-br' => [
            'name' => 'Português (Brasil)',
            'data' => [
                'layouts' => 'layouts.json',
                'pages' => 'pages.json',
                'components' => 'components.json',
                'templates' => 'templates.json',
                'variables' => 'variables.json',
                'modules' => 'modules.json',
                'module_groups' => 'module_groups.json',
                'module_operations' => 'module_operations.json',
                'user_profiles' => 'user_profiles.json',
            ],
            'version' => '1',
        ],
        'en' => [
            'name' => 'English',
            'data' => [
                'layouts' => 'layouts.json',
                'pages' => 'pages.json',
                'components' => 'components.json',
                'templates' => 'templates.json',
                'variables' => 'variables.json',
                'modules' => 'modules.json',
                'module_groups' => 'module_groups.json',
                'module_operations' => 'module_operations.json',
                'user_profiles' => 'user_profiles.json',
            ],
            'version' => '1',
        ],
    ],
];

// ===== Return the variable.

return $resources;