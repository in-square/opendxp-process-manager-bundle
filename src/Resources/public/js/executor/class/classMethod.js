opendxp.registerNS("opendxp.plugin.processmanager.executor.class.classMethod");
opendxp.plugin.processmanager.executor.class.classMethod = Class.create(opendxp.plugin.processmanager.executor.class.abstractExecutor, {

    getFormItems: function () {
        var items = this.getDefaultItems();
        items.push(this.getTextField('executorClass'));
        items.push(this.getTextField('executorMethod'));
        items.push(this.getCheckbox('uniqueExecution'));
        items.push(this.getCronjobField());
        items.push(this.getCronjobDescription());
        items.push(this.getNumberField("keepVersions"));
        items.push(this.getCheckbox("hideMonitoringItem"));
        return items;
    }
});