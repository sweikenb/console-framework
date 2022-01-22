# Framework Structure

This framework consists of a very few core libraries of the Symfony project
and [Pimple](https://github.com/silexphp/Pimple) as di-container.

The goal is to keep the footprint low, so it is easy to pack applications written with this framework in a small `.phar`
-container for distribution.

The service definitions and app configuration is stored in YAML-files in an application root directory which can
specified during the bootstrap period. Please refer to
the [standard project template](https://github.com/sweikenb/console-framework-standard/tree/main/app) for details about
the files or have a look in the corresponding sections below.

## Commands

Commands can be defined by using the [Symfony console](https://symfony.com/doc/current/components/console.html):

```php
namespace App\Command;

use Symfony\Component\Console\Command\Command
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MyCommand extends Command
{
    public function __construct(private string $username, string $name = null)
    {
        parent::__construct($name);
    }

    public function configure()
    {
        $this->setName('my:command');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
         $output->writeln(sprintf("Hello %s", $this->username));

         return self::SUCCESS;
    }
}
```

To make the command available, you have to register the command:

```yaml
# app/commands.yml
commands:
  "App\\Command\\MyCommand":
    - "Foo Bar"
```

## Services

Services can be defined using a simple YAML syntax:

```yaml
# app/services.yml
services:

  some.service:
    class: "App\\Service\\SomeFeatureService"
    arguments:
      - "@another.service"
      - "%settings.additional.param%"

  another.service:
    class: "App\\Service\\AnotherFeatureService"

  some.dependency:
    class: "App\\Api\\SomeFeatureContractInterface"
    arguments:
      - "a static value"
```

### Handling circular dependencies

If you have circular dependencies, you can use a callback to inject these:

```yaml
# app/services.yml
services:

  service.foo:
    class: "App\\Service\\FooService"
    arguments:
      - "@service.bar"

  service.bar:
    class: "App\\Service\\BarService"
    calls:
      - { method: "setFooService", arguments: [ "@service.foo" ] }
```

## Events

You can implement an event-listener pattern using the
symfony [event-dispatcher](https://symfony.com/doc/current/components/event_dispatcher.html). All you need to do is to
define the events you want to listen to in the corresponding YAML-file:

```yaml
# app/events.yaml
events:

  foo.event:
    - [ listener: "@foo.listener.service" ]

  bar.event:
    - [ listener: "@bar.listener.service", method: "customMethod", priority: 42 ]
```

By default, the framework will look for the `handleEvent`-method in the specified listener. If you prefer to use a
different method you can simply specify a custom one as shown in the example above.

The listener execution priority can also be manually specified, by default the priority is set to `0`. For more details
about the priority, please refer to
the [component documentation](https://symfony.com/doc/current/components/event_dispatcher.html#connecting-listeners).

### Default Events

| Event Name          | Description                                                                                                 |
|---------------------|-------------------------------------------------------------------------------------------------------------|
| boostrap.successful | This event gets populated when the framework bootstrapping has finished without errors.                     |
| console.command     | [details](https://symfony.com/doc/current/components/console/events.html#the-consoleevents-command-event)   |
| console.signal      | [details](https://symfony.com/doc/current/components/console/events.html#the-consoleevents-signal-event)    |
| console.terminate   | [details](https://symfony.com/doc/current/components/console/events.html#the-consoleevents-terminate-event) |
| console.error       | [details](https://symfony.com/doc/current/components/console/events.html#the-consoleevents-error-event)     |

## Service Contracts

You can specify your service contracts _(implementations for your interfaces)_ using the corresponding YAML-file:

```yaml
# app/contracts.yml
contracts:
  "App\\Api\\FooFeatureInterface":
    class: "App\\Service\\FooFeatureImplementation"

  "App\\Api\\BarFeatureInterface":
    class: "App\\Service\\BarFeatureImplementation"
```

## Application Settings

You can provide a YAML-file with settings specific to your application. By default, the template-project looks for an
ENV variable (`APP_SETTINGS`) containing a path to the settings file. If this variable is not present or empty, it will
look for a `settings.yaml` or `settings.yaml.dist` file in your project root. You can modify this behavior in
the `App\SettingsResolver…` of the template-project.

```bash
# Specify a settings file:
export APP_SETTINGS="/path/to/settings.yaml"
php bin/console my:command

# Use the default "settings.yaml" or "settings.yaml.dist" instead:
php bin/console my:command
```

### Configuration Examples

The settings file gets parsed in a special way to be flexible when it comes to injecting the settings to your services
and commands. Please note, that every parameter will get prefixed with `settings.` automatically:

```yaml
# settings.yaml
some:
  param: 'foo'

additional:
  param: 'bar'

even_more:
  params:
    - "p1"
    - "p2"
    - "p3"
```

These settings will for example create the following injectable parameters:

```
%settings.some% => ["param" => "foo"]
%settings.some.param% => "foo"

%settings.additional% => ["param" => "bar"]
%settings.additional.param% => "bar"

%settings.even_more% => ["params" => ["p1", "p2", "p3"]]
%settings.even_more.params% => ["p1", "p2", "p3"]
```

**PLEASE NOTE:**

* You can nest these settings infinitely deep _(you know: with great power...)_
* Array nodes will always be injected as arrays
