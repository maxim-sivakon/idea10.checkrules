<?php

namespace Istline\Checkrules;

use Bitrix\Crm\CompanyTable;
use Bitrix\Crm\DealTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Type\DateTime;
use CCurrencyLang;

use Bitrix\Crm\Model\Dynamic\TypeTable;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;

\IncludeModuleLangFile(__FILE__);
Loader::includeModule('crm');

/**
 * Class Rules
 * @package Istline\Checkrules
 */
class Rules
{
    const DEAL_FROM_FIELD = 'UF_CRM_1460029339';
    const COUNTRY_FIELD = 'UF_CRM_1568711077';
    const PAYMENT_METHOD_FIELD = 'UF_CRM_1638167545';
    const TERMS_DELIVERY_FIELD = 'UF_CRM_1600864467';
    public static $dealUfRoi = "UF_CRM_57BBC1C64E";
    public static $leadUfRoi = "UF_CRM_57BBC1BE29";
    protected static $countryList = [
        1177 => 'uz',
        1178 => 'ar',
        1176 => 'bel',
        1174 => 'kaz',
        1175 => 'kir',
    ];
    protected static $termsDeliveryType = [
        1777 => "exw",
        1778 => "dap",
        1780 => "cpt",
        1781 => "fca",
    ];
    protected static $contragentType = [
        608 => 'buyer',
        817 => 'provider',
    ];
    protected static $percentType = [
        2527 => 'green',
        2528 => 'red',
    ];
    protected static $companies = [];
    private static $companyStatus = [
        2568 => 'status_a',
        2569 => 'status_b',
        2570 => 'status_c',
    ];
    private static $moduleID = "istline.checkrules";
    private static $thisClass = "\Istline\Checkrules\Rules";
    private static $optionNameToMethod = [
        "refuse_lead_add_if_find_phone" => "_checkLeadPhone",
        "refuse_contact_add_if_empty_lead_and_company" => "_checkContactLeadOrCompany",
        "refuse_company_add_if_empty_lead" => "_checkCompanyLead",
        "repair_lead_and_roistat_deal" => "_repairDeal",
        "set_roistat_if_empty_lead" => "_checkRoistat",
        "repair_color_deal_from_stage" => "_repairColorDeal",
        "copy_comment_in_deal" => "_copyComment",
        "extend_activity_in_deal_list" => "_extActInDeal",
        "check_sum_by_stage" => "_checkSum",
    ];
    private $currOptions;

    public function __construct()
    {
        $this->currOptions = [];
        foreach (self::$optionNameToMethod as $name => $method) {
            $this->currOptions[$name] = \COption::GetOptionString(self::$moduleID, $name);
            if ($this->currOptions[$name] == "Y") {
                $this->{$method}();
            }
            if ($name == "check_sum_by_stage") {
                $stages = unserialize(\COption::GetOptionString(self::$moduleID, "check_sum_by_stage"));
                if (!empty($stages)) {
                    $this->{$method}();
                }
            }
        }
        $this->includeKanban();
    }

    protected function includeKanban()
    {
        if ($this->isCrmDealKanban()) {
            global $APPLICATION;
            $APPLICATION->AddHeadScript("/local/modules/istline.checkrules/js/kanban.js");
            Asset::getInstance()->addCss("/local/modules/istline.checkrules/css/kanban.css");
        }elseif($this->isCrmDealKanbanSmartProcess()){
            global $APPLICATION;
            $APPLICATION->AddHeadScript("/local/modules/istline.checkrules/js/kanbanSmartProcess.js");
        }
    }

    protected function isCrmDealKanban()
    {
        $url = $_SERVER["REQUEST_URI"];
        $part = preg_split("|/|", $url);
        return ($part[1] == "crm" && $part[2] == "deal" && $part[3] == "kanban");
    }
    protected function isCrmDealKanbanSmartProcess()
    {
        $url = $_SERVER["REQUEST_URI"];
        $part = preg_split("|/|", $url);
        return ($part[1] == "crm" && $part[2] == "type" && $part[4] == "kanban");
    }

    //// Работа с заявкой -start- ////

    //стадия "подготовка спец" - C4:2
    //Переход подготовка спец - UF_CRM_1636993838
    //Поле UF_CRM_1699496271 название Клиенту срочно нужен счет
    public static function  checkTimeOnStageTitlePodgonovcaSpets($ids)
    {
        $result = [];
        foreach ($ids as $id) {
            if(DealTools::checkPodgonovcaSpets($id)){
                $result[$id]['color'] = self::checkTimeOnStage($id,'UF_CRM_1636993838',  20)['status'];
                $result[$id]['info'] = 'Если перевели на текущую стадию и <b>прошло 20 минут, то красится в красный цвет</b>.';
            }
        }
        return $result;
    }
    //стадия "подготовка спец" - C4:2
    //Переход подготовка спец - UF_CRM_1636993838
    public static function  checkTimeOnStageBackgroundPodgonovcaSpets($ids)
    {
        $result = [];
        foreach ($ids as $id) {
            if(!DealTools::checkPodgonovcaSpets($id)){
                $result[$id]['color'] = self::checkTimeOnStage($id,'UF_CRM_1636993838',  40)['status'];
                $result[$id]['info'] = 'Если перевели на текущую стадию и <b>прошло 40 минут, то красится в красный цвет</b>.';
            }
        }
        return $result;
    }

    //// Работа с заявкой -end- ////

    //// Воронка логистика -start- ////

    //стадия "сбор документов для разрешения"
    //Переход в СБОР ДОКУМЕНТОВ ДЛЯ РАЗРЕШЕНИЯ - UF_CRM_1673590374
    public static function collectingDocumentsForPermit($ids)
    {
        $result = [];
        foreach ($ids as $id) {
            $result[$id]['color'] = self::checkTimeOnStage($id,'UF_CRM_1673590374',  420, 240)['status'];
            $result[$id]['info'] = 'Если перевели на текущую стадию и <b>прошло 7 часов то красится в красный цвет</b> или <b>прошло 4 часа то красится в желтый цвет</b>.';
        }
        return $result;
    }

    //стадия "готовность от поставщика"
    //Фактическая дата отгрузки от поставщика - UF_CRM_1626686380
    public static function readinessFromTheSupplier($ids)
    {
        $result = [];
        $idDeals = DealTools::getStatusByField($ids, 'UF_CRM_1626686380', null, -3, null, false);

        foreach ($idDeals as $idDeal => $color) {
            $result[$idDeal]['color'] = $color;
            $result[$idDeal]['info'] = 'Если перевели на текущую стадию и в поле <i>Фактическая дата отгрузки от поставщика</i> дата <b>просрочена на 3 дня и больше, то красим в желтый цвет</b>.';
        }

        return $result;
    }
    //стадия "готовность от поставщика"
    //Фактическая дата отгрузки от поставщика - UF_CRM_1626686380
    public static function readinessFromTheSupplierToDay($ids)
    {
        $result = [];
        $idDeals = DealTools::getStatusByFieldToDay($ids, 'UF_CRM_1626686380', 0, null, null, false);

        foreach ($idDeals as $idDeal => $color) {
            $result[$idDeal]['color'] = $color;
            $result[$idDeal]['info'] = 'Если перевели на текущую стадию и в поле <i>Фактическая дата отгрузки от поставщика</i> <b>текущая дата, то красим в красный цвет</b>.';
        }

        return $result;
    }

