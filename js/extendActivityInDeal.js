let yellow = 'rgba(255, 165, 0, 0.2)';
let brown = 'rgba(139, 69, 19, 0.2)';
let green = 'rgba(0, 181, 0, 0.12)';
let red = 'rgba(181, 26, 0, 0.2)';

let colorWaitMoney = () => {
    let grid = BX?.Main?.gridManager?.getById('CRM_DEAL_LIST_V12_C_5')?.instance;
    if (!grid) {
        return;
    }
    let data = grid?.arParams?.EDITABLE_DATA;
    if (!data) {
        return;
    }

    Object.keys(data).forEach(key => {
        let deal = data[key];
        if (!deal['UF_CRM_1680756150']) {
            return;
        }
        let now = new Date();
        now.setHours(0, 0, 0, 0);
        let date = new Date(deal['UF_CRM_1680756150'].split('.').reverse().join('/'));
        if (deal['STAGE_ID'] == 'C5:2' && date.getTime() <= now.getTime()) {
            let row = grid.rows.getById(key);
            if (!row) {
                return;
            }
            Array.from(row.node.querySelectorAll('td.main-grid-cell')).forEach(td => td.style.background = red);
        }
    });
}

$(document).ready(function () {
    colorWaitMoney();
    return;

    var sum_index = false;
    $("#CRM_DEAL_LIST_V12_table>thead>tr th").each(function (k, v) {
        if ($(v).data("name") == "OPPORTUNITY_ACCOUNT") {
            sum_index = k;
        }
    })
    if (!sum_index) {
        $("#CRM_DEAL_LIST_V12_table>thead>tr th").each(function (k, v) {
            if ($(v).data("name") == "SUM" || $(v).data("name") == "OPPORTUNITY") {
                sum_index = k;
            }
        })
    }
    $(".main-grid-table[id^='CRM_DEAL_LIST_V12_']>thead>tr th").each(function (k, v) {
        if ($(v).data("name") == "SUM" || $(v).data("name") == "OPPORTUNITY") {
            sum_index = k;
        }
    })
    if (sum_index) {
        $(".main-grid-table[id^='CRM_DEAL_LIST_V12_'] tbody tr").each(function (k, v) {
            var roottd = $(v).find(">td");
            sum = roottd.eq(sum_index).text();
            sum = parseFloat(sum.replace(/[\s]+/g, "").replace(/[\,]+/g, "."))
            if (sum > 100000 && sum <= 300000) {
                roottd.css({background: yellow});
            }
            if (sum > 300000 && sum <= 500000) {
                roottd.css({background: brown});
            }
            if (sum > 500000) {
                roottd.css({background: green});
            }
        })
    }
    $(".crm-nearest-activity-wrapper").closest("td").each(function () {
        var obj = $(this);
        var id = $(this).parent().find("td").eq(0).find("input").val();
        $.ajax({
            url: "/bitrix/tools/istlineCheckRules/checkrules.php",
            type: "POST",
            data: {ID: id},
            success: function (data) {
                obj.html(data);
            }

        })
    });
})


BX.ready(function () {
    BX.addCustomEvent("onAjaxSuccessFinish", function () {
        setTimeout(function () {
            colorWaitMoney();
            return;
            var sum_index = false;
            $("#CRM_DEAL_LIST_V12_table>thead>tr th").each(function (k, v) {
                if ($(v).data("name") == "OPPORTUNITY_ACCOUNT") {
                    sum_index = k;
                }
            })
            if (!sum_index) {
                $("#CRM_DEAL_LIST_V12_table>thead>tr th").each(function (k, v) {
                    if ($(v).data("name") == "SUM" || $(v).data("name") == "OPPORTUNITY") {
                        sum_index = k;
                    }
                })
            }
            if (sum_index) {
                $(".main-grid-table[id^='CRM_DEAL_LIST_V12_'] tbody tr").each(function (k, v) {
                    var roottd = $(v).find(">td");
                    sum = roottd.eq(sum_index).text();
                    sum = parseFloat(sum.replace(/[\s]+/g, "").replace(/[\,]+/g, "."))
                    if (sum > 100000 && sum <= 300000) {
                        roottd.css({background: yellow});
                    }
                    if (sum > 300000 && sum <= 500000) {
                        roottd.css({background: brown});
                    }
                    if (sum > 500000) {
                        roottd.css({background: green});
                    }
                })
            }


            $(".crm-nearest-activity-wrapper").closest("td").each(function () {
                // $(".crm-list-deal-today").each(function () {
                var obj = $(this);
                var id = $(this).parent().find("td").eq(0).find("input").val();
                $.ajax({
                    url: "/bitrix/tools/istlineCheckRules/checkrules.php",
                    type: "POST",
                    data: {ID: id},
                    success: function (data) {
                        obj.html(data);
                    }

                })
            })

        }, 3000);
    })
});


