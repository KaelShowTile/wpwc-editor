function updateYoastTitle($field){
    const productId = $field.data('productid');
    let newValue = $field.text().trim();

    var currentUrl = window.location.href;
    var baseUrl = currentUrl.substring(0, currentUrl.lastIndexOf('/'));

    $.ajax({
        url: baseUrl + '/includes/save_yoast_title.php',
        method: 'POST',
        data: {
            product_id: productId,
            status: newValue
        },
        success: function() {
            $field.removeClass('saving').addClass('saved');
            setTimeout(() => $field.removeClass('saved'), 2000);

            $field.data('original', newValue);
        },
        error: function() {
            $field.removeClass('saving').addClass('error');
            setTimeout(() => $field.removeClass('error'), 2000);
            
            // Revert to original value on error
            $field.text($field.data('original'));
        }
    });
}

function updateYoastDesc($field){
    const productId = $field.data('productid');
    let newValue = $field.text().trim();

    var currentUrl = window.location.href;
    var baseUrl = currentUrl.substring(0, currentUrl.lastIndexOf('/'));

    $.ajax({
        url: baseUrl + '/includes/save_yoast_desc.php',
        method: 'POST',
        data: {
            product_id: productId,
            status: newValue
        },
        success: function() {
            $field.removeClass('saving').addClass('saved');
            setTimeout(() => $field.removeClass('saved'), 2000);

            $field.data('original', newValue);
        },
        error: function() {
            $field.removeClass('saving').addClass('error');
            setTimeout(() => $field.removeClass('error'), 2000);
            
            // Revert to original value on error
            $field.text($field.data('original'));
        }
    });
}