    //стадия "едет в НСК"
    //переход в товар едет в нск - UF_CRM_1637209765
    public static function goingToTheNSC($ids)
    {
        $result = [];
        $idDeals = DealTools::getStatusByField($ids, 'UF_CRM_1637209765', -6, null, null, false);

        foreach ($idDeals as $idDeal => $color) {
            $result[$idDeal]['color'] = $color;
            $result[$idDeal]['info'] = 'Если перевели на текущую стадию и <b>прошло 6 дней, то красится в красный цвет</b>';
        }

        return $result;
    }

    //стадия "товар забрали от поставщика"
    //переход в товар забрали от поставщика,ждем документы - UF_CRM_1637682642
    public static function theProductWasTakenFromTheSupplier($ids)
    {
        $result = [];
        $idDeals = DealTools::getStatusByField($ids, 'UF_CRM_1637682642', -2, null, null, false);

        foreach ($idDeals as $idDeal => $color) {
            $result[$idDeal]['color'] = $color;
            $result[$idDeal]['info'] = 'Если перевели на текущую стадию и <b>прошло 2 дня, то красится в красный цвет</b>';
        }

        return $result;
    }

    //стадия "товар в пути"
    //переход товар в пути - UF_CRM_1637682835
    public static function goodsOnTheWay($ids)
    {
        $result = [];
        $idDeals = DealTools::getStatusByField($ids, 'UF_CRM_1637682835', -6, null, null, false);

        foreach ($idDeals as $idDeal => $color) {
            $result[$idDeal]['color'] = $color;
            $result[$idDeal]['info'] = 'Если перевели на текущую стадию и <b>прошло 6 дней, то красится в красный цвет</b>';
        }

        return $result;
    }

    //стадия "товар в пути"
    //Дата получения - UF_CRM_1626853571
    public static function goodsOnTheWayToDay($ids)
    {
        $result = [];
        $idDeals = DealTools::getStatusByFieldToDay($ids, 'UF_CRM_1626853571', 0, null, null, false);

        foreach ($idDeals as $idDeal => $color) {
            $result[$idDeal]['color'] = $color;
            $result[$idDeal]['info'] = 'Если перевели на текущую стадию и в поле <i>Дата получения</i> <b>текущая дата, то красим в красный цвет</b>.';
        }

        return $result;
    }

    //стадия "(lim10)РТУ готово, не отгружено"
    //переход в РТУ готово, не отгружено - UF_CRM_1637682895
    public static function mouthReadyNotShipped($ids)
    {
        $result = [];
        $idDeals = DealTools::getStatusByField($ids, 'UF_CRM_1637682895', -2, null, null, false);

        foreach ($idDeals as $idDeal => $color) {
            $result[$idDeal]['color'] = $color;
            $result[$idDeal]['info'] = 'Если перевели на текущую стадию и <b>прошло 2 дня с момента перехода, то красится в красный цвет</b>';
        }

        return $result;
    }

    //стадия "отгружаем сегодня" - C17:UC_C7BXXO
    //Переход в ОТГРУЖАЕМ СЕГОДНЯ - UF_CRM_1673590433
    public static function shipTodayTitle($ids)
    {
        $result = [];
        $idDeals = DealTools::getStatusByField($ids, 'UF_CRM_1673590433', -1, null, null, false);

        foreach ($idDeals as $idDeal => $color) {
            $result[$idDeal]['color'] = $color;
            $result[$idDeal]['info'] = 'Если перевели на текущую стадию и <b>прошел 1 день с момента перехода, то красится в красный цвет</b>';
        }

        return $result;
    }
    //стадия "отгружаем сегодня" - C17:UC_C7BXXO
    //Переход в ОТГРУЖАЕМ СЕГОДНЯ - UF_CRM_1673590433
    public static function shipTodayBody($ids)
    {
        $result = [];
        $idDeals = DealTools::getStatusByField($ids, 'UF_CRM_1673590433', -6, null, null, false);

        foreach ($idDeals as $idDeal => $color) {
            $result[$idDeal]['color'] = $color;
            $result[$idDeal]['info'] = 'Если перевели на текущую стадию и <b>прошло 6 дней с момента перехода, то красится в красный цвет</b>';
        }

        return $result;
    }

    //стадия "готово к отгрузке" - C17:UC_DKQCKB
    //переход в товар готов к отгрузке - UF_CRM_1637209806
    //Условие поставки - UF_CRM_1600864467 (EXW - 1777)
    public static function readyForShipmentEXW($ids)
    {
        $result = [];
        $idDeals = DealTools::getStatusByFieldValueTime($ids, 'UF_CRM_1600864467', 'UF_CRM_1637209806', -6, -4, 1777);

        foreach ($idDeals as $idDeal => $color) {
            $result[$idDeal]['color'] = $color;
            $result[$idDeal]['info'] = 'Если перевели на текущую стадию: <ul><li>в поле <i>Условие поставки</i> выставлено <b>EXW</b> и прошло <b>6 дней с момента перехода красим в красный цвет</b>;</li><br><li>в поле <i>Условие поставки</i> выставлено <b>EXW</b> и прошло <b>4 дня с момента перехода красим в желтый цвет</b>.</li></ul>';
        }

        return $result;
    }
    //стадия "готово к отгрузке" - C17:UC_DKQCKB
    //переход в товар готов к отгрузке - UF_CRM_1637209806
    //Условие поставки - UF_CRM_1600864467 (DAP - 1778)
    public static function readyForShipmentDAP($ids)
    {
        $result = [];
        $idDeals = DealTools::getStatusByFieldValueTime($ids, 'UF_CRM_1600864467', 'UF_CRM_1637209806', -2, null, 1778);

        foreach ($idDeals as $idDeal => $color) {
            $result[$idDeal]['color'] = $color;
            $result[$idDeal]['info'] = 'Если перевели на текущую стадию: <ul><li>в поле <i>Условие поставки</i> выставлено <b>DAP</b> и прошло <b>2 дня с момента перехода красим в желтый цвет</b>;</li></ul>';
        }

        return $result;
    }
    //стадия "готовность от поставщика" - C17:UC_14SWMS
    //Дата отгрузки от поставщика 1(ставит МПП) - UF_CRM_1629191830
    //Фактическая дата отгрузки от поставщика - UF_CRM_1626686380
    public static function readinessFromTheSupplierTwoDate($ids)
    {
        $result = [];
        $idDeals = DealTools::getStatusByFieldToDate($ids, 'UF_CRM_1629191830', 'UF_CRM_1626686380');

        foreach ($idDeals as $idDeal => $color) {
            $result[$idDeal]['color'] = $color;
            $result[$idDeal]['info'] = 'Если перевели на текущую стадию и в поле <b>Дата отгрузки от поставщика 1(ставит МПП)</b> дата <b>МЕНЬШЕ</b> даты в поле <b>Фактическая дата отгрузки от поставщика</b>, то красим в синий цвет.';
        }

        return $result;
    }
    //стадия "проверка кода ТНВЭД" - C17:UC_S5SV7H
    //Дата отгрузки от поставщика 1(ставит МПП) - UF_CRM_1629191830
    public static function checkNTVED($ids)
    {
        $result = [];
        $idDeals = DealTools::getStatusByField($ids, 'UF_CRM_1629191830', 0, null, null, false);

        foreach ($idDeals as $idDeal => $color) {
            $result[$idDeal]['color'] = $color;
            $result[$idDeal]['info'] = 'Если перевели на текущую стадию и в поле <b>Дата отгрузки от поставщика 1(ставит МПП)</b> дата сегодня и позже, то <b>красим в красный цвет</b>.';
        }

        return $result;
    }
    //стадия "Контроль возврата ДС" - C2:UC_KZHZZY
    //Дата возврата денежных средств - UF_CRM_1706093107
    public static function  returnСontrolDS($ids)
    {
        $result = [];
        $idDeals = DealTools::getStatusByField($ids, 'UF_CRM_1706093107', 2, null, null, false);

        foreach ($idDeals as $idDeal => $color) {
            $result[$idDeal]['color'] = $color;
            $result[$idDeal]['info'] = 'Если перевели на текущую стадию и в поле <b>Дата возврата денежных средств</b> дата <b>больше от текущей, то красим красный цвет</b>.';
        }

        return $result;
    }

