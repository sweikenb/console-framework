<?php declare(strict_types=1);

namespace Sweikenb\ConsoleFramework\Event;

use Symfony\Contracts\EventDispatcher\Event;

class BootstrapSuccessfulEvent extends Event
{
    const EVENT_NAME = 'boostrap.successful';
}