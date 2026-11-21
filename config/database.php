<?php
declare(strict_types=1);

/**
 * ApexGuild Enterprise Workflow & RBAC State Machine
 * Database & Environment Configuration
 */

return [
    'db' => [
        'driver'    => 'sqlite',
        'path'      => dirname(__DIR__) . '/database/data/apexguild.sqlite',
        'options'   => [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ],
        'pragmas'   => [
            'PRAGMA foreign_keys = ON;',
            'PRAGMA journal_mode = WAL;',
            'PRAGMA busy_timeout = 5000;',
        ]
    ],
    'app' => [
        'name'            => 'ApexGuild Enterprise',
        'version'         => '2.4.0',
        'url'             => 'http://localhost:8001',
        'secret_key'      => 'apex-sec-hmac-982348a8f10b78c92de10874314c1d2e',
        'timezone'        => 'UTC',
        'cfo_threshold'   => 5000.00, // Purchase order CFO approval threshold
    ],
    'roles' => [
        'Admin'     => 'System Administrator with full governance & pipeline builder authority',
        'CFO'       => 'Chief Financial Officer with high-threshold fiscal sign-off authority',
        'Manager'   => 'Department Operations Manager for tier-1 organizational reviews',
        'Requester' => 'Enterprise Staff Member submitting dynamic operational requisitions',
    ]
];