    // -- подкраска стадий весящие более 30 минут -- //
    //стадия "Реализация РФ" - C17:UC_NZTLLE
    //поле "переход в передано на реализацию РФ" - UF_CRM_1637682785
    public static function  checkTimeOnStageImplementationRF($ids)
    {
        $result = [];
        foreach ($ids as $id) {
            $result[$id]['color'] = self::checkTimeOnStage($id,'UF_CRM_1637682785',  30)['status'];
            $result[$id]['info'] = 'Если перевели на текущую стадию и прошло больше 30 минут то красим в красный цвет.';
        }
        return $result;
    }
    //стадия "Взято в работу" - C17:UC_BBPEXS
    //поле "Переход взято в работу" - UF_CRM_1702435112
    public static function  checkTimeOnStageTakeWork($ids)
    {
        $result = [];
        foreach ($ids as $id) {
            $result[$id]['color'] = self::checkTimeOnStage($id,'UF_CRM_1702435112',  30)['status'];
            $result[$id]['info'] = 'Если перевели на текущую стадию и прошло больше 30 минут то красим в красный цвет.';
        }
        return $result;
    }
    //стадия "ошибка" - C17:UC_JASZW0
    //поле "Переход в ОШИБКА (вор. ЛОГИСТИКА)" - UF_CRM_1710926726
    public static function  checkTimeOnStageError($ids)
    {
        $result = [];
        foreach ($ids as $id) {
            $result[$id]['color'] = self::checkTimeOnStage($id,'UF_CRM_1710926726',  30)['status'];
            $result[$id]['info'] = 'Если перевели на текущую стадию и прошло больше 30 минут то красим в красный цвет.';
        }
        return $result;
    }
    //стадия "исправлено" - C17:UC_XWCDGP
    //поле "переход исправлено (вор. Логистика)" - UF_CRM_1713182494
    public static function  checkTimeOnStageFixed($ids)
    {
        $result = [];
        foreach ($ids as $id) {
            $result[$id]['color'] = self::checkTimeOnStage($id,'UF_CRM_1713182494',  30)['status'];
            $result[$id]['info'] = 'Если перевели на текущую стадию и прошло больше 30 минут то красим в красный цвет.';
        }
        return $result;
    }
    //стадия "в работе, подготовка УПД" - C17:UC_CHZP3S
    //поле "Переход в работе подготовка УПД" - UF_CRM_1709104311
    public static function  checkTimeOnStagePreparationUPD($ids)
    {
        $result = [];
        foreach ($ids as $id) {
            $result[$id]['color'] = self::checkTimeOnStage($id,'UF_CRM_1709104311',  30)['status'];
            $result[$id]['info'] = 'Если перевели на текущую стадию и прошло больше 30 минут то красим в красный цвет.';
        }
        return $result;
    }
    //стадия "ждем данные для СНТ" - C17:UC_1XG6DD
    //поле "Переход в ждем данные для СНТ" - UF_CRM_1709104385
    public static function  checkTimeOnStageWaitingSNT($ids)
    {
        $result = [];
        foreach ($ids as $id) {
            $result[$id]['color'] = self::checkTimeOnStage($id,'UF_CRM_1709104385',  30)['status'];
            $result[$id]['info'] = 'Если перевели на текущую стадию и прошло больше 30 минут то красим в красный цвет.';
        }
        return $result;
    }
    //стадия "Реализация КЗ" - C17:UC_DTOYSW
    //поле "Переход в передано на реализация КЗ" - UF_CRM_1678934976
    public static function  checkTimeOnStageImplementationKZ($ids)
    {
        $result = [];
        foreach ($ids as $id) {
            $result[$id]['color'] = self::checkTimeOnStage($id,'UF_CRM_1678934976',  30)['status'];
            $result[$id]['info'] = 'Если перевели на текущую стадию и прошло больше 30 минут то красим в красный цвет.';
        }
        return $result;
    }
    //стадия "(lim10)РТУ готово, не отгружено" - C17:UC_B7G4ZH
    //поле "переход в РТУ готово, не отгружено" - UF_CRM_1637682895
    public static function  checkTimeOnStageTheMouthReadyNotShipped($ids)
    {
        $result = [];
        foreach ($ids as $id) {
            $result[$id]['color'] = self::checkTimeOnStage($id,'UF_CRM_1637682895',  30)['status'];
            $result[$id]['info'] = 'Если перевели на текущую стадию и прошло больше 30 минут то красим в красный цвет.';
        }
        return $result;
    }
    // -- подкраска стадий весящие более 30 минут - конец - -- //

    //// Воронка логистика -end- ////
    //// подсчет дней -start- ////
    public static function checkDayTransferToStageLim13($ids)
    {
        $result = [];
        foreach ($ids as $id) {
            $timeInfo = self::returnTimeSmartProcess($id, 166,'UF_CRM_8_1698805588');
            $dateTimeText = $timeInfo['days'] . ":" . $timeInfo['hours'] . ":" . $timeInfo['minutes'];
            $dateTimeInfo =  'C момента перевода на эту стадию прошло: ' . $timeInfo['days']  . ' '. self::declination($timeInfo['days'], ['дней', 'день', 'дня']) . ' ' . $timeInfo['hours'] . ' ' . self::declination($timeInfo['hours'], ['часов', 'час', 'часа']). ' ' . $timeInfo['minutes'] . ' ' . self::declination($timeInfo['minutes'], ['минут', 'минута', 'минуты']);
            $result[$id]['dateTime'] = $dateTimeText;
            $result[$id]['info'] = $dateTimeInfo;
        }
        return $result;
    }
    public static function checkDayTransferToStage3Milliards($ids)
    {
        $result = [];
        foreach ($ids as $id) {
            $timeInfo = self::returnTimeSmartProcess($id, 166,'UF_CRM_8_1722411842');
            $dateTimeText = $timeInfo['days'] . ":" . $timeInfo['hours'] . ":" . $timeInfo['minutes'];
            $dateTimeInfo =  'C момента перевода на эту стадию прошло: ' . $timeInfo['days']  . ' '. self::declination($timeInfo['days'], ['дней', 'день', 'дня']) . ' ' . $timeInfo['hours'] . ' ' . self::declination($timeInfo['hours'], ['часов', 'час', 'часа']). ' ' . $timeInfo['minutes'] . ' ' . self::declination($timeInfo['minutes'], ['минут', 'минута', 'минуты']);
            $result[$id]['dateTime'] = $dateTimeText;
            $result[$id]['info'] = $dateTimeInfo;
        }
        return $result;
    }
    public static function checkDayCreateDeal($ids)
    {
        $result = [];
        foreach ($ids as $id) {
            $timeInfo = self::returnTimeSmartProcess($id, 166, 'CREATED_TIME');
            $dateTimeText = $timeInfo['days'] . ":" . $timeInfo['hours'] . ":" . $timeInfo['minutes'];
            $dateTimeInfo =  'C момента создания текущего элемента прошло: ' . $timeInfo['days']  . ' '. self::declination($timeInfo['days'], ['дней', 'день', 'дня']) . ' ' . $timeInfo['hours'] . ' ' . self::declination($timeInfo['hours'], ['часов', 'час', 'часа']). ' ' . $timeInfo['minutes'] . ' ' . self::declination($timeInfo['minutes'], ['минут', 'минута', 'минуты']);
            $result[$id]['dateTime'] = $dateTimeText;
            $result[$id]['info'] = $dateTimeInfo;
        }
        return $result;
    }
    //// подсчет дней -end- ////
    protected static function declination(int $num, array $declinations){
        return (intdiv($num % 100, 10) === 1)
            ? $declinations[0]
            : $declinations[[0, 1, 2, 2, 2, 0, 0, 0, 0, 0][$num % 10]];
    }

