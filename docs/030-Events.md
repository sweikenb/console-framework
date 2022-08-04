# Events

1. [Event Definition](#event-definition)
2. [Default Events](#default-events)
3. [Process Manager Events](#process-manager-events)

## Event Definition

You can implement an event-listener pattern using the
symfony [event-dispatcher](https://symfony.com/doc/current/components/event_dispatcher.html). All you need to do is to
define the events you want to listen to in the corresponding YAML-file:

```yaml
# app/events.yaml
events:

  foo.event:
    - { listener: "@foo.listener.service" }

  bar.event:
    - { listener: "@bar.listener.service", method: "customMethod", priority: 42 }
```

The event dispatcher is available for injection by using `event.dispatcher` as service name for reference:

```yaml
# app/services.yml
services:
  my.service:
    class: My\ServiceClass
    arguments:
      - "@event.dispatcher"
```

By default, the framework will look for the `handleEvent`-method in the specified listener. If you prefer to use a
different method you can simply specify a custom one as shown in the example above.

The listener execution priority can also be manually specified, by default the priority is set to `0`. For more details
about the priority, please refer to
the [component documentation](https://symfony.com/doc/current/components/event_dispatcher.html#connecting-listeners).

## Default Events

| Event Name           | Description                                                                                                 |
|----------------------|-------------------------------------------------------------------------------------------------------------|
| bootstrap.successful | This event gets populated when the framework bootstrapping has finished without errors.                     |
| console.command      | [details](https://symfony.com/doc/current/components/console/events.html#the-consoleevents-command-event)   |
| console.signal       | [details](https://symfony.com/doc/current/components/console/events.html#the-consoleevents-signal-event)    |
| console.terminate    | [details](https://symfony.com/doc/current/components/console/events.html#the-consoleevents-terminate-event) |
| console.error        | [details](https://symfony.com/doc/current/components/console/events.html#the-consoleevents-error-event)     |

## Process Manager Events

In case you have installed the `sweikenb/pcntl` library, you also have access to its related events.

For more details and a full list of additional event, please visit
the [library documentation](https://github.com/sweikenb/pcntl#event-dispatcher-support).
