<?

//TODO добавить обработчик подключеня
Class CIstlineCheckrules
{
    public static function Check(){
        if (CModule::IncludeModule('istline.checkrules')) {
            $rules = new \Istline\Checkrules\Rules();
        }
    }
}

?>
