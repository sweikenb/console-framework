# Service Contracts

1. [Service Contract Definition](#service-contract-definition)

## Service Contract Definition

You can specify your service contracts _(implementations for your interfaces)_ using the corresponding YAML-file:

```yaml
# app/contracts.yml
contracts:
  "App\\Api\\FooFeatureInterface":
    class: "App\\Service\\FooFeatureImplementation"

  "App\\Api\\BarFeatureInterface":
    class: "App\\Service\\BarFeatureImplementation"
    arguments:
      - "overwrite Arg 1"
      - "overwrite Arg 2"
    calls:
      - { method: "setService", arguments: [ "@some.service" ] }
```

If your implementation requires different arguments than the default defined in the `services.yaml` file, you can
overwrite them as you can see in the example above _(this also applies to circular dependencies)_.
