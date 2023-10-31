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
    wp_enqueue_style('novaflex-style', plugins_url('/assets/css/novaflex-style.css', __FILE__));
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

function led_new_product_tab_content() {
    global $product;

    // Retrieve and display the PDF files associated with the product
    $pdf_files = get_post_meta($product->get_id(), 'downloadable-files', true);

    // Retrieve and display the PDF files associated with the product in the "Other Downloads" section
    $other_files = get_post_meta($product->get_id(), 'other-downloads', true);


    if (empty($pdf_files) && empty($other_files)) {
        
    } else {
        // Output the title in bold
        echo '<h2 style="margin-bottom: 5px;"><strong>' . __('Documents', 'woocommerce') . '</strong></h2>';

        // Output the additional text below the title
        if (!empty($pdf_files)) {
            echo '<p style="font-weight: bold; font-size: smaller; margin-top: 0;">Select which sections you would like to be merged into one PDF.</p>';
        }

        
        // Output the form with checkboxes
        echo '<form action="" id="downloadable-files-form" method="post">';
        if ($pdf_files) {
            foreach ($pdf_files as $pdf_file_url) {
                echo '<label>';
                echo '<input type="checkbox" id="cb1" name="selected_files[]" value="' . esc_attr($pdf_file_url['file-url']) . '">';
                echo '<span class="checkbox-tooltip">Select for download</span>'; // Add the "Select for download" tooltip for the checkbox
                echo '<a href="' . esc_attr($pdf_file_url['file-url']) . '" target="_blank">';
                echo '<span class="checkbox-text">' . esc_html(basename($pdf_file_url['name'])) . '</span>';
                echo '<span class="tooltip">Preview File</span></a>'; // Add the "Preview File" tooltip for the text
                echo '</label>';
                echo '<br>';
            }
        }

        // Output the download button only when there are PDF files and loading spinner 
    if ($pdf_files) {
        echo '<div id="loading-spinner" class="hidden">';
        echo '    <div class="spinner"></div>';
        echo '    <div class="loading-text">Merging PDFs...</div>';
        echo '</div>';

        echo '<button id="download-button" type="submit" name="download_files">' . __('Download', 'woocommerce') . '</button>';
    }
    echo '</form><div id="download-link-container"></div>';
}

    // Output the "Other Downloads" area
    echo '<h2 style="margin-top: 20px;"><strong style="font-size: 20px;">' . __('Other Downloads', 'woocommerce') . '</strong></h2>';
    echo '<div id="individual-downloads">';

    /* *********************** OTHER DOWNLOAD SECTION   *********************** */

    // Output individual download buttons for each PDF
    if ($other_files) {
        foreach ($other_files as $pdf_file_url) {
            echo '<a class="other-download-button" href="' . esc_attr($pdf_file_url['file-url']) . '" target="_blank" download>' . esc_html(basename($pdf_file_url['name'])) . '</a><br>';
        }
    }    

    echo '</div>';
    
    echo '</div>';
    
    // Output the "Additional Information" content manually
    echo '<div id="additional-info-container">';
    echo '<h2 style="margin-top: 20px;"><strong style="font-size: 20px;">' . __('Additional Information', 'woocommerce') . '</strong></h2>';
    echo '<table class="woocommerce-product-attributes">';
    
    // Define the list of attribute names to display
    $attribute_names = array(
        'LED\'s/M',
        'Binning',
        'Wattage',
        'Environment',
        'Color Temperature'
    );

    foreach ($attribute_names as $attribute_name) {
        $attribute_value = $product->get_attribute($attribute_name);

        if (!empty($attribute_value)) {
            echo '<tr class="woocommerce-product-attributes-item">';
            echo '<th>' . esc_html($attribute_name) . '</th>';
            echo '<td>' . wp_kses_post($attribute_value) . '</td>';
            echo '</tr>';
        }
    }

    echo '</table>';
    echo '</div>';
}


add_action('wp_ajax_led_handle_download_files', 'led_handle_download_files');
add_action('wp_ajax_nopriv_led_handle_download_files', 'led_handle_download_files');

function led_handle_download_files()
{
    if (isset($_POST['selected_files']) && is_array($_POST['selected_files']) && !empty($_POST['selected_files'])) {
      
        
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

        global $product;

        $label = 'files';

        if(!empty($_POST['label_text'])) {
            $label = $_POST['label_text'];
        }

        // Specify the destination path for the merged PDF
        $destination_filename = wp_unique_filename($destination_dir, $_POST['label_text'] . '.pdf');
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
        
    } else {
        echo '<script>';
        echo 'console.log("Please select files to download");'; // Log message to the console
        echo '</script>';      
    }

    wp_send_json_success("none");
}


// Function to change the variations checkbox text in product data
function custom_change_used_for_variations_text($translated_text, $text, $domain) {
    if ($text === 'Used for variations') {
        $translated_text = 'Used for Sku'; // Change the text here
    }
    return $translated_text;
}

// Hook to modify the text
function custom_modify_woocommerce_strings() {
    add_filter('gettext', 'custom_change_used_for_variations_text', 20, 3);
}

add_action('admin_init', 'custom_modify_woocommerce_strings');

add_filter('woocommerce_product_tabs', 'remove_additional_information_tab', 98);

function remove_additional_information_tab($tabs) {
    unset($tabs['additional_information']);
    return $tabs;
}

function my_added_page_content () {
    global $product, $wp;
    $current_url = home_url($wp->request);
    if(($current_url === home_url() || str_contains($current_url, 'resources')) && $product !== null) {
        echo get_download_files_content($product);
    }
}
add_action( 'resources_files', 'my_added_page_content');

function get_download_files_content($product) {
    // Retrieve and display the PDF files associated with the product
    $pdf_files = get_post_meta($product->get_id(), 'downloadable-files', true);
    $other_files = get_post_meta($product->get_id(), 'other-downloads', true);

    $product_html =  '';

    if (!empty($pdf_files) || !empty($other_files)) {
        $product_html .= '<div class="download-wrapper" style="display: flex; flex-wrap: wrap; justify-content: center; max-width: 300px;">';

        $buttonCounter = 1; // Counter for unique IDs

        if (!empty($pdf_files)) {
            foreach ($pdf_files as $pdf_file_url) {
                $uniqueButtonID = 'button' . $buttonCounter; // Generate a unique ID for each button
                $product_html .= '<a id="' . $uniqueButtonID . '" class="download-button-style" href="' . esc_attr($pdf_file_url['file-url']) . '" target="_blank">';
                $product_html .= esc_html(basename($pdf_file_url['name']));
                $product_html .= '</a><br>';
                $buttonCounter++; // Increment the counter for the next button
            }
        }

        if (!empty($other_files)) {
            foreach ($other_files as $pdf_file_url) {
                $uniqueButtonID = 'button' . $buttonCounter; // Generate a unique ID for each button
                $product_html .= '<a id="' . $uniqueButtonID . '" class="download-button-style" href="' . esc_attr($pdf_file_url['file-url']) . '" target="_blank">';
                $product_html .= esc_html(basename($pdf_file_url['name']));
                $product_html .= '</a><br>';
                $buttonCounter++; // Increment the counter for the next button
            }
        }

        $product_html .= '</div>';
    }

    return $product_html;
}


