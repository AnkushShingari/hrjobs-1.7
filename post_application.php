<?php
ob_start(); // Start output buffering
if (!defined('ABSPATH')) {
    exit;
}
global $wpdb;
$jobs_table = $wpdb->prefix . 'jobs'; 
$jobs__application_table = $wpdb->prefix . 'job_applications';

// Handle single deletion
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['application_id'])) {
    $application_id = intval($_GET['application_id']);
    $wpdb->delete($jobs__application_table, ['id' => $application_id]);
    wp_redirect(admin_url('admin.php?page=hrjobs&tab=applications'));
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify nonce
    if (!isset($_POST['hrjobs_nonce']) || !wp_verify_nonce($_POST['hrjobs_nonce'], 'bulk_applications')) {
        wp_die(__('Security check failed.', 'hrjobs'));
    }

    // Handle exports first
    if (isset($_POST['export_csv'])) {
        hrjobs_export_csv();
    } elseif (isset($_POST['export_zip'])) {
        hrjobs_export_zip();
    }
    // Then handle bulk actions
    else {
        $bulk_action = isset($_POST['action']) ? $_POST['action'] : '';
        if ($bulk_action === 'delete') {
            $application_ids = isset($_POST['application_ids']) ? array_map('intval', $_POST['application_ids']) : [];
            if (!empty($application_ids)) {
                foreach ($application_ids as $id) {
                    $wpdb->delete($jobs__application_table, ['id' => $id]);
                }
                wp_redirect(admin_url('admin.php?page=hrjobs&tab=applications'));
                exit;
            }
        }
    }
}

// Export functions
function hrjobs_get_filtered_applications() {
    global $wpdb;
    $jobs_table = $wpdb->prefix . 'jobs';
    $jobs__application_table = $wpdb->prefix . 'job_applications';

    $selected_country = isset($_POST['filter_country']) ? sanitize_text_field($_POST['filter_country']) : '';
    $selected_job_title = isset($_POST['filter_job_title']) ? sanitize_text_field($_POST['filter_job_title']) : '';

    $where = "1=1";
    $params = array();

    if (!empty($selected_country)) {
        $where .= " AND j.country = %s";
        $params[] = $selected_country;
    }

    if (!empty($selected_job_title)) {
        $where .= " AND j.job_title = %s";
        $params[] = $selected_job_title;
    }

    $sql = "SELECT a.*, j.job_title 
            FROM $jobs__application_table AS a
            LEFT JOIN $jobs_table AS j ON a.job_id = j.id
            WHERE $where";

    if (!empty($params)) {
        $sql = $wpdb->prepare($sql, ...$params);
    }

    return $wpdb->get_results($sql);
}

function hrjobs_export_csv() {
    // Clear all output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }

    $applications = hrjobs_get_filtered_applications();

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="applications_' . date('Ymd') . '.csv"');

    $output = fopen('php://output', 'w');

    // CSV headers
    fputcsv($output, array(
        __('Job Title', 'hrjobs'),
        __('First Name', 'hrjobs'),
        __('Last Name', 'hrjobs'),
        __('Email', 'hrjobs'),
        __('Phone', 'hrjobs'),
        __('Birthday', 'hrjobs'),
        __('Applied On', 'hrjobs'),
        __('Document URL', 'hrjobs')
    ));

    // Data rows
    foreach ($applications as $app) {
        fputcsv($output, array(
            $app->job_title,
            $app->first_name,
            $app->last_name,
            $app->email,
            $app->phone,
            $app->birthday,
            $app->date_applied,
            $app->file
        ));
    }

    fclose($output);
    exit;
}

function hrjobs_export_zip() {
    // Clear all output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }

    $applications = hrjobs_get_filtered_applications();
    $files = array();

    foreach ($applications as $app) {
        if (!empty($app->file)) {
            $upload_dir = wp_upload_dir();
            // Convert URL to server path
            $file_path = str_replace(
                [$upload_dir['baseurl']], 
                [$upload_dir['basedir']], 
                $app->file
            );
            
            // Verify file exists and is readable
            if (@is_file($file_path) && @is_readable($file_path)) {
                $files[] = [
                    'path' => $file_path,
                    'name' => sanitize_file_name(basename($file_path))
                ];
            }
        }
    }

    if (empty($files)) {
        wp_die(__('No documents to export.', 'hrjobs'));
    }

    // Create temporary file in uploads directory
    $zip_filename = 'hrjobs_documents_' . date('Ymd_His') . '.zip';
    $zip_path = trailingslashit(wp_upload_dir()['basedir']) . $zip_filename;
    
    $zip = new ZipArchive();
    if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        wp_die(__('Could not create ZIP file.', 'hrjobs'));
    }

    foreach ($files as $file) {
        $zip->addFile($file['path'], $file['name']);
    }

    $zip->close();

    // Stream the file
    if (file_exists($zip_path)) {
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zip_filename . '"');
        header('Content-Length: ' . filesize($zip_path));
        readfile($zip_path);
        
        // Clean up temporary file
        unlink($zip_path);
        exit;
    }
    
    wp_die(__('Failed to generate ZIP file.', 'hrjobs'));
}

// Output HTML
?>
<h2><?php esc_html_e('Posted Applications', 'hrjobs'); ?></h2>

<div class="tab-content">
    <?php hrjobs_list_jobs($wpdb, $jobs__application_table); ?>
</div>

