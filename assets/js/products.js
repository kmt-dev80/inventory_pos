jQuery(document).ready(function($) {
    // Category chain dropdowns
    $('#category_id').change(function() {
        var categoryId = $(this).val();
        $('#sub_category_id').html('<option value="">Loading...</option>').prop('disabled', true);
        $('#child_category_id').html('<option value="">Select Child Category</option>').prop('disabled', true);
        
        if (categoryId) {
            $.ajax({
                url: 'add_product.php',
                data: {get_sub_categories: 1, category_id: categoryId},
                dataType: 'json', // Explicitly expect JSON
                success: function(data) {
                    var options = '<option value="">Select Sub Category</option>';
                    if (data && data.length) {
                        $.each(data, function(key, value) {
                            options += '<option value="' + value.id + '">' + value.category_name + '</option>';
                        });
                    }
                    $('#sub_category_id').html(options).prop('disabled', false);
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", status, error);
                    $('#sub_category_id').html('<option value="">Error loading sub-categories</option>');
                }
            });
        }
    });
    
    $('#sub_category_id').change(function() {
        var subCategoryId = $(this).val();
        $('#child_category_id').html('<option value="">Loading...</option>').prop('disabled', true);
        
        if (subCategoryId) {
            $.ajax({
                url: 'add_product.php',
                data: {get_child_categories: 1, sub_category_id: subCategoryId},
                dataType: 'json', // Explicitly expect JSON
                success: function(data) {
                    var options = '<option value="">Select Child Category</option>';
                    if (data && data.length) {
                        $.each(data, function(key, value) {
                            options += '<option value="' + value.id + '">' + value.category_name + '</option>';
                        });
                    }
                    $('#child_category_id').html(options).prop('disabled', false);
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", status, error);
                    $('#child_category_id').html('<option value="">Error loading child categories</option>');
                }
            });
        }
    });
    
});