    protected static function returnTimeSmartProcess($id,$idSmartProcess, $field)
    {
        $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($idSmartProcess);
        $sourceItemId = $id;//Идентификатор
        $item = $factory->getItem($sourceItemId);
        $itemSmartProcess = $item->getData();

        if ($itemSmartProcess[$field]) {
            $diff = (new DateTime())->getDiff(new DateTime($itemSmartProcess[$field]));

            $diffD = $diff->format('%d');
            $diffM = $diff->format('%i');
            $diffH = $diff->format('%h');
        }
        return ['minutes' => $diffM, 'hours' => $diffH, 'days' => $diffD];
    }
    protected static function returnTimeDeal($id, $field)
    {
        $entityResult = \CCrmDeal::GetListEx(
            ['SOURCE_ID' => 'DESC'],
            [
                'ID' => $id,
                'CHECK_PERMISSIONS' => 'N'
            ],
            false,
            false,
            [
                'ID',
                'TITLE',
                'STAGE_ID',
                $field,
            ]
        );

        while ($entity = $entityResult->fetch()) {
            $dealInfo = $entity;
        }

        if ($dealInfo[$field]) {
            $diff = (new DateTime())->getDiff(new DateTime($dealInfo[$field]));

            $diffD = $diff->format('%d');
            $diffM = $diff->format('%i');
            $diffH = $diff->format('%h');
        }
        return ['minutes' => $diffM, 'hours' => $diffH, 'days' => $diffD];
    }
    protected static function checkTimeOnStage($id, $field, $minutsRed = null, $minutsYellow = null)
    {
        $status = false;

        $entityResult = \CCrmDeal::GetListEx(
            ['SOURCE_ID' => 'DESC'],
            [
                'ID' => $id,
                'CHECK_PERMISSIONS' => 'N'
            ],
            false,
            false,
            [
                'ID',
                'TITLE',
                'STAGE_ID',
                $field,
            ]
        );

        while ($entity = $entityResult->fetch()) {
            $dealInfo = $entity;
        }

        $lastChangeStage = $dealInfo[$field];

        if ($lastChangeStage) {
            $diff = (new DateTime())->getDiff(new DateTime($lastChangeStage));

            $diffD = $diff->format('%d');
            $diffM = $diff->format('%i');
            $diffH = $diff->format('%h');
            $diffM = (($diffD * 24) + ($diffH * 60) + $diffM);

            if ($minutsYellow !== null && $diffM >= $minutsYellow) {
                $status = 'yellow';
            }
            if ($minutsRed !== null && $diffM >= $minutsRed) {
                $status = 'red';
            }


        }
        return ['status' => $status, 'diff' => $diffM];
    }
    public static function checkLogisticShipmentExw($ids)
    {
        $result = [];
        $idDeals = DealTools::getStatusByField($ids, DealTools::READY_SHIPMENT_EXW_FIELD, -5, -3);

        foreach ($idDeals as $idDeal => $color) {
            $result[$idDeal]['color'] = $color;
            $result[$idDeal]['info'] = 'тут пока нет описания';
        }

        return $result;
    }

    public static function checkLogisticShipmentExwAndDap($ids)
    {
        $result = [];
        $idDeals = DealTools::getStatusByField($ids, DealTools::READY_SHIPMENT_EXW_DAP_FIELD, -7, null);

        foreach ($idDeals as $idDeal => $color) {
            $result[$idDeal]['color'] = $color;
            $result[$idDeal]['info'] = 'тут пока нет описания';
        }

        return $result;
    }

    public static function checkLogisticProductStock($ids)
    {
        $result = [];
        $idDeals = DealTools::getStatusByField($ids, DealTools::MOVE_TO_NSK_STOCK, -5, null);

        foreach ($idDeals as $idDeal => $color) {
            $result[$idDeal]['color'] = $color;
            $result[$idDeal]['info'] = 'тут пока нет описания';
        }

        return $result;
    }

    public static function checkPrepareTn($ids)
    {
        $result = [];
        $idDeals = DealTools::getStatusByField($ids, DealTools::MOVE_TO_PREPARE_TN_FIELD, -7);

        foreach ($idDeals as $idDeal => $color) {
            $result[$idDeal]['color'] = $color;
            $result[$idDeal]['info'] = 'тут пока нет описания';
        }

        return $result;
    }

    public static function checkTalonStatus($ids)
    {
        $result = [];
        $idDeals = DealTools::getStatusByField($ids, DealTools::MOVE_TO_TALON_FIELD, -72, -24, null, true);

        foreach ($idDeals as $idDeal => $color) {
            $result[$idDeal]['color'] = $color;
            $result[$idDeal]['info'] = 'тут пока нет описания';
        }

        return $result;
    }

    public static function checkWithdrawList($ids): array
    {
        $result = [];
        $idDeals = DealTools::getStatusByFieldValue($ids, DealTools::WITHDRAW_LIST_FIELD);

        foreach ($idDeals as $idDeal => $color) {
            $result[$idDeal]['color'] = $color;
            $result[$idDeal]['info'] = 'тут пока нет описания';
        }

        return $result;
    }

    public static function checkRemoval($ids): array
    {
        $result = [];
        $idDeals = DealTools::getStatusByFieldValueYesNo($ids, 'UF_CRM_1694092634', 'blue', 3060);

        foreach ($idDeals as $idDeal => $color) {
            $result[$idDeal]['color'] = $color;
            $result[$idDeal]['info'] = 'тут пока нет описания';
        }

        return $result;
    }

    public static function checkWaitCorrectionFromProvider($ids)
    {
        $result = [];
        $idDeals = DealTools::getStatusByField($ids, DealTools::WAIT_CORRECTION_FROM_PROVIDER_FIELD, -3);

        foreach ($idDeals as $idDeal => $color) {
            $result[$idDeal]['color'] = $color;
            $result[$idDeal]['info'] = 'тут пока нет описания';
        }

        return $result;
    }

    public static function checkWaitCorrectionFromContragent($ids)
    {
        $result = [];
        $idDeals = DealTools::getStatusByField($ids, DealTools::WAIT_CORRECTION_FROM_CONTRAGENT_FIELD, -3);

        foreach ($idDeals as $idDeal => $color) {
            $result[$idDeal]['color'] = $color;
            $result[$idDeal]['info'] = 'тут пока нет описания';
        }

        return $result;
    }

