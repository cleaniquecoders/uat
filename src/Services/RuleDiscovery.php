<?php

declare(strict_types=1);

namespace CleaniqueCoders\Uat\Services;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;

class RuleDiscovery
{
    /**
     * Discover middleware rules by analyzing actual middleware classes
     */
    public function discoverMiddlewareRules(array $middleware): array
    {
        // Use array_map to analyze each middleware, then array_filter to remove nulls
        return array_values(array_filter(
            array_map([$this, 'analyzeMiddleware'], $middleware)
        ));
    }

    /**
     * Discover policy rules by analyzing actual policy classes
     */
    public function discoverPolicyRules(Route $route): array
    {
        $prerequisites = [];
        $action = $route->getActionName();

        if (str_contains($action, '@')) {
            [$controller, $method] = explode('@', $action);
            $controllerName = class_basename($controller);

            // Try to find and analyze the policy for this controller
            $policyClass = $this->findPolicyForController($controller);

            if ($policyClass) {
                $policyMethod = $this->determinePolicyMethod($method, $route->getName());
                $policyInfo = $this->analyzePolicyMethod($policyClass, $policyMethod);

                if ($policyInfo) {
                    $prerequisites[] = [
                        'type' => 'policy_authorization',
                        'description' => $policyInfo['description'],
                        'action' => $policyInfo['action'],
                        'validation' => $policyInfo['validation'],
                        'policy' => class_basename($policyClass),
                        'method' => $policyMethod,
                        'permissions_required' => $policyInfo['permissions'] ?? [],
                    ];
                }
            }
        }

        return $prerequisites;
    }

    /**
     * Analyze middleware to determine its requirements
     */
    private function analyzeMiddleware($middlewareName): ?array
    {
        // Handle non-string middleware (like Closures)
        if (! is_string($middlewareName)) {
            if ($middlewareName instanceof \Closure) {
                return [
                    'type' => 'closure_middleware',
                    'description' => 'Custom closure middleware',
                    'action' => 'Ensure closure middleware requirements are met',
                    'validation' => 'Manually verify closure middleware behavior',
                ];
            }

            // Handle other middleware objects/classes
            if (is_object($middlewareName)) {
                $className = get_class($middlewareName);

                return [
                    'type' => 'object_middleware',
                    'description' => "Object middleware: {$className}",
                    'action' => "Ensure requirements for {$className} are met",
                    'validation' => "Verify {$className} middleware passes",
                    'middleware_class' => $className,
                ];
            }

            // Skip other non-string types
            return null;
        }

        // Handle built-in Laravel middleware patterns
        if ($this->isBuiltInMiddleware($middlewareName)) {
            return $this->getBuiltInMiddlewareRule($middlewareName);
        }

        // Handle dynamic middleware patterns
        if ($this->isDynamicMiddleware($middlewareName)) {
            return $this->getDynamicMiddlewareRule($middlewareName);
        }

        // Try to find and analyze custom middleware class
        $middlewareClass = $this->findMiddlewareClass($middlewareName);
        if ($middlewareClass) {
            return $this->analyzeCustomMiddleware($middlewareClass, $middlewareName);
        }

        return null;
    }

