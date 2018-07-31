var $ = require('jquery');
var clipboard = require('clipboard');

$(function() {
    new clipboard("a[data-clipboard-text]");
    $('a.var-placeholder').click(function (e) {
        e.preventDefault();
    });
});