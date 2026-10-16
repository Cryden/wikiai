<?php

return [
    'plugin' => [
        'name'        => 'Fun Wiki Knowledge Base',
        'description' => 'Knowledge base management: templates with hierarchy, articles, nodes, variants and REST API',
    ],
    'permissions' => [
        'tab'              => 'Knowledge Base',
        'manage_templates' => 'Manage Templates',
        'manage_articles'  => 'Manage Articles',
    ],
    'navigation' => [
        'wiki'      => 'Knowledge Base',
        'articles'  => 'Articles',
        'templates'     => 'Templates',
        'templates_vue' => 'Templates (Vue)',
    ],
    'template' => [
        'name'           => 'Template Name',
        'code'           => 'Template Code',
        'type'           => 'Entity Type',
        'parent'         => 'Parent Template',
        'is_mandatory'   => 'Mandatory',
        'is_nested'      => 'Self-nested Structure',
        'is_auto_create' => 'Auto-create Record',
    ],
    'field' => [
        'label'        => 'Field Label',
        'name'         => 'Field Name (Code)',
        'type'         => 'Data Type',
        'is_mandatory' => 'Mandatory',
    ],
];

