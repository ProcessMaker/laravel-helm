# Laravel Helm Package

[![GitHub License](https://img.shields.io/github/license/ProcessMaker/laravel-helm)](https://github.com/ProcessMaker/laravel-helm/blob/main/LICENSE)
[![Codecov](https://img.shields.io/codecov/c/github/ProcessMaker/laravel-helm)](https://app.codecov.io/gh/ProcessMaker/laravel-helm)
[![Packagist Version](https://img.shields.io/packagist/v/ProcessMaker/laravel-helm)](https://packagist.org/packages/ProcessMaker/laravel-helm)

This package provides a wrapper to invoke Helm v3 commands using PHP code.

## Installing

Installing can be done through a variety of methods, although Composer is recommended.

### Composer (recommended)

Include the following snipped into the *composer.json* file.

```json
"require": {
  "processmaker/laravel-helm": "^1.0"
}
```

or by using the `composer require` command:

```bash
composer require processmaker/laravel-helm
```

### Github

Releases are available on [Github](https://github.com/processmaker/laravel-helm/releases).

## Configuration

In order to use the Laravel Helm package, Helm v3 binary must be installed on your system. It is required to setup the right path where the Helm binary is located on the server.

Laravel Helm uses the path `/usr/bin/helm` by default, but it can be configured using an environment variable defined into the `.env`

```dotenv
HELM_BINARY_PATH=/path/to/helm/binary
```

### Other configurations

```dotenv
# Set the internal `Process` timeout, default=3600
HELM_PROCESS_TIMEOUT=3600
```

## How to use

All Helm commands follow this pattern:
1. Call a static factory method (e.g., `Helm::install()`)
2. Call `run()` to execute the command
3. Check `isSuccessful()` to verify execution
4. Use `getOutput()` to get command output

The current version of the package implements the following commands:

- helm version
- helm install
- helm upgrade
- helm delete
- helm uninstall

Also implements a method `rawCommand` where any other command can be executed.

### Helm::version

```php
use ProcessMaker\LaravelHelm\Helm;
...
$helm = Helm::version();
$helm->run();
// Prints the command response for the command 'helm version'
$version = $helm->getOutput();
...
```

### Helm::install($name, $chart, $options, $environments)

Parameters:

- name: installation name (**required**)
- chart: helm chart (**required**)
- options: options array (**optional**)
- environments: environment variables array (**optional**)

```php
use ProcessMaker\LaravelHelm\Helm;
...
$helm = Helm::install(
    'releasename',
    'oci://registry-1.docker.io/bitnamicharts/redis',
    [
        "--version" => '16.18.2'
    ]
);
$helm->run();
// To check the command was executed successfully
if ($helm->isSuccessful()) {
    // HELM app installed
    ...
}
...
```

### Helm::upgrade($name, $chart, $options, $environments)

Parameters:

- name: installation name (**required**)
- chart: helm chart (**required**)
- options: options array (**optional**)
- environments: environment variables array (**optional**)

```php
use ProcessMaker\LaravelHelm\Helm;
...
$helm = Helm::upgrade(
    'releasename',
    'oci://registry-1.docker.io/bitnamicharts/redis',
    [
        "--version" => '16.18.2',
        '--install'
    ]
);
$helm->run();
// To check the command was executed successfully
if ($helm->isSuccessful()) {
    // HELM app upgraded
    ...
}
...
```

### Helm::delete($name, $options, $environments)

Parameters:

- name: installation name (**required**)
- options: options array (**optional**)
- environments: environment variables array (**optional**)

```php
use ProcessMaker\LaravelHelm\Helm;
...
$helm = Helm::delete('releasename');
$helm->run();
// To check the command was executed successfully
if ($helm->isSuccessful()) {
    // HELM app uninstalled
    ...
}
...
```

### Helm::uninstall($name, $options, $environments)

Parameters:

- name: installation name (**required**)
- options: options array (**optional**)
- environments: environment variables array (**optional**)

```php
use ProcessMaker\LaravelHelm\Helm;
...
$helm = Helm::uninstall('releasename');
$helm->run();
// To check the command was executed successfully
if ($helm->isSuccessful()) {
    // HELM app uninstalled
    ...
}
...
```

### Helm::rawCommand($command, $options, $environments)

Parameters:

- command: string command to be executed (**required**)
- options: options array (**optional**)
- environments: environment variables array (**optional**)

```php
use ProcessMaker\LaravelHelm\Helm;
...
// Example using simple commands
$helm = Helm::rawCommand('list');
$helm->run();

// Example using commands with parameters
$helm = Helm::rawCommand('list --all');
$helm->run();

// Example combining with $options
$helm = Helm::rawCommand('list --output json', ['--all']);
$helm->run();
...
```

## Parsing $options array

The most of the methods for the Helm object uses the `$options` array. This is an example how the option are being parsed:

```php
use ProcessMaker\LaravelHelm\Helm;
...
// Define options array
$options = [
    '--version' => '1.0.0', // include parameters using --name=value
    '--create-namespace', // include single --parameters
    'app.host' => 'https://10.0.0.1', // values from values.yaml file
    '-n' => 'default', // -name value parameters
    '-A', // single - parameters
    'dry-run' // fix invalid inputs 
];
$helm = Helm::install(
    'myredis',
    'oci://registry-1.docker.io/bitnamicharts/redis',
    $options
);
$helm->run();
// the command parsed will be:
// helm install myredis oci://registry-1.docker.io/bitnamicharts/redis --version=1.0.0 
//   --create-namespace --set app.host=https://10.0.0.1 -n default -A --dry-run
...
```

## Injecting environment variables using $environments

If is required to include environment variables to the helm execution context, this can be done using the `$environments` array.

```php
use ProcessMaker\LaravelHelm\Helm;
...
// List all helm applications pointing to a different cluster
$helm = Helm::rawCommand(
    'list --all',
    [],
    [
        'KUBECONFIG' => '/path/to/another/cluster/kubeconfig'
    ]
);
// Executing helm with the KUBECONFIG env vars enabled
$helm->run();
...
```

## Error Handling

Always check if the command was successful after execution:

```php
use ProcessMaker\LaravelHelm\Helm;
...
$helm = Helm::install('myapp', 'chart/name');
$helm->run();

if (!$helm->isSuccessful()) {
    $error = $helm->getErrorOutput();
    // Handle error appropriately
    ...
}
...
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

## Testing

In order to validate that the package is fully functional always we can run:

```bash
composer test
```

## License

Laravel Helm package is licensed under the Apache 2.0. See the LICENSE file for details.

## Support

Issues can be opened directly in Github.
