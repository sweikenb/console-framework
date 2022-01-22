<?php declare(strict_types=1);

namespace Sweikenb\ConsoleFramework;

use Sweikenb\ConsoleFramework\Event\BootstrapSuccessfulEvent;
use Sweikenb\ConsoleFramework\Exception\BootstrapException;
use Pimple\Container;
use ReflectionClass;
use Sweikenb\ConsoleFramework\Exception\RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Yaml\Yaml;

class BootstrapProcessor
{
    private array $contracts = [];
    private array $contractArgs = [];
    private array $contractCalls = [];

    public function __construct(
        private Container $container,
        private Application $application,
        private string $appConfigDir
    ) {}

    /**
     * @throws \ReflectionException
     * @throws \Sweikenb\ConsoleFramework\Exception\BootstrapException
     */
    public function execute(?string $settingsFile = null): void
    {
        // prepare event dispatcher
        $eventDispatcher = $this->prepareEvents();

        // prepare application settings
        $this->prepareSettings($settingsFile);

        // prepare container definitions
        $this->prepareServiceContracts();
        $this->prepareServices();
        $this->prepareCommands();

        // bootstrapping done
        $successEvent = new BootstrapSuccessfulEvent();
        $eventDispatcher->dispatch($successEvent, BootstrapSuccessfulEvent::EVENT_NAME);
    }

    private function prepareEvents(): EventDispatcher
    {
        // prepare the event dispatcher as first service
        $eventDispatcher = new EventDispatcher();
        $this->container['event.dispatcher'] = function () use ($eventDispatcher) {
            return $eventDispatcher;
        };

        // parse event listener definitions
        $eventsData = Yaml::parseFile(sprintf("%s/events.yml", $this->appConfigDir));
        $definitions = $eventsData['events'] ?? [];
        foreach ($definitions as $eventName => $listeners) {
            foreach ($listeners as $config) {

                // get listener priority
                $priority = (int)$config['priority'] ?? 0;

                // register listener
                $eventDispatcher->addListener($eventName, function (...$args) use ($config, $eventName) {

                    // get defined listener service
                    $serviceName = $config['listener'];
                    if (mb_substr($serviceName, 0, 1) === '@') {
                        $serviceName = mb_substr($serviceName, 1);
                    }
                    $listener = $this->container[$serviceName];

                    // set proper callback method
                    $method = $config['method'] ?? 'handleEvent';

                    // invoke callback if method possible
                    if (is_object($listener) && method_exists($listener, $method)) {
                        call_user_func([$listener, $method], $args);
                    }
                    else {
                        throw new RuntimeException(
                            sprintf(
                                'Can not invoke event listener for "%s". Unknown service (%s) or method (%s).',
                                $eventName,
                                $serviceName,
                                $method
                            )
                        );
                    }
                }, $priority);
            }
        }

        // inject dispatcher to console app, so it can also dispatch its events
        $this->application->setDispatcher($eventDispatcher);

        return $eventDispatcher;
    }

    private function resolveDiArguments(array $arguments, Container $parsedContainer): array
    {
        $resolved = [];
        foreach ($arguments as $argument) {
            if (is_array($argument) || empty($argument)) {
                $resolved[] = $argument;
            }
            else {
                // service or parameter reference?
                $argument = match (mb_substr($argument, 0, 1)) {
                    '@' => $parsedContainer[mb_substr($argument, 1)],
                    '%' => $parsedContainer[$argument]
                };
            }
            $resolved[] = $argument;
        }

        return $resolved;
    }

    private function prepareSettings(?string $settingsFile = null): void
    {
        // settings file present?
        if ($settingsFile) {
            $settings = Yaml::parseFile($settingsFile) ?: [];
            $this->generateParameterKeys('settings', $settings);
        }
    }

