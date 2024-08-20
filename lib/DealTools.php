<?php

namespace Istline\Checkrules;

use Bitrix\Crm\DealTable;
use Bitrix\Crm\History\Entity\DealStageHistoryTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use DateInterval;

\Bitrix\Main\UI\Extension::load("ui.hint");

Loader::includeModule('crm');

class DealTools
{
    const READY_SHIPMENT_EXW_FIELD = 'UF_CRM_1637209835';
    const READY_SHIPMENT_EXW_DAP_FIELD = 'UF_CRM_1637209835';
    const READY_SHIPMENT_FIELD = 'UF_CRM_1637209806';
    const PICK_FROM_SUPP_FIELD = 'UF_CRM_1637652682';
    const TRANSFER_TO_NSK_FIELD = 'UF_CRM_1637209765';
    const SHIPMENT_DATE_FROM_PROVIDER_FIELD = 'UF_CRM_1629191830';
    const WAIT_MONEY_FROM_CONTRAGENT_FIELD = 'UF_CRM_1636995087';
    const WAIT_CORRECTION_FROM_PROVIDER_FIELD = 'UF_CRM_1636993690';
    const WAIT_CORRECTION_FROM_CONTRAGENT_FIELD = 'UF_CRM_1636993727';
    const MOVE_TO_PREPARE_TN_FIELD = 'UF_CRM_1637683331';
    const WITHDRAW_LIST_FIELD = 'UF_CRM_1634625263559';
    const DELIVERY_DEADLINE_TENDER = 'UF_CRM_1626927285230';
    const MOVE_TO_TALON_FIELD = 'UF_CRM_1637683358';
    const MOVE_TO_NSK_STOCK = 'UF_CRM_1698804442';
    const URGENT_IMPLEMENTATION = 'UF_CRM_1700817221464';


