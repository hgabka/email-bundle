$(function() {
    $('#hgabka_kunstmaanemail_message_send_type_sendTime_type').change(function() {
       var val = $(this).val();
       if (val == 'now') {
           hideElem('#hgabka_kunstmaanemail_message_send_type_sendTime_time');
       } else {
           showElem('#hgabka_kunstmaanemail_message_send_type_sendTime_time');
       }
    }).change();

    function hideElem(selector)
    {
        $(selector).parent('.form-group').hide();
    }
    function showElem(selector)
    {
        $(selector).parent('.form-group').show();
    }
});