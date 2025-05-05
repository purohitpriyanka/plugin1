jQuery(document).ready(function($) {
    // Hide all tab content except the first one by default
    $('.tab-content').hide();
    $('.tab-content:first').show();

    // Add click event listener to tab links
    $('.tab-link').click(function(e) {
        e.preventDefault(); // Prevent default link behavior

        // Remove active class from all tabs
        $('.tab-link').removeClass('active');

        // Add active class to the clicked tab
        $(this).addClass('active');

        // Hide all tab content
        $('.tab-content').hide();

        // Show the selected tab content
        var tabID = $(this).data('tab');
        $('#' + tabID).show();
    });



        var mediaUploader;
    
        $('#upload_image_button').click(function(e) {
            e.preventDefault();
    
            // If the media uploader exists, open it.
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
    
            // Create a new media uploader
            mediaUploader = wp.media.frames.file_frame = wp.media({
                title: 'Choose Preview Image',
                button: {
                    text: 'Choose Image'
                },
                multiple: false // Allow only one image to be selected
            });
    
            // When an image is selected, run a callback
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#templify_preview_image').val(attachment.url); // Store the image URL in the hidden field
                $('#templify_preview_image_preview').attr('src', attachment.url).show(); // Update the preview image
            });
    
            // Open the uploader
            mediaUploader.open();
        });
    
    



    
    $('#templify-link-button').on('click', function(e) {
        e.preventDefault(); // Prevent traditional form submission
    
        var coreUrl = $('#templify-core-url').val();
        var coreKey = $('#templify-core-key').val();
        var isLinked = wpApiSettings.link_status.linked;
    
        // If already linked, alert the user
        if (isLinked === 1) {
            alert('Already linked with Templify Core.');
            return;
        }
    
        // Validate core URL and key
        if (!coreUrl || !coreKey) {
            alert('Please enter both the URL and Core Key.');
            return;
        }
    
        // Prepare the data for the AJAX request
        var requestData = {
            url: coreUrl,
            key: coreKey
        };
    
        // Make the AJAX request to link with Templify Core
        $.ajax({
            url: wpApiSettings.root + 'templify/v1/link', // Use the dynamic root URL
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(requestData),
            success: function(response) {
                if (response.status === 'success') {
                    $('#templify-link-button').text('Unlink With Templify Core');
                    alert('Successfully linked with Templify Core!');
                    $('li[data-tab="libraries"]').removeClass('hidden'); // Show libraries tab
                    wpApiSettings.link_status.linked = 1; // Update link status
                } else {
                    alert(response.message || 'Error linking with Templify Core.');
                }
            },
            error: function(xhr, status, error) {
                alert('An error occurred while linking with Templify Core.');
                console.log(error);
            }
        });
    });
    

    




});



jQuery(document).ready(function($) {
    // Select the form that triggers the zip generation
    const generateButton = $('form[action*="generate_templify_zip"]');
    
    // Add a submit event listener to the form
    generateButton.on('submit', function(event) {
        // Retrieve values from Tab 2 inputs
        const themeName = $('input[name="templify_theme_name"]').val();
        const authorName = $('input[name="templify_author"]').val();
        const version = $('input[name="templify_version"]').val();
        const previewImage = $('input[name="templify_preview_image"]').val();
        const authorLink = $('input[name="templify_author_link"]').val();

        // Check if any field is empty
        if (!themeName || !authorName || !version || !previewImage || !authorLink) {
            alert('Please fill out all the required fields in Tab 2 before generating the ZIP file.');
            event.preventDefault(); // Prevent form submission
        }
    });
});

