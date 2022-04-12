<?php
return [
    'sa' => [
        'type' => 1,
        'children' => [
            'developer'
        ],
    ],
    'developer' => [
        'type' => 1,
        'children' => [
            'operator'
        ],
    ],
    'operator' => [
        'type' => 1,
        'children' => [
            'viewer',
            'form-core:edit',
            'form-expedition:edit',
            'form-hole:edit',
            'form-lithology:edit',
            'form-program:edit',
            'form-sample:edit',
            'form-sample-requests:edit',
            'form-scientists:edit',
            'form-section:edit',
            'form-site:edit',
            'form-split:edit',
            'form-archive-file:edit',
        ],
    ],
    'viewer' => [
        'type' => 1,
        'children' => [
            'form-core:view',
            'form-expedition:view',
            'form-hole:view',
            'form-lithology:view',
            'form-program:view',
            'form-sample:view',
            'form-sample-requests:view',
            'form-scientists:view',
            'form-section:view',
            'form-site:view',
            'form-split:view',
            'form-archive-file:view',
        ],
    ],
    'form-core:edit' => [
        'type' => 2,
        'children' => [
            'form-core:view',
        ],
    ],
    'form-core:view' => [
        'type' => 2,
    ],
    'form-expedition:edit' => [
        'type' => 2,
        'children' => [
            'form-expedition:view',
        ],
    ],
    'form-expedition:view' => [
        'type' => 2,
    ],
    'form-hole:edit' => [
        'type' => 2,
        'children' => [
            'form-hole:view',
        ],
    ],
    'form-hole:view' => [
        'type' => 2,
    ],
    'form-lithology:edit' => [
        'type' => 2,
        'children' => [
            'form-lithology:view',
        ],
    ],
    'form-lithology:view' => [
        'type' => 2,
    ],
    'form-program:edit' => [
        'type' => 2,
        'children' => [
            'form-program:view',
        ],
    ],
    'form-program:view' => [
        'type' => 2,
    ],
    'form-sample:edit' => [
        'type' => 2,
        'children' => [
            'form-sample:view',
        ],
    ],
    'form-sample:view' => [
        'type' => 2,
    ],
    'form-sample-requests:edit' => [
        'type' => 2,
        'children' => [
            'form-sample-requests:view',
        ],
    ],
    'form-sample-requests:view' => [
        'type' => 2,
    ],
    'form-scientists:edit' => [
        'type' => 2,
        'children' => [
            'form-scientists:view',
        ],
    ],
    'form-scientists:view' => [
        'type' => 2,
    ],
    'form-section:edit' => [
        'type' => 2,
        'children' => [
            'form-section:view',
        ],
    ],
    'form-section:view' => [
        'type' => 2,
    ],
    'form-site:edit' => [
        'type' => 2,
        'children' => [
            'form-site:view',
        ],
    ],
    'form-site:view' => [
        'type' => 2,
    ],
    'form-split:edit' => [
        'type' => 2,
        'children' => [
            'form-split:view',
        ],
    ],
    'form-split:view' => [
        'type' => 2,
    ],
    'form-archive-file:edit' => [
        'type' => 2,
        'children' => [
            'form-archive-file:view',
        ],
    ],
    'form-archive-file:view' => [
        'type' => 2,
    ],
];
