<?php

declare(strict_types=1);

namespace CleaniqueCoders\Uat\Services;

use Illuminate\Support\Facades\File;

class ProjectAnalyzer
{
    /**
     * Analyze the entire project to discover testing patterns automatically
     */
    public function analyzeProject(): array
    {
        return [
            'middleware_patterns' => $this->discoverMiddlewarePatterns(),
            'policy_patterns' => $this->discoverPolicyPatterns(),
            'authentication_methods' => $this->discoverAuthenticationMethods(),
            'authorization_patterns' => $this->discoverAuthorizationPatterns(),
            'validation_patterns' => $this->discoverValidationPatterns(),
        ];
    }

    /**
     * Discover middleware patterns by scanning middleware directory
     */
    private function discoverMiddlewarePatterns(): array
    {
        $patterns = [];
        $middlewarePath = app_path('Http/Middleware');

        if (! File::exists($middlewarePath)) {
            return $patterns;
        }

        $middlewareFiles = File::files($middlewarePath);

        foreach ($middlewareFiles as $file) {
            $className = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            $content = File::get($file->getPathname());

            $patterns[] = [
                'class' => $className,
                'file' => $file->getPathname(),
                'requirements' => $this->extractMiddlewareRequirements($content),
                'description' => $this->extractClassDescription($content),
            ];
        }

        return $patterns;
    }

    /**
     * Discover policy patterns by scanning policies directory
     */
    private function discoverPolicyPatterns(): array
    {
        $patterns = [];
        $policiesPath = app_path('Policies');

        if (! File::exists($policiesPath)) {
            return $patterns;
        }

        $policyFiles = File::files($policiesPath);

        foreach ($policyFiles as $file) {
            $className = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            $content = File::get($file->getPathname());

            $patterns[] = [
                'class' => $className,
                'file' => $file->getPathname(),
                'methods' => $this->extractPolicyMethods($content),
                'permissions' => $this->extractPolicyPermissions($content),
                'description' => $this->extractClassDescription($content),
            ];
        }

        return $patterns;
    }

    /**
     * Discover authentication methods from auth configuration and guards
     */
    private function discoverAuthenticationMethods(): array
    {
        $authConfig = config('auth', []);
        $methods = [];

        // Analyze guards
        if (isset($authConfig['guards'])) {
            foreach ($authConfig['guards'] as $guard => $config) {
                $methods[] = [
                    'guard' => $guard,
                    'driver' => $config['driver'] ?? 'unknown',
                    'provider' => $config['provider'] ?? null,
                    'requirements' => $this->getGuardRequirements($guard, $config),
                ];
            }
        }

        // Check for custom authentication methods
        $authPath = app_path('Http/Controllers/Auth');
        if (File::exists($authPath)) {
            $authFiles = File::files($authPath);
            foreach ($authFiles as $file) {
                $content = File::get($file->getPathname());
                $methods = array_merge($methods, $this->extractAuthMethods($content));
            }
        }

        return $methods;
    }

    /**
     * Discover authorization patterns from controllers and policies
     */
    private function discoverAuthorizationPatterns(): array
    {
        $patterns = [];

        // Scan controllers for authorization patterns
        $controllersPath = app_path('Http/Controllers');
        if (File::exists($controllersPath)) {
            $this->scanDirectoryForAuthPatterns($controllersPath, $patterns);
        }

        return $patterns;
    }

    /**
     * Discover validation patterns from form requests and controllers
     */
    private function discoverValidationPatterns(): array
    {
        $patterns = [];

        // Scan form requests
        $requestsPath = app_path('Http/Requests');
        if (File::exists($requestsPath)) {
            $requestFiles = File::allFiles($requestsPath);
            foreach ($requestFiles as $file) {
                $content = File::get($file->getPathname());
                $validationRules = $this->extractValidationRules($content);
                if (! empty($validationRules)) {
                    $patterns[] = [
                        'file' => $file->getPathname(),
                        'class' => pathinfo($file->getFilename(), PATHINFO_FILENAME),
                        'rules' => $validationRules,
                    ];
                }
            }
        }

        return $patterns;
    }

    /**
     * Extract middleware requirements from source code
     */
    private function extractMiddlewareRequirements(string $content): array
    {
        $requirements = [];

        // Look for common authentication checks
        if (str_contains($content, 'Auth::check()') || str_contains($content, 'auth()->check()')) {
            $requirements[] = 'authentication_required';
        }

        if (str_contains($content, 'Auth::guest()') || str_contains($content, 'auth()->guest()')) {
            $requirements[] = 'guest_required';
        }

        // Look for role/permission checks
        if (str_contains($content, 'hasRole(') || str_contains($content, '->roles')) {
            $requirements[] = 'role_check';
        }

        if (str_contains($content, 'hasPermission(') || str_contains($content, 'can(')) {
            $requirements[] = 'permission_check';
        }

        // Look for email verification
        if (str_contains($content, 'email_verified_at') || str_contains($content, 'hasVerifiedEmail')) {
            $requirements[] = 'email_verification';
        }

        return $requirements;
    }