    public static function checkTransferNsk($ids)
    {
        $result = [];
        $idDeals = DealTools::getStatusByField($ids, DealTools::TRANSFER_TO_NSK_FIELD, -5);

        foreach ($idDeals as $idDeal => $color) {
            $result[$idDeal]['color'] = $color;
            $result[$idDeal]['info'] = 'тут пока нет описания';
        }

        return $result;
    }

    public static function checkLogisticShipment($ids)
    {
        $result = [];
        $idDeals = DealTools::getStatusByField($ids, DealTools::READY_SHIPMENT_FIELD, -1);

        foreach ($idDeals as $idDeal => $color) {
            $result[$idDeal]['color'] = $color;
            $result[$idDeal]['info'] = 'тут пока нет описания';
        }

        return $result;
    }

    public static function checkDelivery($params)
    {
        $status = false;
        $error = false;
        $id = $params['id'];
        if (!empty($id)) {
            $filter = [
                'ID' => $id,
            ];
            try {
                $deal = DealTable::getList([
                    'filter' => $filter,
                    'select' => ['UF_CRM_1626069863'],
                ])->fetch();
            } catch (\Throwable $e) {
                $deal = false;
                $error = $e->getMessage();
            }
            if ($deal && $deal['UF_CRM_1626069863']) {
                $diff = (new DateTime())->getDiff(new DateTime($deal['UF_CRM_1626069863']));
                $diff = ($diff->invert ? -1 : 1) * $diff->format('%a');
                if ($diff < 2) {
                    $status = 'yellow';
                }
                if ($diff < 0) {
                    $status = 'red';
                }
            }
        }
        return ['status' => $status, 'error' => $error, 'params' => $params];
    }

    public static function getLostDeals($params)
    {
        $result = [];
        $ids = $params['ids'];
        $period = $params['period'];
        $date = (new DateTime())->add("-$period days");
        if (!empty($ids)) {
            $filter = [
                'ID' => $ids,
                '<=DATE_MODIFY' => $date,
            ];
            $list = DealTable::getList([
                'filter' => $filter,
                'select' => ['ID'],
            ])->fetchAll();
            $result = array_column($list, 'ID');
        }
        return ['dealList' => $result, 'params' => $params, 'filter' => $filter, 'date' => $date->format('d.m.Y H:i:s')];
    }

    public static function repairDeal(&$arFields)
    {
        if (isset($arFields["ID"]) && $arFields["ID"] > 0) {
            $obDeal = \CCrmDeal::GetByID($arFields["ID"], false);
            $contID = $obDeal["CONTACT_ID"];
        } else {
            $obDeal = $arFields;
            $contID = $obDeal["CONTACT_BINDINGS"][0]["CONTACT_ID"];
        }
        if ($contID) {
            $obContact = \CCrmContact::GetByID($contID, false);
        }
        if ($obDeal["COMPANY_ID"] > 0) {
            $obCompany = \CCrmCompany::GetByID($obDeal["CONTACT_ID"], false);
        }

        if (!$obDeal["LEAD_ID"] && !$arFields["LEAD_ID"]) {
            $arFields["LEAD_ID"] = $obContact["LEAD_ID"] ? $obContact["LEAD_ID"] : $obCompany["LEAD_ID"];
        }
        $obLead = \CCrmLead::GetList(false, ["ID" => $obContact["LEAD_ID"] ? $obContact["LEAD_ID"] : $obCompany["LEAD_ID"]]);
        $obLead = $obLead->GetNext();
        if (!$obDeal[self::$dealUfRoi] && !$arFields[self::$dealUfRoi]) {
            $arFields[self::$dealUfRoi] = $obLead[self::$leadUfRoi];
        }
        return true;
    }

    public static function checkContactLeadOrCompany(&$arFields)
    {
        if (!isset($_REQUEST["external_context"]) && !(isset($arFields["LEAD_ID"]) && intval($arFields["LEAD_ID"]) > 0)
            && !(isset($arFields["COMPANY_ID"]) && intval($arFields["COMPANY_ID"]) > 0)
        ) {
            $arFields['RESULT_MESSAGE'] = "Контакты могут создаваться только из лидов или компаний!!!";
            return false;
        }
        return true;
    }

    public static function checkCompanyLead(&$arFields)
    {
        if (!(isset($arFields["LEAD_ID"]) && intval($arFields["LEAD_ID"]) > 0)) {
            $arFields['RESULT_MESSAGE'] = "Компании могут создаваться только из лидов!!!";
            return false;
        }
        return true;
    }

    /**
     * @param $arFields
     * @return bool
     */
    public static function checkLeadPhone(&$arFields)
    {
        $result = true;
        if (isset($arFields["FM"]["PHONE"]) && is_array(($arFields["FM"]["PHONE"]))) {
            $str = "";
            foreach ($arFields["FM"]["PHONE"] as $tel) {
                $entity = self::getEntityByPhone($tel["VALUE"]);
                if ($entity) {
                    \CIstTools::debug($arFields, "dublicate_lead");
                    $str .= "Найдено совпадение по телефону:<br>";
                }
                foreach ($entity as $type => $ents) {
                    foreach ($ents as $ent) {
                        if (!$ent["TITLE"]) {
                            $ent["TITLE"] = $ent["FORMATTED_NAME"];
                        }
                        $str .= "<a href='" . $ent["SHOW_URL"] . "'>" . $ent["TITLE"] . "</a><br>";
                    }
                }
            }
        }
        if (strlen($str) > 0) {
            $arFields['RESULT_MESSAGE'] = "Ошибка создания лида";
            echo $str;
            $result = false;
        }
        return $result;
    }

    /**
     * @param $phone
     * @return array|bool
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentTypeException
     */
    protected static function getEntityByPhone($phone)
    {
        $result = false;
        $phone = preg_replace('/[^\d]/', '', $phone);
        $len = strlen($phone);
        if ($len >= 5) {
            for ($i = 5; $i <= $len; $i++) {
                $phonePart = substr($phone, -$i);
                $ent = \CCrmSipHelper::findByPhoneNumber($phonePart, ['USER_ID' => 1]);
                if (!empty($ent)) {
                    break;
                }
            }
            if ($len == 10 && empty($ent)) {
                $phonePart = "8" . $phone;
                $ent = \CCrmSipHelper::findByPhoneNumber($phonePart, ['USER_ID' => 1]);
            }
            if ($len == 10 && empty($ent)) {
                $phonePart = "7" . $phone;
                $ent = \CCrmSipHelper::findByPhoneNumber($phonePart, ['USER_ID' => 1]);
            }
            if (!empty($ent)) {
                $result = $ent;
            }
        }
        return $result;
    }

    public static function checkRoistat(&$arFields)
    {
        if (!$arFields[self::$leadUfRoi] && $arFields["SOURCE_ID"]) {
            $obStat = \CCrmStatus::GetList(false, ["ENTITY_ID" => "SOURCE", "STATUS_ID" => $arFields["SOURCE_ID"]]);
            if ($stat = $obStat->GetNext()) {
                $arFields[self::$leadUfRoi] = $stat["NAME"];
            }
        }
        return true;
    }

    public static function repairColorDeal()
    {
        global $APPLICATION;
        $dir = $APPLICATION->GetCurDir();
        $pieces = explode("/", $dir);
        $dealId = $pieces[4];
        $pos = strpos($dir, "/deal/show/");
        $arDeal = \CCrmDeal::GetByID($dealId, true);
        $checkParam = \COption::GetOptionString('istline.checkrules', "repair_color_deal_from_stage");
        $stageParam = \COption::GetOptionString('istline.checkrules', "select_stage_deal");

        if ($checkParam && $pos !== false && $arDeal["STAGE_ID"] == $stageParam) {
            global $APPLICATION;
            $APPLICATION->AddHeadScript("/local/modules/istline.checkrules/js/script.js");
        }
    }

