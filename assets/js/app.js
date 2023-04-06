var $ = require('jquery');
var clipboard = require('clipboard');

$(function() {
    new clipboard("a[data-clipboard-text]");
	
    $('.sonata-ba-form').on('click', 'a.var-placeholder', e => {
        e.preventDefault();
    });
});