if (typeof (BX.CrmEntityLiveFeedActivityList) === "undefined") {
    BX.CrmEntityLiveFeedActivityList = function () {
        this._prefix = "";
        this._activityEditor = null;
        this._items = {};
    };

    BX.CrmEntityLiveFeedActivityList.prototype =
        {
            initialize: function (id, settings) {
                this._id = id;
                this._settings = settings;
                this._prefix = this.getSetting("prefix");

                var activityEditorId = this.getSetting("activityEditorId", "");
                if (BX.type.isNotEmptyString(activityEditorId) && typeof (BX.CrmActivityEditor !== "undefined")) {
                    this._activityEditor = typeof (BX.CrmActivityEditor.items[activityEditorId]) !== "undefined"
                        ? BX.CrmActivityEditor.items[activityEditorId] : null;
                }

                var activityWrapper = this._resolveElement("activities");
                if (activityWrapper) {
                    var data = this.getSetting("data", []);
                    for (var i = 0; i < data.length; i++) {
                        var itemData = data[i];
                        var itemId = parseInt(itemData["ID"]);
                        var activityContainer = BX.findChild(activityWrapper, {"attribute": {"data-entity-id": itemId}}, true, false);
                        if (activityContainer) {
                            this._items[itemId.toString()] = BX.CrmEntityLiveFeedActivity.create(
                                itemId,
                                {
                                    "activityEditor": this._activityEditor,
                                    "container": activityContainer,
                                    "clientTemplate": this.getSetting("clientTemplate", ""),
                                    "referenceTemplate": this.getSetting("referenceTemplate", ""),
                                    "params": BX.CrmParamBag.create(itemData)
                                }
                            );
                        }
                    }
                }
            },
            getSetting: function (name, defaultVal) {
                return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultVal;
            },
            setSetting: function (name, val) {
                this._settings[name] = val;
            },
            _resolveElement: function (id) {
                var elementId = id;
                if (this._prefix) {
                    elementId = this._prefix + elementId
                }

                return BX(elementId);
            }
        };

    BX.CrmEntityLiveFeedActivityList.create = function (id, settings) {
        var self = new BX.CrmEntityLiveFeedActivityList();
        self.initialize(id, settings);
        return self;
    };
}

