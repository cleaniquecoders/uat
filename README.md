# User Acceptance Testing

[![Latest Version on Packagist](https://img.shields.io/packagist/v/cleaniquecoders/uat.svg?style=flat-square)](https://packagist.org/packages/cleaniquecoders/uat)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/cleaniquecoders/uat/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/cleaniquecoders/uat/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/cleaniquecoders/uat/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/cleaniquecoders/uat/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/cleaniquecoders/uat.svg?style=flat-square)](https://packagist.org/packages/cleaniquecoders/uat)

This package automatically generates comprehensive User Acceptance Testing (UAT) documentation for Laravel applications by analyzing your routes, middleware, policies, and authorization rules. It creates structured test scripts that help QA teams and stakeholders understand what needs to be tested and how to test it.

## Features

- **📋 Automatic UAT Script Generation**: Analyzes your Laravel application and generates detailed testing documentation
- **🔐 Authorization Testing**: Identifies middleware, policies, and authorization requirements for each route
- **📊 Multiple Output Formats**: Supports Markdown and JSON output formats
- **🎯 Route Analysis**: Analyzes all application routes and groups them by modules/controllers
- **👥 User Role Documentation**: Generates user role and permission requirements
- **⚙️ Configurable Rules**: Customizable middleware and authorization rule mappings
- **🚫 Smart Filtering**: Excludes development routes and packages from documentation

## What It Generates

The package creates comprehensive UAT documentation including:

1. **Project Information**: Technical stack, environment details, and configuration
2. **User Roles & Permissions**: Required user types and their capabilities
3. **Available Modules**: Organized overview of all testable application modules
4. **Module Test Suites**: Detailed testing scripts for each module including:
   - Route information and HTTP methods
   - Authentication and authorization requirements
   - Step-by-step testing instructions
   - Expected behaviors and validation criteria
   - Prerequisites and setup requirements

## Installation

You can install the package via composer:

```bash
composer require cleaniquecoders/uat
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag="uat-config"
```

## Configuration

The published configuration file (`config/uat.php`) allows you to customize:

- Output directory for generated documentation
- Middleware and authorization rule mappings
- Policy mappings for controllers
- Excluded route prefixes
- Custom service implementations
- Output format configurations

### Example Configuration

```php
return [
    // Output directory for UAT documentation
    'directory' => env('UAT_DIRECTORY', 'uat'),

    // Available output formats
    'formats' => [
        'markdown' => MarkdownGenerator::class,
        'json' => JsonGenerator::class,
    ],

    // Middleware rule mappings
    'rules' => [
        'middleware' => [
            'auth' => [
                'type' => 'authentication',
                'description' => 'User must be logged in',
                'action' => 'Navigate to /login and authenticate',
                'validation' => 'Verify user session is active',
            ],
        ],
        'pattern' => [
            'role:*' => [
                'type' => 'role_authorization',
                'description' => "User must have '{placeholder}' role",
                'action' => "Login with user assigned to '{placeholder}' role",
            ],
        ],
    ],

    // Controller policy mappings
    'policy_mappings' => [
        'UserController' => [
            'policy' => 'UserPolicy',
            'methods' => [
                'index' => [
                    'description' => 'User must have user security view permissions',
                    'permissions' => ['view-user-security'],
                    'roles' => ['superadmin', 'administrator'],
                ],
            ],
        ],
    ],
];
```

## Usage

### Generate UAT Documentation

Generate UAT scripts using the Artisan command:

```bash
# Generate Markdown documentation (default)
php artisan uat:generate

# Generate JSON documentation
php artisan uat:generate --format=json

# Specify custom output directory
php artisan uat:generate --output-dir=/path/to/custom/directory

# Generate with verbose output for debugging
php artisan uat:generate --verbose
```

### Programmatic Usage

You can also generate UAT scripts programmatically:

```php
use CleaniqueCoders\Uat\Actions\GenerateUatScript;

// Generate UAT scripts
$result = GenerateUatScript::run(
    outputDir: storage_path('uat-docs'),
    format: 'markdown'
);

// Result contains:
// - directory: Path to generated files
// - generated_files: Array of created file paths
// - date: Generation date
```

### Using the Facade

```php
use CleaniqueCoders\Uat\Facades\Uat;

// Access UAT services through the facade
$projectInfo = Uat::getProjectInformation();
$modules = Uat::getAvailableModules();
$users = Uat::getUsers();
```

## Generated Documentation Structure

When you run the command, it creates a directory structure like this:

```text
storage/uat/2024-01-15/
├── 01-project-info.md           # Project overview and technical details
├── 02-users.md                  # User roles and permissions
├── 03-available-modules.md      # Module overview
├── 05-module-dashboard.md       # Dashboard module test suite
├── 06-module-users.md          # Users module test suite
├── 07-module-posts.md          # Posts module test suite
└── ...                         # Additional module test suites
```

### Sample Generated Content

**Project Information:**

- Laravel version, PHP version, database configuration
- Environment details and technical stack
- UAT testing guidelines

**Module Test Suites:**

- Route-by-route testing instructions
- Authentication and authorization requirements
- Step-by-step user actions
- Expected results and validation criteria
- Prerequisites and test data requirements

## Extending the Package

The concept is to extend the core features without modifying the core classes. Current status still requirement study.

At the moment, you may customise the existing core services. See following sections for more details.

### Custom Data Service

Implement the `Data` contract to customize data collection:

```php
use CleaniqueCoders\Uat\Contracts\Data;

class CustomDataService implements Data
{
    public function getProjectInformation(): array
    {
        // Custom project info logic
    }

    public function getUsers(): Collection
    {
        // Custom user data logic
    }

    public function getAvailableModules(): array
    {
        // Custom module discovery logic
    }
}
```

### Custom Presentation Format

Implement the `Presentation` contract for custom output formats:

```php
use CleaniqueCoders\Uat\Contracts\Presentation;

class CustomPresentationFormat implements Presentation
{
    public function getExtension(): string
    {
        return 'custom';
    }

    public function generateProjectInfo(array $projectInfo): string
    {
        // Custom formatting logic
    }

    // Implement other required methods...
}
```

## Integration with CI/CD

You can integrate UAT generation into your deployment pipeline:

```yaml
# GitHub Actions example
- name: Generate UAT Documentation
  run: |
    php artisan uat:generate --format=markdown
    php artisan uat:generate --format=json

- name: Archive UAT Documentation
  uses: actions/upload-artifact@v3
  with:
    name: uat-documentation
    path: storage/uat/
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Nasrul Hazim Bin Mohamad](https://github.com/nasrulhazim)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
