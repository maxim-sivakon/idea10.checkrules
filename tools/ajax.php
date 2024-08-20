<?

use Bitrix\Main\Context;
use Bitrix\Main\HttpResponse;
use Bitrix\Main\Web\Json;
use Istline\Checkrules\Rules;

define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC', 'Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

if (check_bitrix_sessid()) {
    $result = false;
    $request = Context::getCurrent()->getRequest();
    if (is_callable([
        Rules::class,
        $request->get('method')
    ])) {
        $result = call_user_func_array([
            Rules::class,
            $request->get('method')
        ], [$request->get('params')]);
    }
    $response = new HttpResponse();
    $response->addHeader('Content-Type', 'application/json');
    if($result == false){
        $result = $request->get('method');
    }
    $response->flush(Json::encode($result));
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
die();