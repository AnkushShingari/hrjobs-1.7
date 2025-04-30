<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$jobs_table = $wpdb->prefix . 'jobs'; // Jobs table name

// Handle tab switching
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'all_jobs';



// Handle delete job

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['job_id'])) {

    $job_id = intval($_GET['job_id']);

    $wpdb->delete($jobs_table, ['id' => $job_id]);

    echo '<div class="updated"><p>Job deleted successfully.</p></div>';

}



// Handle the country filter

$filter_country = isset($_POST['filter_country']) ? sanitize_text_field($_POST['filter_country']) : '';



// Get all distinct countries for filter

$countries = $wpdb->get_col("SELECT DISTINCT country FROM $jobs_table");



?>

<h2><?php esc_html_e('Job Section', 'hrjobs'); ?></h2>



<h2 class="nav-tab-wrapper">

    <a href="?page=hrjobs&tab=all_jobs" class="nav-tab <?php echo $current_tab == 'all_jobs' ? 'nav-tab-active' : ''; ?>">All Jobs</a>

    <a href="?page=hrjobs&tab=add_job" class="nav-tab <?php echo $current_tab == 'add_job' ? 'nav-tab-active' : ''; ?>">Add Job</a>

    <a href="?page=hrjobs&tab=open_jobs" class="nav-tab <?php echo $current_tab == 'open_jobs' ? 'nav-tab-active' : ''; ?>">Open Jobs</a>

    <a href="?page=hrjobs&tab=closed_jobs" class="nav-tab <?php echo $current_tab == 'closed_jobs' ? 'nav-tab-active' : ''; ?>">Closed Jobs</a>

</h2>



<div class="tab-content">

    <?php

    switch ($current_tab) {

        case 'add_job':

            hrjobs_add_job_form($wpdb, $jobs_table);

            break;

        case 'open_jobs':

            hrjobs_list_jobs($wpdb, $jobs_table, 1, $filter_country);

            break;

        case 'closed_jobs':

            hrjobs_list_jobs($wpdb, $jobs_table, 0, $filter_country);

            break;

        case 'all_jobs':

        default:

            hrjobs_list_jobs($wpdb, $jobs_table, null, $filter_country);

            break;

    }

    ?>

</div>



<?php



// Function to display the "Add Job" form

function hrjobs_add_job_form($wpdb, $jobs_table)

{

    if (isset($_POST['submit_job'])) {

        $data = [

            'job_title' => sanitize_text_field($_POST['job_title']),

            'location' => sanitize_text_field($_POST['location']),

            'country' => sanitize_text_field($_POST['country']),

            'tagline' => sanitize_text_field($_POST['tagline']),

            'job_brief' => wp_kses_post($_POST['job_brief']),

            'responsibilities' => wp_kses_post($_POST['responsibilities']),

            'requirement' => wp_kses_post($_POST['requirement']),

            'date_of_application' => current_time('mysql'),

            'status' => 1

        ];

        $wpdb->insert($jobs_table, $data);

        echo '<div class="updated"><p>Job added successfully.</p></div>';

    }

?>


<form method="POST">
    <table class="form-table">
        <tr>
            <th><label for="job_title">Job Title</label></th>
            <td><input type="text" id="job_title" name="job_title" required></td>
        </tr>

        <tr>
            <th><label for="location">Location</label></th>
            <td><input type="text" id="location" name="location" required></td>
        </tr>

        <tr>
            <th><label for="country">Country</label></th>
            <td><input type="text" id="country" name="country" required></td>
        </tr>

        <tr>
            <th><label for="tagline">Tagline</label></th>
            <td><input type="text" id="tagline" name="tagline"></td>
        </tr>

        <tr>
            <th><label for="job_brief">Job Brief</label></th>
            <td>
            <?php
                // Apply the WordPress editor to the job_brief field
                wp_editor('', 'job_brief', [
                    'textarea_name' => 'job_brief',
                    'textarea_rows' => 6,  // You can adjust the number of rows if needed
                    'editor_class' => 'wp-editor-area',  // This is the class for WordPress editor
                    'media_buttons' => false, // Hide the media buttons
                    'quicktags' => true,  // Enable quick tags for basic formatting
                ]);
                ?>
            </td>
        </tr>

        <tr>
            <th><label for="responsibilities">Responsibilities</label></th>
            <td>
                <?php 
                wp_editor('', 'responsibilities', [
                    'textarea_name' => 'responsibilities',
                    'textarea_rows' => 6,
                    'editor_class' => 'wp-editor-area',
                    'media_buttons' => false,
                    'quicktags' => true,
                ]);
                ?>
            </td>
        </tr>

        <tr>
            <th><label for="requirement">Requirement</label></th>
            <td>
                <?php
                // Apply the WordPress editor to the requirement field
                wp_editor('', 'requirement', [
                    'textarea_name' => 'requirement',
                    'textarea_rows' => 6,
                    'editor_class' => 'wp-editor-area',
                    'media_buttons' => false,
                    'quicktags' => true,
                ]);
                ?>
            </td>
        </tr>
    </table>

    <p><input type="submit" name="submit_job" value="Add Job" class="button button-primary"></p>
</form>


<?php

}

