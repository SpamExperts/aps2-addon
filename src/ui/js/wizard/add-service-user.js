define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/when",
    "dojox/mvc/getPlainValue",
    "dojox/mvc/at",
    "dojox/mvc/getStateful",
    "dojo/promise/all",
    "aps/TextBox",
    "aps/ResourceStore",
    "aps/Memory",
    "aps/_View"

], function (declare,
             lang,
             when,
             getPlainValue,
             at,
             getStateful,
             all,
             TextBox,
             Store,
             Memory,
             _View
             ) {
    return declare(_View, {
        init: function () {

            // var email = aps.context.params.user.login;

            var emailUser = getStateful({
                aps: { type: "http://aps.spamexperts.com/app/email/1.0"},
                domain: { aps: { id: ""}},
                name: 'temp',
                status: false
            });


            /* Define and return widgets */
            return ["aps/FieldSet", { id: "general", title: "General"}, [
                ["aps/TextBox", {
                    id: "suEmailInput",
                    label: "Email Address",
                    required: true,
                    placeHolder: "Enter an email.",
                    missingMessage: "Sorry, the field is empty"
                }],
                ["aps/CheckBox", { id: "protection",   label:  "Protection", value: "protection", disabled: false }]
            ]];
        }, // End of Init

        /* Create the handler for the Next navigation button */
        onNext: function () {
            var page = this.byId("apsPageContainer");

            page.get("messageList").removeAll();

            if (!page.validate()) {
                aps.apsc.cancelProcessing();
                return;
            }

            aps.apsc.next();

        }, // End of onNext

        onContext: function () {

            var email = aps.context.vars.context.adminEmail;

            /* Declare the data source */
            this.offerStore = new Store({
                target: "/aps/2/resources",
                apsType: "http://aps.spamexperts.com/app/email/1.0"
            });

            var userStore = new Store({ // The list of users for selection
                apsType: "http://aps-standard.org/types/core/user/1.0",
                target: "/aps/2/resources/"
            });

            all([userStore.query()]).then(function (offersAndUsers) {

                console.log('OFFERS AND USERS!!!!');
                console.log(offersAndUsers);


            }).then(function () {
                aps.apsc.hideLoading();
            });


            /* Create the resource model */
            var emailUser = getStateful({
                aps: { type: "http://aps.spamexperts.com/app/email/1.0"},
                domain: { aps: { id: ""}},
                name: 'temp',
                status: false
            });

            this.byId("protection").set("checked", emailUser.status);

            var self = this;
            self.offerStore.query().then(function (offers) {
                if (offers.length === 0) return;
            }).then(aps.apsc.hideLoading());
        } // End of onContext

    }); // End of Declare
}); // End of Define
