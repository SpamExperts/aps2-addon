define([
    "aps/Message",
    "aps/xhr",
    "dijit/registry",
    "dojo/Deferred",
    "dojo/_base/declare",
    "dojo/when",
    "aps/Tiles",
    "aps/tiles/UsageInfoTile",
    "aps/ResourceStore",
    "aps/FieldSet",
    "aps/tiles/PieTile",
    "aps/_View"

], function(
    Message,
    xhr,
    registry,
    Deferred,
    declare,
    when,
    Tiles,
    UsageInfoTile,
    Store,
    FieldSet,
    PieTile,
    _View

) {
    var page;

    return declare(_View, {
        init: function() {

            return ["aps/Tiles", {
                id: "operations"
            },
                [
                    ["aps/Tile", {
                        id: "suLoginTile",
                        title: "SpamExperts control panel login.",
                        gridSize: "md-4 xs-12"
                    },
                        [
                            ["aps/Container", {}
                            ]
                        ]
                    ]
                ]
            ];

        }, // End of Init
        onContext: function() {

          var seEmailUserId = false;

          var common = {
            page: "apsPageContainer",
            types: {
                context: "http://aps.spamexperts.com/app/context/2.0",
                domain:  "http://aps.spamexperts.com/app/domain/1.0",
                email:   "http://aps.spamexperts.com/app/email/1.0"
            },
            fields: {
                domain: "name",
                email:  "login"
            },
            SEA: function(action, options) {
                return xhr("/aps/2/resources/" + aps.context.vars.context.aps.id + "/" + action, typeof options !== 'undefined' ? options : {});
            },
            fetchApsResources: function (path) {
                var fetcher = new Deferred(),
                    page = 1,
                    allResources = [],
                    checker = function (p) {
                        var pageSize = 1000,
                            limitClause = '?limit(' + (pageSize * (p - 1)) + ',' + pageSize + ')',
                            errorHandler = function () {
                                fetcher.reject("An error occured");
                                common.requestError();
                            };

                        common.SEA(path + limitClause).then(function (resources) {
                            allResources = allResources.concat(resources);

                            if (resources.length === pageSize) {
                                checker(++page);
                            } else {
                                fetcher.resolve(allResources);
                            }
                        }).otherwise(errorHandler);
                    };

                checker(page);

                return fetcher.promise;
            },
            messageCounter: {
                warning:  0,
                error:    0,
                info:     0,
                progress: 0,
                update:   0,
                limits:   0
            },
            resetCounter: function () {
                for (var type in common.messageCounter) {
                    if (common.messageCounter.hasOwnProperty(type)) {
                        common.messageCounter[type] = 0;
                    }
                }
            },
            message: function (message, type, closeable) {
                type = typeof type != 'undefined' ? type : 'warning';
                closeable = typeof closeable != 'undefined' ? closeable : true;
                common.messageCounter[type]++;
                var newMessage = new Message({
                    description: message,
                    type:        type,
                    closeable:   closeable
                });
                registry.byId(common.page).get("messageList").addChild(newMessage);
                return newMessage;
            },
            report: function () {
                registry.byId(common.page).get("messageList").removeAll();
                common.SEA('/report').then(function (messages) {
                    if((typeof(messages) != 'undefined') && messages !== null) {
                        for (var type in messages) {
                            if (messages.hasOwnProperty(type)) {
                                for (var msg in messages[type]) {
                                    if (messages[type].hasOwnProperty(msg)) {
                                        common.message(messages[type][msg], type);
                                    }
                                }
                            }
                        }
                    }
                });
            },
            requestError: function(e) {
                common.message("Error requesting/processing information from the server. " + e, 'error');
            },
            actionHandler: function(action, button) {
                action.then(function() {
                    common.report();
                    button.cancel();
                }).otherwise(function(e) {
                    common.requestError(e);
                    button.cancel();
                });
            },
            enableEnter: function() {
                registry.byId("input").on("keypress", function(e) {
                    if (e.keyCode == 13 /* Enter/Return */) {
                        registry.byId("button").onClick();
                        e.preventDefault();
                    }
                });
            },
            status: {
                green:  '<span class="icon-green"><img src="/pem/images/icons/green_16x16.gif" border="0">\nProtected</span>',
                yellow: '<span class="icon-yellow"><img src="/pem/images/icons/yellow_16x16.gif" border="0">\nNot Protected</span>',
                red:    '<span class="icon-red"><img src="/pem/images/icons/red_16x16.gif" border="0">\nUnknown</span>'
            }
        };

        xhr("/aps/2/resources?implementing(" + common.types.email +
                "),linkedWith(" + aps.context.vars.user.aps.id +
                ")").then(function (seEmailUsers) {
            if (seEmailUsers[0] && seEmailUsers[0].aps.id) {
                seEmailUserId = seEmailUsers[0].aps.id;
                registry.byId("suLoginButton").set("disabled", false);
            }
        });

        this.byId("suLoginTile").set("buttons", [{
            id: "suLoginButton",
            title: "Login",
            onClick: function() {
                        xhr("/aps/2/resources/" + seEmailUserId + "/controlPanelLoginLink",
                            { handleAs: "text" }).then(function (authticket) {
                                if (authticket) {
                                    window.open(authticket, "_blank");
                                } else {
                                    common.report();
                                }
                                registry.byId("suLoginButton").cancel();
                            }).otherwise(function (e) {
                                common.requestError(e);
                                registry.byId("suLoginButton").cancel();
                            });
                    }
        }]);

            page = page || this.byId("apsPageContainer");

            aps.apsc.hideLoading();
        },
        onHide: function() {

        }
    }); // End of Declare
}); // End of Define
