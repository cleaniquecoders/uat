<?php

declare(strict_types=1);

namespace CleaniqueCoders\Uat\Services;

use CleaniqueCoders\Uat\Contracts\Data;
use CleaniqueCoders\Uat\Contracts\Rule;
use Illuminate\Foundation\Auth\User;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route as RouteFacade;

class DataService implements Data
{
    private ?Rule $ruleDiscoveryService = null;

    public function __construct()
    {
        try {
            $ruleService = config('uat.services.rule');
            if ($ruleService) {
                // If it's already an object (for testing), use it directly
                if (is_object($ruleService)) {
                    $this->ruleDiscoveryService = $ruleService;
                }
                // If it's a string class name, instantiate it
                elseif (is_string($ruleService) && class_exists($ruleService)) {
                    $this->ruleDiscoveryService = new $ruleService;
                } else {
                    $this->ruleDiscoveryService = null;
                }
            } else {
                $this->ruleDiscoveryService = null;
            }
        } catch (\Exception $e) {
            $this->ruleDiscoveryService = null;
        }
    }

    public function getProjectInformation(): array
    {
        $composer = json_decode(File::get(base_path('composer.json')), true);

        return [
            'name' => $composer['name'] ?? 'Laravel',
            'description' => $composer['description'] ?? 'Laravel Application',
            'version' => $composer['version'] ?? '1.0.0',
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'generated_at' => now()->toDateTimeString(),
            'environment' => app()->environment(),
            'database_connection' => config('database.default'),
            'queue_connection' => config('queue.default'),
            'cache_driver' => config('cache.default'),
            'session_driver' => config('session.driver'),
            'mail_driver' => config('mail.default'),
        ];
    }

