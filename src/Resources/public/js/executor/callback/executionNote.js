opendxp.registerNS("opendxp.plugin.processmanager.executor.callback.executionNote");
opendxp.plugin.processmanager.executor.callback.executionNote = Class.create(opendxp.plugin.processmanager.executor.callback.abstractCallback, {

    name: "executionNote",

    getFormItems: function () {
        var items = [];
        items.push(this.getTextArea('note'));
        return items;
    },

    execute: function () {
        this.openConfigWindow();
    }
});