    public static function emptySum(&$arFields)
    {
        if (isset($arFields["STAGE_ID"]) || isset($arFields["OPPORTUNITY"])) {
            $stages = unserialize(\COption::GetOptionString(self::$moduleID, "check_sum_by_stage"));
            $obStatus = \CCrmStatus::GetStatus("DEAL_STAGE");
            $stageParam = unserialize(\COption::GetOptionString('istline.checkrules', "select_stage_deal"));
            $stageParam = $stageParam[0];
            $sortParam = $obStatus[$stageParam]["SORT"];
            $obDeal = false;
            if (isset($arFields["STAGE_ID"])) {
                $sortDealStatus = $obStatus[$arFields["STAGE_ID"]]["SORT"];
            } else {
                $obDeal = \CCrmDeal::GetByID($arFields["ID"]);
                $sortDealStatus = $obStatus[$obDeal["STAGE_ID"]]["SORT"];
            }
            if (isset($arFields["OPPORTUNITY"])) {
                $summ = intval($arFields["OPPORTUNITY"]);
            } else {
                if (!$obDeal) {
                    $obDeal = \CCrmDeal::GetByID($arFields["ID"]);
                }
                $summ = intval($obDeal["OPPORTUNITY"]);
            }
            $checkSumByStage = ((!empty($stages)) && (in_array($arFields["STAGE_ID"], $stages)));
            if ($sortDealStatus >= $sortParam && $sortDealStatus < 130 && $summ <= 0) {
                $arFields['RESULT_MESSAGE'] = "Необходимо заполнить поле \"Сумма\"";
                static::CrmDealListEndResonse([
                    'ERROR' => $arFields['RESULT_MESSAGE'],
                    'FIELDS' => [
                        "STAGES" => $stages,
                        "F" => $arFields,
                        "SUM" => $summ,
                        "RES" => $checkSumByStage,
                        "SDS" => $sortDealStatus,
                        "OBSTAT" => $obStatus,
                        "SORTPAR" => $sortParam,
                        "STAGEPAR" => $stageParam,
                    ],
                ]);
                return false;
            }
            if ($checkSumByStage && ($summ <= 0)) {
                $arFields['RESULT_MESSAGE'] = "Необходимо заполнить поле \"Сумма\"";
                static::CrmDealListEndResonse([
                    'ERROR' => $arFields['RESULT_MESSAGE'],
                    'FIELDS1' => [
                        "STAGES" => $stages,
                        "F" => $arFields,
                        "SUM" => $summ,
                        "RES" => $checkSumByStage,
                    ],
                ]);
                return false;
            }

        }
        return true;
    }

    public static function getStatus($params)
    {
        $status = [];
        $error = [];
        $data = [];
        foreach ($params as $item) {
            foreach ($item['method'] as $method) {
                $data[$method] = $data[$method] ?: [];
                $data[$method][] = $item['id'];
            }
        }
        foreach ($data as $method => $ids) {
            if (is_callable([self::class, $method])) {
                try {
                    $status += call_user_func([self::class, $method], $ids);
                } catch (\Throwable $e) {
                    $error[] = $e->getMessage();
                }
            }
        }
        return ['status' => $status ?? false, 'error' => $error ?? false, 'params' => $params];
    }

    protected static function CrmDealListEndResonse($result)
    {
        $GLOBALS['APPLICATION']->RestartBuffer();
        Header('Content-Type: application/x-javascript; charset=' . LANG_CHARSET);
        if (!empty($result)) {
            echo \CUtil::PhpToJSObject($result);
        }
        require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
        die();
    }

    public static function checkWorkTo($params)
    {
        $status = false;
        $error = false;
        $id = $params['id'];
        if (!empty($id)) {
            $filter = [
                'ID' => $id,
            ];
            try {
                $deal = DealTable::getList([
                    'filter' => $filter,
                    'select' => [
                        'UF_CRM_1592278129',
                    ],
                ])->fetch();
            } catch (\Throwable $e) {
                $deal = false;
                $error = $e->getMessage();
            }
            if ($deal && !empty($deal['UF_CRM_1592278129'])) {
                $deal['UF_CRM_1592278129'] = is_array($deal['UF_CRM_1592278129']) ? reset($deal['UF_CRM_1592278129']) : $deal['UF_CRM_1592278129'];
                $diff = (new DateTime())->getDiff(new DateTime($deal['UF_CRM_1592278129']));
                $diff = ($diff->invert ? -1 : 1) * $diff->format('%a');
                if ($diff < 3) {
                    $status = 'red';
                }
                switch ($diff) {
                    case 4:
                        $status = 'green';
                        break;
                    case 3:
                        $status = 'yellow';
                        break;
                }
            }
        }
        return ['status' => $status, 'error' => $error, 'params' => $params, 'diff' => $diff, 'deal' => $deal['DATE_CREATE']];
    }

    public static function withSuppInvoice($params)
    {
        $status = false;
        $error = false;
        $id = $params['id'];
        if (!empty($id)) {
            $filter = [
                'ID' => $id,
                'UF_CRM_1478514709' => 896,
            ];
            try {
                $deal = DealTable::getList([
                    'filter' => $filter,
                    'select' => [
                        'DATE_CREATE',
                    ],
                ])->fetch();
            } catch (\Throwable $e) {
                $deal = false;
                $error = $e->getMessage();
            }
            if ($deal && !empty($deal['DATE_CREATE'])) {
                $diff = (new DateTime())->getTimestamp() - $deal['DATE_CREATE']->getTimestamp();
                $hours = $diff / 3600;
                if ($hours > 3) {
                    $status = 'green';
                }
                $deal['DATE_CREATE'] = $deal['DATE_CREATE']->format('d.m.Y H:i:s');
            }
        }
        return ['status' => $status, 'error' => $error, 'params' => $params, 'diff' => $diff, 'hours' => $hours, 'deal' => $deal['DATE_CREATE']];
    }

    public static function check30Proc($params)
    {
        $status = false;
        $error = false;
        $id = $params['id'];
        if (!empty($id)) {
            $filter = [
                'ID' => $id,
            ];
            try {
                $deal = DealTable::getList([
                    'filter' => $filter,
                    'select' => [
                        'UF_CRM_1574838682',
                        'UF_CRM_1460029339',
                    ],
                ])->fetch();
            } catch (\Throwable $e) {
                $deal = false;
                $error = $e->getMessage();
            }
            if ($deal && !empty($deal['UF_CRM_1574838682'])) {
                $deal['UF_CRM_1574838682'] = is_array($deal['UF_CRM_1574838682']) ? reset($deal['UF_CRM_1574838682']) : $deal['UF_CRM_1574838682'];
                $diff = (new DateTime())->getDiff(new DateTime($deal['UF_CRM_1574838682']));
                $diff = ($diff->invert ? -1 : 1) * $diff->format('%a');
                if ($diff < -2) {
                    $status = $deal['UF_CRM_1460029339'] == 2197 ? 'blue' : 'red';
                }
            }
        }
        return ['status' => $status, 'error' => $error, 'params' => $params, 'diff' => $diff, 'deal' => $deal['UF_CRM_1574838682']];
    }

