define([
    "aps/Message",
    "aps/ResourceStore",
    "aps/xhr",
    "dijit/registry",
    "dojo/Deferred",
    "dojo/_base/declare",
    "dojo/when",
    "aps/Toolbar",
    "aps/ResourceStore",
    "aps/Memory",
    "aps/Button",
    "aps/_View"
], function(
    Message,
    ResourceStore,
    xhr,
    registry,
    Deferred,
    declare,
    when,
    Toolbar,
    Store,
    Memory,
    Button,
    _View

) {
    var page, grid;

    return declare(_View, {
        init: function() {

            return ["aps/Grid", {
                id: "domainsGrid",
                showPaging: true,
                selectionMode: "multiple"
            },
                [
                    ["aps/Toolbar",
                        [
                            ["aps/TextBox", {
                                id: "domainsInput",
                                placeHolder: 'Search for a domain',
                                style: "margin-top: 5px; float: left; margin-right: 10px"
                            }
                            ],
                            ["aps/ToolbarButton", {
                                id: "domainsSearchBtn",
                                label: "Search",
                                autoBusy: false
                            }
                            ],
                            ["aps/ToolbarButton", {
                                id: "domainsResetBtn",
                                label: "Reset",
                                autoBusy: false,
                                type: "default"

                            }
                            ],
                            ["aps/ToolbarSeparator"],
                            ["aps/ToolbarButton", {
                                id: "domainsCheck",
                                label: "Check",
                                autoBusy: true,
                                requireItems: true,
                                type: "info"
                            }
                            ],
                            ["aps/ToolbarButton", {
                                id: "domainsProtect",
                                label: "Protect",
                                autoBusy: true,
                                requireItems: true,
                                type: "success"
                            }
                            ],
                            ["aps/ToolbarButton", {
                                id: "domainsUnprotect",
                                label: "Unprotect",
                                autoBusy: true,
                                requireItems: true,
                                type: "danger"
                            }
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
                        button.cancel();
                    }).otherwise(function(e) {
                        common.requestError(e);
                        button.cancel();
                    });
                },
                // enableEnter: function() {
                //     registry.byId("domainsInput").on("keypress", function(e) {
                //         if (e.keyCode == 13 /* Enter/Return */) {
                //             registry.byId("domainsSearch").onClick();
                //             e.preventDefault();
                //         }
                //     });
                // },
                status: {
                    green:  '<span class="icon-green"><img src="/pem/images/icons/green_16x16.gif" border="0">\nProtected</span>',
                    yellow: '<span class="icon-yellow"><img src="/pem/images/icons/yellow_16x16.gif" border="0">\nNot Protected</span>',
                    red:    '<span class="icon-red"><img src="/pem/images/icons/red_16x16.gif" border="0">\nUnknown</span>'
                }
            };

            var field, Type, target, excludedDomains = [], entriesFilter = function () { return ''; };

            field = common.fields.domain;
            Type = 'Domain';
            target = "/domains";
//            entriesFilter = function () {
//                return (excludedDomains.length ? "?out(" + field + ",(" + excludedDomains.join(",") + "))" : "");
//            };

            common.SEA('account').then(function (account) {
                common.fetchApsResources('domains').then(function (resources) {
                    xhr("/aps/2/resources?implementing(" + common.types.domain +
                        "),linkedWith(" + aps.context.vars.context.aps.id +
                        ")").then(function (excludedResources) {
                        if (Object.prototype.toString.call(excludedResources) === '[object Array]') {
                            for (var i = 0; i < excludedResources.length; i++) {
                                if (excludedResources[i][common.fields.domain]) {
                                    excludedDomains.push(excludedResources[i][common.fields.domain]);
                                }
                            }
                        }

                        loadList(account[0], resources);
                    }).otherwise(function () {
                        loadList(account[0], resources);
                    });
                });
            });


            function loadList(account, resources) {
                var getSEData = function(resources) {
                        var SEData = {};
                        for (var index in resources) {
                            if (resources.hasOwnProperty(index)) {
                                SEData[resources[index]['name']] = { status: resources[index]['status']};
                            }
                        }
                        return SEData;
                    },
                    store = new ResourceStore({
                        //target: "/aps/2/resources/" + account.aps.id + target + entriesFilter(),
                        target: "/aps/2/resources/" + account.aps.id + target,
                        idProperty: "aps.id"
                    }),

                    SEData = getSEData(resources),
                    login = aps.context.vars.context['cp_' + 'domains'],
                    layoutGrid = [
                        {
                            'class': "sort se_name",
                            name: Type,
                            field: field,
                            sortable: true
                        },
                        {
                            'class': "se_status",
                            name: "Status",
                            field: field,
                            sortable: true,
                            escapeHTML: false,
                            renderCell: function(row, name) {
                                return (typeof SEData[name] == 'undefined') ? common.status.yellow : SEData[name]['status'] ? common.status.green : common.status.yellow;
                            }
                        },
                        {
                            'class': "se_cp",
                            name: "Control Panel",
                            field: field,
                            sortable: false,
                            renderCell: function(row, name) {
                                var button = new Button({
                                    label: "Login",
                                    autoBusy: true,
                                    iconName: "/pem/images/panelset/enabled/login.gif",
                                    disabled: (typeof SEData[name] == 'undefined' || (typeof login != 'undefined' && ! login.limit)),
                                    onClick: function() {
                                        common.SEA("getAuthTicket", { query: { username: name }, handleAs: "text" }).then(function(authticket) {
                                            if (authticket) {
                                                window.open(authticket, "_blank");
                                            } else {
                                                common.report();
                                            }
                                            button.cancel();
                                        }).otherwise(function(e) {
                                            common.requestError(e);
                                            button.cancel();
                                        });
                                    }
                                });
                                return  button;
                            }
                        }
                    ];

                self.byId("domainsGrid").set("store", store);
                self.byId("domainsGrid").set("columns", layoutGrid);

                self.byId("domainsProtect").set("onClick", function() {
                    var
                        button = this,
                        grid = registry.byId("domainsGrid"),
                        items = grid.get("selectionArray");

                    common.SEA('domainProtect', { data: JSON.stringify(items), method: "PUT" }).then(function() {
                        common.fetchApsResources('domains').then(function(resources) {
                            SEData = getSEData(resources);
                            registry.byId("domainsGrid").refresh();
                            common.report();
                            button.cancel();
                        }).otherwise(common.requestError);
                    }).otherwise(function(e) {
                        common.requestError(e);
                        button.cancel();
                    });
                    registry.byId("domainsGrid").refresh();
                });

                self.byId("domainsUnprotect").set("onClick", function() {
                    var
                        button = this,
                        grid = registry.byId("domainsGrid"),
                        items = grid.get("selectionArray");

                    common.SEA('domainUnprotect', { data: JSON.stringify(items), method: "PUT" }).then(function() {
                        common.fetchApsResources('domains').then(function(resources) {
                            SEData = getSEData(resources);
                            registry.byId("domainsGrid").refresh();
                            common.report();
                            button.cancel();
                        }).otherwise(common.requestError);
                    }).otherwise(function(e) {
                        common.requestError(e);
                        button.cancel();
                    });
                    registry.byId("domainsGrid").refresh();
                });

                self.byId("domainsCheck").set("onClick", function() {
                    var
                        button = this,
                        grid = registry.byId("domainsGrid"),
                        items = grid.get("selectionArray");

                    common.SEA('domainCheck', { data: JSON.stringify(items), method: "PUT" }).then(function() {
                        common.fetchApsResources('domains').then(function(resources) {
                            SEData = getSEData(resources);
                            registry.byId("domainsGrid").refresh();
                            common.report();
                            button.cancel();
                        }).otherwise(common.requestError);
                    }).otherwise(function(e) {
                        common.requestError(e);
                        button.cancel();
                    });
                    registry.byId("domainsGrid").refresh();
                });

                self.byId("domainsResetBtn").set("onClick", function() {
                    registry.byId("domainsInput").set("value", "");
                    registry.byId("domainsGrid").set("store", store);
                });

                self.byId("domainsSearchBtn").set("onClick", function() {
                    when(store.query('like(' + field + ',*' + self.byId("domainsInput").get("value") + '*)'), function(data) {
                        self.byId("domainsGrid").set("store", new Memory({ data: data }));
                        }
                    );
                });
            }

            var tenatId = aps.context.vars.context.aps.id;
            console.log(tenatId);

            page = page || self.byId("apsPageContainer");

            aps.apsc.hideLoading();
        },
        onHide: function() {

        }
    }); // End of Declare
}); // End of Define
