<?php

declare(strict_types=1);

namespace CleaniqueCoders\Uat\Presentations;

use CleaniqueCoders\Uat\Contracts\Presentation;
use Illuminate\Support\Collection;

class JsonGenerator implements Presentation
{
    public function generateProjectInfo(array $projectInfo): string
    {
        $output = [
            'title' => 'Project Information',
            'generated_at' => $projectInfo['generated_at'],
            'basic_information' => [
                'project_name' => $projectInfo['name'],
                'description' => $projectInfo['description'],
                'version' => $projectInfo['version'],
                'environment' => $projectInfo['environment'],
            ],
            'technical_stack' => [
                'php_version' => $projectInfo['php_version'],
                'laravel_version' => $projectInfo['laravel_version'],
                'database' => $projectInfo['database_connection'],
                'queue_driver' => $projectInfo['queue_connection'],
                'cache_driver' => $projectInfo['cache_driver'],
                'session_driver' => $projectInfo['session_driver'],
                'mail_driver' => $projectInfo['mail_driver'],
            ],
            'uat_testing_notes' => [
                'foundation' => 'This document serves as the foundation for User Acceptance Testing (UAT)',
                'environment' => 'All testing should be performed in a controlled environment',
                'configuration' => 'Verify all components are properly configured before testing',
                'reporting' => 'Report any discrepancies between expected and actual behavior',
            ],
        ];

        return json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function generateAccessControls(array $accessControls): string
    {
        $output = [
            'title' => 'Access Controls',
            'generated_at' => now()->toDateTimeString(),
            'roles_overview' => $accessControls['roles'],
            'permissions_overview' => $accessControls['permissions'],
            'role_permission_matrix' => $accessControls['role_permissions'],
            'uat_testing_scenarios' => [
                'role_based_access_testing' => [
                    'admin_role_testing' => [
                        'tasks' => [
                            'Verify admin users can access all system functionalities',
                            'Test user management capabilities',
                            'Confirm access to system settings and configurations',
                        ],
                    ],
                    'supervisor_role_testing' => [
                        'tasks' => [
                            'Test API oversight and approval workflows',
                            'Verify access to monitoring and analytics features',
                            'Confirm limited access to user management',
                        ],
                    ],
                    'maintainer_role_testing' => [
                        'tasks' => [
                            'Test API lifecycle management capabilities',
                            'Verify access to own APIs and assigned projects',
                            'Confirm restricted access to system-wide settings',
                        ],
                    ],
                    'developer_role_testing' => [
                        'tasks' => [
                            'Test API creation and modification features',
                            'Verify access to developer portal and documentation',
                            'Confirm limited access to published APIs',
                        ],
                    ],
                    'partner_role_testing' => [
                        'tasks' => [
                            'Test limited API access and consumption features',
                            'Verify subscription and access request processes',
                            'Confirm restricted system access',
                        ],
                    ],
                    'guest_role_testing' => [
                        'tasks' => [
                            'Test read-only catalog access',
                            'Verify public API documentation availability',
                            'Confirm no access to management features',
                        ],
                    ],
                ],
            ],
        ];

        return json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function generateUsers(Collection $users): string
    {
        $roleDistribution = $users->flatMap(fn ($user) => $user['roles'])->countBy();
        $uniqueRoles = $users->flatMap(fn ($user) => $user['roles'])->unique();

        $testUserMatrix = [];
        foreach ($uniqueRoles as $role) {
            $roleUsers = $users->filter(fn ($user) => in_array($role, $user['roles']))->take(2);

            $testUserMatrix[$role] = [
                'available_users' => $roleUsers->isNotEmpty() ? $roleUsers->toArray() : [],
                'has_users' => $roleUsers->isNotEmpty(),
                'warning' => $roleUsers->isEmpty() ? "No users found with {$role} role - Create test users before UAT" : null,
            ];
        }

        $output = [
            'title' => 'Users',
            'generated_at' => now()->toDateTimeString(),
            'total_users' => $users->count(),
            'users_overview' => $users->toArray(),
            'user_roles_distribution' => $roleDistribution->toArray(),
            'uat_test_users' => [
                'description' => 'For comprehensive UAT testing, ensure you have test users for each role',
                'recommended_test_user_matrix' => $testUserMatrix,
            ],
            'pre_uat_user_checklist' => [
                'verified_emails' => 'All test users have verified email addresses',
                'role_coverage' => 'Each role has at least one test user assigned',
                'password_documentation' => 'Test user passwords are documented and accessible to UAT team',
                'mfa_configuration' => 'Multi-factor authentication is configured for admin users (if enabled)',
                'permission_testing' => 'User permissions are properly assigned and tested',
            ],
        ];

        return json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function generateAvailableModules(array $modules): string
    {
        $modulesSummary = [];
        foreach ($modules as $index => $module) {
            $fileNumber = str_pad((string) ($index + 5), 2, '0', STR_PAD_LEFT);
            $modulesSummary[] = [
                'module' => $module['module'],
                'routes_count' => count($module['routes']),
                'file' => "{$fileNumber}-module-{$module['module']}.md",
            ];
        }

        $output = [
            'title' => 'Available Modules Overview',
            'generated_at' => now()->toDateTimeString(),
            'total_modules' => count($modules),
            'modules_summary' => $modulesSummary,
            'general_uat_testing_guidelines' => [
                'pre_testing_checklist' => [
                    'modules_deployed' => 'All modules are deployed and accessible',
                    'database_seeded' => 'Database is properly seeded with test data',
                    'external_dependencies' => 'All external dependencies are available (Kong Gateway, etc.)',
                    'test_users_created' => 'Test users are created for each role',
                    'browser_compatibility' => 'Browser compatibility testing setup is ready',
                ],
                'testing_methodology' => [
                    'smoke_testing' => 'Verify all routes are accessible',
                    'functional_testing' => 'Test core business logic for each module',
                    'authorization_testing' => 'Verify role-based access controls',
                    'policy_testing' => 'Verify policy-based authorization rules',
                    'integration_testing' => 'Test module interactions',
                    'security_testing' => 'Test for common vulnerabilities',
                ],
                'bug_reporting_template' => [
                    'description' => 'When reporting issues found during UAT',
                    'fields' => [
                        'test_case_id' => '[TC-XXX-XXX]',
                        'module' => '[Module Name]',
                        'route' => '[Route URI]',
                        'user_role' => '[Role being tested]',
                        'expected_behavior' => '[What should happen]',
                        'actual_behavior' => '[What actually happened]',
                        'steps_to_reproduce' => '[Detailed steps]',
                        'browser_environment' => '[Testing environment details]',
                        'severity' => '[Critical/High/Medium/Low]',
                    ],
                ],
            ],
        ];

        return json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function generateModuleTestSuite(array $module, int $moduleIndex): string
    {
        $moduleName = $module['module'];
        $modulePrefix = strtoupper(substr($moduleName, 0, 3));

        // Build module overview
        $moduleOverview = [];
        foreach ($module['routes'] as $route) {
            $routeName = $route['name'] ?? '_unnamed_';
            $middleware = $this->formatMiddlewareForDisplay($route['middleware']);
            $action = class_basename($route['action']);
            $prerequisiteCount = count($route['prerequisites']);
            $prerequisiteText = $prerequisiteCount > 0 ? "{$prerequisiteCount} item(s)" : 'None';

            $moduleOverview[] = [
                'uri' => "/{$route['uri']}",
                'name' => $routeName,
                'action' => $action,
                'middleware' => $middleware,
                'prerequisites' => $prerequisiteText,
            ];
        }

        // Build test cases
        $testCases = [];
        $testCaseCounter = 1;

        foreach ($module['routes'] as $routeIndex => $route) {
            $routeId = str_pad((string) ($routeIndex + 1), 2, '0', STR_PAD_LEFT);
            $routeTestCases = [
                'route_uri' => "/{$route['uri']}",
                'prerequisites' => $route['prerequisites'],
                'tests' => [],
            ];

            // Basic Functionality Test
            $testId = "TC-{$modulePrefix}-{$routeId}-001";
            $basicTest = [
                'test_id' => $testId,
                'test_name' => 'Basic Access Test',
                'test_objective' => 'Verify route is accessible and loads without errors',
                'prerequisites' => empty($route['prerequisites']) ? ['None'] : array_column($route['prerequisites'], 'description'),
                'test_steps' => [
                    "Navigate to /{$route['uri']}",
                    'Wait for page to load completely',
                    'Verify page content is displayed',
                ],
                'expected_result' => 'Page loads successfully without errors',
                'status' => ['pass' => false, 'fail' => false, 'not_tested' => true],
                'notes' => '',
            ];
            $routeTestCases['tests'][] = $basicTest;
            $testCaseCounter++;

            // Authorization Tests
            if (in_array('auth', $route['middleware'])) {
                $testId = "TC-{$modulePrefix}-{$routeId}-002";
                $authTest = [
                    'test_id' => $testId,
                    'test_name' => 'Authentication Required Test',
                    'test_objective' => 'Verify unauthenticated users are redirected to login',
                    'prerequisites' => [
                        'User must be logged out',
                        'Clear all browser sessions',
                    ],
                    'test_steps' => [
                        'Ensure user is not logged in',
                        "Navigate directly to /{$route['uri']}",
                        'Observe browser behavior',
                    ],
                    'expected_result' => 'User is redirected to login page',
                    'status' => ['pass' => false, 'fail' => false, 'not_tested' => true],
                    'notes' => '',
                ];
                $routeTestCases['tests'][] = $authTest;
                $testCaseCounter++;
            }

            // Role-based Authorization Tests
            foreach ($route['middleware'] as $middlewareName) {
                if (! is_string($middlewareName)) {
                    continue;
                }

                if (str_starts_with($middlewareName, 'role:')) {
                    $role = str_replace('role:', '', $middlewareName);
                    $testId = "TC-{$modulePrefix}-{$routeId}-".str_pad((string) $testCaseCounter, 3, '0', STR_PAD_LEFT);

                    $roleTest = [
                        'test_id' => $testId,
                        'test_name' => "Role Authorization Test - {$role}",
                        'test_objective' => "Verify only users with '{$role}' role can access route",
                        'prerequisites' => [
                            "Test user without '{$role}' role",
                            "Test user with '{$role}' role",
                        ],
                        'test_steps' => [
                            "Login with user WITHOUT '{$role}' role",
                            "Navigate to /{$route['uri']}",
                            'Verify access is denied (403 or redirect)',
                            "Logout and login with user WITH '{$role}' role",
                            "Navigate to /{$route['uri']}",
                            'Verify access is granted',
                        ],
                        'expected_result' => 'Access denied for unauthorized user, granted for authorized user',
                        'status' => ['pass' => false, 'fail' => false, 'not_tested' => true],
                        'notes' => '',
                    ];
                    $routeTestCases['tests'][] = $roleTest;
                    $testCaseCounter++;
                }
            }

            // Policy-based Authorization Tests
            foreach ($route['prerequisites'] as $prerequisite) {
                if ($prerequisite['type'] === 'policy_authorization') {
                    $testId = "TC-{$modulePrefix}-{$routeId}-".str_pad((string) $testCaseCounter, 3, '0', STR_PAD_LEFT);

                    $policyPrerequisites = [];
                    $testSteps = [];
                    $expectedResult = 'Access granted for authorized user';

                    if (! empty($prerequisite['permissions_required'])) {
                        foreach ($prerequisite['permissions_required'] as $permission) {
                            $policyPrerequisites[] = "Test user with '{$permission}' permission";
                        }
                        $policyPrerequisites[] = 'Test user without required permissions';

                        $testSteps = [
                            $prerequisite['action'],
                            "Navigate to /{$route['uri']}",
                            $prerequisite['validation'],
                            'Logout and login with user WITHOUT required permissions',
                            "Navigate to /{$route['uri']}",
                            'Verify access is denied (403, 404, or redirect)',
                        ];
                        $expectedResult = 'Access granted for authorized user, denied for unauthorized user';
                    } else {
                        $policyPrerequisites = [
                            'Test user with appropriate authorization',
                            'Test user without authorization',
                        ];
                        $testSteps = [
                            $prerequisite['action'],
                            "Navigate to /{$route['uri']}",
                            $prerequisite['validation'],
                        ];
                    }

                    $policyTest = [
                        'test_id' => $testId,
                        'test_name' => "Policy Authorization Test - {$prerequisite['policy']}::{$prerequisite['method']}",
                        'test_objective' => $prerequisite['description'],
                        'prerequisites' => $policyPrerequisites,
                        'test_steps' => $testSteps,
                        'expected_result' => $expectedResult,
                        'policy' => $prerequisite['policy'],
                        'method' => $prerequisite['method'],
                        'required_permissions' => $prerequisite['permissions_required'] ?? [],
                        'status' => ['pass' => false, 'fail' => false, 'not_tested' => true],
                        'notes' => '',
                    ];
                    $routeTestCases['tests'][] = $policyTest;
                    $testCaseCounter++;
                }
            }

            $testCases[] = $routeTestCases;
        }

        $output = [
            'title' => "{$moduleName} Module - UAT Test Suite",
            'generated_at' => now()->toDateTimeString(),
            'module' => $moduleName,
            'routes_count' => count($module['routes']),
            'module_overview' => $moduleOverview,
            'test_cases' => $testCases,
            'test_summary' => [
                'total_test_cases' => $testCaseCounter - 1,
                'passed' => 0,
                'failed' => 0,
                'not_tested' => $testCaseCounter - 1,
                'completion_percentage' => 0,
                'tester_name' => '',
                'test_date' => '',
                'notes' => '',
            ],
        ];

        return json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Format middleware array for display, handling different middleware types
     */
    private function formatMiddlewareForDisplay(array $middleware): string
    {
        $formatted = [];

        foreach ($middleware as $m) {
            if (is_string($m)) {
                $formatted[] = $m;
            } elseif ($m instanceof \Closure) {
                $formatted[] = 'Closure';
            } elseif (is_object($m)) {
                $formatted[] = class_basename(get_class($m));
            } else {
                $formatted[] = 'Unknown';
            }
        }

        return implode(', ', $formatted);
    }
}
