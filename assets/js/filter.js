jQuery(document).ready(function($) {
    console.log('WC Category Filter script loaded');
    $('#wc-category-filter').on('submit', function(e) {
        e.preventDefault();
        var category = $('#product_cat').val();

        $.ajax({
            url: wc_filter_params.ajax_url,
            type: 'POST',
            data: {
                action: 'wc_category_filter',
                category: category
            },
            success: function(response) {
                if (response.success) {
                    $('#wc-category-filter-results').html(response.data);
                }
            }
        });
    });
});