<?php
function hrjobs_list_jobs($wpdb, $jobs__application_table) {
    $jobs_table = $wpdb->prefix . 'jobs';

    $selected_country = isset($_POST['filter_country']) ? sanitize_text_field($_POST['filter_country']) : '';
    $selected_job_title = isset($_POST['filter_job_title']) ? sanitize_text_field($_POST['filter_job_title']) : '';

    $countries = $wpdb->get_col("SELECT DISTINCT country FROM $jobs_table WHERE country IS NOT NULL AND country != ''");
    $job_titles = $wpdb->get_col("SELECT DISTINCT job_title FROM $jobs_table WHERE job_title IS NOT NULL AND job_title != ''");

    ?>
    <form method="post">
        <?php wp_nonce_field('bulk_applications', 'hrjobs_nonce'); ?>
        <div class="tablenav top">
            <div class="alignleft actions">
                <label for="filter_country"><?php _e('Filter by Country:', 'hrjobs'); ?></label>
                <select name="filter_country" id="filter_country" style="float: unset;">
                    <option value=""><?php _e('All Countries', 'hrjobs'); ?></option>
                    <?php foreach ($countries as $country): ?>
                        <option value="<?php echo esc_attr($country); ?>" <?php selected($selected_country, $country); ?>>
                            <?php echo esc_html(ucfirst($country)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="filter_job_title"><?php _e('Filter by Job Title:', 'hrjobs'); ?></label>
                <select name="filter_job_title" id="filter_job_title" style="float: unset;">
                    <option value=""><?php _e('All Job Titles', 'hrjobs'); ?></option>
                    <?php foreach ($job_titles as $title): ?>
                        <option value="<?php echo esc_attr($title); ?>" <?php selected($selected_job_title, $title); ?>>
                            <?php echo esc_html($title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <input type="submit" name="filter_action" value="<?php _e('Filter', 'hrjobs'); ?>" class="button">
            </div>

            <div class="alignleft actions">
                <input type="submit" name="export_csv" value="<?php esc_attr_e('Export to CSV', 'hrjobs'); ?>" class="button">
                <input type="submit" name="export_zip" value="<?php esc_attr_e('Export Documents as ZIP', 'hrjobs'); ?>" class="button">
            </div>

            <div class="alignleft actions bulkactions" style="float: right;">
                <select name="action" id="bulk-action-selector-top">
                    <option value="-1"><?php _e('Bulk Actions', 'hrjobs'); ?></option>
                    <option value="delete"><?php _e('Delete', 'hrjobs'); ?></option>
                </select>
                <input type="submit" id="doaction" class="button action" value="<?php _e('Apply', 'hrjobs'); ?>">
            </div>
        </div>

        <table class="widefat">
            <thead>
                <tr>
                    <th class="check-column"><input type="checkbox" id="select_all"></th>
                    <th><?php _e('Job Title', 'hrjobs'); ?></th>
                    <th><?php _e('First Name', 'hrjobs'); ?></th>
                    <th><?php _e('Last Name', 'hrjobs'); ?></th>
                    <th><?php _e('Email', 'hrjobs'); ?></th>
                    <th><?php _e('Phone', 'hrjobs'); ?></th>
                    <th><?php _e('Birthday', 'hrjobs'); ?></th>
                    <th><?php _e('Applied On', 'hrjobs'); ?></th>
                    <th><?php _e('Document', 'hrjobs'); ?></th>
                    <th><?php _e('Action', 'hrjobs'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Build dynamic query with conditional filters
                $where = "1=1";
                $params = [];

                if (!empty($selected_country)) {
                    $where .= " AND j.country = %s";
                    $params[] = $selected_country;
                }

                if (!empty($selected_job_title)) {
                    $where .= " AND j.job_title = %s";
                    $params[] = $selected_job_title;
                }

                $query = $wpdb->prepare(
                    "SELECT a.*, j.job_title 
                     FROM $jobs__application_table AS a
                     LEFT JOIN $jobs_table AS j ON a.job_id = j.id
                     WHERE $where",
                     ...$params
                );

                $applications = $wpdb->get_results($query);
                echo '<p><strong>Total Applications Found:</strong> ' . count($applications) . '</p>';

                if (!empty($applications)) :
                    foreach ($applications as $application) : ?>
                        <tr>
                            <td><input type="checkbox" name="application_ids[]" value="<?php echo esc_attr($application->id); ?>"></td>
                            <td><?php echo esc_html($application->job_title ?? __('N/A', 'hrjobs')); ?></td>
                            <td><?php echo esc_html($application->first_name ?? ''); ?></td>
                            <td><?php echo esc_html($application->last_name ?? ''); ?></td>
                            <td><?php echo esc_html($application->email ?? ''); ?></td>
                            <td><?php echo esc_html($application->phone ?? ''); ?></td>
                            <td><?php echo esc_html($application->birthday ?? ''); ?></td>
                            <td><?php echo esc_html($application->date_applied ?? ''); ?></td>
                            <td>
                                <?php if (!empty($application->file)) : ?>
                                    <a href="<?php echo esc_url($application->file); ?>" target="_blank">
                                        <?php _e('Download', 'hrjobs'); ?>
                                    </a>
                                <?php else : ?>
                                    <?php _e('No file', 'hrjobs'); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="?page=hrjobs&tab=applications&action=delete&application_id=<?php echo esc_attr($application->id); ?>" 
                                   onclick="return confirm('<?php _e('Are you sure?', 'hrjobs'); ?>')">
                                    <?php _e('Delete', 'hrjobs'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach;
                else : ?>
                    <tr>
                        <td colspan="10" style="text-align: center;">
                            <?php _e('No applications found.', 'hrjobs'); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </form>

    <script>
        document.getElementById('select_all').addEventListener('change', function(e) {
            var checkboxes = document.querySelectorAll('input[name="application_ids[]"]');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = e.target.checked;
            });
        });
    </script>
    <?php
}

ob_end_flush();
?>
