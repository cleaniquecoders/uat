<?php

// config for CleaniqueCoders/Uat

use CleaniqueCoders\Uat\Presentations\JsonGenerator;
use CleaniqueCoders\Uat\Presentations\MarkdownGenerator;
use CleaniqueCoders\Uat\Services\DataService;
use CleaniqueCoders\Uat\Services\ProjectAnalyzer;
use CleaniqueCoders\Uat\Services\RuleDiscovery;

return [
    'document_path' => env('UAT_DOC_PATH'),
    'services' => [
        'project' => ProjectAnalyzer::class,
        'data' => DataService::class,
        'rule' => RuleDiscovery::class,
        'presentation' => MarkdownGenerator::class,
    ],
    'extensions' => [
        'project' => [],
        'data' => [],
        'rules' => [],
    ],
    'generators' => [
        'markdown' => MarkdownGenerator::class,
        'json' => JsonGenerator::class,
    ],
    'methods_mapping' => [
        'index' => 'viewAny',
        'show' => 'view',
        'create' => 'create',
        'store' => 'create',
        'edit' => 'update',
        'update' => 'update',
        'destroy' => 'delete',
    ],
    'excluded_prefixes' => [
        'telescope',
        'horizon',
        'sanctum',
        'api/',
        '_ignition',
        'livewire',
        '_debugbar',
        'impersonate',
        'doc',
        'errors',
        'up',
        'test',
    ],
    'rules' => [
        'middleware' => [
            'auth' => [
                'type' => 'authentication',
                'description' => 'User must be logged in',
                'action' => 'Navigate to /login and authenticate with valid credentials',
                'validation' => 'Verify user session is active',
            ],

            'auth:sanctum' => [
                'type' => 'sanctum_authentication',
                'description' => 'User must be authenticated via Laravel Sanctum',
                'action' => 'Login or provide valid Sanctum token',
                'validation' => 'Verify Sanctum authentication guard is active',
            ],

            'verified' => [
                'type' => 'email_verification',
                'description' => 'User email must be verified',
                'action' => 'Ensure test user has verified email address',
                'validation' => 'Check email_verified_at timestamp is not null',
            ],
        ],
        'pattern' => [
            'role:*' => [
                'type' => 'role_authorization',
                'description' => "User must have '{placeholder}' role",
                'action' => "Login with user assigned to '{placeholder}' role",
                'validation' => "Verify user has '{placeholder}' role in database (use Spatie role system)",
            ],

            'permission:*' => [
                'type' => 'permission_authorization',
                'description' => "User must have '{placeholder}' permission",
                'action' => "Login with user having '{placeholder}' permission",
                'validation' => "Verify user has '{placeholder}' permission directly or via role (use Spatie permission system)",
            ],

            'can:*' => [
                'type' => 'gate_authorization',
                'description' => "User must pass '{placeholder}' gate check",
                'action' => "Login with user authorized for '{placeholder}' gate",
                'validation' => 'Verify gate check passes for current user',
            ],

            'throttle:*' => [
                'type' => 'rate_limiting',
                'description' => "Request must not exceed rate limit for '{placeholder}' limiter",
                'action' => 'Ensure test requests stay within rate limit bounds',
                'validation' => 'Verify rate limiting headers and 429 responses when exceeded',
            ],

            'auth:*' => [
                'type' => 'guard_authentication',
                'description' => "User must be authenticated with '{placeholder}' guard",
                'action' => 'Authenticate using the specified guard (web, sanctum, api)',
                'validation' => 'Verify authentication state for the specified guard',
            ],
        ],
    ],
    'policy_mappings' => [
        'UserController' => [
            'policy' => 'UserPolicy',
            'methods' => [
                'index' => [
                    'description' => 'User must have user security view permissions',
                    'action' => 'Login with user having user security permissions',
                    'validation' => 'Verify user can view user listings',
                    'permissions' => ['view-user-security'],
                    'roles' => ['superadmin', 'administrator'],
                ],
                'show' => [
                    'description' => 'User must have user security view permissions',
                    'action' => 'Login with user having user security permissions',
                    'validation' => 'Verify user can view user details',
                    'permissions' => ['view-user-security'],
                    'roles' => ['superadmin', 'administrator'],
                ],
            ],
        ],
    ],
];
