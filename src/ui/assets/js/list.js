define([  "aps/Message", "aps/Button", "aps/ResourceStore", "aps/Memory", "aps/xhr", "aps/load", "dojo/when", "dojo/query", "dijit/registry", "./assets/js/common.js" ],
    function ( Message,       Button,       ResourceStore,       Memory,       xhr,       load,        when,        query,         registry,               common ) {
        return function (type) {
            var field, Type, target, excludedDomains = [], entriesFilter = function () { return ''; };

            switch (type) {
                case 'domain':
                    field = common.fields.domain;
                    Type = 'Domain';
                    target = "/domains";
                    entriesFilter = function () {
                        return (excludedDomains.length ? "?out(" + field + ",(" + excludedDomains.join(",") + "))" : "");
                    };
                    break;
                case 'email':
                    field = common.fields.email;
                    Type = 'Email';
                    target = "/users";
                    entriesFilter = function () {
                        var likes = [];

                        if (excludedDomains.length) {
                            for (var i = 0; i < excludedDomains.length; i++) {
                                likes.push("like(" + field + ",*@" + excludedDomains[i] + ")");
                            }
                        }

                        return (likes.length ? "?not(or(" + likes.join(",") + "))" : "");
                    };
                    break;
            }

            common.SEA('account').then(function (account) {
                common.SEA(type + 's').then(function (resources) {
                    xhr("/aps/2/resources?implementing(" + common.types.domain +
                        "),not(linkedWith(" + aps.context.vars.context.aps.id +
                        "))").then(function (excludedResources) {
                        if (Object.prototype.toString.call(excludedResources) === '[object Array]') {
                            for (var i = 0; i < excludedResources.length; i++) {
                                if (excludedResources[i][common.fields.domain]) {
                                    excludedDomains.push(excludedResources[i][common.fields.domain]);
                                }
                            }
                        }

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
                        target: "/aps/2/resources/" + account.aps.id + target + entriesFilter()
                    }),
                    SEData = getSEData(resources),
                    login = aps.context.vars.context['cp_' + type],
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
                                    disabled: !(typeof SEData[name] != 'undefined' && (typeof login != 'undefined' ? !login.limit : true)),
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
                    ],
                    list = ["aps/PageContainer",
                        [
                            [ "aps/Container", {
                                title: Type + " List"
                            }
                            ],
                            ["aps/Grid", {
                                id: "grid",
                                columns: layoutGrid,
                                showPaging: true,
                                store: store,
                                selectionMode: "multiple"
                            },
                                [
                                    ["aps/Toolbar",
                                        [
                                            ["aps/TextBox", {
                                                id: "input",
                                                placeHolder: 'Search for a ' + type,
                                                size: 40,
                                                style: "margin-top: 5px; float: left; margin-right: 10px"

                                            }
                                            ],
                                            ["aps/ToolbarButton", {
                                                id: "button",
                                                label: "Search",
                                                iconClass: "sb-search",
                                                autoBusy: false,
                                                onClick: function() {
                                                    when(store.query('like(' + field + ',*' + registry.byId("input").get("value") + '*)'), function(data) {
                                                            registry.byId("grid").set("store", new Memory({ data: data }));
                                                        }
                                                    );
                                                }
                                            }
                                            ],
                                            ["aps/ToolbarButton", {
                                                label: "Reset",
                                                iconClass: "sb-show-all",
                                                autoBusy: false,
                                                onClick: function() {
                                                    registry.byId("input").set("value", "");
                                                    registry.byId("grid").set("store", store);
                                                }

                                            }
                                            ],
                                            ["aps/ToolbarSeparator"],
                                            ["aps/ToolbarButton", {
                                                label: "Check",
                                                iconName: "/pem/images/icons/action_16x16.gif",
                                                autoBusy: true,
                                                requireItems: true,
                                                onClick: function() {
                                                    var
                                                        button = this,
                                                        grid = registry.byId("grid"),
                                                        items = grid.get("selectionArray");

                                                    common.SEA(type + 'Check', { query: { IDs: JSON.stringify(items) }, method: "PUT" }).then(function() {
                                                        common.SEA(type + 's').then(function(resources) {
                                                            SEData = getSEData(resources);
                                                            registry.byId("grid").refresh();
                                                            common.report();
                                                            button.cancel();
                                                        }).otherwise(common.requestError);
                                                    }).otherwise(function(e) {
                                                        common.requestError(e);
                                                        button.cancel();
                                                    });
                                                }
                                            }
                                            ],
                                            ["aps/ToolbarButton", {
                                                label: "Protect",
                                                iconName: "./assets/img/spamexperts-16x16.png",
                                                autoBusy: true,
                                                requireItems: true,
                                                onClick: function() {
                                                    var
                                                        button = this,
                                                        grid = registry.byId("grid"),
                                                        items = grid.get("selectionArray");

                                                    common.SEA(type + 'Protect', { query: { IDs: JSON.stringify(items) }, method: "PUT" }).then(function() {
                                                        common.SEA(type + 's').then(function(resources) {
                                                            SEData = getSEData(resources);
                                                            registry.byId("grid").refresh();
                                                            common.report();
                                                            button.cancel();
                                                        }).otherwise(common.requestError);
                                                    }).otherwise(function(e) {
                                                        common.requestError(e);
                                                        button.cancel();
                                                    });
                                                }
                                            }
                                            ],
                                            ["aps/ToolbarButton", {
                                                label: "Unprotect",
                                                iconName: "/pem/images/icons/delete_16x16.gif",
                                                autoBusy: true,
                                                requireItems: true,
                                                onClick: function() {
                                                    var
                                                        button = this,
                                                        grid = registry.byId("grid"),
                                                        items = grid.get("selectionArray");

                                                    common.SEA(type + 'Unprotect', { query: { IDs: JSON.stringify(items) }, method: "PUT" }).then(function() {
                                                        common.SEA(type + 's').then(function(resources) {
                                                            SEData = getSEData(resources);
                                                            registry.byId("grid").refresh();
                                                            common.report();
                                                            button.cancel();
                                                        }).otherwise(common.requestError);
                                                    }).otherwise(function(e) {
                                                        common.requestError(e);
                                                        button.cancel();
                                                    });
                                                }
                                            }
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ];



                load(list).then(common.enableEnter);
            }
        };
    });
