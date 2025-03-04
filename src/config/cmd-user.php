<?php

return [
    'id' => 'email',

    'fields' => [
        'id' => [
            'label' => 'ID'
        ],
        'name' => [
            'type' => 'text',
            'label' => 'Name'
        ],
        'email' => [
            'type' => 'text',
            'label' => 'Email'
        ],
        'email_verified_at' => [
            'label' => 'Email verified at',
            'exclude-list' => [ true, false ],
        ],
        'password' => [
            'type' => 'password',
            'label' => 'Password',
            'exclude-list' => true
        ],
        'created_at' => [
            'label' => 'Created',
            'exclude-list' => [ true, false ],
        ],
        'updated_at' => [
            'label' => 'Updated',
            'exclude-list' => [ true, false ],
        ]
    ]
];
