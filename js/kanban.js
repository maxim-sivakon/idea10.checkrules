if (typeof KanbanManager === "undefined") {
    KanbanManager = function () {
        this.RED = '#ffbebe';
        this.BLUE = '#bebeff';
        this.YELLOW = '#ffffb3';
        this.GREEN = '#33ff33';

        // this.RED = '#f5481873';
        // this.BLUE = '#bebeff73';
        // this.YELLOW = '#ffffb3a6';
        // this.GREEN = '#bbed2173';

        this.serviceUrl = '/local/modules/istline.checkrules/tools/ajax.php?sessid=' + BX.bitrix_sessid();
        BX.addCustomEvent(window, 'Kanban.Grid:onRender', this.init.bind(this));
        BX.addCustomEvent(window, 'Kanban.Column:render', this.process.bind(this));
        BX.addCustomEvent(window, 'Crm.Kanban.Column:onItemAdded', this.onColumnChanged.bind(this));
        BX.addCustomEvent(window, 'Kanban.Grid:onItemMoved', this.onColumnChanged.bind(this));
        BX.addCustomEvent(window, 'tasksTaskEvent', this.onNeedRecalculateColumn.bind(this));
    }
    KanbanManager.prototype = {
        init(grid = null) {
            if (!this.kanban) {
                this.kanban = grid || null;
                this.options = {
                    checkLostDeal: [
                        {
                            stageId: '8',
                            period: 7
                        },
                        {
                            stageId: '11',
                            period: 3
                        }
                    ],
                    checkDeliveryTime: {
                        stages: ['30', '24']
                    },
                    check30Proc: {
                        stages: ['NEGOTIATION']
                    },
                    checkTalon: {
                        stages: ['25']
                    },
                    checkHarvest: {
                        stages: ['31']
                    },
                    checkShipment: {
                        stages: [
                            '5', '6',
                            'C6:1', 'C6:2'
                        ]
                    },
                    withSuppInvoice: {
                        stages: [
                            'NEW', 'ON_HOLD', 'NEGOTIATION',
                            'C4:NEW', 'C4:PREPARATION', 'C4:PREPAYMENT_INVOICE', 'C4:EXECUTING', 'C4:FINAL_INVOICE'
                        ]
                    },
                    checkWorkTo: {
                        stages: [
                            'C3:NEW', 'C3:PREPARATION', 'C3:PREPAYMENT_INVOICE', 'C3:EXECUTING', 'C3:1',
                            'C2:NEW', 'C2:PREPARATION', 'C2:PREPAYMENT_INVOICE', 'C2:EXECUTING', 'C2:9'
                        ]
                    },
                    getStatus: [
                        {
                            method: 'checkTimeOnStageBackgroundPodgonovcaSpets',
                            stages: [
                                'C4:2'
                            ]
                        },
                        {
                            method: 'checkLogisticShipment',
                            stages: ['C6:8'],
                        },
                        {
                            method: 'checkLogisticShipmentExw',
                            stages: ['C6:9'],
                        },
                        {
                            method: 'checkPickFromSupp',
                            stages: ['C6:PREPAYMENT_INVOICE'],
                        },
                        {
                            method: 'checkTransferNsk',
                            stages: ['C6:10'],
                        },
                        {
                            method: 'checkWaitShipmentDate',
                            stages: ['C6:NEW'],
                        },
                        {
                            method: 'checkWaitMoneyFromContragent',
                            stages: ['C5:2'],
                        },
                        {
                            method: 'checkWaitCorrectionFromProvider',
                            stages: ['C4:PREPAYMENT_INVOICE'],
                        },
                        {
                            method: 'checkWaitCorrectionFromContragent',
                            stages: ['C4:EXECUTING'],
                        },
                        {
                            method: 'checkDeliveryDeadlineTender',
                            stages: ['C2:3', 'C2:4', 'C3:4', 'C3:5'],
                        },
                        {
                            method: 'minPauseOnStage',
                            stages: [
                                'C6:FINAL_INVOICE',
                            ]
                        },
                        {
                            method: 'checkWithdrawList',
                            stages: [
                                'C6:NEW',
                                'C6:10',
                                'C6:8',
                                'C6:9',
                                'C6:PREPARATION',
                                'C6:PREPAYMENT_INVOICE',
                                'C6:5',
                                'C6:FINAL_INVOICE',
                                'C6:1',
                                'C6:2',
                                'C6:4',
                                'C6:6',
                                'C6:7',
                                'C7:NEW',
                                'C7:EXECUTING',
                                'C7:3',
                                'C7:UC_BVBURC',
                                'C7:UC_B81ME9',
                                'C7:PREPARATION',
                                'C7:PREPAYMENT_INVOICE',
                                'C7:FINAL_INVOICE',
                                'C7:1',
                                'C7:2',
                            ]
                        },

                        {
                            method: 'lastUpdateDealStageOne',
                            stages: ['C8:PREPARATION']
                        },
                        {
                            method: 'checkReadyOrderDap',
                            stages: ['C6:PREPARATION']
                        },
                        {
                            method: 'checkLogisticProductStock',
                            stages: ['C6:UC_VD29WG']
                        },
                        {
                            method: 'lastUpdateDealStage',
                            stages: [
                                'C6:5',
                                'C6:UC_U4STVC'
                            ]
                        },
                        {
                            method: 'collectingDocumentsForPermit',
                            stages: [
                                'C17:PREPARATION'
                            ]
                        },
                        {
                            method: 'readinessFromTheSupplier',
                            stages: [
                                'C17:UC_14SWMS'
                            ]
                        },
                        {
                            method: 'readinessFromTheSupplierToDay',
                            stages: [
                                'C17:UC_14SWMS'
                            ]
                        },
                        {
                            method: 'goingToTheNSC',
                            stages: [
                                'C17:UC_52JIRY'
                            ]
                        },
                        {
                            method: 'theProductWasTakenFromTheSupplier',
                            stages: [
                                'C17:UC_7LRTPY'
                            ]
                        },
                        {
                            method: 'goodsOnTheWayToDay',
                            stages: [
                                'C17:UC_383LFD'
                            ]
                        },
                        {
                            method: 'mouthReadyNotShipped',
                            stages: [
                                'C17:UC_B7G4ZH'
                            ]
                        },
                        {
                            method: 'shipTodayBody',
                            stages: [
                                'C17:UC_C7BXXO'
                            ]
                        },
                        {
                            method: 'readyForShipmentDAP',
                            stages: [
                                'C17:UC_DKQCKB'
                            ]
                        },
                        {
                            method: 'checkNTVED',
                            stages: [
                                'C17:UC_S5SV7H'
                            ]
                        },
                        {
                            method: 'returnСontrolDS',
                            stages: [
                                'C2:UC_KZHZZY'
                            ]
                        },
                        {
                            method: 'checkTimeOnStageImplementationRF',
                            stages: [
                                'C17:UC_NZTLLE',
                            ]
                        },
                        {
                            method: 'checkTimeOnStageTakeWork',
                            stages: [
                                'C17:UC_BBPEXS',
                            ]
                        },
                        {
                            method: 'checkTimeOnStageError',
                            stages: [
                                'C17:UC_JASZW0',
                            ]
                        },
                        {
                            method: 'checkTimeOnStageFixed',
                            stages: [
                                'C17:UC_XWCDGP',
                            ]
                        },
                        {
                            method: 'checkTimeOnStagePreparationUPD',
                            stages: [
                                'C17:UC_CHZP3S',
                            ]
                        },
                        {
                            method: 'checkTimeOnStageWaitingSNT',
                            stages: [
                                'C17:UC_1XG6DD',
                            ]
                        },
                        {
                            method: 'checkTimeOnStageImplementationKZ',
                            stages: [
                                'C17:UC_DTOYSW',
                            ]
                        },
                        {
                            method: 'checkTimeOnStageTheMouthReadyNotShipped',
                            stages: [
                                'C17:UC_B7G4ZH'
                            ]
                        },


                    ],
                    getTitleStatus: [
                        {
                            method: 'checkTimeOnStageTitlePodgonovcaSpets',
                            stages: [
                                'C4:2'
                            ]
                        },
                        {
                            method: 'readinessFromTheSupplierTwoDate',
                            stages: [
                                'C17:UC_14SWMS'
                            ]
                        },
                        {
                            method: 'readyForShipmentEXW',
                            stages: [
                                'C17:UC_DKQCKB'
                            ]
                        },
                        {
                            method: 'checkLogisticShipmentExw',
                            stages: [
                                'C17:UC_DKQCKB'
                            ]
                        },
                        {
                            method: 'shipTodayTitle',
                            stages: [
                                'C17:UC_C7BXXO'
                            ]
                        },
                        {
                            method: 'goodsOnTheWay',
                            stages: [
                                'C17:UC_383LFD'
                            ]
                        },
                        {
                            method: 'checkPrepareTn',
                            stages: [
                                'C7:EXECUTING',
                                'C7:3',
                                'C7:UC_BVBURC',
                                'C7:UC_B81ME9',
                                'C7:PREPARATION',
                                'C7:PREPAYMENT_INVOICE'
                            ]
                        },
                        {
                            method: 'checkTalonStatus',
                            stages: [
                                'C7:3',
                            ]
                        },
                        {
                            method: 'checkRtuReady',
                            stages: [
                                'C6:4',
                            ]
                        },
                        {
                            method: 'checkLogisticShipmentExwAndDap',
                            stages: ['C6:1', 'C6:2'],
                        },
                        {
                            method: 'checkSntData',
                            stages: [
                                'C6:UC_L2MDJF',
                            ]
                        },
                        {
                            method: 'urgentImplementation',
                            stages: [
                                'C17:UC_NZTLLE',
                                'C17:UC_BBPEXS',
                                'C17:UC_JASZW0',
                                'C17:UC_XWCDGP',
                                'C17:UC_CHZP3S',
                                'C17:UC_1XG6DD',
                                'C17:UC_DTOYSW',
                                'C17:UC_B7G4ZH'
                            ]
                        },
                        {
                            method: 'checkRemoval',
                            stages: [
                                'C8:NEW',
                                'C8:UC_VN4I74',
                                'C8:UC_YRGPWW',
                                'C8:PREPARATION',
                                'C8:UC_5Z81QA',
                                'C8:PREPAYMENT_INVOICE',
                                'C8:EXECUTING',
                                'C8:UC_PYYYP0',
                                'C8:FINAL_INVOICE',
                                'C8:UC_94VQKE',
                                'C8:UC_S91J2F',
                                'C8:UC_ALESMK',
                                'C8:UC_006YWA',
                                'C8:WON',
                                'C8:LOSE',
                                'C8:APOLOGY',
                            ]
                        },
                    ]
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
                this.checkLostDeal();
                this.checkDeliveryTime();
                this.check30Proc();
                this.checkTalon();
                this.checkHarvest();
                this.checkShipment();
                this.withSuppInvoice();
                this.checkWorkTo();
                this.bindIconPanel();
                this.getStatus();
                this.getTitleStatus();
            }
        },
        bindColorInfo(item, info, text) {
            var crmKanbanItemAside = item.container.querySelector('.crm-kanban-item-aside');
            if(crmKanbanItemAside && !crmKanbanItemAside.querySelector('color-info-'+item.getId())){
                var newElement = document.createElement('span');
                newElement.id = 'color-info-'+item.getId();
                newElement.innerHTML = '<div data-hint="<b>'+text+'</b>:<br><br>'+info+'" data-hint-center data-hint-html></div>';
                crmKanbanItemAside.appendChild(newElement);
                BX.UI.Hint.init(BX('color-info-'+item.getId()));
            }else{
                console.log("нет DOM");
            }
        },
        // bindColorInfo(item, info, text) {
        //     var crmKanbanItemAside = item.container.querySelector('.crm-kanban-item-aside');
        //     if (crmKanbanItemAside) {
        //         var newElement = document.createElement('span');
        //         newElement.id = 'color-info-' + item.getId();
        //
        //         var hintContent = document.createElement('div');
        //         hintContent.setAttribute('data-hint', '<b>' + text + '</b>:<br><br>' + info);
        //         hintContent.setAttribute('data-hint-center', '');
        //         hintContent.setAttribute('data-hint-html', '');
        //         hintContent.innerHTML = '<b>' + text + '</b>:<br><br>' + info;
        //
        //         if(newElement.appendChild(hintContent) && crmKanbanItemAside.appendChild(newElement)){
        //             console.log('yes');
        //         } else{
        //             console.log('no' + item.getId());
        //         }
        //
        //
        //         BX.UI.Hint.init(BX('color-info-'+item.getId())); // передайте сам элемент, а не его id
        //     }
        // },
        getStatus() {
            let stagesList = [];
            this.options.getStatus.map(opt => {
                stagesList = [...stagesList, ...opt.stages];
            });
            let params = [];
            let checkList = this.items.filter(item => {
                let stage = item.getColumnId();
                let isChecked = item.statusChecked;
                let id = item.getId();
                let method = [];
                this.options.getStatus.map(opt => {
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
                }, this.onAfterGetStatus.bind(this, checkList));

            }

        },
        getTitleStatus() {
            let stagesList = [];
            this.options.getTitleStatus.map(opt => {
                stagesList = [...stagesList, ...opt.stages];
            });
            let params = [];
            let checkList = this.items.filter(item => {
                let stage = item.getColumnId();
                let isChecked = item.titleStatusChecked;
                let id = item.getId();
                let method = [];
                this.options.getTitleStatus.map(opt => {
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
                }, this.onAfterGetTitleStatus.bind(this, checkList));
            }
        },
        bindIconPanel() {
            let ids = [];
            this.items.map(item => {
                if (
                    item.iconBind
                    || item.getId() <= 0
                ) {
                    return;
                }
                ids.push(item.getId());
            });
            this.sendRequest({
                method: 'getIconInfo',
                params: {
                    ids: ids
                }
            }, this.onAfterGetIconInfo.bind(this));

        },
        onAfterGetIconInfo(data) {
            if (!data) {
                return;
            }
            this.items.map(item => {
                if (item.getId() <= 0 || !data['result'][item.getId()]) {
                    return;
                }
                item.iconBind = true;
                let info = data['result'][item.getId()];
                let panel = this.buildIconPanel(info);

                if(!item.layout.bodyContainer.querySelector('.crm-kanban-item').classList.contains('.icon-panel-wrapper')){
                    item.layout.bodyContainer.querySelector('.crm-kanban-item').appendChild(panel.wrapper);
                }
            });
        },
        buildIconPanel(info) {
            let result = {};
            let wrapper = this.getBlock({
                classes: 'icon-panel-wrapper',
            });
            result.wrapper = wrapper;
            for (let type in info) {
                if (info[type]) {
                    let block = this.getBlock({
                        classes: [
                            'icon-panel-item',
                            type,
                            info[type]
                        ],
                    });
                    wrapper.appendChild(block);
                    result[type] = block;
                }
            }
            return result;
        },
        checkWorkTo() {
            this.items.map(item => {
                if (
                    this.options.checkWorkTo.stages.indexOf(item.getColumnId()) == -1
                    || item.checked
                    || item.getId() <= 0
                ) {
                    return;
                }
                this.sendRequest({
                    method: 'checkWorkTo',
                    params: {
                        id: item.getId()
                    }
                }, this.onAftercheckWorkTo.bind(this, item));
            });
        },
        onAftercheckWorkTo(item, data) {
            item.checked = true;
            if (data && data.hasOwnProperty('status')) {
                switch (data.status) {
                    case 'green':
                        item.container.style.backgroundColor = this.GREEN;
                        break;
                    case 'yellow':
                        item.container.style.backgroundColor = this.YELLOW;
                        break;
                    case 'red':
                        item.container.style.backgroundColor = this.RED;
                        break;
                }
            }
        },
        setBgColor(target, color) {
            let colorValue = false;
            switch (color) {
                case 'green':
                    colorValue = this.GREEN;
                    break;
                case 'yellow':
                    colorValue = this.YELLOW;
                    break;
                case 'red':
                    colorValue = this.RED;
                    break;
                case 'blue':
                    colorValue = this.BLUE;
                    break;
            }
            if (colorValue) {
                target.style.backgroundColor = colorValue;
            }
        },
        onAfterGetStatus(items, data) {
            let statusList = data['status'];
            items.map(item => {
                item.statusChecked = true;
                let id = item.getId();
                if(statusList && statusList[id]){
                    if(statusList[id]["color"] !== undefined){
                        this.setBgColor(item.container, statusList[id]["color"]);
                        this.bindColorInfo(item, statusList[id]["info"], 'Описание подкраски фона сделки');
                    }else{
                        this.setBgColor(item.container, statusList[id]);
                    }
                }
            });
        },
        onAfterGetTitleStatus(items, data) {
            let statusList = data['status'];
            items.map(item => {
                item.titleStatusChecked = true;
                let id = item.getId();
                if(statusList && statusList[id]){
                    if(statusList[id]["color"] !== undefined){
                        this.setBgColor(item.container.querySelector('.crm-kanban-item-title'), statusList[id]["color"]);
                        this.bindColorInfo(item, statusList[id]["info"], 'Описание подкраски заголовка сделки');
                    }else{
                        this.setBgColor(item.container.querySelector('.crm-kanban-item-title'), statusList[id]);
                    }
                }
            });
        },
        withSuppInvoice() {
            this.items.map(item => {
                if (
                    this.options.withSuppInvoice.stages.indexOf(item.getColumnId()) == -1
                    || item.checked
                    || item.getId() <= 0
                ) {
                    return;
                }
                this.sendRequest({
                    method: 'withSuppInvoice',
                    params: {
                        id: item.getId()
                    }
                }, this.onAfterWithSuppInvoice.bind(this, item));
            });
        },
        onAfterWithSuppInvoice(item, data) {
            item.checked = true;
            if (data && data.hasOwnProperty('status')) {
                switch (data.status) {
                    case 'green':
                        item.container.style.backgroundColor = this.GREEN;
                        this.bindColorInfo(item, 'тут пока нет описания', '---');
                        break;
                }
            }

        },
        checkLostDeal() {
            this.options.checkLostDeal.map(item => {
                item.items = this.items.filter(elem => item.stageId == elem.getColumnId());
            });
            this.getInfoByLostDeal();
        },
        check30Proc() {
            this.items.map(item => {
                if (
                    this.options.check30Proc.stages.indexOf(item.getColumnId()) == -1
                    || item.checked
                    || item.getId() <= 0
                ) {
                    return;
                }
                this.sendRequest({
                    method: 'check30Proc',
                    params: {
                        id: item.getId()
                    }
                }, this.onAfterCheck30Proc.bind(this, item));
            });
        },
        onAfterCheck30Proc(item, data = {}) {
            item.checked = true;
            if (data && data.hasOwnProperty('status')) {
                switch (data.status) {
                    case 'red':
                        item.container.style.backgroundColor = this.RED;
                        break;
                    case 'blue':
                        item.container.style.backgroundColor = this.BLUE;
                        break;
                }
            }
        },
        checkHarvest() {
            this.items.map(item => {
                if (
                    this.options.checkHarvest.stages.indexOf(item.getColumnId()) == -1
                    || item.checked
                    || item.getId() <= 0
                ) {
                    return;
                }
                this.sendRequest({
                    method: 'checkHarvest',
                    params: {
                        id: item.getId()
                    }
                }, this.onAfterCheckHarvest.bind(this, item));
            });
        },
        onAfterCheckHarvest(item, data = {}) {
            item.checked = true;
            if (data && data.hasOwnProperty('status')) {
                switch (data.status) {
                    case 'red':
                        item.container.style.backgroundColor = this.RED;
                        break;
                    case 'yellow':
                        item.container.style.backgroundColor = this.YELLOW;
                        break;
                }
            }
        },
        checkTalon() {
            this.items.map(item => {
                if (
                    this.options.checkTalon.stages.indexOf(item.getColumnId()) == -1
                    || item.checked
                    || item.getId() <= 0
                ) {
                    return;
                }
                this.sendRequest({
                    method: 'checkTalon',
                    params: {
                        id: item.getId()
                    }
                }, this.onAfterCheckTalon.bind(this, item));
            });
        },
        onAfterCheckTalon(item, data = {}) {
            item.checked = true;
            if (data && data.hasOwnProperty('status')) {
                switch (data.status) {
                    case 'red':
                        item.container.style.backgroundColor = this.RED;
                        break;
                }
            }
        },
        checkShipment() {
            this.items.map(item => {
                if (
                    this.options.checkShipment.stages.indexOf(item.getColumnId()) == -1
                    || item.checked
                    || item.getId() <= 0
                ) {
                    return;
                }
                this.sendRequest({
                    method: 'checkShipment',
                    params: {
                        id: item.getId()
                    }
                }, this.onAfterCheckShipment.bind(this, item));
            });
        },
        onAfterCheckShipment(item, data = {}) {
            item.checked = true;
            if (data && data.hasOwnProperty('status')) {
                switch (data.status) {
                    case 'red':
                        item.container.style.backgroundColor = this.RED;
                        break;
                }
            }
        },
        checkDeliveryTime() {
            this.items.map(item => {
                if (
                    this.options.checkDeliveryTime.stages.indexOf(item.getColumnId()) == -1
                    || item.checked
                    || item.getId() <= 0
                ) {
                    return;
                }
                this.sendRequest({
                    method: 'checkDelivery',
                    params: {
                        id: item.getId()
                    }
                }, this.onAfterCheckDeliveryTime.bind(this, item));
            });
        },
        onAfterCheckDeliveryTime(item, data = {}) {
            item.checked = true;
            if (data && data.hasOwnProperty('status')) {
                switch (data.status) {
                    case 'red':
                        item.container.style.backgroundColor = this.RED;
                        break;
                    case 'yellow':
                        item.container.style.backgroundColor = this.YELLOW;
                        break;
                }
            }
        },
        getInfoByLostDeal() {
            this.options.checkLostDeal.map(item => {
                this.sendRequest({
                    method: 'getLostDeals',
                    params: {
                        ids: item.items.map(elem => elem.getId()),
                        period: item.period,
                    }
                }, this.onAfterGetInfoByLostDeal.bind(this, item));
            });
        },
        onAfterGetInfoByLostDeal(item, data = {}) {
            if (data && data.hasOwnProperty('dealList') && Array.isArray(data.dealList)) {
                item.items.map(elem => {
                    if (data.dealList.indexOf(elem.getId()) != -1) {
                        elem.container.style.backgroundColor = this.RED;
                    }
                });
            }
        },
        getBlock: function (data = {}) {
            let classes = data.classes || [];
            let tag = data.tag || 'div';
            let html = data.html || '';
            let name = data.name || null;
            let value = data.value || null;
            let type = data.type || null;
            let dataset = data.dataset || null;
            let events = data.events || null;
            if (typeof classes === 'object' && classes.join !== undefined) {
                classes = classes.join(' ');
            }
            if (typeof classes !== 'string') {
                classes = '';
            }
            let params = {
                props: {}
            };
            if (classes) {
                params.props.className = classes;
            }
            if (name) {
                params.props.name = name;
            }
            if (value) {
                params.props.value = value;
            }
            if (dataset) {
                params.dataset = data.dataset
            }

            if (typeof html === 'string') {
                params.html = html;
            } else if (html instanceof HTMLElement) {
                params.children = [html];
            } else {
                params.children = html;
            }
            if (type) {
                params.props.type = type;
            }
            if (events) {
                params.events = events;
            }
            return BX.create(tag, params);
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
        getParamsByColumn(column) {
            if (!column) return;
            let stage = column.getId();
            let category = column.getGridData().params.CATEGORY_ID;
            return {
                stage,
                category,
            }
        },
        onColumnChanged(colData) {
            if (!colData) return;
            let column;
            column = colData instanceof BX.CRM.Kanban.Item ? colData.getColumn() : false;
            if (!column) {
                let data = colData.getData();
                if (!data) return;
                column = data.targetColumn;
            }
            let params = this.getParamsByColumn(column);
            if (!column.requestInProgress) {
                column.requestInProgress = true;
                this.sendRequest({
                    method: 'getSumPriceDeal',
                    params
                }, this.onAfterGetSumPriceDeal.bind(this, column));
            }
        },
        onAfterGetSumPriceDeal(column, priceData) {
            setTimeout(() => {
                column.requestInProgress = false;
                if (!priceData) return;
                let wrapper = column.layout.subTitlePriceText;
                wrapper.innerText = '';
                wrapper.parentElement.style.height = '60px';

                // анулируем отступы
                document.querySelectorAll('.crm-kanban-total-price-total').forEach(function(element) {
                    element.style.padding = '0px';
                });

                // удаляем кнопку быстрого добавления сделки.
                document.querySelectorAll('.crm-kanban-column-add-item-button').forEach(function(element) {
                    element.remove();
                });
                document.querySelectorAll('.crm-kanban-quick-form').forEach(function(element) {
                    element.remove();
                });

                let holder = this.getBlock({
                    classes: 'sum-price-with-currency'
                });
                holder.style.display = 'flex';
                holder.style.fontSize = '15px';
                holder.style.lineHeight = '19px';
                holder.style.flexDirection = 'column';
                holder.style.fontWeight = '600';
                holder.style.alignItems = 'baseline';
                holder.style.padding = '2px 10px';
                wrapper.appendChild(holder);
                for (let currency in priceData) {
                    holder.appendChild(
                        this.getBlock({
                            classes: 'sum-price-item',
                            html: Intl.NumberFormat("ru", {maximumFractionDigits: 2}).format(priceData[currency]).replace(',', '.') + ' ' + currency
                        })
                    );
                }
            }, 1000);
        },
        onNeedRecalculateColumn(action, data) {
            if (!(data && data.task)) {
                return;
            }
            this.onColumnChanged(data.task);
        }
    }
    BX.ready(function () {
        new KanbanManager();
    });
}