if (typeof (BX.CrmEntityLiveFeedActivity) === "undefined") {
    BX.CrmEntityLiveFeedActivity = function () {
        this._settings = {};
        this._id = 0;
        this._activityEditor = null;
        this._container = this._completeButton = this._subjectElem = this._timeElem = this._responsibleElem = null;
        this._params = null;
        this._enableExternalChange = true;
    };

    BX.CrmEntityLiveFeedActivity.prototype =
        {
            initialize: function (id, settings) {
                this._id = id;
                this._settings = settings;

                this._activityEditor = this.getSetting("activityEditor", null);
                if (!this._activityEditor) {
                    throw "BX.CrmEntityLiveFeedActivity: Could not find activityEditor.";
                }

                this._activityEditor.addActivityChangeHandler(BX.delegate(this._onExternalChange, this));

                this._container = this.getSetting("container");
                if (!this._container) {
                    throw "BX.CrmEntityLiveFeedActivity: Could not find container.";
                }

                this._completeButton = BX.findChild(this._container, {"className": "crm-right-block-checkbox"}, true, false);
                if (this._completeButton) {
                    BX.bind(this._completeButton, "click", BX.delegate(this._onCompleteButtonClick, this));
                }

                this._subjectElem = BX.findChild(this._container, {"className": "crm-right-block-item-title-text"}, true, false);
                if (this._subjectElem) {
                    BX.bind(this._subjectElem, "click", BX.delegate(this._onTitleClick, this));
                }

                this._timeElem = BX.findChild(this._container, {"className": "crm-right-block-date"}, true, false);
                this._responsibleElem = BX.findChild(this._container, {"className": "crm-right-block-name"}, true, false);
                this._bindingElem = BX.findChild(this._container, {"className": "crm-right-block-item-label"}, true, false);

                this._params = this.getSetting("params", null);
                if (!this._params) {
                    this._params = BX.CrmParamBag.create();
                }
            },
            getId: function () {
                return this._id;
            },
            getSetting: function (name, defaultVal) {
                return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultVal;
            },
            setSetting: function (name, val) {
                this._settings[name] = val;
            },
            isCompleted: function () {
                return this._params.getBooleanParam("completed", false);
            },
            setCompleted: function (completed) {
                completed = !!completed;
                if (this.isCompleted() !== completed) {
                    this._enableExternalChange = false;
                    this._activityEditor.setActivityCompleted(this._id, completed, BX.delegate(this._onComplete, this));
                }
            },
            layout: function (classOnly) {
                classOnly = !!classOnly;

                var typeId = this._params.getIntParam("typeID", 0);
                var direction = this._params.getIntParam("direction", 0);
                var completed = this._params.getBooleanParam("completed", false);

                var containerClassName = "";
                if (typeId === BX.CrmActivityType.call) {
                    containerClassName = direction === BX.CrmActivityDirection.incoming
                        ? "crm-right-block-call" : "crm-right-block-call-to";
                } else if (typeId === BX.CrmActivityType.meeting) {
                    containerClassName = "crm-right-block-meet";
                } else if (typeId === BX.CrmActivityType.task) {
                    containerClassName = "crm-right-block-task";
                }

                if (completed) {
                    BX.removeClass(this._container, containerClassName);
                    BX.addClass(this._container, containerClassName + "-done");
                } else {
                    BX.removeClass(this._container, containerClassName + "-done");
                    BX.addClass(this._container, containerClassName);
                }

                var now = new Date();
                var time = BX.parseDate(this._params.getParam("deadline"));
                if (!time) {
                    time = new Date();
                }

                if (this._timeElem) {
                    if (!completed && time <= now) {
                        BX.addClass(this._container, "crm-right-block-deadline");
                    } else {
                        BX.removeClass(this._container, "crm-right-block-deadline");
                    }
                }

                if (classOnly) {
                    return;
                }

                if (this._subjectElem) {
                    this._subjectElem.innerHTML = BX.util.htmlspecialchars(this._params.getParam("subject"));
                }

                if (this._completeButton && this._completeButton.checked !== completed) {
                    this._completeButton.checked = completed;
                }

                if (this._timeElem) {
                    this._timeElem.innerHTML = BX.CrmActivityEditor.trimDateTimeString(BX.date.format(BX.CrmActivityEditor.getDateTimeFormat(), time));
                }

                if (this._responsibleElem) {
                    this._responsibleElem.innerHTML = BX.util.htmlspecialchars(this._params.getParam("responsibleName"));
                }

                if (this._bindingElem) {
                    var clientTitle = this._params.getParam("clientTitle", "");
                    var clientInfo = clientTitle !== ""
                        ? this.getSetting("clientTemplate").replace(/#CLIENT#/gi, clientTitle)
                        : "";

                    var ownerType = this._params.getParam("ownerType", "");
                    var ownerTitle = this._params.getParam("ownerTitle", "");
                    var referenceInfo = ownerTitle !== "" && (ownerType == "DEAL" || ownerType == "LEAD")
                        ? this.getSetting("referenceTemplate").replace(/#REFERENCE#/gi, ownerTitle)
                        : "";

                    var bindingHtml = clientInfo;
                    if (referenceInfo !== "") {
                        if (bindingHtml !== "") {
                            bindingHtml += " ";
                        }

                        bindingHtml += referenceInfo;
                    }
                    this._bindingElem.innerHTML = BX.util.htmlspecialchars(bindingHtml);
                }
            },
            _onCompleteButtonClick: function (e) {
                this.setCompleted(!this.isCompleted());
            },
            _onComplete: function (data) {
                this._enableExternalChange = true;

                if (BX.type.isBoolean(data["COMPLETED"])) {
                    this._params.setParam("completed", data["COMPLETED"]);
                }

                this.layout(true);
            },
            _onTitleClick: function (e) {
                this._activityEditor.viewActivity(this._id);
                return BX.PreventDefault(e);
            },
            _onExternalChange: function (sender, action, settings) {
                if (!this._enableExternalChange) {
                    return;
                }

                var id = typeof (settings["ID"]) !== "undefined" ? parseInt(settings["ID"]) : 0;
                if (this._id !== id) {
                    return;
                }

                this._params.setParam("subject", BX.type.isNotEmptyString(settings["subject"]) ? settings["subject"] : "");
                this._params.setParam("direction", BX.type.isNotEmptyString(settings["direction"]) ? parseInt(settings["direction"]) : 0);
                this._params.setParam("completed", BX.type.isBoolean(settings["completed"]) ? settings["completed"] : false);
                this._params.setParam("deadline", BX.type.isNotEmptyString(settings["deadline"]) ? settings["deadline"] : "");
                this._params.setParam("responsibleName", BX.type.isNotEmptyString(settings["responsibleName"]) ? settings["responsibleName"] : "");
                this._params.setParam("ownerType", BX.type.isNotEmptyString(settings["ownerType"]) ? settings["ownerType"] : "");
                this._params.setParam("ownerTitle", BX.type.isNotEmptyString(settings["ownerTitle"]) ? settings["ownerTitle"] : "");

                if (BX.type.isArray(settings["communications"])) {
                    var comms = settings["communications"];
                    for (var i = 0; i < comms.length; i++) {
                        var comm = comms[i];
                        var entityType = comm["entityType"];
                        if (entityType === "CONTACT" || entityType === "COMPANY") {
                            this._params.setParam("clientTitle", BX.type.isNotEmptyString(comm["entityTitle"]) ? comm["entityTitle"] : "");
                            break;
                        }
                    }
                } else {
                    this._params.setParam("clientTitle", "");
                }

                this.layout(false);
            }
        };

    BX.CrmEntityLiveFeedActivity.create = function (id, settings) {
        var self = new BX.CrmEntityLiveFeedActivity();
        self.initialize(id, settings);
        return self;
    };
}

