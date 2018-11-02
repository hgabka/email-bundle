$(function() {
    $('#hg_email_message_send_type_sendTime_type').change(function() {
       var val = $(this).val();
        console.log(val);
       if (val == 'now') {
           hideElem('#hg_email_message_send_type_sendTime_time');
       } else {
           showElem('#hg_email_message_send_type_sendTime_time');
       }
    }).change();

    function hideElem(selector)
    {
        $(selector).closest('.form-group').hide();
    }
    function showElem(selector)
    {
        $(selector).closest('.form-group').show();
    }
});