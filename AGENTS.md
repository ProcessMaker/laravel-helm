# AGENTS.md

This document provides context for AI agents working with the Laravel Helm package in Laravel applications.

## Package Overview

The `processmaker/laravel-helm` package provides a PHP wrapper for executing Helm v3 commands in Laravel applications. It allows you to interact with Helm charts programmatically without directly executing shell commands.

## Installation and Setup

### Installation

```bash
composer require processmaker/laravel-helm
```

### Configuration

The package requires Helm v3 binary to be installed on your system. Configure the binary path in your `.env` file:

```env
HELM_BINARY_PATH=/usr/bin/helm
HELM_PROCESS_TIMEOUT=3600
```

## Usage Patterns

### Basic Usage

All Helm commands follow this pattern:
1. Call a static factory method (e.g., `Helm::install()`)
2. Call `run()` to execute the command
3. Check `isSuccessful()` to verify execution
4. Use `getOutput()` to get command output

### Available Methods

- `Helm::install($name, $chart, $options = [], $environments = [])` - Install a Helm chart
- `Helm::upgrade($name, $chart, $options = [], $environments = [])` - Upgrade a Helm chart
- `Helm::delete($name, $options = [], $environments = [])` - Delete a Helm release
- `Helm::uninstall($name, $options = [], $environments = [])` - Uninstall a Helm release
- `Helm::version()` - Get Helm version
- `Helm::rawCommand($command, $options = [], $environments = [])` - Execute any Helm command

### Understanding Options Array

The `$options` parameter accepts an array that gets converted to Helm command-line arguments:

- **Integer keys** (flags without values): `['--create-namespace', '-A']`
- **String keys starting with `--` or `-`** (flags with values): `['--version' => '1.0.0', '-n' => 'default']`
- **Other string keys** (treated as `--set` values): `['app.host' => 'https://example.com']`

Example:
```php
$options = [
    '--version' => '1.0.0',        // becomes: --version 1.0.0
    '--create-namespace',          // becomes: --create-namespace
    'app.host' => 'https://10.0.0.1', // becomes: --set app.host=https://10.0.0.1
    '-n' => 'default',             // becomes: -n default
];
```

### Environment Variables

Use the `$environments` parameter to inject environment variables (useful for `KUBECONFIG`):

```php
$helm = Helm::rawCommand('list', [], [
    'KUBECONFIG' => '/path/to/kubeconfig'
]);
```

### Error Handling

Always check if the command was successful:

```php
$helm = Helm::install('myapp', 'chart/name');
$helm->run();

if (!$helm->isSuccessful()) {
    $error = $helm->getErrorOutput();
    // Handle error
}
```

### Getting Output

```php
$helm = Helm::version();
$helm->run();
$versionOutput = $helm->getOutput();
```

## Common Use Cases

### Install a Chart from OCI Registry

```php
use ProcessMaker\LaravelHelm\Helm;

$helm = Helm::install(
    'myredis',
    'oci://registry-1.docker.io/bitnamicharts/redis',
    [
        '--version' => '16.18.2',
        '--create-namespace',
        '-n' => 'production'
    ]
);
$helm->run();

if ($helm->isSuccessful()) {
    // Chart installed successfully
}
```

### Upgrade with Custom Values

```php
$helm = Helm::upgrade(
    'myapp',
    'chart/name',
    [
        '--version' => '2.0.0',
        'app.replicas' => '3',
        'app.image.tag' => 'latest',
        '--install' // Install if not exists
    ]
);
$helm->run();
```

### List Releases in Different Cluster

```php
$helm = Helm::rawCommand(
    'list --all',
    [],
    ['KUBECONFIG' => '/path/to/other/cluster/kubeconfig']
);
$helm->run();
$releases = json_decode($helm->getOutput(), true);
```

## Important Notes

- Commands are **lazy** - they don't execute until `run()` is called
- The `Helm` instance proxies methods to Symfony `Process`, so you can use any Process methods
- Process timeout defaults to 3600 seconds (1 hour), configurable via `HELM_PROCESS_TIMEOUT`
- Default Helm binary path is `/usr/bin/helm`, configurable via `HELM_BINARY_PATH`
- All methods return a `Helm` instance, allowing method chaining

## Best Practices

1. Always check `isSuccessful()` after running commands
2. Use `rawCommand()` for commands not covered by specific methods
3. Use environment variables for multi-cluster scenarios
4. Set appropriate timeouts for long-running operations
5. Handle errors gracefully and log command outputs for debugging
