if (typeof KanbanManager === "undefined") {
    KanbanManager = function () {
        this.serviceUrl = '/local/modules/istline.checkrules/tools/ajax.php?sessid=' + BX.bitrix_sessid();
        BX.addCustomEvent(window, 'Kanban.Grid:onRender', this.init.bind(this));
        BX.addCustomEvent(window, 'Kanban.Column:render', this.process.bind(this));
    }
    KanbanManager.prototype = {
        init(grid = null) {
            if (!this.kanban) {
                this.kanban = grid || null;
                this.options = {
                    getDayStatus: [
                        {
                            method: 'checkDayTransferToStageLim13',
                            stages: [
                                'DT166_12:UC_QDNW15'
                            ]
                        },
                        {
                            method: 'checkDayTransferToStage3Milliards',
                            stages: [
                                'DT166_12:UC_5Z4LQJ'
                            ]
                        },
                        {
                            method: 'checkDayCreateDeal',
                            stages: [
                                'DT166_12:NEW',
                                'DT166_12:PREPARATION',
                                'DT166_12:UC_3K2V0Y',
                                'DT166_12:UC_VPWH1F',
                                'DT166_12:UC_OJJ0W1',
                                'DT166_12:UC_RUQJP5',
                                'DT166_12:UC_AYVIAU'
                            ]
                        },
                    ],
                };
                this.lastChange = {};
            }
            this.process();
        },
        process(column = {}) {
            if (!this.kanban) {
                return;
            }
            let now = new Date().getTime();
            let columnId = column instanceof BX.CRM.Kanban.Column ? column.getId() : '-';
            if (!this.lastChange.hasOwnProperty(columnId)) {
                this.lastChange[columnId] = 0;
            }
            if ((now - this.lastChange[columnId]) > 1000) {
                this.lastChange[columnId] = new Date().getTime();
                this.items = Object.values(this.kanban.items) || [];
                this.getDayStatus();
            }
        },
        bindCountDays(item, datetime, info) {
            var crmKanbanItemAside = item.container.querySelector('.crm-kanban-item-last-activity-time-ago');
            if (crmKanbanItemAside) {
                crmKanbanItemAside.innerHTML = '';
                var newElement = document.createElement('span');
                newElement.id = 'time-info-'+item.getId();
                newElement.innerHTML = '<div data-hint="<b>Описение времени</b>:<br><br>'+info+'" data-hint-center data-hint-html data-hint-no-icon>' + datetime + '</div>';
                crmKanbanItemAside.appendChild(newElement);
                BX.UI.Hint.init(BX('color-info-'+item.getId()));
            }
        },
        getDayStatus() {
            let stagesList = [];
            this.options.getDayStatus.map(opt => {
                stagesList = [...stagesList, ...opt.stages];
            });
            let params = [];
            let checkList = this.items.filter(item => {
                let stage = item.getColumnId();
                let isChecked = item.titleStatusChecked;
                let id = item.getId();
                let method = [];
                this.options.getDayStatus.map(opt => {
                    if (opt.stages.indexOf(stage) > -1) {
                        method.push(opt.method);
                    }
                });
                if (method.length && !isChecked && id > 0) {
                    params.push({
                        id: id,
                        method: method
                    });
                    return true;
                }
                return false;
            });
            if (checkList.length) {
                this.sendRequest({
                    method: 'getStatus',
                    params: params,
                }, this.onAfterGetDayStatus.bind(this, checkList));
            }
        },
        onAfterGetDayStatus(items, data) {
            let statusList = data['status'];
            items.map(item => {
                item.titleStatusChecked = true;
                let id = item.getId();
                if (statusList && statusList[id] && statusList[id]["dateTime"] !== undefined) {
                    this.bindCountDays(item, statusList[id]["dateTime"], statusList[id]["info"]);
                }
            });
        },
        sendRequest(params, cb) {
            if (!this.serviceUrl) {
                return;
            }
            setTimeout(() => {
                BX.ajax(
                    {
                        url: this.serviceUrl,
                        method: "POST",
                        dataType: "json",
                        data: params,
                        onsuccess: cb
                    }
                );
            }, Math.floor(Math.random() * (101)) + 50);
        },
    }
    BX.ready(function () {
        new KanbanManager();
    });
}