    /**
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getLastChangeStageDate($id)
    {
        $result = false;
        if ((int)$id > 0) {
            Loader::includeModule('crm');
            $filter = [
                'OWNER_ID' => $id,
            ];
            $lastChangeStage = DealStageHistoryTable::getList([
                'filter' => $filter,
                'limit' => 1,
                'select' => [
                    'CREATED_DATE',
                ],
                'order' => [
                    'ID' => 'DESC'
                ],
            ])->fetch();
            $result = $lastChangeStage['CREATED_DATE'];
        }
        return $result;
    }

    public static function getDateHarvest($id)
    {
        $result = false;
        if ((int)$id > 0) {
            $filter = [
                'ID' => $id,
            ];
            $deal = DealTable::getList([
                'filter' => $filter,
                'select' => [
                    'UF_CRM_1626853571',
                ]
            ])->fetch();
            $result = $deal['UF_CRM_1626853571'];
        }
        return $result;
    }


    //// Воронка логистика -start- ////


    // Переход в СБОР ДОКУМЕНТОВ ДЛЯ РАЗРЕШЕНИЯ - UF_CRM_1673590374
//    public static function checkCollectingDocumentsForPermit($ids)
//    {
//        $field = 'UF_CRM_1673590374';
//        $result = [];
//        foreach ($ids as $id) {
//            $diff = static::checkDealByDateField($id, $field);
//            if ($diff !== false) {
//                if ($diff >= 168) {
//                    $result[$id] = 'yellow';
//                }
//                if ($diff <= 96) {
//                    $result[$id] = 'yellow';
//                }
//            }
//        }
//
//        return $result;
//    }

    //// Воронка логистика -end- ////


    public static function checkLogisticShipmentExw($id)
    {
        $diff = static::checkDealByDateField($id, static::READY_SHIPMENT_EXW_FIELD);
        return $diff < 0 ? 'red' : false;
    }

    protected static function checkDealByDateField($id, $field, $hours = false)
    {
        $diff = false;
        $deal = DealTable::getByPrimary($id, ['select' => [$field]])->fetch();
        if ($deal && $deal[$field]) {
            if ($hours) {
                $diff = static::getDiffHours($deal[$field]);
            } else {
                $diff = static::getDiffDay($deal[$field]);
            }
        }
        return $diff;
    }

    protected static function getDiffHours($date)
    {
        $currDate = (new DateTime())->getTimestamp();
        $date = (new DateTime($date))->getTimestamp();
        $diff = ($date - $currDate) / 3600;
        return $diff;
    }

    protected static function getDiffDay($date)
    {
        $diff = static::getDiff($date);
        $diff = ($diff->invert ? -1 : 1) * $diff->format('%a');
        return $diff;
    }

    protected static function getDiff($date, $byDayEnd = true): DateInterval
    {
        $currDate = new DateTime();
        $date = new DateTime($date);
        if ($byDayEnd) {
            $date->setTime(23, 59, 59);
        }
        return $currDate->getDiff($date);
    }

    public static function checkLogisticShipment($id)
    {
        $diff = static::checkDealByDateField($id, static::READY_SHIPMENT_FIELD);
        return $diff < 0 ? 'red' : false;
    }

    public static function checkPickFromSupp($params)
    {
        try {
            $diff = static::checkDealByDateField($params['id'], static::PICK_FROM_SUPP_FIELD);
            $status = $diff < 0 ? 'red' : false;
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }
        return ['status' => $status ?? false, 'error' => $error ?? false, 'params' => $params];
    }

    public static function getStatusByFieldValueTime($ids, $field, $fieldTime, $redLimit = null, $yellowLimit = null, $value = false): array
    {
        $result = [];
        foreach ($ids as $id) {
            $deal = DealTable::getByPrimary($id, ['select' => [$field]])->fetch();
            if ($deal[$field] == $value) {
                $diff = static::checkDealByDateField($id, $fieldTime);
                if ($diff !== false) {
                    if ($yellowLimit !== null && $diff <= $yellowLimit) {
                        $result[$id] = 'yellow';
                    }
                    if ($redLimit !== null && $diff <= $redLimit) {
                        $result[$id] = 'red';
                    }
                }

            }
        }
        return $result;
    }
    public static function getStatusByFieldValueYesNo($ids, $field, $statusColor = 'blue', $value = false): array
    {
        $result = [];
        foreach ($ids as $id) {
            $deal = DealTable::getByPrimary($id, ['select' => [$field]])->fetch();
            if($value && $deal[$field] == 'Да' || $deal[$field] == 'Y' || $deal[$field] == $value){
                $result[$id] = $statusColor;
            }
        }
        return $result;
    }
    public static function getStatusByFieldValue($ids, $field, $statusColor = 'blue', $value = false): array
    {
        $result = [];
        foreach ($ids as $id) {
            $value = static::checkDealField($id, $field, $value);
            if ($value) {
                $result[$id] = $statusColor;
            }
        }
        return $result;
    }

    public static function checkPodgonovcaSpets($id)
    {
        return static::checkDealField($id, 'UF_CRM_1699496271', 3119) == true ? true : false;
    }

    public static function getUrgentImplementation($id)
    {
        return static::checkDealField($id, self::URGENT_IMPLEMENTATION, 3130) == true ? "blue" : "";
    }

    private static function checkDealField($id, $field, $value = false): bool
    {
        $result = false;
        $deal = DealTable::getByPrimary($id, ['select' => [$field]])->fetch();

//        if($deal[$field] == $value){
//            $result = true;
//        }
//        return $result;

        return $res = $value ? $deal[$field] == $value : !!$deal[$field];
    }

    public static function getStatusByField($ids, $field, $redLimit = null, $yellowLimit = null, $greenLimit = null, $inHours = false): array
    {
        $result = [];
        foreach ($ids as $id) {
            $diff = static::checkDealByDateField($id, $field, $inHours);
            if ($diff !== false) {
                if ($greenLimit !== null && $diff <= $greenLimit) {
                    $result[$id] = 'green';
                }
                if ($redLimit !== null && $diff <= $redLimit) {
                    $result[$id] = 'red';
                }
                if ($yellowLimit !== null && $diff <= $yellowLimit) {
                    $result[$id] = 'yellow';
                }
            }
        }
        return $result;
    }

    public static function getStatusByFieldToDay($ids, $field, $redLimit = null, $yellowLimit = null, $greenLimit = null, $inHours = false): array
    {
        $result = [];
        foreach ($ids as $id) {
            $diff = static::checkDealByDateField($id, $field, $inHours);
            if ($diff !== false) {
                if ($greenLimit !== null && $diff == $greenLimit) {
                    $result[$id] = 'green';
                }
                if ($redLimit !== null && $diff == $redLimit) {
                    $result[$id] = 'red';
                }
                if ($yellowLimit !== null && $diff == $yellowLimit) {
                    $result[$id] = 'yellow';
                }
            }
        }
        return $result;
    }

    public static function getStatusByFieldToDate($ids, $field1, $field2): array
    {
        $result = [];
        foreach ($ids as $id) {
            $diff1 = static::checkDealByDateField($id, $field1);
            $diff2 = static::checkDealByDateField($id, $field2);
            if ($diff1 !== false || $diff2 !== false) {
                if ($diff1 < $diff2) {
                    $result[$id] = 'blue';
                }
            }
        }
        return $result;
    }
}