    private function generateParameterKeys(string $prefix, array &$config)
    {
        foreach ($config as $key => $value) {
            $subPrefix = sprintf('%s.%s', $prefix, $key);
            $this->container[sprintf('%%%s%%', $subPrefix)] = $value;
            if (is_array($value) && !array_is_list($value)) {
                $this->generateParameterKeys($subPrefix, $value);
            }
        }
    }

    private function prepareServiceContracts(): void
    {
        // define default lookup path for file
        $filePath = sprintf("%s/contracts.yml", $this->appConfigDir);

        // parse file
        $contractData = Yaml::parseFile($filePath);
        $serviceContracts = $contractData['contracts'] ?? [];

        // process contracts
        foreach ($serviceContracts as $interface => $contractConfig) {
            $this->contracts[$interface] = $contractConfig['class'];
            if (isset($contractConfig['arguments'])) {
                $this->contractArgs[$interface] = $contractConfig['arguments'];
            }
            if (isset($contractConfig['calls'])) {
                $this->contractCalls[$interface] = $contractConfig['calls'];
            }
        }
    }

    private function prepareServices(): void
    {
        // define default lookup path for file
        $servicesFile = sprintf("%s/services.yml", $this->appConfigDir);

        // parse file
        $serviceData = Yaml::parseFile($servicesFile);
        $serviceDefinitions = $serviceData['services'] ?? [];

        // process service definitions
        foreach ($serviceDefinitions as $serviceId => $serviceConfig) {
            $this->container[$serviceId] = function (Container $parsedContainer) use ($serviceId, $serviceConfig) {

                // resolve contracts if needed
                $class = $serviceConfig['class'];
                if (isset($this->contracts[$class])) {
                    if (isset($this->contractArgs[$class])) {
                        $serviceConfig['arguments'] = $this->contractArgs[$class];
                    }
                    if (isset($this->contractCalls[$class])) {
                        $serviceConfig['calls'] = $this->contractCalls[$class];
                    }
                    $class = $this->contracts[$class];
                }

                // prepare service args
                $arguments = $this->resolveDiArguments($serviceConfig['arguments'] ?? [], $parsedContainer);

                // create service instance
                $reflector = new ReflectionClass($class);
                $instance = $reflector->newInstanceArgs($arguments);

                // run callbacks if present
                $callbacks = $serviceConfig['calls'] ?? [];
                foreach ($callbacks as $callConfig) {

                    // check if the callback is valid
                    if (!$reflector->hasMethod($callConfig['method'])) {
                        throw new BootstrapException(
                            sprintf(
                                'Can not call method "%s" for service "%s". Method does not exist!',
                                $callConfig['method'], $serviceId
                            )
                        );
                    }

                    // prepare method args
                    $methodArguments = $this->resolveDiArguments($callConfig['arguments'] ?? [], $parsedContainer);

                    // execute callback
                    $reflector
                        ->getMethod($callConfig['method'])
                        ->invoke($instance, ...$methodArguments);
                }

                // return final service instance
                return $instance;
            };
        }
    }

    /**
     * @throws \ReflectionException
     * @throws \Sweikenb\ConsoleFramework\Exception\BootstrapException
     */
    private function prepareCommands(): void
    {
        // define default lookup path for file
        $filePath = sprintf("%s/commands.yml", $this->appConfigDir);

        // parse file
        $commandData = Yaml::parseFile($filePath);
        $commands = $commandData['commands'] ?? [];;

        // process service definitions
        foreach ($commands as $commandClass => $commandArgs) {

            // prepare command args
            $arguments = $this->resolveDiArguments($commandArgs ?? [], $this->container);

            // check command requirements
            $reflector = new ReflectionClass($commandClass);
            if (!$reflector->isSubclassOf(Command::class)) {
                throw new BootstrapException(
                    sprintf(
                        'Can not register invalid command (%s). Please extend the Symfony base command.', $commandClass
                    )
                );
            }

            // create instance
            $instance = $reflector->newInstanceArgs($arguments);
            /* @var \Symfony\Component\Console\Command\Command $instance */

            // register command
            $this->application->add($instance);
        }
    }
}
