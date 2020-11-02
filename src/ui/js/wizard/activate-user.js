define([
    "dojo/_base/declare",
    "dojo/when",

    "aps/_View"
], function(
    declare,
    when,

    _View
) {

    return declare(_View, {
        init: function() {

            return ["aps/PageContainer", [
                ["aps/FieldSet", { id: "general", title: "General" }, [
                    ["aps/Output", { id: "email_user", label: "Email User" }],
                    ["aps/CheckBox", { id: "protection",   label: "Protection", value: "protection", disabled: false }]
                ]]
            ]];
        }, // End of Init function

        onContext: function(context) {

            console.log("ACTIVATE-USER");

            var emailUser = aps.context.params.objects[0];
            this.byId("email_user").set("value", emailUser.name);
            this.byId("protection").set("checked", emailUser.status);

            aps.apsc.hideLoading();

        }, // End of onContext

        onNext: function() {

            aps.apsc.next({
            });
        } // End of onNext

    }); // End of Declare
}); // End of Define