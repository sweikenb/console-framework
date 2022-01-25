# Services

1. [Service Definition](#service-definition)
2. [Handling circular dependencies](#handling-circular-dependencies)

## Service Definition

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

## Handling Circular Dependencies

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