    public static function checkHarvest($params)
    {
        $status = false;
        $error = false;
        $id = $params['id'];
        try {
            $dateHarvest = DealTools::getDateHarvest($id);
        } catch (\Throwable $e) {
            $dateHarvest = false;
            $error = $e->getMessage();
        }
        if ($dateHarvest) {
            $diff = (new DateTime())->getDiff(new DateTime($dateHarvest));
            $diff = ($diff->invert ? -1 : 1) * $diff->format('%a');
            if ($diff < 0) {
                $status = 'red';
            } elseif ($diff < 2) {
                $status = 'yellow';
            }

        }
        return ['status' => $status, 'error' => $error, 'params' => $params, 'diff' => $diff];
    }

    public static function checkTalon($params)
    {
        $result = [];
        $idDeals = self::checkLastChangeStageDate($params['id'], 7);

        foreach ($idDeals as $idDeal => $color) {
            $result[$idDeal]['color'] = $color;
            $result[$idDeal]['info'] = 'тут пока нет описания';
        }

        return $result;
    }

    protected static function checkLastChangeStageDate($id, $dayCount = 7)
    {
        $status = false;
        $error = false;
        try {
            $lastChangeStage = DealTools::getLastChangeStageDate($id);
        } catch (\Throwable $e) {
            $lastChangeStage = false;
            $error = $e->getMessage();
        }
        if ($lastChangeStage) {
            $diff = (new DateTime())->getDiff(new DateTime($lastChangeStage));
            $diff = ($diff->invert ? -1 : 1) * $diff->format('%a');
            if ($diff < -$dayCount) {
                $status = 'red';
            }
            $lastChangeStage = $lastChangeStage->format('d.m.Y');
        }
        return ['status' => $status, 'error' => $error, 'diff' => $diff, 'lastChangeStage' => $lastChangeStage];
    }

    protected static function checkLastChangeStageDateMinutes($id, $minuts = 40)
    {
        $status = false;


        $entityResult = \CCrmDeal::GetListEx(
            ['SOURCE_ID' => 'DESC'],
            [
                'ID' => $id,
                'CHECK_PERMISSIONS' => 'N'
            ],
            false,
            false,
            [
                'ID',
                'TITLE',
                'STAGE_ID',
                'UF_CRM_1702620235',
            ]
        );

        while ($entity = $entityResult->fetch()) {
            $dealInfo = $entity;
        }

        $lastChangeStage = $dealInfo['UF_CRM_1702620235'];

        if ($lastChangeStage) {
            $diff = (new DateTime())->getDiff(new DateTime($lastChangeStage));

            $diffD = $diff->format('%d');
            $diffM = $diff->format('%i');
            $diffH = $diff->format('%h');
            $diffM = (($diffD * 24) + ($diffH * 60) + $diffM);

            if ($diffM > $minuts) {
                $status = 'red';
            }
        }
        return ['status' => $status, 'diff' => $diffM];
    }

    public static function minPauseOnStage($ids)
    {
        $result = [];
        foreach ($ids as $id) {
            $result[$id]['color'] = self::checkLastChangeStageDateMinutes($id, 40)['status'];
            $result[$id]['info'] = 'тут пока нет описания';
        }
        return $result;
    }

    public static function checkRtuReady($ids)
    {
        $result = [];
        foreach ($ids as $id) {
            $result[$id]['color'] = self::checkLastChangeStageDate($id, 2)['status'];
            $result[$id]['info'] = 'тут пока нет описания';
        }
        return $result;
    }

    public static function checkReadyOrderDap($ids)
    {
        $result = [];
        foreach ($ids as $id) {
            $result[$id]['color'] = self::checkLastChangeStageDate($id, 2)['status'];
            $result[$id]['info'] = 'тут пока нет описания';
        }
        return $result;
    }

    public static function checkSntData($ids)
    {
        $result = [];
        foreach ($ids as $id) {
            $result[$id]['color'] = self::checkLastChangeStageDate($id, 1)['status'];
            $result[$id]['info'] = 'тут пока нет описания';
        }
        return $result;
    }

    public static function lastUpdateDealStageOne($ids)
    {
        $result = [];
        foreach ($ids as $id) {
            $result[$id]['color'] = self::checkLastChangeStageDate($id, 1)['status'];
            $result[$id]['info'] = 'тут пока нет описания';
        }
        return $result;
    }

    public static function lastUpdateDealStage($ids)
    {
        $result = [];
        foreach ($ids as $id) {
            $result[$id]['color'] = self::checkLastChangeStageDate($id, 2)['status'];
            $result[$id]['info'] = 'тут пока нет описания';
        }
        return $result;
    }

    public static function urgentImplementation($ids)
    {
        $result = [];
        foreach ($ids as $id) {
            $result[$id]['color'] = DealTools::getUrgentImplementation($id);
            $result[$id]['info'] = 'Если реализация срочная, красится в синий цвет.';
        }
        return $result;
    }

    public static function checkShipment($params)
    {
        $status = false;
        $error = false;
        $id = $params['id'];
        try {
            $dateHarvest = DealTools::getDateHarvest($id);
        } catch (\Throwable $e) {
            $dateHarvest = false;
            $error = $e->getMessage();
        }
        if ($dateHarvest) {
            $currDate = new DateTime();
            $harvest = new DateTime($dateHarvest);
            $diff = $currDate->getDiff($harvest);
            $diff = ($diff->invert ? -1 : 1) * $diff->format('%a');
            if ($diff < 0 || $currDate->format('d.m.Y') == $harvest->format('d.m.Y')) {
                $status = 'red';
            }
        }
        return ['status' => $status, 'error' => $error, 'params' => $params, 'diff' => $diff];
    }

    public static function checkWaitMoneyFromContragent($ids)
    {
        $result = [];
        $idDeals = DealTools::getStatusByField($ids, DealTools::WAIT_MONEY_FROM_CONTRAGENT_FIELD, -3);

        foreach ($idDeals as $idDeal => $color) {
            $result[$idDeal]['color'] = $color;
            $result[$idDeal]['info'] = 'тут пока нет описания';
        }

        return $result;
    }

    public static function checkWaitShipmentDate($ids)
    {
        $result = [];
        $idDeals = DealTools::getStatusByField($ids, DealTools::SHIPMENT_DATE_FROM_PROVIDER_FIELD, 0, 3);

        foreach ($idDeals as $idDeal => $color) {
            $result[$idDeal]['color'] = $color;
            $result[$idDeal]['info'] = 'тут пока нет описания';
        }

        return $result;
    }

    public static function checkPickFromSupp($ids)
    {
        $result = [];
        $idDeals = DealTools::getStatusByField($ids, DealTools::PICK_FROM_SUPP_FIELD, -1);

        foreach ($idDeals as $idDeal => $color) {
            $result[$idDeal]['color'] = $color;
            $result[$idDeal]['info'] = 'тут пока нет описания';
        }

        return $result;
    }

    public static function checkDeliveryDeadlineTender($ids)
    {
        $result = [];
        $idDeals = DealTools::getStatusByField($ids, DealTools::DELIVERY_DEADLINE_TENDER, 2, 7);

        foreach ($idDeals as $idDeal => $color) {
            $result[$idDeal]['color'] = $color;
            $result[$idDeal]['info'] = 'тут пока нет описания';
        }

        return $result;
    }