// All Jobs and Filters
function hrjobs_list_jobs($wpdb, $jobs_table, $status = null, $filter_country = '') {
    // Capture selected country from form
    $filter_country = isset($_POST['filter_country']) ? sanitize_text_field($_POST['filter_country']) : '';

    // Get all distinct countries for the dropdown
    $countries = $wpdb->get_col("SELECT DISTINCT country FROM $jobs_table WHERE country IS NOT NULL AND country != ''");

    // Country filter form
    ?>
    <form method="POST" style="margin: 20px 0px;">
        <label for="filter_country"><strong>Filter by country:</strong></label>
        <select name="filter_country" id="filter_country">
            <option value="">All countries</option>
            <?php
            if (!empty($countries)) {
                foreach ($countries as $country) {
                    $selected = ($filter_country == $country) ? 'selected' : '';
                    echo '<option value="' . esc_attr($country) . '" ' . $selected . '>' . esc_html($country) . '</option>';
                }
            } else {
                echo '<option value="">No country found.</option>';
            }
            ?>
        </select>
        <input type="submit" value="Filter" class="button">
    </form>
    <?php

    // Build the query
    $query = "SELECT * FROM $jobs_table WHERE 1=1";

    // Add status condition
    if ($status !== null) {
        $query .= $wpdb->prepare(" AND status = %d", $status);
    }

    // Add country filter condition
    if (!empty($filter_country)) {
        $query .= $wpdb->prepare(" AND country = %s", $filter_country);
    }

    $jobs = $wpdb->get_results($query);

    echo '<p><strong>Jobs Found: </strong>' . count($jobs) . '</p>';

    if ($jobs) {
        ?>
        <table class="widefat">
            <thead>
                <tr>
                    <th>Job Title</th>
                    <th>Location</th>
                    <th>Country</th>
                    <th>Date of Application</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($jobs as $job) { ?>
                    <tr>
                        <td><?php echo esc_html($job->job_title); ?></td>
                        <td><?php echo esc_html($job->location); ?></td>
                        <td><?php echo esc_html($job->country); ?></td>
                        <td><?php echo esc_html($job->date_of_application); ?></td>
                        <td><?php echo $job->status ? 'Open' : 'Closed'; ?></td>
                        <td>
                            <a href="?page=hrjobs&tab=view_job&job_id=<?php echo $job->id; ?>">View</a> |
                            <a href="?page=hrjobs&tab=edit_job&job_id=<?php echo $job->id; ?>">Edit</a> |
                            <a href="?page=hrjobs&tab=all_jobs&action=delete&job_id=<?php echo $job->id; ?>" onclick="return confirm('Are you sure you want to delete this job?');">Delete</a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        <?php
    } else {
        echo '<p>No jobs found.</p>';
    }
}
