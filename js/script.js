<?
$fieldParam = strtolower(\COption::GetOptionString('istline.checkrules', "select_field_deal"));
?>
<script>
    window.onload = function() {
        reg = document.querySelector("tr[id$='<?php echo $fieldParam;?>']");
        reg.style.background = '#3c9dbf';
    };
</script>