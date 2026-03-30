opendxp.registerNS("opendxp.plugin.processmanager.executor.class.command");
opendxp.plugin.processmanager.executor.class.command = Class.create(opendxp.plugin.processmanager.executor.class.abstractExecutor, {

    getFormItems: function () {
        var items = this.getDefaultItems();
        items.push(this.getTextField('command'));
        items.push(this.getCheckbox('uniqueExecution'));
        items.push(this.getCronjobField());
        items.push(this.getCronjobDescription());
        items.push(this.getNumberField("keepVersions"));
        items.push(this.getCheckbox("hideMonitoringItem"));
        return items;
    }

});