define([  "aps/Message", "aps/ResourceStore", "aps/xhr", "dojo/when", "dijit/registry" ],
    function ( Message,       ResourceStore,       xhr,        when,         registry ) {
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
        return common;
    });
