define([
    "aps/Button",
    "aps/Message",
    "aps/ResourceStore",
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
    Button,
    Message,
    ResourceStore,
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
                        id: "protectTile",
                        title: "Total Protection",
                        gridSize: "md-4 xs-12"

                    },
                        [
                            ["aps/Container", {},
                                [
                                   ["aps/FieldSet", [
                                        ["aps/Output", {
                                            content: "Protect all resources."
                                        }]
                                    ]]
                                ]
                            ]
                        ]
                    ],

                    ["aps/Tile", {
                        id: "uninstallTile",
                        title: "Uninstall",
                        gridSize: "md-4 xs-12"
                    },
                        [
                            ["aps/Container", {},
                                [
                                    ["aps/FieldSet", [
                                        ["aps/Output", {
                                            content: "Remove protection from all resources."
                                        }]
                                    ]]
                                ]
                            ]
                        ]
                    ],

                    ["aps/Tile", {
                        id: "refreshTile",
                        title: "Refresh Container",
                        gridSize: "md-4 xs-12"
                    },
                        [
                            ["aps/Container", {},
                                [
                                    ["aps/FieldSet", [
                                        ["aps/Output", {
                                            content: "Updates SE container data."
                                        }]
                                    ]]
                                ]
                            ]
                        ]
                    ]


                ]
            ];

        }, // End of Init
        onContext: function() {

            var self = this;

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
                        console.log(button);
                        button.cancel();
                    }).otherwise(function(e) {
                        common.requestError(e);
                        console.log(button);
                        button.cancel();
                    });
                }
            };

            this.byId("protectTile").set("buttons", [{
                id: "seInstallBtn",
                title: "Protect All",
                onClick: function() {

                    when(xhr("/aps/2/resources/" + aps.context.vars.context.aps.id + '/protectAll', {
                            method: "GET",
                            handleAs: "json"
                        }),
                        function (response) {
                            console.log(response);

                            common.message("All SE resources are protected!", 'success');

                            self.byId("seInstallBtn").cancel();

                            aps.apsc.hideLoading();
                        },
                        function (err) {
                            console.log(err);
                        }
                    );

                }
            }]);

            this.byId("uninstallTile").set("buttons", [{
                id: "seUninstallBtn",
                title: "Uninstall",
                onClick: function() {

                    when(xhr("/aps/2/resources/" + aps.context.vars.context.aps.id + '/unprotectAll', {
                            method: "GET",
                            handleAs: "json"
                        }),
                        function (response) {
                            console.log(response);

                            common.message("All SE resources are unprotected!");

                            self.byId("seUninstallBtn").cancel();

                            aps.apsc.hideLoading();
                        },
                        function (err) {
                            console.log(err);
                        }
                    );

                }
            }]);

            this.byId("refreshTile").set("buttons", [{
                id: "refreshContainerBtn",
                title: "Refresh",
                onClick: function() {

                    when(xhr("/aps/2/resources/" + aps.context.vars.context.aps.id + '/refreshContainer', {
                            method: "GET",
                            handleAs: "json"
                        }),
                        function (response) {
                            console.log(response);

                            common.message("SE container refreshed!", 'success');

                            self.byId("refreshContainerBtn").cancel();

                            aps.apsc.hideLoading();
                        },
                        function (err) {
                            console.log(err);
                        }
                    );

                }
            }]);

            page = page || this.byId("apsPageContainer");


            var tenatId = aps.context.vars.context.aps.id;
            console.log(tenatId);
            // console.log(this.byId("seInstallBtn"));

            aps.apsc.hideLoading();
        },
        onHide: function() {

        }
    }); // End of Declare
}); // End of Define