    public static function getSumPriceDeal($params)
    {
        $result = [];
        $stage = $params['stage'];
        $category = $params['category'];
        $filter = [
            'STAGE_ID' => $stage,
            'CATEGORY_ID' => $category,
        ];
        $dealList = DealTable::getList(
            [
                'select' => [
                    'ID',
                    'OPPORTUNITY',
                    'CURRENCY_ID',
                ],
                'filter' => $filter,
            ]
        )->fetchAll();
        $currencyInfo = [];
        foreach ($dealList as $deal) {
            if (!$currencyInfo[$deal['CURRENCY_ID']]) {
                $currencyInfo[$deal['CURRENCY_ID']] = CCurrencyLang::GetCurrencyFormat($deal['CURRENCY_ID']);
            }
            $currencyTitle = trim(str_replace('#', '', $currencyInfo[$deal['CURRENCY_ID']]['FORMAT_STRING']));
            $result[$currencyTitle] = $result[$currencyTitle] ?: 0;
            $result[$currencyTitle] += floatval($deal['OPPORTUNITY']);
        }
        ksort($result);
        return $result;
    }

    public static function getIconInfo($params)
    {
        $error = false;
        $ids = $params['ids'];
        $result = [];
        if (!empty($ids)) {
            $filter = [
                'ID' => $ids,
            ];
            try {
                $dealList = DealTable::getList([
                    'filter' => $filter,
                    'select' => [
                        'ID',
                        'COMPANY_ID',
                        'COMPANY_STATUS' => 'COMPANY.UF_CRM_1639110967',
                        self::DEAL_FROM_FIELD,
                        self::COUNTRY_FIELD,
                        self::PAYMENT_METHOD_FIELD,
                        self::TERMS_DELIVERY_FIELD,
                    ],
                ])->fetchAll();
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
            foreach ($dealList as $deal) {
                $result[$deal['ID']] = [
                    'contragent-type' => self::$contragentType[$deal[self::DEAL_FROM_FIELD]] ?: '',
                    'country' => (is_array($deal[self::COUNTRY_FIELD]) && count($deal[self::COUNTRY_FIELD]))
                        ? (self::$countryList[reset($deal[self::COUNTRY_FIELD])] ?: 'world')
                        : 'world',
                    'percent' => self::$percentType[$deal[self::PAYMENT_METHOD_FIELD]] ?: '',
                    'terms-delivery' => self::$termsDeliveryType[$deal[self::TERMS_DELIVERY_FIELD]] ?: '',
                    'company-status' => self::$companyStatus[$deal['COMPANY_STATUS']] ?: '',
                    'snt' => self::checkSnt($deal['COMPANY_ID']) ? 'SNT' : '',
                    'edo' => self::checkEdo($deal['COMPANY_ID']) ? 'ЭДО' : '',
                ];
            }
        }
        return ['result' => $result, 'error' => $error, 'params' => $params];
    }

    protected static function checkSnt($companyId)
    {
        $company = self::getCompany($companyId);
        return !!$company['UF_CRM_1653300155983'];
    }

    protected static function getCompany($companyId)
    {
        if (!self::$companies[$companyId]) {
            $filter = [
                'ID' => $companyId,
            ];
            $select = [
                'UF_CRM_1653300155983',
                'UF_CRM_1653300185161',
            ];
            self::$companies[$companyId] = CompanyTable::getRow([
                'filter' => $filter,
                'select' => $select,
            ]);
        }
        return self::$companies[$companyId];
    }

    protected static function checkEdo($companyId)
    {
        $company = self::getCompany($companyId);
        return !!$company['UF_CRM_1653300185161'];
    }

    public static function copyComment(&$arFields = [])
    {
        if (
            (
                strpos($_SERVER["REQUEST_URI"], "crm/deal/edit/") ||
                (
                    strpos($_SERVER["REQUEST_URI"], "crm/deal/show/") &&
                    $_POST["action"] == "checkCommentChange"
                )
            ) &&
            ($id = self::getDealID($_SERVER["REQUEST_URI"])) &&
            isset($_POST["COMMENTS"]) &&
            strlen($_POST["COMMENTS"])
        ) {
            $_SESSION["KAA_NO_EVENTS"] = "Y";
            $deal = \CCrmDeal::GetByID($id);
            if ($deal["COMMENTS"] != $_POST["COMMENTS"] || $_POST["action"] == "checkCommentChange") {
                $fields = [
                    "ENTITY_TYPE_ID" => \CCrmOwnerType::Deal,
                    "ENTITY_ID" => $id,
                    "TITLE" => "Добавлен комментарий",
                    "MESSAGE" => HTMLToTxt($_POST["COMMENTS"]),
                    "RIGHTS" => [],
                ];
                \CCrmLiveFeed::CreateLogMessage($fields);
            }
        }
    }

    public static function getDealID($url)
    {
        $part = preg_split("|/|", $url);
        return $part[count($part) - 2];
    }

    private function _repairDeal()
    {
        addEventHandler("crm", "OnBeforeCrmDealAdd", [self::$thisClass, "repairDeal"]);
        addEventHandler("crm", "OnBeforeCrmDealUpdate", [self::$thisClass, "repairDeal"]);
    }

    private function _checkCompanyLead()
    {
        addEventHandler("crm", "OnBeforeCrmCompanyAdd", [self::$thisClass, "checkCompanyLead"]);
    }

    private function _checkContactLeadOrCompany()
    {
        addEventHandler("crm", "OnBeforeCrmContactAdd", [self::$thisClass, "checkContactLeadOrCompany"]);
    }

    private function _checkRoistat()
    {
        addEventHandler("crm", "OnBeforeCrmLeadAdd", [self::$thisClass, "checkRoistat"]);
    }

    private function _checkLeadPhone()
    {
        if ($this->checkDir()) {
            addEventHandler("crm", "OnBeforeCrmLeadAdd", [self::$thisClass, "checkLeadPhone"]);
        }
    }

    /**
     * @return bool
     */
    private function checkDir()
    {
        global $APPLICATION;
        $dir = $APPLICATION->GetCurDir();
        return $dir != "/crm/configs/import/";
    }

    private function _checkSum()
    {
        addEventHandler("crm", "OnBeforeCrmDealUpdate", [self::$thisClass, "emptySum"]);
    }

    private function _repairColorDeal()
    {
        addEventHandler("main", "OnEpilog", [self::$thisClass, "repairColorDeal"]);
        addEventHandler("crm", "OnBeforeCrmDealUpdate", [self::$thisClass, "emptySum"]);
    }

    private function _copyComment()
    {
        if (self::isCrmDeal($_SERVER["REQUEST_URI"])) {
            global $APPLICATION;
            $APPLICATION->AddHeadScript('/local/modules/istline.checkrules/js/editCommentControl.js');
            addEventHandler("main", "OnBeforeProlog", [self::$thisClass, "copyComment"]);
        }
    }

    public static function isCrmDeal($url)
    {
        $part = preg_split("|/|", $url);
        return ($part[1] == "crm" && $part[2] == "deal");
    }
    private function _extActInDeal()
    {
        if (self::isCrmDealList($_SERVER["REQUEST_URI"])) {
            \CModule::IncludeModule('socialnetwork');
            global $APPLICATION;
            $APPLICATION->AddHeadScript('/local/modules/istline.checkrules/js/extendActivityInDeal.js');
        }
    }

    public static function isCrmDealList($url)
    {
        $part = preg_split("|/|", $url);
        return ($part[1] == "crm" && $part[2] == "deal" && ($part[3] == "list" || $part[3] == "category" || !$part[3]));
    }
}