    /**
     * Extract policy methods from source code
     */
    private function extractPolicyMethods(string $content): array
    {
        $methods = [];

        // Extract public method names
        preg_match_all('/public function (\w+)\s*\(/', $content, $matches);
        if (! empty($matches[1])) {
            foreach ($matches[1] as $method) {
                if (! in_array($method, ['__construct', '__call', '__get', '__set'])) {
                    $methods[] = $method;
                }
            }
        }

        return $methods;
    }

    /**
     * Extract permissions from policy source code
     */
    private function extractPolicyPermissions(string $content): array
    {
        $permissions = [];

        // Look for permission strings
        preg_match_all('/[\'"]([a-z-]+(?:-[a-z-]+)*)[\'"]/', $content, $matches);
        if (! empty($matches[1])) {
            foreach ($matches[1] as $match) {
                if (str_contains($match, '-') && strlen($match) > 5) {
                    $permissions[] = $match;
                }
            }
        }

        return array_unique($permissions);
    }

    /**
     * Extract class description from docblock
     */
    private function extractClassDescription(string $content): ?string
    {
        preg_match('/\/\*\*\s*\n\s*\*\s*(.+?)(?:\n|\*\/)/s', $content, $matches);

        return isset($matches[1]) ? trim($matches[1]) : null;
    }

    /**
     * Get requirements for authentication guard
     */
    private function getGuardRequirements(string $guard, array $config): array
    {
        $requirements = [];

        switch ($config['driver'] ?? '') {
            case 'session':
                $requirements[] = 'session_authentication';
                break;
            case 'token':
                $requirements[] = 'token_authentication';
                break;
            case 'jwt':
                $requirements[] = 'jwt_authentication';
                break;
            case 'sanctum':
                $requirements[] = 'sanctum_token';
                break;
        }

        return $requirements;
    }

    /**
     * Extract authentication methods from controller source
     */
    private function extractAuthMethods(string $content): array
    {
        $methods = [];

        // Look for login methods
        if (str_contains($content, 'function login')) {
            $methods[] = [
                'guard' => 'custom',
                'driver' => 'standard_login',
                'requirements' => ['standard_authentication'],
            ];
        }

        // Look for social authentication
        if (str_contains($content, 'Socialite') || str_contains($content, 'social')) {
            $methods[] = [
                'guard' => 'social',
                'driver' => 'social_authentication',
                'requirements' => ['social_authentication'],
            ];
        }

        // Look for 2FA/MFA
        if (str_contains($content, '2fa') || str_contains($content, 'two-factor')) {
            $methods[] = [
                'guard' => '2fa',
                'driver' => 'two_factor_authentication',
                'requirements' => ['two_factor_authentication'],
            ];
        }

        return $methods;
    }

    /**
     * Scan directory for authorization patterns
     */
    private function scanDirectoryForAuthPatterns(string $path, array &$patterns): void
    {
        $files = File::allFiles($path);

        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $content = File::get($file->getPathname());

                // Look for authorize calls
                preg_match_all('/\$this->authorize\([\'"]([^\'"]+)[\'"]/', $content, $matches);
                if (! empty($matches[1])) {
                    $patterns[] = [
                        'file' => $file->getPathname(),
                        'type' => 'controller_authorization',
                        'permissions' => $matches[1],
                    ];
                }

                // Look for Gate::allows calls
                preg_match_all('/Gate::allows\([\'"]([^\'"]+)[\'"]/', $content, $matches);
                if (! empty($matches[1])) {
                    $patterns[] = [
                        'file' => $file->getPathname(),
                        'type' => 'gate_authorization',
                        'gates' => $matches[1],
                    ];
                }
            }
        }
    }

    /**
     * Extract validation rules from form request
     */
    private function extractValidationRules(string $content): array
    {
        $rules = [];

        // Look for rules method
        preg_match('/public function rules\(\)[\s\S]*?return\s*\[([\s\S]*?)\];/', $content, $matches);
        if (isset($matches[1])) {
            // Simple extraction of field names
            preg_match_all('/[\'"](\w+)[\'"]/', $matches[1], $fieldMatches);
            if (! empty($fieldMatches[1])) {
                $rules = array_unique($fieldMatches[1]);
            }
        }

        return $rules;
    }
}
