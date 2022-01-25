# Commands

1. [Command Definition](#command-definition)

## Command Definition

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