    public function getUsers(): Collection
    {
        return User::query()
            ->select(['id', 'name', 'email', 'created_at', 'email_verified_at'])
            ->orderBy('created_at')
            ->get()
            ->map(function (User $user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified' => $user->email_verified_at !== null,
                    'created_at' => $user->created_at?->toDateTimeString(),
                ];
            });
    }

    public function getAvailableModules(): array
    {
        // Use Artisan facade to get routes instead of reading files
        $routes = collect(RouteFacade::getRoutes())
            ->filter(function (Route $route) {
                // Only GET method routes for UAT testing
                return in_array('GET', $route->methods()) &&
                       // Exclude vendor routes (API routes, telescope, horizon, etc.)
                       ! $this->isExcludedRoute($route) &&
                       // Exclude routes with parameters for simplicity in UAT
                       ! str_contains($route->uri(), '{') &&
                       // Only include web routes (exclude API for now)
                       $this->isWebRoute($route);
            })
            ->map(function (Route $route) {
                $middleware = $route->gatherMiddleware();

                return [
                    'uri' => $route->uri(),
                    'name' => $route->getName(),
                    'action' => $route->getActionName(),
                    'middleware' => $middleware,
                    'prerequisites' => $this->getRoutePrerequisites($route, $middleware),
                    'module' => $this->extractModuleFromRoute($route),
                ];
            })
            ->groupBy('module')
            ->map(function (Collection $routes, string $module) {
                return [
                    'module' => $module,
                    'routes' => $routes->values()->toArray(),
                ];
            })
            ->values()
            ->toArray();

        return $routes;
    }

    public function getRoutePrerequisites(Route $route, array $middleware): array
    {
        $prerequisites = [];

        // Use discovery service for dynamic rule discovery if available
        if ($this->ruleDiscoveryService) {
            try {
                $prerequisites = array_merge($prerequisites, $this->ruleDiscoveryService->discoverMiddlewareRules($middleware));
                $prerequisites = array_merge($prerequisites, $this->ruleDiscoveryService->discoverPolicyRules($route));
            } catch (\Exception $e) {
                // Discovery failed, will use fallback
                Log::warning('Dynamic rule discovery failed: '.$e->getMessage());
            }
        }

        // Fallback to config-based rules if discovery doesn't find anything or failed
        if (empty($prerequisites)) {
            $prerequisites = array_merge($prerequisites, $this->getMiddlewarePrerequisites($middleware));
            $prerequisites = array_merge($prerequisites, $this->getPolicyPrerequisites($route));
        }

        return $prerequisites;
    }

    public function getMiddlewarePrerequisites(array $middleware): array
    {
        $prerequisites = [];
        $middlewareRules = config('uat.rules.middleware', []);
        $patternRules = config('uat.rules.pattern', []);

        foreach ($middleware as $middlewareName) {
            // Check for exact middleware match
            if (isset($middlewareRules[$middlewareName])) {
                $prerequisites[] = $middlewareRules[$middlewareName];

                continue;
            }

            // Check for pattern matches (role:*, permission:*, can:*)
            $patternMatched = false;
            foreach ($patternRules as $pattern => $rule) {
                $patternPrefix = str_replace('*', '', $pattern);

                if (str_starts_with($middlewareName, $patternPrefix)) {
                    $placeholder = str_replace($patternPrefix, '', $middlewareName);

                    // Replace placeholder in rule
                    $dynamicRule = [];
                    foreach ($rule as $key => $value) {
                        $dynamicRule[$key] = str_replace('{placeholder}', $placeholder, $value);
                    }

                    $prerequisites[] = $dynamicRule;
                    $patternMatched = true;
                    break;
                }
            }

            // If no pattern matched, you might want to log or handle unknown middleware
            if (! $patternMatched && ! isset($middlewareRules[$middlewareName])) {
                // Optional: Log unknown middleware for debugging
                Log::debug("Unknown middleware encountered in UAT generation: {$middlewareName}");
            }
        }

        return $prerequisites;
    }

    private function getPolicyPrerequisites(Route $route): array
    {
        $prerequisites = [];
        $action = $route->getActionName();

        // Extract controller and method from action
        if (str_contains($action, '@')) {
            [$controller, $method] = explode('@', $action);
            $controllerName = class_basename($controller);

            // Map controllers to their associated policies and permissions
            $policyMappings = $this->getPolicyMappings();

            if (isset($policyMappings[$controllerName])) {
                $policyInfo = $policyMappings[$controllerName];

                // Determine the policy method based on HTTP method and route name
                $policyMethod = $this->determinePolicyMethod($method, $route->getName());

                if (isset($policyInfo['methods'][$policyMethod])) {
                    $methodInfo = $policyInfo['methods'][$policyMethod];

                    $prerequisites[] = [
                        'type' => 'policy_authorization',
                        'description' => $methodInfo['description'],
                        'action' => $methodInfo['action'],
                        'validation' => $methodInfo['validation'],
                        'policy' => $policyInfo['policy'],
                        'method' => $policyMethod,
                        'permissions_required' => $methodInfo['permissions'] ?? [],
                    ];
                }
            }
        }

        return $prerequisites;
    }

    private function getPolicyMappings(): array
    {
        return config('uat.policy_mappings', []);
    }

    private function determinePolicyMethod(string $controllerMethod, ?string $routeName): string
    {
        $methodMap = config('uat.methods_mapping', []);

        // Check if we have a direct mapping
        if (isset($methodMap[$controllerMethod])) {
            return $methodMap[$controllerMethod];
        }

        // Fallback to the controller method name
        return $controllerMethod;
    }

    private function isExcludedRoute(Route $route): bool
    {
        $excludedPrefixes = config('uat.excluded_prefixes', []);

        foreach ($excludedPrefixes as $prefix) {
            if (str_starts_with($route->uri(), $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function isWebRoute(Route $route): bool
    {
        // Check if route belongs to web routes (has web middleware or no api prefix)
        $middleware = $route->gatherMiddleware();

        return in_array('web', $middleware) ||
               (! str_starts_with($route->uri(), 'api/') && ! in_array('api', $middleware));
    }

    private function extractModuleFromRoute(Route $route): string
    {
        $uri = $route->uri();
        $name = $route->getName();

        // Extract module from route name first
        if ($name) {
            $parts = explode('.', $name);
            if (count($parts) > 1) {
                return ucfirst($parts[0]);
            }
        }

        // Extract module from URI
        $uriParts = explode('/', trim($uri, '/'));
        if (! empty($uriParts[0]) && $uriParts[0] !== '/') {
            return ucfirst($uriParts[0]);
        }

        // Default to Dashboard for root routes
        return 'Dashboard';
    }
}
