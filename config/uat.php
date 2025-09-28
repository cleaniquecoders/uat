<?php

/**
 * Configuration file for CleaniqueCoders/Uat package
 *
 * This package provides User Acceptance Testing (UAT) documentation generation
 * capabilities for Laravel applications, analyzing routes, middleware, and policies
 * to create comprehensive testing documentation.
 */

use CleaniqueCoders\Uat\Presentations\JsonGenerator;
use CleaniqueCoders\Uat\Presentations\MarkdownGenerator;
use CleaniqueCoders\Uat\Services\DataService;
use CleaniqueCoders\Uat\Services\ProjectAnalyzer;
use CleaniqueCoders\Uat\Services\RuleDiscovery;

return [
    /*
    |--------------------------------------------------------------------------
    | Document Output Path
    |--------------------------------------------------------------------------
    |
    | Specifies the directory where generated UAT documentation files will be
    | saved. If not set, defaults to the application's base path.
    | Can be overridden using the UAT_DOC_PATH environment variable.
    |
    */
    'directory' => env('UAT_DIRECTORY', 'uat'),

    /*
    |--------------------------------------------------------------------------
    | Core Services
    |--------------------------------------------------------------------------
    |
    | These are the core service classes that handle different aspects of
    | UAT documentation generation:
    | - project: Analyzes Laravel project structure and routes
    | - data: Processes and structures the collected data
    | - rule: Discovers and applies middleware/policy rules
    | - presentation: Handles the output format (default: Markdown)
    |
    */
    'services' => [
        'project' => ProjectAnalyzer::class,
        'data' => DataService::class,
        'rule' => RuleDiscovery::class,
        'presentation' => MarkdownGenerator::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Service Extensions
    |--------------------------------------------------------------------------
    |
    | Allow extending core services with custom implementations.
    | These arrays can contain additional service classes that will be
    | merged with or override the default services behavior.
    |
    */
    'extensions' => [
        'project' => [],  // Custom project analyzers
        'data' => [],     // Custom data processors
        'rules' => [],    // Custom rule discovery services
    ],

    /*
    |--------------------------------------------------------------------------
    | Documentation Generators
    |--------------------------------------------------------------------------
    |
    | Available output format generators for UAT documentation.
    | Each generator produces documentation in a specific format:
    | - markdown: Human-readable Markdown format
    | - json: Machine-readable JSON format for API consumption
    |
    */
    'generators' => [
        'markdown' => MarkdownGenerator::class,
        'json' => JsonGenerator::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Method to Policy Method Mapping
    |--------------------------------------------------------------------------
    |
    | Maps Laravel controller methods to their corresponding policy methods.
    | This helps the UAT generator understand which policy method should be
    | checked for authorization when analyzing routes.
    |
    | Format: 'controller_method' => 'policy_method'
    |
    */
    'methods_mapping' => [
        'index' => 'viewAny',    // List/index pages
        'show' => 'view',        // Show single resource
        'create' => 'create',    // Show create form
        'store' => 'create',     // Store new resource
        'edit' => 'update',      // Show edit form
        'update' => 'update',    // Update existing resource
        'destroy' => 'delete',   // Delete resource
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Route Prefixes
    |--------------------------------------------------------------------------
    |
    | Route prefixes that should be ignored during UAT documentation generation.
    | These are typically development tools, package routes, or system routes
    | that don't require user acceptance testing documentation.
    |
    */
    'excluded_prefixes' => [
        'telescope',    // Laravel Telescope debug assistant
        'horizon',      // Laravel Horizon queue dashboard
        'sanctum',      // Laravel Sanctum authentication
        'api/',         // Generic API routes (customize as needed)
        '_ignition',    // Ignition error page
        'livewire',     // Livewire component routes
        '_debugbar',    // Laravel Debugbar
        'impersonate',  // User impersonation routes
        'doc',          // Documentation routes
        'errors',       // Error handling routes
        'up',           // Application up check
        'test',         // Test routes
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware and Pattern Rules
    |--------------------------------------------------------------------------
    |
    | Defines how different middleware and route patterns should be interpreted
    | for UAT documentation. Each rule specifies what requirements must be met,
    | what actions testers should take, and how to validate the behavior.
    |
    */
    'rules' => [
        /*
        |----------------------------------------------------------------------
        | Middleware Rules
        |----------------------------------------------------------------------
        |
        | Exact middleware name matches. These rules define how specific
        | middleware should be tested and documented.
        |
        */
        'middleware' => [
            // Basic Laravel authentication middleware
            'auth' => [
                'type' => 'authentication',
                'description' => 'User must be logged in',
                'action' => 'Navigate to /login and authenticate with valid credentials',
                'validation' => 'Verify user session is active',
            ],

            // Laravel Sanctum API authentication
            'auth:sanctum' => [
                'type' => 'sanctum_authentication',
                'description' => 'User must be authenticated via Laravel Sanctum',
                'action' => 'Login or provide valid Sanctum token',
                'validation' => 'Verify Sanctum authentication guard is active',
            ],

            // Email verification requirement
            'verified' => [
                'type' => 'email_verification',
                'description' => 'User email must be verified',
                'action' => 'Ensure test user has verified email address',
                'validation' => 'Check email_verified_at timestamp is not null',
            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Pattern-Based Rules
        |----------------------------------------------------------------------
        |
        | Rules that match middleware using patterns (wildcards).
        | The {placeholder} will be replaced with the actual matched value
        | from the middleware parameter.
        |
        */
        'pattern' => [
            // Spatie Laravel Permission - Role-based access
            'role:*' => [
                'type' => 'role_authorization',
                'description' => "User must have '{placeholder}' role",
                'action' => "Login with user assigned to '{placeholder}' role",
                'validation' => "Verify user has '{placeholder}' role in database (use Spatie role system)",
            ],

            // Spatie Laravel Permission - Permission-based access
            'permission:*' => [
                'type' => 'permission_authorization',
                'description' => "User must have '{placeholder}' permission",
                'action' => "Login with user having '{placeholder}' permission",
                'validation' => "Verify user has '{placeholder}' permission directly or via role (use Spatie permission system)",
            ],

            // Laravel Gates - Custom authorization logic
            'can:*' => [
                'type' => 'gate_authorization',
                'description' => "User must pass '{placeholder}' gate check",
                'action' => "Login with user authorized for '{placeholder}' gate",
                'validation' => 'Verify gate check passes for current user',
            ],

            // Laravel Rate Limiting
            'throttle:*' => [
                'type' => 'rate_limiting',
                'description' => "Request must not exceed rate limit for '{placeholder}' limiter",
                'action' => 'Ensure test requests stay within rate limit bounds',
                'validation' => 'Verify rate limiting headers and 429 responses when exceeded',
            ],

            // Custom authentication guards
            'auth:*' => [
                'type' => 'guard_authentication',
                'description' => "User must be authenticated with '{placeholder}' guard",
                'action' => 'Authenticate using the specified guard (web, sanctum, api)',
                'validation' => 'Verify authentication state for the specified guard',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Policy Mappings
    |--------------------------------------------------------------------------
    |
    | Maps controllers to their corresponding Laravel Policy classes and
    | defines the specific authorization requirements for each controller method.
    | This helps generate accurate UAT documentation for policy-based authorization.
    |
    | Structure:
    | 'ControllerName' => [
    |     'policy' => 'PolicyClassName',
    |     'methods' => [
    |         'method_name' => [
    |             'description' => 'What the user needs',
    |             'action' => 'How to set up the test',
    |             'validation' => 'How to verify it works',
    |             'permissions' => ['required', 'permissions'],
    |             'roles' => ['allowed', 'roles']
    |         ]
    |     ]
    | ]
    |
    */
    'policy_mappings' => [
        // Example: User management controller with policy-based authorization
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

        // Add more controller mappings here as needed
        // 'PostController' => [
        //     'policy' => 'PostPolicy',
        //     'methods' => [
        //         'index' => [...],
        //         'store' => [...],
        //         'update' => [...],
        //         'destroy' => [...],
        //     ],
        // ],
    ],
];
