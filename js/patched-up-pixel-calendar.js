jQuery(document).ready(function($){
    function updateColorPickers(){
        $('#widgets-right .color').each(function(){
            $(this).wpColorPicker({
                // you can declare a default color here,
                // or in the data-default-color attribute on the input
                defaultColor: false,
                // a callback to fire whenever the color changes to a valid color
                change: function(event, ui){},
                // a callback to fire when the input is emptied or an invalid color
                clear: function() {},
                // hide the color picker controls on load
                hide: true
                // show a group of common colors beneath the square
                // or, supply an array of colors to customize further
                //palettes: ['#ffffff','#000000','#ff7c0b']
            });
        }); 
    }
    updateColorPickers();   
    $(document).ajaxSuccess(function(e, xhr, settings) {

        if(settings.data.search('action=save-widget') != -1 ) { 
            $('.color-field .wp-picker-container').remove();    
            updateColorPickers();       
        }
    });
 });

