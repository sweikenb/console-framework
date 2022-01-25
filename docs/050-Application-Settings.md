# Application Settings

1. [Resolving Of The Settings File](#resolving-of-the-settings-file)
2. [Configuration Examples](#configuration-examples)

## Resolving Of The Settings File

You can provide a YAML-file with settings specific to your application. By default, the template-project looks for an
ENV variable (`APP_SETTINGS`) containing a path to the settings file. If this variable is not present or empty, it will
look for a `settings.yaml` or `settings.yaml.dist` file in your project root. You can modify this behavior in
the `App\SettingsResolver` of the template-project which extends the default one provided by the
framework `Sweikenb\ConsoleFramework\Resolver\AbstractSettingsResolver`.

```bash
# Specify a settings file:
export APP_SETTINGS="/path/to/settings.yaml"
php bin/console my:command

# Use the default "settings.yaml" or "settings.yaml.dist" instead:
php bin/console my:command
```

## Configuration Examples

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
