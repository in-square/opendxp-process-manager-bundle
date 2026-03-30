### Configuration
A sample configuration could look like this.

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
    configurationMigrationsDirectory: "%kernel.project_dir%/src/Migrations"
    configurationMigrationsNamespace: 'App\Migrations'

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

You can execute 
```command 
bin/console debug:config InSquareOpendxpProcessManagerBundle
```
to dump the configuration.
