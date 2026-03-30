opendxp.registerNS("opendxp.plugin.processmanager.executor.class.pimcoreCommand");
opendxp.plugin.processmanager.executor.class.pimcoreCommand = Class.create(opendxp.plugin.processmanager.executor.class.abstractExecutor, {

    initialize: function () {

        //this.settings.windowHeight = 800;
    },

    getCommandList: function () {

        let store = [];

        for (let key in processmanagerPlugin.config.pimcoreCommands) {
            if (processmanagerPlugin.config.pimcoreCommands.hasOwnProperty(key)) {
                store.push([key, key]);
            }
        }

        this.command = {
            xtype: "combo",
            editable: false,
            name: "command",
            labelWidth: this.labelWidth,
            value: this.getFieldValue('command'),
            store: store,
            mode: "local",
            width: "100%",
            triggerAction: "all",
            mandatory: true
        }
        this.command.fieldLabel = this.getFieldLabel('command',this.command)
        return this.command;
    },

    getFormItems: function () {
        let items = this.getDefaultItems();
        items.push(this.getCommandList());
        items.push(this.getTextField('commandOptions'));
        items.push(this.getCheckbox('uniqueExecution'));
        items.push(this.getCronjobField());
        items.push(this.getCronjobDescription());
        items.push(this.getNumberField("keepVersions"));
        items.push(this.getNumberField("deleteAfterHours"));
        items.push(this.getCheckbox("hideMonitoringItem"));
        return items;
    }

});