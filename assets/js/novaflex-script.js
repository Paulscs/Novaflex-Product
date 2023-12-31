jQuery(document).ready(function ($) {
    // Disable the "Download" button on page load
    $('#download-button').prop('disabled', true);

    // Add an event listener to all the checkboxes
    $('input[type="checkbox"]').change(function () {
        // Check if any checkboxes are checked
        if ($('input[type="checkbox"]:checked').length > 0) {
            // Enable the "Download" button
            $('#download-button').prop('disabled', false);
        } else {
            // Disable the "Download" button
            $('#download-button').prop('disabled', true);
        }
    });

    var isMobile = window.innerWidth <= 768; // Adjust the width as needed
    
    if (isMobile) {
        $('.tooltip, .checkbox-tooltip').hide(); // Hide both tooltips on mobile devices
    }

    // Handle the "Select for download" tooltip when hovering over the checkbox
    $('label input[type="checkbox"]').hover(function () {
        var checkboxTooltip = $(this).siblings('.checkbox-tooltip');
        var isMobile = window.innerWidth <= 768; // Recheck on hover

        if (!isMobile) {
            checkboxTooltip.show(); // Display the "Select for download" tooltip for the checkbox
            $(this).parents('label').find('.tooltip').hide(); // Hide the "Preview File" tooltip
        }
    }, function () {
        $(this).siblings('.checkbox-tooltip').hide(); // Hide the "Select for download" tooltip on mouseout
    });

    // Handle the "Preview File" tooltip when hovering over the text
    $('label a').hover(function () {
        var previewFileTooltip = $(this).find('.tooltip');
        var isMobile = window.innerWidth <= 768; // Recheck on hover

        if (!isMobile) {
            previewFileTooltip.show(); // Display the "Preview File" tooltip for the text
            $(this).parents('label').find('.checkbox-tooltip').hide(); // Hide the "Select for download" tooltip
        }
    }, function () {
        $(this).find('.tooltip').hide(); // Hide the "Preview File" tooltip on mouseout
    });

    $('label').on('click', function () {
        var isMobile = window.innerWidth <= 768; // Recheck on click
        if (isMobile) {
            $(this).find('.tooltip, .checkbox-tooltip').hide(); // Hide both tooltips on mobile devices when clicked
        }
    });

    $('#downloadable-files-form').on('submit', function (e) {
        e.preventDefault();
        console.log("triggers");
        let formData = $(this).serializeArray();
        let labelTexts = '';
        // Get all checkboxes with the name 'selected_files[]'
        var checkboxes = $("input[name='selected_files[]']");
        
        // Check if all checkboxes are checked
        var allChecked = true;
        checkboxes.each(function () {
            if (!$(this).is(":checked")) {
                allChecked = false;
                return false; // Break the loop if any checkbox is not checked
            }
        });

        let productName = $('.product_title').text().replace(/ /g, '');

        if (allChecked) {
            // Set the download attribute with the desired file name (optional)
            labelTexts = productName + '-Master-Sheet.pdf';
        } else {
            labelTexts = productName;
            checkboxes.each(function () {
                if ($(this).is(":checked")) {
                    let labelText = $(this).attr('data-name');
                    labelTexts = labelTexts + '-'  + labelText;
                }
            });
        }

        console.log(labelTexts);
        
        $('#loading-spinner').removeClass('hidden');

        // Send an AJAX request to led_handle_download_files
        $.ajax({
            url: vars.ajax_url + "?action=led_handle_download_files",
            dataType: "json",
            type: "POST",
            async: true,
            data: {
                selected_files: formData,
                label_text: labelTexts,
            },
            success: function (response) {
                // Create a dynamic anchor (a) element
                var downloadLink = document.createElement('a');
        
                // Set the href attribute to the file URL you want to open in a new tab
                downloadLink.href = response.data.file_url;
        
                // Set the target attribute to "_blank" to open in a new tab
                downloadLink.target = "_blank";
        
                // Append the anchor element to the DOM (you can hide it if needed)
                document.body.appendChild(downloadLink);
        
                // Trigger a click event on the anchor element
                downloadLink.click();
        
                // Remove the anchor element from the DOM (optional)
                document.body.removeChild(downloadLink);

                setTimeout(() => {   
                    $('#loading-spinner').addClass('hidden');
                }, 2000);
            }
        });
    });

    function hideAdditionalInfo() {
        $('#additional-info-container').hide();
    }

    // Show the additional info container when the Description tab is active
    $('ul.tabs li.description_tab').click(function () {
        showAdditionalInfo();
    });

    // Function to show the additional info container
    function showAdditionalInfo() {
        $('#additional-info-container').show();
    }

    // Hide the additional info container by default
    hideAdditionalInfo();

    // Listen for clicks on the product tabs
    $('ul.tabs li').click(function () {
        if ($(this).hasClass('led_downloads_tab')) {
            // If the "Downloads" tab is clicked, hide the additional info
            hideAdditionalInfo();
        }
    });

    $('#downloadable-files-form').on('submit', function (e) {
        e.preventDefault();
        console.log("triggers");
        let formData = $(this).serializeArray();
        let labelTexts = '';
        // Get all checkboxes with the name 'selected_files[]'
        var checkboxes = $("input[name='selected_files[]']");
    });

    function HideAdditionalInformationTitle() {
        // Check if the table inside #additional-info-container is empty
        var additionalInfoTable = $('#additional-info-container table.woocommerce-product-attributes');
    
        // Check if the table is empty or not
        if (!additionalInfoTable.length || !additionalInfoTable.find('tbody').children().length) {
            // If the table is empty, hide the title
            $('#additional-info-container h2').hide();
        }
    }

    function hideOtherDownloadsTitle() {
        // Check if #individual-downloads has no content
        if ($('#individual-downloads').is(':empty')) {
            // If it's empty, hide the title using the specified class
            $('.other-downloads-title').hide();
        } else {
            // If it has content, show the title
            $('.other-downloads-title').show();
        }
    }

    hideOtherDownloadsTitle();
    HideAdditionalInformationTitle();
});

jQuery(document).ready(function ($) {
    // Add a click event listener to the tabs
    $('#tab-title-description, #tab-title-led_downloads').click(function () {
        // Remove the 'active' class from all tabs
        $('#tab-title-description, #tab-title-led_downloads').removeClass('active');

        // Add the 'active' class to the clicked tab
        $(this).addClass('active');
    });
});

