define([
    "dojo/_base/declare",
    "dojo/when",
    "dojox/mvc/getPlainValue",

    "aps/_View",
    "aps/ResourceStore"

], function (
    declare,
    when,
    getPlainValue,

    _View,
    Store
) {
    var subscriptionId;

    return declare(_View, {
        init: function() {
            /* Create widgets */
            return ["aps/PageContainer", [
                ["aps/FieldSet", { id: "general", title: "General" }, [
                    ["aps/Output", { id: "email_user", label: "Email User" }],
                    ["aps/CheckBox", { id: "protection",   label: "Protection", value: "protection", disabled: true }]
                ]]
            ]];
        },

        onContext: function() {
            subscriptionId = aps.context.vars.context.aps.subscription;
            aps.context.wizardState.forEach(function(view) {
                    view.visible = true;
                }
            );
            aps.apsc.wizardState(aps.context.wizardState);
            aps.apsc.hideLoading();
        },

        onNext: function() {
            aps.app.isNew = true;
            aps.apsc.next();
        },

        onSubmit: function() {
            aps.context.subscriptionId = subscriptionId;

            aps.apsc.gotoView("activate-user");
        },

        onCancel: function () {
            aps.app.isNew = false;
            aps.apsc.gotoView("domains-view");
        }

    });
});
