<?php
define("NOT_CHECK_PERMISSIONS", true);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
IncludeModuleLangFile(__FILE__);

$APPLICATION->IncludeComponent(
    'bitrix:crm.livefeed.activity.list',
    'small',
    array(
        'ENTITY_TYPE_ID' => 2,
        'ENTITY_ID' => $_REQUEST["ID"],
        'ACTIVITY_EDITOR_UID' => "CRM_DEAL_LIST_V12_activity_editor",
//        'PATH_TO_USER' => $arResult['PATH_TO_USER_PROFILE']
    ),
    null,
    array('HIDE_ICONS' => 'Y')
);