$(document).ready(function() { 
    // Custom DataTable Sorting
    jQuery.extend( jQuery.fn.dataTableExt.oSort, {
        "formatted-num-pre": function ( a ) {
            a = (a==="-") ? 0 : a.replace( /[^\d\-\.]/g, "" );
            return parseFloat( a );
        },
     
        "formatted-num-asc": function ( a, b ) {
            return a - b;
        },
     
        "formatted-num-desc": function ( a, b ) {
            return b - a;
        }
    } );
	
	jQuery.extend( jQuery.fn.dataTableExt.oSort, {
		"item-sort-pre": function ( a ) {
			th = a.replace(/(<([^>]+)>)/ig,'');
			return th.replace(/&nbsp; \d+ x /ig,'');
		},
		"item-sort-asc": function( a, b ) {
			return ((a < b) ? -1 : ((a > b) ? 1 : 0));
		},
	 
		"item-sort-desc": function(a,b) {
			return ((a < b) ? 1 : ((a > b) ? -1 : 0));
		}
	} );
	
	$('span.pop').popover({ html : true })
    
    var lpOffers = $('#lpOffers').dataTable({
        //"bStateSave": true,
        "bPaginate": false,
        "oColVis": {
			"aiExclude": [ 0 ],
            "sAlign" : "right",
		},
        "sDom": 'C<"clear">lfrtip',
        "aaSorting": [[ 1, "asc" ]],
        "aoColumns": [
            { "sType": 'item-sort' },
            { "sType": 'formatted-num' },
            { "sType": 'formatted-num' },
            null,
            { "sType": 'formatted-num' },
            { "sType": 'formatted-num' },
            { "sType": 'formatted-num' },
            { "sType": 'formatted-num' }],

    });
    
	$('input.clearonfocus').focus(function() {
		if (!$(this).data('originalValue')) {
			$(this).data('originalValue', $(this).val());
		}
		if ($(this).val() == $(this).data('originalValue')) {
			$(this).val('');
		}
	}).blur(function(){
		if ($(this).val() == '') {
			$(this).val($(this).data('originalValue'));
		}
	});
	
	$("#lpStore_search").autocomplete({
		source: function(request, response) {
			$.getJSON("/lpStore/suggest.php", { corpName: request.term }, response);
		},
		minLength: 2,
		select: function(event, ui) {
			$('#autoCorpID').val(ui.item.id);
		}
	});
}); 