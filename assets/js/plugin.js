$(document).ready(function(){
    
    //hide sample product
    $('#hide-sample-product').change(function(){
        var isChecked = $(this).is(':checked');
        var changeSetting;

        if(isChecked == false){
            changeSetting = "0"; //don't hide
        }else{
            changeSetting = "1"; //hide
        }

        $.ajax({
            url: baseUrl + '/plugin/save_sample_plugin_setting.php',
            method: 'POST',
            data: {
                value: changeSetting
            },
            success: function() {
                $select.removeClass('saving').data('original', newStatus);
                $select.siblings('.saving-indicator').remove();
                $select.prop('disabled', false);
                
                // Show success badge
                const $badge = $(`<span class="badge bg-success position-absolute top-0 start-100 translate-middle">Saved!</span>`);
                $select.parent().append($badge);
                setTimeout(() => $badge.fadeOut(500, () => $badge.remove()), 2000);
            },
            error: function() {
                $select.removeClass('saving').prop('disabled', false)
                    .siblings('.saving-indicator').remove();
                $select.val(originalStatus); // Revert to original value
                
                // Show error badge
                const $badge = $(`<span class="badge bg-danger position-absolute top-0 start-100 translate-middle">Error!</span>`);
                $select.parent().append($badge);
                setTimeout(() => $badge.fadeOut(500, () => $badge.remove()), 2000);
            }
        });

    });
});