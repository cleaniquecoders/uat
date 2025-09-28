<?php

declare(strict_types=1);

namespace CleaniqueCoders\Uat\Presentations;

use CleaniqueCoders\Uat\Contracts\Presentation;
use Illuminate\Support\Collection;

class MarkdownGenerator implements Presentation
{
    public function getExtension(): string
    {
        return 'md';
    }

    public function generateProjectInfo(array $projectInfo): string
    {
        $markdown = "# Project Information\n\n";
        $markdown .= "> Generated on: {$projectInfo['generated_at']}\n\n";

        $markdown .= "## Basic Information\n\n";
        $markdown .= "| Field | Value |\n";
        $markdown .= "|-------|-------|\n";
        $markdown .= "| **Project Name** | {$projectInfo['name']} |\n";
        $markdown .= "| **Description** | {$projectInfo['description']} |\n";
        $markdown .= "| **Version** | {$projectInfo['version']} |\n";
        $markdown .= "| **Environment** | {$projectInfo['environment']} |\n\n";

        $markdown .= "## Technical Stack\n\n";
        $markdown .= "| Component | Version/Configuration |\n";
        $markdown .= "|-----------|----------------------|\n";
        $markdown .= "| **PHP Version** | {$projectInfo['php_version']} |\n";
        $markdown .= "| **Laravel Version** | {$projectInfo['laravel_version']} |\n";
        $markdown .= "| **Database** | {$projectInfo['database_connection']} |\n";
        $markdown .= "| **Queue Driver** | {$projectInfo['queue_connection']} |\n";
        $markdown .= "| **Cache Driver** | {$projectInfo['cache_driver']} |\n";
        $markdown .= "| **Session Driver** | {$projectInfo['session_driver']} |\n";
        $markdown .= "| **Mail Driver** | {$projectInfo['mail_driver']} |\n\n";

        $markdown .= "## UAT Testing Notes\n\n";
        $markdown .= "- This document serves as the foundation for User Acceptance Testing (UAT)\n";
        $markdown .= "- All testing should be performed in a controlled environment\n";
        $markdown .= "- Verify all components are properly configured before testing\n";
        $markdown .= "- Report any discrepancies between expected and actual behavior\n\n";

        return $markdown;
    }

    public function generateAccessControls(array $accessControls): string
    {
        $markdown = "# Access Controls\n\n";
        $markdown .= '> Generated on: '.now()->toDateTimeString()."\n\n";

        $markdown .= "## Roles Overview\n\n";
        $markdown .= "| Role ID | Role Name | Guard | Permissions Count | Users Count | Created At |\n";
        $markdown .= "|---------|-----------|-------|-------------------|-------------|------------|\n";

        foreach ($accessControls['roles'] as $role) {
            $markdown .= "| {$role['id']} | **{$role['name']}** | {$role['guard_name']} | {$role['permissions_count']} | {$role['users_count']} | {$role['created_at']} |\n";
        }

        $markdown .= "\n## Permissions Overview\n\n";
        $markdown .= "| Permission ID | Permission Name | Guard | Associated Roles | Created At |\n";
        $markdown .= "|---------------|----------------|-------|------------------|------------|\n";

        foreach ($accessControls['permissions'] as $permission) {
            $roles = implode(', ', $permission['roles']);
            $markdown .= "| {$permission['id']} | **{$permission['name']}** | {$permission['guard_name']} | {$roles} | {$permission['created_at']} |\n";
        }

        $markdown .= "\n## Role-Permission Matrix\n\n";
        foreach ($accessControls['role_permissions'] as $roleName => $permissions) {
            $markdown .= "### {$roleName}\n\n";
            if (empty($permissions)) {
                $markdown .= "_No permissions assigned_\n\n";
            } else {
                foreach ($permissions as $permission) {
                    $markdown .= "- {$permission}\n";
                }
                $markdown .= "\n";
            }
        }

        $markdown .= "## UAT Testing Scenarios\n\n";
        $markdown .= "### Role-Based Access Testing\n\n";
        $markdown .= "1. **Admin Role Testing**\n";
        $markdown .= "   - Verify admin users can access all system functionalities\n";
        $markdown .= "   - Test user management capabilities\n";
        $markdown .= "   - Confirm access to system settings and configurations\n\n";

        $markdown .= "2. **Supervisor Role Testing**\n";
        $markdown .= "   - Test API oversight and approval workflows\n";
        $markdown .= "   - Verify access to monitoring and analytics features\n";
        $markdown .= "   - Confirm limited access to user management\n\n";

        $markdown .= "3. **Maintainer Role Testing**\n";
        $markdown .= "   - Test API lifecycle management capabilities\n";
        $markdown .= "   - Verify access to own APIs and assigned projects\n";
        $markdown .= "   - Confirm restricted access to system-wide settings\n\n";

        $markdown .= "4. **Developer Role Testing**\n";
        $markdown .= "   - Test API creation and modification features\n";
        $markdown .= "   - Verify access to developer portal and documentation\n";
        $markdown .= "   - Confirm limited access to published APIs\n\n";

        $markdown .= "5. **Partner Role Testing**\n";
        $markdown .= "   - Test limited API access and consumption features\n";
        $markdown .= "   - Verify subscription and access request processes\n";
        $markdown .= "   - Confirm restricted system access\n\n";

        $markdown .= "6. **Guest Role Testing**\n";
        $markdown .= "   - Test read-only catalog access\n";
        $markdown .= "   - Verify public API documentation availability\n";
        $markdown .= "   - Confirm no access to management features\n\n";

        return $markdown;
    }

