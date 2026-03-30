# Installation

### Enable and install the PimcoreApplicationLoggerBundle 

Open /config/bundle.php
and add
```php
\Pimcore\Bundle\ApplicationLoggerBundle\PimcoreApplicationLoggerBundle::class => ['all' => true],
``` 

then execute:
```command
bin/console pimcore:bundle:install PimcoreApplicationLoggerBundle
```

### Installation of the ProcessManager

Execute 

```command
composer require insquare/opendxp-process-manager-bundle
```

to get the Bundle from composer.


Open /config/bundle.php
and add
```php
InSquare\OpendxpProcessManagerBundle\InSquareOpendxpProcessManagerBundle::class => ['all' => true]
``` 

to enable the bundle.

Then execute

```command
./bin/console pimcore:bundle:install InSquareOpendxpProcessManagerBundle
./bin/console doctrine:migrations:migrate --prefix=InSquare\OpendxpProcessManagerBundle 
```
to install the bundle and execute all migrations.


## Post installation

After the installation you have to configure the bundle. Execute
```command 
bin/console config:dump-reference InSquareOpendxpProcessManagerBundle
```
to dump the reference configuration.

A sample configuration could look like this
```yaml
in_square_opendxp_process_manager:
    archiveThresholdLogs: 14
    processTimeoutMinutes : 60
    disableShortcutMenu : false
    additionalScriptExecutionUsers : ["www-data","stagingUser"]
    reportingEmailAddresses : ["firstname.lastname@example.com"]
    restApiUsers:
        - {username: "tester" , apiKey: "1234"}
        - {username: "tester2" , apiKey: "344"}

services:
    example:
        class : InSquare\OpendxpProcessManagerBundle\Executor\Callback\General
        arguments :
            $name: "example"
            $extJsClass: "pimcore.plugin.processmanager.executor.callback.example"
            $jsFile: "/bundles/insquareopendxpprocessmanager/js/executor/callback/example.js"
        tags:
            - { name: "elements.processManager.executorCallbackClasses" }
```

Please set up the Cronjob which checks/executes the processes.

```
* * * * * php ~/www/bin/console process-manager:maintenance > /dev/null 2>&1
```

# Update
To update the bundle please use the following command:

```
composer update insquare/opendxp-process-manager-bundle
./bin/console doctrine:migrations:migrate --prefix=InSquare\OpendxpProcessManagerBundle 
```

If you want, that the migrations of the ProcessManagerBundle be executed automatically, please add the following
line to your **project composer.json**
```
  "scripts": {
    "post-update-cmd": [
       //...,
      "./bin/console doctrine:migrations:migrate --prefix=InSquare\OpendxpProcessManagerBundle --no-interaction"
    ],
```
