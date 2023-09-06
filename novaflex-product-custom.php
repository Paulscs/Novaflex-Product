<?php
/*
Plugin Name:  Novaflex Product Custom
Plugin URI:   https://novaflexled.com
Description:  This plugin is used for the customization of the features of website
Version:      1.0
Author:       Calderon
Author URI:   https://novaflexled.com
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  novaflexled
Domain Path:  /languages
*/

/**
 * Add a custom product data tab
 */

include_once(__DIR__ . '/PDFMerger/PDFMerger.php');


function random_asset_version()
{
    // Generate a random number or timestamp as the version
    $version = rand(1000, 9999); // Adjust the range as needed

    return $version;
}

// Define a function to enqueue your JS file
function enqueue_custom_script()
{
    // Enqueue your JS file
    wp_enqueue_script('novaflex-script', plugins_url('/assets/js/novaflex-script.js', __FILE__), array('jquery'), random_asset_version(), true);
    wp_localize_script('novaflex-script', 'vars', [
        'ajax_url' => admin_url('admin-ajax.php'),
    ]);
}

// Hook the enqueue function to the 'wp_enqueue_scripts' action
add_action('wp_enqueue_scripts', 'enqueue_custom_script');

add_filter('woocommerce_product_tabs', 'led_new_product_tab');
function led_new_product_tab($tabs)
{

    // Adds the new tab

    $tabs['led_downloads'] = array(
        'title' => __('Downloads', 'woocommerce'),
        'priority' => 50,
        'callback' => 'led_new_product_tab_content'
    );

    return $tabs;
}

function led_new_product_tab_content()
{
    global $product;

    // Retrieve and display the PDF files associated with the product
    $pdf_files = get_post_meta($product->get_id(), 'downloadable-files', true);

    if (empty($pdf_files)) {
        echo "<p>No Downloadable Files</p>";
        return;
    }

    // Output the title in bold
    echo '<h2><strong>' . __('Downloads', 'woocommerce') . '</strong></h2>';

    // Output the form with checkboxes
    echo '<form action="" id="downloadable-files-form" method="post">';

    // Retrieve and display the PDF files associated with the product
    $pdf_files = get_post_meta($product->get_id(), 'downloadable-files', true); // Replace with the actual key 'downloadable-files'`
    if ($pdf_files) {
        foreach ($pdf_files as $pdf_file_url) {
            echo '<label><input type="checkbox" name="selected_files[]" value="' . esc_attr($pdf_file_url['file-url']) . '"> ' . esc_html(basename($pdf_file_url['name'])) . '</label><br>';
        }
    }

    // Output the download button
    echo '<button type="submit" name="download_files">' . __('Download Files', 'woocommerce') . '</button>';
    echo '</form><div id="download-link-container"></div>';
}

add_action('wp_ajax_led_handle_download_files', 'led_handle_download_files');
add_action('wp_ajax_nopriv_led_handle_download_files', 'led_handle_download_files');

function led_handle_download_files()
{
    if (isset($_POST['selected_files']) && is_array($_POST['selected_files'])) {

        // Initialize PDFMerger
        $pdf = new PDFMerger\PDFMerger;

        foreach ($_POST['selected_files'] as $file_url) {

            // Generate a temporary file name to save the downloaded PDF
            $temp_file = tempnam(sys_get_temp_dir(), 'temp_pdf_');

            // Download the PDF from the URL and save it to the temporary file
            $pdf_content = file_get_contents($file_url['value']);

            if ($pdf_content !== false) {
                file_put_contents($temp_file, $pdf_content);

                // Add the downloaded PDF to the merger
                $pdf->addPDF($temp_file);

                // Optionally, you can delete the temporary file to clean up
                // unlink($temp_file);
            }
        }

        // Specify the destination directory for the merged PDFs within the uploads folder
        $upload_dir = wp_upload_dir();
        $destination_dir = $upload_dir['basedir'] . '/merged_pdfs';

        // Create the destination directory if it doesn't exist
        wp_mkdir_p($destination_dir);

        // Specify the destination path for the merged PDF
        $destination_filename = wp_unique_filename($destination_dir, 'merged.pdf');
        $destination_path = $destination_dir . '/' . $destination_filename;

        // Merge the PDFs
        $pdf->merge('file', $destination_path);

        // Now, you have the merged PDF saved in the 'wp-content/uploads/merged_pdfs' directory
        // Get the URL of the merged PDF
        $merged_pdf_url = $upload_dir['baseurl'] . '/merged_pdfs/' . $destination_filename;

        // Prepare the response data
        $response_data = array(
            'message' => 'PDF merge successful',
            'file_url' => $merged_pdf_url,
        );

        // Send the JSON response with the file URL
        wp_send_json_success($response_data);
    }

    wp_send_json_success("none");
}