    public function generateUsers(Collection $users): string
    {
        $markdown = "# Users\n\n";
        $markdown .= '> Generated on: '.now()->toDateTimeString()."\n";
        $markdown .= "> Total Users: {$users->count()}\n\n";

        $markdown .= "## Users Overview\n\n";
        $markdown .= "| User ID | Name | Email | Email Verified | Roles | Created At |\n";
        $markdown .= "|---------|------|-------|----------------|-------|------------|\n";

        foreach ($users as $user) {
            $roles = implode(', ', $user['roles']);
            $verified = $user['email_verified'] ? '✅' : '❌';
            $markdown .= "| {$user['id']} | **{$user['name']}** | {$user['email']} | {$verified} | {$roles} | {$user['created_at']} |\n";
        }

        $markdown .= "\n## User Roles Distribution\n\n";
        $roleDistribution = $users->flatMap(fn ($user) => $user['roles'])->countBy();

        foreach ($roleDistribution as $role => $count) {
            $markdown .= "- **{$role}**: {$count} users\n";
        }

        $markdown .= "\n## UAT Test Users\n\n";
        $markdown .= "### Recommended Test User Matrix\n\n";
        $markdown .= "For comprehensive UAT testing, ensure you have test users for each role:\n\n";

        $uniqueRoles = $users->flatMap(fn ($user) => $user['roles'])->unique();

        foreach ($uniqueRoles as $role) {
            $roleUsers = $users->filter(fn ($user) => in_array($role, $user['roles']))->take(2);

            $markdown .= "#### {$role} Role\n\n";

            if ($roleUsers->isNotEmpty()) {
                $markdown .= "**Available Test Users:**\n";
                foreach ($roleUsers as $user) {
                    $markdown .= "- {$user['name']} ({$user['email']})\n";
                }
            } else {
                $markdown .= "⚠️ **No users found with {$role} role - Create test users before UAT**\n";
            }
            $markdown .= "\n";
        }

        $markdown .= "## Pre-UAT User Checklist\n\n";
        $markdown .= "- [ ] All test users have verified email addresses\n";
        $markdown .= "- [ ] Each role has at least one test user assigned\n";
        $markdown .= "- [ ] Test user passwords are documented and accessible to UAT team\n";
        $markdown .= "- [ ] Multi-factor authentication is configured for admin users (if enabled)\n";
        $markdown .= "- [ ] User permissions are properly assigned and tested\n\n";

        return $markdown;
    }

