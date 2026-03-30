### Commands validator

The Commands validator is a Service which determines which Commands should be executable.
By default all Commands which implements the "InSquare\OpendxpProcessManagerBundle\ExecutionTrait"
will be available.

You can change or alter the behaviour by changing the Service.
If you want that all commands are available, change the $strategy to "all". Then no validation is done. 

```yaml
    InSquare\OpendxpProcessManagerBundle\Service\CommandsValidator:
        public: true
        arguments:
            $strategy: "default"
            $whiteList: ["router:match","valid:command"]
            $blackList: ["process-manager:maintenance","do-no-execute:command"]
```

### Validating commands / command options before they get saved

In the pimcore admin you can set the "Commands options". By default there is no validation for the command options (the whole command is passed through "https://www.php.net/escapeshellcmd").
If you need certain logic to validate the command options you could implement public methods in your command, which validates the options:

E.g:

```php

    public function validatedCommandOptions(string $commandOptions, \InSquare\OpendxpProcessManagerBundle\Model\Configuration $configuration): void
    {
    #    throw new \Exception('invalid command options');
    }
```

Or you overwrite the "validateCommandConfiguration" method of the InSquare\OpendxpProcessManagerBundle\Service\CommandsValidator