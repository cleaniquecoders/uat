# Changelog

All notable changes to `uat` will be documented in this file.

## 1.1.0 - 2026-03-31

### What's Changed

#### Added

- Laravel 13 support (illuminate constraints include `^13.0`)
- PHPUnit 12 compatibility
- Pest 4 support

#### Changed

- Updated `phpunit.xml.dist` for PHPUnit 12
- Standardized CI workflow (Laravel 12 + PHP 8.4/8.3)
- Updated dev dependencies (larastan, phpstan plugins, collision)

**Full Changelog**: https://github.com/cleaniquecoders/uat-1/compare/1.0.0...1.1.0

## First Release - 2025-09-28

### 🎉 Release v1.0.0: User Acceptance Testing Documentation Generator

We're excited to announce the first stable release of the **User Acceptance Testing (UAT) Documentation Generator** for Laravel applications!

#### 🚀 What's New

This powerful package automatically generates comprehensive UAT documentation by analyzing your Laravel application's routes, middleware, policies, and authorization rules - saving countless hours of manual documentation work.

##### ✨ Key Features

- **📋 Automatic UAT Script Generation** - Analyzes your entire Laravel app and creates detailed testing documentation
- **🔐 Smart Authorization Detection** - Identifies middleware, policies, and auth requirements for every route
- **📊 Multiple Output Formats** - Generate documentation in Markdown and JSON formats
- **🎯 Intelligent Route Analysis** - Groups routes by modules and controllers for organized testing
- **👥 User Role Documentation** - Automatically documents required user types and permissions
- **⚙️ Fully Configurable** - Customize middleware mappings, policy rules, and output formats
- **🚫 Smart Filtering** - Excludes development routes and vendor packages automatically

##### 🛠️ Perfect For

- **QA Teams** - Get structured, comprehensive test scripts without manual effort
- **Development Teams** - Ensure nothing is missed during UAT phases
- **Project Managers** - Have clear documentation of what needs testing
- **Stakeholders** - Understand application functionality and user requirements

##### 🎯 What It Generates

- Project technical overview and environment details
- Complete user roles and permission matrices
- Module-by-module test suites with step-by-step instructions
- Authentication and authorization test scenarios
- Expected behaviors and validation criteria

#### 📦 Installation

```bash
composer require cleaniquecoders/uat
php artisan vendor:publish --tag="uat-config"
php artisan uat:generate


```
#### 🌟 Why This Matters

Manual UAT documentation is time-consuming, error-prone, and often incomplete. This package ensures your testing documentation is always up-to-date, comprehensive, and automatically reflects your application's current state.


---

**Full documentation**: [GitHub Repository](https://github.com/cleaniquecoders/uat)
**Package**: [Packagist](https://packagist.org/packages/cleaniquecoders/uat)

Happy Testing! 🧪✨


---

*Made with ❤️ by [Nasrul Hazim](https://github.com/nasrulhazim)*
