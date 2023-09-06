jQuery(document).ready(function ($) {
    $('#downloadable-files-form').on('submit', function (e) {
        e.preventDefault();
        console.log("triggers");
        let formData = $(this).serializeArray();

        // Send an AJAX request to led_handle_download_files
        $.ajax({
            url: vars.ajax_url + "?action=led_handle_download_files",
            dataType: "json",
            type: "POST",
            async: true,
            data: {
                selected_files: formData,
            },
            success: function (response) {
                // Create a dynamic anchor (a) element
                var downloadLink = document.createElement('a');

                // Set the href attribute to the file URL you want to download
                downloadLink.href = response.data.file_url;

                // Set the download attribute with the desired file name (optional)
                downloadLink.download = 'downloaded-file.pdf';

                // Append the anchor element to the DOM (you can hide it if needed)
                document.body.appendChild(downloadLink);

                // Trigger a click event on the anchor element
                downloadLink.click();

                // Remove the anchor element from the DOM (optional)
                document.body.removeChild(downloadLink);
            }
        });
    });
});