    /**
     * Check if middleware is built-in Laravel middleware
     */
    private function isBuiltInMiddleware(string $middleware): bool
    {
        $builtInMiddleware = [
            'auth', 'guest', 'verified', 'throttle', 'can', 'signed', 'password.confirm',
        ];

        foreach ($builtInMiddleware as $builtin) {
            if ($middleware === $builtin || str_starts_with($middleware, $builtin.':')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get rule for built-in middleware
     */
    private function getBuiltInMiddlewareRule(string $middleware): array
    {
        $baseMiddleware = explode(':', $middleware)[0];

        return match ($baseMiddleware) {
            'auth' => [
                'type' => 'authentication',
                'description' => 'User must be authenticated',
                'action' => 'Login with valid credentials',
                'validation' => 'Verify user session exists',
            ],
            'guest' => [
                'type' => 'unauthenticated',
                'description' => 'User must not be authenticated',
                'action' => 'Ensure no active user session',
                'validation' => 'Verify no user session exists',
            ],
            'verified' => [
                'type' => 'email_verification',
                'description' => 'User email must be verified',
                'action' => 'Ensure user email is verified',
                'validation' => 'Check email_verified_at is not null',
            ],
            'can' => $this->getCanMiddlewareRule($middleware),
            default => [
                'type' => 'unknown_builtin',
                'description' => "Built-in middleware: {$middleware}",
                'action' => "Ensure requirements for {$middleware} are met",
                'validation' => "Verify {$middleware} middleware passes",
            ],
        };
    }

    /**
     * Handle 'can:permission' middleware
     */
    private function getCanMiddlewareRule(string $middleware): array
    {
        $permission = str_replace('can:', '', $middleware);

        return [
            'type' => 'gate_authorization',
            'description' => "User must pass '{$permission}' gate check",
            'action' => "Login with user authorized for '{$permission}'",
            'validation' => "Verify gate check passes for '{$permission}'",
        ];
    }

    /**
     * Check if middleware follows dynamic pattern (role:*, permission:*)
     */
    private function isDynamicMiddleware(string $middleware): bool
    {
        $dynamicPatterns = ['role:', 'permission:', 'can:', 'throttle:'];

        foreach ($dynamicPatterns as $pattern) {
            if (str_starts_with($middleware, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get rule for dynamic middleware patterns
     */
    private function getDynamicMiddlewareRule(string $middleware): array
    {
        if (str_starts_with($middleware, 'role:')) {
            $role = str_replace('role:', '', $middleware);

            return [
                'type' => 'role_authorization',
                'description' => "User must have '{$role}' role",
                'action' => "Login with user assigned to '{$role}' role",
                'validation' => "Verify user has '{$role}' role",
            ];
        }

        if (str_starts_with($middleware, 'permission:')) {
            $permission = str_replace('permission:', '', $middleware);

            return [
                'type' => 'permission_authorization',
                'description' => "User must have '{$permission}' permission",
                'action' => "Login with user having '{$permission}' permission",
                'validation' => "Verify user has '{$permission}' permission",
            ];
        }

        if (str_starts_with($middleware, 'throttle:')) {
            return [
                'type' => 'rate_limiting',
                'description' => 'Request rate limiting applies',
                'action' => 'Ensure requests do not exceed rate limits',
                'validation' => 'Verify rate limiting behavior',
            ];
        }

        return [
            'type' => 'dynamic_middleware',
            'description' => "Dynamic middleware: {$middleware}",
            'action' => "Ensure requirements for {$middleware} are met",
            'validation' => "Verify {$middleware} middleware passes",
        ];
    }

    /**
     * Find middleware class file
     */
    private function findMiddlewareClass(string $middlewareName): ?string
    {
        // Try to resolve middleware from kernel
        $kernel = app('Illuminate\Contracts\Http\Kernel');
        $middlewareGroups = $kernel->getMiddlewareGroups();
        $routeMiddleware = $kernel->getRouteMiddleware();

        if (isset($routeMiddleware[$middlewareName])) {
            return $routeMiddleware[$middlewareName];
        }

        // Try to find custom middleware by name
        $middlewarePath = app_path('Http/Middleware');
        if (File::exists($middlewarePath)) {
            $middlewareFiles = File::files($middlewarePath);

            foreach ($middlewareFiles as $file) {
                $className = pathinfo($file->getFilename(), PATHINFO_FILENAME);
                if (Str::snake($className) === $middlewareName ||
                    Str::kebab($className) === $middlewareName ||
                    strtolower($className) === strtolower($middlewareName)) {
                    return "App\\Http\\Middleware\\{$className}";
                }
            }
        }

        return null;
    }

    /**
     * Analyze custom middleware class
     */
    private function analyzeCustomMiddleware(string $middlewareClass, string $middlewareName): array
    {
        try {
            $reflection = new ReflectionClass($middlewareClass);
            $docComment = $reflection->getDocComment();

            // Try to extract information from docblock
            $description = $this->extractDescriptionFromDocComment($docComment) ?:
                          "Custom middleware: {$middlewareName}";

            return [
                'type' => 'custom_middleware',
                'description' => $description,
                'action' => "Ensure requirements for {$middlewareName} are met",
                'validation' => "Verify {$middlewareName} middleware passes",
                'middleware_class' => $middlewareClass,
            ];
        } catch (\Exception $e) {
            return [
                'type' => 'unknown_middleware',
                'description' => "Unknown middleware: {$middlewareName}",
                'action' => "Investigate requirements for {$middlewareName}",
                'validation' => "Manually verify {$middlewareName} behavior",
            ];
        }
    }

    /**
     * Find policy class for a controller
     */
    private function findPolicyForController(string $controllerClass): ?string
    {
        // Try to find policy by convention
        $controllerName = class_basename($controllerClass);
        $modelName = str_replace('Controller', '', $controllerName);

        // Common policy naming patterns
        $possiblePolicies = [
            "App\\Policies\\{$modelName}Policy",
            "App\\Policies\\{$controllerName}Policy",
            'App\\Policies\\'.Str::singular($modelName).'Policy',
        ];

        foreach ($possiblePolicies as $policyClass) {
            if (class_exists($policyClass)) {
                return $policyClass;
            }
        }

        // Try to find from Gate policy mappings
        try {
            $gate = app('Illuminate\Contracts\Auth\Access\Gate');
            $policies = $gate->policies();

            // Look for model-based policies
            foreach ($policies as $model => $policy) {
                $modelBasename = class_basename($model);
                if (str_contains($controllerName, $modelBasename)) {
                    return $policy;
                }
            }
        } catch (\Exception $e) {
            // Gate may not be available or no policies registered
        }

        return null;
    }

    /**
     * Analyze policy method to extract requirements
     */
    private function analyzePolicyMethod(string $policyClass, string $method): ?array
    {
        try {
            $reflection = new ReflectionClass($policyClass);

            if (! $reflection->hasMethod($method)) {
                return null;
            }

            $methodReflection = $reflection->getMethod($method);
            $docComment = $methodReflection->getDocComment();

            // Extract information from method docblock or source code
            $description = $this->extractDescriptionFromDocComment($docComment) ?:
                          "Must pass {$method} policy check";

            // Try to detect required permissions from method body
            $permissions = $this->extractPermissionsFromPolicyMethod($methodReflection, $policyClass);

            return [
                'description' => $description,
                'action' => "Login with user authorized for {$method} action",
                'validation' => "Verify {$method} policy check passes",
                'permissions' => $permissions,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Determine policy method from controller method
     */
    private function determinePolicyMethod(string $controllerMethod, ?string $routeName): string
    {
        // Standard RESTful mappings
        $methodMap = config('uat.method_mapping');

        return $methodMap[$controllerMethod] ?? $controllerMethod;
    }

    /**
     * Extract description from docblock comment
     */
    private function extractDescriptionFromDocComment($docComment): ?string
    {
        if (! $docComment) {
            return null;
        }

        // Simple extraction of first line after /**
        preg_match('/\/\*\*\s*\n\s*\*\s*(.+?)(?:\n|\*\/)/s', $docComment, $matches);

        return isset($matches[1]) ? trim($matches[1]) : null;
    }

    /**
     * Extract permissions from policy method source code
     */
    private function extractPermissionsFromPolicyMethod(ReflectionMethod $method, string $policyClass): array
    {
        try {
            // Get method source code
            $filename = $method->getFileName();
            $startLine = $method->getStartLine();
            $endLine = $method->getEndLine();

            if (! $filename) {
                return [];
            }

            $methodSource = '';
            $fileObject = new \SplFileObject($filename);
            for ($line = $startLine; $line <= $endLine; $line++) {
                $fileObject->seek($line - 1);
                $methodSource .= $fileObject->current();
            }

            // Look for permission checks in the source
            $permissions = [];

            // Pattern: hasPermissionTo('permission-name')
            preg_match_all('/hasPermissionTo\([\'"]([^\'"]+)[\'"]\)/', $methodSource, $matches);
            if (! empty($matches[1])) {
                $permissions = array_merge($permissions, $matches[1]);
            }

            // Pattern: can('permission-name')
            preg_match_all('/can\([\'"]([^\'"]+)[\'"]\)/', $methodSource, $matches);
            if (! empty($matches[1])) {
                $permissions = array_merge($permissions, $matches[1]);
            }

            // Pattern: hasRole('role-name')
            preg_match_all('/hasRole\([\'"]([^\'"]+)[\'"]\)/', $methodSource, $matches);
            if (! empty($matches[1])) {
                $permissions = array_merge($permissions, array_map(fn ($role) => "role:{$role}", $matches[1]));
            }

            return array_unique($permissions);
        } catch (\Exception $e) {
            return [];
        }
    }
}