    public function generateAvailableModules(array $modules): string
    {
        $markdown = "# Available Modules Overview\n\n";
        $markdown .= '> Generated on: '.now()->toDateTimeString()."\n";
        $markdown .= '> Total Modules: '.count($modules)."\n\n";

        $markdown .= "## Modules Summary\n\n";
        $markdown .= "| Module | Routes | File |\n";
        $markdown .= "|--------|--------|----- |\n";

        foreach ($modules as $index => $module) {
            $fileNumber = str_pad((string) ($index + 5), 2, '0', STR_PAD_LEFT);
            $markdown .= "| **{$module['module']}** | ".count($module['routes'])." | `{$fileNumber}-module-{$module['module']}.md` |\n";
        }

        $markdown .= "\n## General UAT Testing Guidelines\n\n";
        $markdown .= "### Pre-Testing Checklist\n\n";
        $markdown .= "- [ ] All modules are deployed and accessible\n";
        $markdown .= "- [ ] Database is properly seeded with test data\n";
        $markdown .= "- [ ] All external dependencies are available (Kong Gateway, etc.)\n";
        $markdown .= "- [ ] Test users are created for each role\n";
        $markdown .= "- [ ] Browser compatibility testing setup is ready\n\n";

        $markdown .= "### Testing Methodology\n\n";
        $markdown .= "1. **Smoke Testing**: Verify all routes are accessible\n";
        $markdown .= "2. **Functional Testing**: Test core business logic for each module\n";
        $markdown .= "3. **Authorization Testing**: Verify role-based access controls\n";
        $markdown .= "4. **Policy Testing**: Verify policy-based authorization rules\n";
        $markdown .= "5. **Integration Testing**: Test module interactions\n";
        $markdown .= "6. **Security Testing**: Test for common vulnerabilities\n\n";

        $markdown .= "### Bug Reporting Template\n\n";
        $markdown .= "When reporting issues found during UAT:\n\n";
        $markdown .= "**Bug Report Template:**\n";
        $markdown .= "- **Test Case ID**: [TC-XXX-XXX]\n";
        $markdown .= "- **Module**: [Module Name]\n";
        $markdown .= "- **Route**: [Route URI]\n";
        $markdown .= "- **User Role**: [Role being tested]\n";
        $markdown .= "- **Expected Behavior**: [What should happen]\n";
        $markdown .= "- **Actual Behavior**: [What actually happened]\n";
        $markdown .= "- **Steps to Reproduce**: [Detailed steps]\n";
        $markdown .= "- **Browser/Environment**: [Testing environment details]\n";
        $markdown .= "- **Severity**: [Critical/High/Medium/Low]\n\n";

        return $markdown;
    }

