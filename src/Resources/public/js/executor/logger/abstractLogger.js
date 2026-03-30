opendxp.registerNS("opendxp.plugin.processmanager.executor.logger.abstractLogger");
opendxp.plugin.processmanager.executor.logger.abstractLogger = Class.create(opendxp.plugin.processmanager.helper.form, {
    values: {},
    getTopBar: function (niceName, id) {
        return [{
            xtype: "tbtext",
            text: "<b>" + niceName + "</b>"
        },
            "->",
            {
                iconCls: "opendxp_icon_delete",
                handler: this.removeForm.bind(this, id)
            }
        ];
    },

    setValues: function (values) {
        this.values = values;
    },

    removeForm: function (id) {
        Ext.getCmp('plugin_pm_logger_panel').remove(Ext.getCmp(id));
    },

    getFieldValue: function (fieldName) {
        if (this.values) {
            return this.values[fieldName];
        }
    },

    addForm: function () {
        Ext.getCmp('plugin_pm_logger_panel').add(this.getForm());
        this.form.updateLayout();
        Ext.getCmp('plugin_pm_logger_panel').updateLayout();
        return this.form;
    },

    getButton: function () {
        this.button = {
            iconCls: "opendxp_icon_add",
            text: t("plugin_pm_logger_" + this.type),
            "handler": this.addForm.bind(this)
        }
        return this.button;
    },

    stopRefresh: function () {
        Ext.TaskManager.stop(opendxp.globalmanager.get("plugin_pm_cnf").monitoringItems.autoRefreshTask);
    },

    startRefresh: function () {
        if (opendxp.globalmanager.get("plugin_pm_cnf").monitoringItems.autoRefresh.getValue()) {
            Ext.TaskManager.start(opendxp.globalmanager.get("plugin_pm_cnf").monitoringItems.autoRefreshTask);
        }
    }
});