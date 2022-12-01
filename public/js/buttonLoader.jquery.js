
$.fn.buttonLoader = function (show = true) {

    if (show) {

        // Disable the button
        $(this).prop('disabled', true);
        $(this).addClass('disabled');

        // Remember button size because it can change when
        // the inner HTML of the button is updated
        let width = $(this).width();
        let height = $(this).height();

        // Remember original button
        $(this).data('original-width', width);
        $(this).data('original-height', height);
        $(this).data('original-html', $(this).html());

        // Set button contents to loader
        $(this).html('<div class="lds-ring"><div></div><div></div><div></div><div></div></div>');

        // Reset button size (because it might have changed)
        $(this).width(width);
        $(this).height(height);

    } else {

        // Enable the button
        $(this).prop('disabled', false);
        $(this).removeClass('disabled');

        // Reset button contents
        if($(this).data('original-html')){
            $(this).html($(this).data('original-html'));
            $(this).removeData('original-html');
        }

        // Reset button size
        if($(this).data('original-width')){
            $(this).width($(this).data('original-width'));
            $(this).removeData('original-width');
        }
        if($(this).data('original-height')){
            $(this).height($(this).data('original-height'));
            $(this).removeData('original-height');
        }
    }
};