    public function generateModuleTestSuite(array $module, int $moduleIndex): string
    {
        $moduleName = $module['module'];
        $modulePrefix = strtoupper(substr($moduleName, 0, 3));

        $markdown = "# {$moduleName} Module - UAT Test Suite\n\n";
        $markdown .= '> Generated on: '.now()->toDateTimeString()."\n";
        $markdown .= "> Module: {$moduleName}\n";
        $markdown .= '> Routes: '.count($module['routes'])."\n\n";

        $markdown .= "## Module Overview\n\n";
        $markdown .= "| Route URI | Route Name | Action | Middleware | Prerequisites |\n";
        $markdown .= "|-----------|------------|--------|------------|---------------|\n";

        foreach ($module['routes'] as $route) {
            $routeName = $route['name'] ?? '_unnamed_';
            $middleware = $this->formatMiddlewareForDisplay($route['middleware']);
            $action = class_basename($route['action']);
            $prerequisiteCount = count($route['prerequisites']);
            $prerequisiteText = $prerequisiteCount > 0 ? "{$prerequisiteCount} item(s)" : 'None';

            $markdown .= "| `/{$route['uri']}` | {$routeName} | {$action} | {$middleware} | {$prerequisiteText} |\n";
        }

        $markdown .= "\n## Test Cases\n\n";

        $testCaseCounter = 1;
        foreach ($module['routes'] as $routeIndex => $route) {
            $routeId = str_pad((string) ($routeIndex + 1), 2, '0', STR_PAD_LEFT);

            $markdown .= "### Route: `/{$route['uri']}`\n\n";

            // Prerequisites Section
            if (! empty($route['prerequisites'])) {
                $markdown .= "#### Prerequisites\n\n";
                foreach ($route['prerequisites'] as $prereqIndex => $prerequisite) {
                    $markdown .= "**{$prerequisite['type']}:**\n";
                    $markdown .= "- **Description**: {$prerequisite['description']}\n";
                    $markdown .= "- **Setup Action**: {$prerequisite['action']}\n";
                    $markdown .= "- **Validation**: {$prerequisite['validation']}\n\n";
                }
            }

            // Basic Functionality Test
            $testId = "TC-{$modulePrefix}-{$routeId}-001";
            $markdown .= "#### {$testId}: Basic Access Test\n\n";
            $markdown .= "**Test Objective**: Verify route is accessible and loads without errors\n\n";
            $markdown .= "**Prerequisites**:\n";
            foreach ($route['prerequisites'] as $prerequisite) {
                $markdown .= "- {$prerequisite['description']}\n";
            }
            if (empty($route['prerequisites'])) {
                $markdown .= "- None\n";
            }
            $markdown .= "\n**Test Steps**:\n";
            $markdown .= "1. Navigate to `/{$route['uri']}`\n";
            $markdown .= "2. Wait for page to load completely\n";
            $markdown .= "3. Verify page content is displayed\n\n";
            $markdown .= "**Expected Result**: Page loads successfully without errors\n\n";
            $markdown .= "**Status**: [ ] Pass [ ] Fail [ ] Not Tested\n\n";
            $markdown .= "**Notes**: ________________________________\n\n";
            $testCaseCounter++;

            // Authorization Tests
            if (in_array('auth', $route['middleware'])) {
                $testId = "TC-{$modulePrefix}-{$routeId}-002";
                $markdown .= "#### {$testId}: Authentication Required Test\n\n";
                $markdown .= "**Test Objective**: Verify unauthenticated users are redirected to login\n\n";
                $markdown .= "**Prerequisites**:\n";
                $markdown .= "- User must be logged out\n";
                $markdown .= "- Clear all browser sessions\n\n";
                $markdown .= "**Test Steps**:\n";
                $markdown .= "1. Ensure user is not logged in\n";
                $markdown .= "2. Navigate directly to `/{$route['uri']}`\n";
                $markdown .= "3. Observe browser behavior\n\n";
                $markdown .= "**Expected Result**: User is redirected to login page\n\n";
                $markdown .= "**Status**: [ ] Pass [ ] Fail [ ] Not Tested\n\n";
                $markdown .= "**Notes**: ________________________________\n\n";
                $testCaseCounter++;
            }

            // Role-based Authorization Tests
            foreach ($route['middleware'] as $middlewareName) {
                // Skip non-string middleware (Closures, objects, etc.)
                if (! is_string($middlewareName)) {
                    continue;
                }

                if (str_starts_with($middlewareName, 'role:')) {
                    $role = str_replace('role:', '', $middlewareName);
                    $testId = "TC-{$modulePrefix}-{$routeId}-".str_pad((string) $testCaseCounter, 3, '0', STR_PAD_LEFT);

                    $markdown .= "#### {$testId}: Role Authorization Test - {$role}\n\n";
                    $markdown .= "**Test Objective**: Verify only users with '{$role}' role can access route\n\n";
                    $markdown .= "**Prerequisites**:\n";
                    $markdown .= "- Test user without '{$role}' role\n";
                    $markdown .= "- Test user with '{$role}' role\n\n";
                    $markdown .= "**Test Steps**:\n";
                    $markdown .= "1. Login with user WITHOUT '{$role}' role\n";
                    $markdown .= "2. Navigate to `/{$route['uri']}`\n";
                    $markdown .= "3. Verify access is denied (403 or redirect)\n";
                    $markdown .= "4. Logout and login with user WITH '{$role}' role\n";
                    $markdown .= "5. Navigate to `/{$route['uri']}`\n";
                    $markdown .= "6. Verify access is granted\n\n";
                    $markdown .= "**Expected Result**: Access denied for unauthorized user, granted for authorized user\n\n";
                    $markdown .= "**Status**: [ ] Pass [ ] Fail [ ] Not Tested\n\n";
                    $markdown .= "**Notes**: ________________________________\n\n";
                    $testCaseCounter++;
                }
            }

            // Policy-based Authorization Tests
            foreach ($route['prerequisites'] as $prerequisite) {
                if ($prerequisite['type'] === 'policy_authorization') {
                    $testId = "TC-{$modulePrefix}-{$routeId}-".str_pad((string) $testCaseCounter, 3, '0', STR_PAD_LEFT);

                    $markdown .= "#### {$testId}: Policy Authorization Test - {$prerequisite['policy']}::{$prerequisite['method']}\n\n";
                    $markdown .= "**Test Objective**: {$prerequisite['description']}\n\n";
                    $markdown .= "**Prerequisites**:\n";
                    if (! empty($prerequisite['permissions_required'])) {
                        foreach ($prerequisite['permissions_required'] as $permission) {
                            $markdown .= "- Test user with '{$permission}' permission\n";
                        }
                        $markdown .= "- Test user without required permissions\n";
                    } else {
                        $markdown .= "- Test user with appropriate authorization\n";
                        $markdown .= "- Test user without authorization\n";
                    }
                    $markdown .= "\n**Test Steps**:\n";
                    $markdown .= "1. {$prerequisite['action']}\n";
                    $markdown .= "2. Navigate to `/{$route['uri']}`\n";
                    $markdown .= "3. {$prerequisite['validation']}\n";
                    if (! empty($prerequisite['permissions_required'])) {
                        $markdown .= "4. Logout and login with user WITHOUT required permissions\n";
                        $markdown .= "5. Navigate to `/{$route['uri']}`\n";
                        $markdown .= "6. Verify access is denied (403, 404, or redirect)\n";
                    }
                    $markdown .= "\n**Expected Result**: Access granted for authorized user";
                    if (! empty($prerequisite['permissions_required'])) {
                        $markdown .= ', denied for unauthorized user';
                    }
                    $markdown .= "\n\n**Policy**: {$prerequisite['policy']}\n";
                    $markdown .= "**Method**: {$prerequisite['method']}\n";
                    if (! empty($prerequisite['permissions_required'])) {
                        $markdown .= '**Required Permissions**: '.implode(', ', $prerequisite['permissions_required'])."\n";
                    }
                    $markdown .= "\n**Status**: [ ] Pass [ ] Fail [ ] Not Tested\n\n";
                    $markdown .= "**Notes**: ________________________________\n\n";
                    $testCaseCounter++;
                }
            }

            $markdown .= "---\n\n";
        }

        $markdown .= "## Test Summary\n\n";
        $markdown .= "| Status | Count |\n";
        $markdown .= "|-----------|-------|\n";
        $markdown .= '| **Total Test Cases** | '.($testCaseCounter - 1)." |\n";
        $markdown .= "| **Passed** | _____ |\n";
        $markdown .= "| **Failed** | _____ |\n";
        $markdown .= "| **Not Tested** | _____ |\n\n";

        $markdown .= "**Test Completion**: _____%\n\n";
        $markdown .= "**Tester Name**: ___________________________\n\n";
        $markdown .= "**Test Date**: _____________________________\n\n";
        $markdown .= "**Notes**: \n";
        $markdown .= "_________________________________________________\n\n";
        $markdown .= "_________________________________________________\n\n";

        return $markdown;
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
