$(document).ready(function () {
    $(document).keyup("click",function(){
        if (event.keyCode == '13') {
            var btn=$("#CRM_DEAL_EDIT_V12_saveAndView,.main-ui-filter-find");
            if(btn.length>0){
                btn.click();
            }
        }
        return false;
    });


    var oldComments = $(".crm-lead-header-lhe-view-wrapper").text();
    var newComments = "";
    $(document).on("click", function () {
        oldComments = $(".crm-lead-header-lhe-view-wrapper").text();
        if ($(".crm-lead-header-lhe-edit-wrapper").css("display") != "none") {
            setTimeout(function () {
                newComments = $("input[name='lha_content']").val();
                if (newComments != undefined && oldComments != newComments) {
                    $.ajax({
                        url: "",
                        type: "POST",
                        data: {COMMENTS: newComments, action: "checkCommentChange"},
                        success: function () {
                            oldComments = newComments;
                        }
                    })
                }
            }, 2000);
        }
    })
})
