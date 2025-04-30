<?php
/*
Plugin Name: HR Jobs
Description: A plugin to manage job applications and job postings.
Version: 1.7
Author: AnkushK2022
*/

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Activation hook to create tables and add menu.
register_activation_hook(__FILE__, 'hrjobs_create_tables');

function hrjobs_create_tables(){
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    $table_applications = $wpdb->prefix . 'job_applications';
	
    $sql_applications = "CREATE TABLE IF NOT EXISTS $table_applications (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        job_id bigint(20) UNSIGNED NOT NULL,
        first_name varchar(255) NOT NULL,
        last_name varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        phone bigint(20) UNSIGNED NOT NULL,
        birthday date NOT NULL,
        file varchar(255) NOT NULL,
        date_applied datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

// Table name for jobs

$table_jobs = $wpdb->prefix . 'jobs';

$sql_jobs = "CREATE TABLE IF NOT EXISTS $table_jobs (

    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    job_title varchar(255) NOT NULL,
    location varchar(255) NOT NULL,
    country varchar(255) NOT NULL,
    tagline varchar(255),
    job_brief longtext NOT NULL,
    responsibilities longtext NOT NULL,
    requirement longtext NOT NULL,
    status tinyint(1) NOT NULL DEFAULT 1,  /* 1 as open | 0 as closed */
    PRIMARY KEY (id)

) $charset_collate;";
	
	// SQL for jobs_mail_addresses table
	
    $table_jobs_mail_addresses = $wpdb->prefix . 'jobs_mail_addresses';
	
    $sql_jobs_mail_addresses = "CREATE TABLE $table_jobs_mail_addresses (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        jobs_mail_address varchar(255) NOT NULL,  -- assuming a varchar for the mail address
        PRIMARY KEY (id)
    ) $charset_collate;";

    $wpdb->query("ALTER TABLE $table_applications ADD COLUMN date_applied datetime NOT NULL DEFAULT CURRENT_TIMESTAMP");

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    dbDelta($sql_applications);
    dbDelta($sql_jobs_mail_addresses);
    dbDelta($sql_jobs);
}



// Deactivation hook to delete tables.
// register_deactivation_hook(__FILE__, 'hrjobs_delete_tables');

// function hrjobs_delete_tables()
// {
//    global $wpdb;

//     // Table names for job applications and jobs.
//     $table_applications = $wpdb->prefix . 'job_applications';
//     $table_jobs = $wpdb->prefix . 'jobs';
// 	   $table_jobs_mail_addresses = $wpdb->prefix . 'jobs_mail_addresses';

//     // Drop tables if they exist.
//     $sql = "DROP TABLE IF EXISTS $table_applications, $table_jobs, $table_jobs_mail_addresses;";
//     $wpdb->query($sql);
// }


// Add a menu in the admin panel

function hrjobs_menu() {
    if ( current_user_can('manage_options') || current_user_can('access_hr_jobs') ) {
        add_menu_page(
            'HR Jobs',                    // Page title
            'HR Jobs',                    // Menu title
            'read',                       // Placeholder capability
            'hrjobs',                     // Menu slug
            'hrjobs_page_content',        // Callback function
            'dashicons-clipboard',        // Icon
            6                             // Position
        );
    }
}
add_action('admin_menu', 'hrjobs_menu');


// Displaying the admin page content with tabs

function hrjobs_page_content(){
    if ( ! current_user_can('manage_options') && ! current_user_can('access_hr_jobs') ) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    ?>

    <div class="wrap">
        <h1><?php esc_html_e('HR Jobs', 'hrjobs'); ?></h1>

        <h2 class="nav-tab-wrapper">
            <a href="?page=hrjobs&tab=jobs" class="nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] == 'jobs' ? 'nav-tab-active' : ''; ?>">
                Job Section
            </a>
            <a href="?page=hrjobs&tab=applications" class="nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] == 'applications' ? 'nav-tab-active' : ''; ?>">
                Posted Applications
            </a>
            <a href="?page=hrjobs&tab=addresse" class="nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] == 'addresse' ? 'nav-tab-active' : ''; ?>">
                Email Addresse
            </a>
        </h2>

        <div class="tab-content">
            <?php
            $current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'jobs';

            // Switch based on the tab
            switch ($current_tab) {
                case 'applications':
                    hrjobs_posted_applications_content();
                    break;

				case 'addresse':
						hrjobs_adresses_content();
					break;

                case 'view_job':
                    if (isset($_GET['job_id'])) {
                        hrjobs_view_job_content($_GET['job_id']);
                    }
                    break;

                case 'edit_job':
                    if (isset($_GET['job_id'])) {
                        hrjobs_edit_job_content($_GET['job_id']);
                    }
                    break;

                case 'jobs':
                default:
                    hrjobs_job_section_content();
                    break;
            }
            ?>
        </div>
    </div>
    <?php
}

// Content for the Job Section tab

function hrjobs_job_section_content()
{
    // Include the job_section.php file
    include plugin_dir_path(__FILE__) . 'job_section.php';
}


// Content for the Posted Applications tab
function hrjobs_posted_applications_content()
{
    // Include the post_application.php file
    include plugin_dir_path(__FILE__) . 'post_application.php';
}


function hrjobs_view_job_content($job_id)
{
    global $wpdb;
    $table_jobs = $wpdb->prefix . 'jobs';

    // Fetch job data from the database
    $job = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_jobs WHERE id = %d", $job_id));

    if ($job) {
    ?>

        <a href="?page=hrjobs&tab=jobs" class="button">Back to Job List</a>
        <h2><?php echo esc_html($job->job_title); ?></h2>
        <p><strong>Location:</strong> <?php echo esc_html($job->location); ?></p>
        <p><strong>Country:</strong> <?php echo esc_html($job->country); ?></p>
        <p><strong>Tagline:</strong> <?php echo esc_html($job->tagline); ?></p>
        <p><strong>Job Brief:</strong> <?php echo wp_kses_post($job->job_brief); ?></p>
        <p><strong>Responsibilities:</strong> <?php echo wp_kses_post($job->responsibilities); ?></p>
        <p><strong>Requirement:</strong> <?php echo wp_kses_post($job->requirement); ?></p>
        <p><strong>Date of Application:</strong> <?php echo esc_html($job->date_of_application); ?></p>
        <p><strong>Status:</strong> <?php echo $job->status ? 'Open' : 'Closed'; ?></p>
    <?php

    } else {
        echo '<p>Job not found.</p>';
    }
}


function hrjobs_edit_job_content($job_id)
{
    global $wpdb;
    $table_jobs = $wpdb->prefix . 'jobs';

    // Fetch job data
    $job = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_jobs WHERE id = %d", $job_id));

    if ($job) {

        // Form to update the job details
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_job'])) {

            $job_title = sanitize_text_field($_POST['job_title']);
            $location = sanitize_text_field($_POST['location']);
            $country = sanitize_text_field($_POST['country']);
            $tagline = sanitize_text_field($_POST['tagline']);
            $job_brief = wp_kses_post($_POST['job_brief']);
            $responsibilities = wp_kses_post($_POST['responsibilities']);
            $requirement = wp_kses_post($_POST['requirement']);
            
            $status = isset($_POST['status']) ? 1 : 0;

            // Update job in the database
            $wpdb->update(
                $table_jobs,
                [
                    'job_title' => $job_title,
                    'location' => $location,
                    'country' => $country,
                    'tagline' => $tagline,
                    'job_brief' => $job_brief,
                    'responsibilities' => $responsibilities,
                    'requirement' => $requirement,
                    'status' => $status,
                ],
                ['id' => $job_id]
            );

            echo '<p>Job updated successfully!</p>';
        }

        ?>

        <a href="?page=hrjobs&tab=jobs" class="button">Back to Job List</a>

        <form method="post">
            <h2>Edit Job</h2>

            <label for="job_title">Job Title:</label>
            <input type="text" name="job_title" value="<?php echo esc_attr($job->job_title); ?>" required><br>

            <label for="location">Location:</label>
            <input type="text" name="location" value="<?php echo esc_attr($job->location); ?>" required><br>

            <label for="country">Country:</label>
            <input type="text" name="country" value="<?php echo esc_attr($job->country); ?>" required><br>

            <label for="tagline">Tagline:</label>
            <input type="text" name="tagline" value="<?php echo esc_attr($job->tagline); ?>" required><br>

            <label for="job_brief">Job Brief:</label>
            <?php
            // Apply the WordPress editor to the job_brief field
            wp_editor($job->job_brief, 'job_brief', [
                'textarea_name' => 'job_brief',
                'textarea_rows' => 6,  // You can adjust the number of rows if needed
                'editor_class' => 'wp-editor-area',  // This is the class for WordPress editor
                'media_buttons' => false, // Hide the media buttons
                'quicktags' => true,  // Enable quick tags for basic formatting
            ]);
            ?>
            <br>

            <label for="responsibilities">Responsibilities:</label>
            <?php
            // Apply the WordPress editor to the responsibilities field
            wp_editor($job->responsibilities, 'responsibilities', [
                'textarea_name' => 'responsibilities',
                'textarea_rows' => 6,
                'editor_class' => 'wp-editor-area',
                'media_buttons' => false,
                'quicktags' => true,
            ]);
            ?>
            <br>

            <label for="requirement">Requirement:</label>
            <?php
            // Apply the WordPress editor to the requirement field
            wp_editor($job->requirement, 'requirement', [
                'textarea_name' => 'requirement',
                'textarea_rows' => 6,
                'editor_class' => 'wp-editor-area',
                'media_buttons' => false,
                'quicktags' => true,
            ]);
            ?>
            <br>

            <label for="status">Status:</label>
            <input type="checkbox" name="status" value="1" <?php checked($job->status, 1); ?>> Open<br>

            <input type="submit" name="update_job" value="Update Job">
        </form>

        <?php
    } else {
        echo '<p>Job not found.</p>';
    }
}



function hrjobs_adresses_content() {  
    global $wpdb;  

    $table_addresses = $wpdb->prefix . 'jobs_mail_addresses';
    
    // Fetch addresses data 
    $addresses = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_addresses WHERE id = %d", 1));  

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_address'])) {  
        $jobs_mail_address = sanitize_text_field($_POST['jobs_mail_address']);  
        
        // Validate if the provided email is valid
        if (!is_email($jobs_mail_address)) {
            echo '<p>Please enter a valid email address!</p>';
        } else {
            if ($addresses) {
                // Update existing address
                $updated = $wpdb->update(  
                    $table_addresses,  
                    [  
                        'jobs_mail_address' => $jobs_mail_address,  
                    ],  
                    ['id' => 1]  
                );

                if ($updated === false) {
                    echo '<p>Error updating email address: ' . $wpdb->last_error . '</p>';
                } else {
                    echo '<p>E-mail Address updated successfully!</p>';
                }
            } else {
                // Insert new address (without specifying id since it may be auto-increment)
                $inserted = $wpdb->insert(  
                    $table_addresses,  
                    [  
                        'jobs_mail_address' => $jobs_mail_address,  
                    ]  
                );

                if ($inserted === false) {
                    echo '<p>Error inserting email address: ' . $wpdb->last_error . '</p>';
                } else {
                    echo '<p>New E-mail Address added successfully!</p>';
                }
            }
        
            // Re-fetch addresses data after update or insert
            $addresses = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_addresses WHERE id = %d", 1));  
        }  
    }  

    ?>  

    <a href="?page=hrjobs&tab=jobs" class="button">Back to Job List</a>  

    <form method="post">  
        <h2>Edit E-mail Address</h2>  
        
        <label for="jobs_mail_address">E-mail Address:</label>  
        <input type="text" name="jobs_mail_address" value="<?php echo isset($addresses->jobs_mail_address) ? esc_attr($addresses->jobs_mail_address) : ''; ?>" required><br/><br/>

        <input type="submit" name="update_address" value="Update Address">  
    </form>  

    <?php  
}
 
		



function custom_search_shortcode()

{

    ob_start();

    ?>

    <style>

.job-container {
    display: flex;
    gap: 20px;
    padding: 20px 0;
    color: #fff !important;
    flex-wrap: wrap;
}
.job-container h4{
    color: #fff !important;
}
.job-list {
    flex: 3;
    background-color: #2c2520;
    padding: 20px;
    border-radius: 8px;
}

.job-item {
    margin-bottom: 15px;
}

.accordion {
    background-color: #2c2520;
    color: #fff;
    cursor: pointer;
    padding: 15px !important;
    width: 100%;
    text-align: left;
    border: none;
    outline: none;
    font-size: 16px;
    transition: 0.4s;
    border-bottom: 1px solid #707070 !important; 
    border-radius:0 !important;
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
}
.accordion:hover{
    background-color: transparent;
}

.title,.unit{
    margin: 0;
}
.profile .title{
    font-size: 35px;
    color: #e59163;
    margin-bottom:15px;
}
.accordion p{
    margin-bottom: 15px;
}
.accordion .profile, .accordion .apply{
    display: flex;
    flex-direction: column;
}
.accordion img{
    width: 25px;
    margin-right: 8px;
}
.accordion .profile p, .accordion .apply p {
    display: flex;
    align-items: center;
    font-size: 20px;
    font-weight: 600;
}
.accordion .apply .date{
    font-size:16px;
}
.accordion:hover{
    color: #fff !important;
}
.accordion .apply .btn-form{
    position: relative !important;
    justify-content: end;
    margin-top:35px;
}
.accordion .apply .btn{
    font-size: 16px;
    font-weight: normal;
    text-transform: uppercase;
    color: #fff;
    text-decoration: none;
    padding: 13px 20px;
    margin-right: 5px;
}
.accordion .apply .btn:before {
    content: '';
    background-color: #E59163;
    position: absolute;
    width: 75%;
    height: 1px;
    right: 0;
    top: 0;
}
.accordion .apply .btn:after {
    content: '';
    background-color: #E59163;
    position: absolute;
    width: 75%;
    height: 1px;
    right: 10px;
    bottom: 1px;
}
.panel {
    display: none;
    background-color: #2c2520;
    padding: 15px;
    padding-bottom: 40px;
    border-bottom: 1px solid #707070 !important; 
}

.accordion.active{
   border-bottom:0 !important;
}
.panel table td {
    box-shadow: inset 0px 0px 1px 0px rgb(255 255 255) !important;
}

.apply-form {
    display: flex;
    gap: 10px;
    justify-content: space-between;
    flex-wrap: wrap;
}

.apply-form div,
.apply-form button {
    padding: 10px;
    border: none;
    border-radius: 5px;
    width: 45%;
}

.apply-form .input-box{
    display: flex;
    flex-direction: column-reverse;
}

.apply-form .input-box input{
    background-color: transparent;
    border: 0;
    border-bottom: 1px solid #916b53;
    margin-bottom: 12px;
    color: #fff;
    width:100%;
}
.apply-form .input-box input[type="file"]{border: 0;}
.apply-form .input-box input:focus-visible{
    outline: 0;
}

.apply-form button {
    background-color: #916b53;
    color: #fff;
    cursor: pointer;
    display: inline-block;
    width: auto;
    padding: 15px 30px;
    border-radius: 0;
    font-size: 24px;
    margin-right:30px;
}

.location-filter {
    flex: 1;
    background-color: #2c2520;
    padding: 20px;
    border-radius: 8px;
    height:100%;
}

.location-filter h3 {
    margin-bottom: 10px;
}

.location-filter label {
    display: block;
    margin-bottom: 10px;
}
.location-filter label:last-child{
    border-bottom: 1px solid #fff;
    padding-bottom: 30px;
}
.checkbox a {
    text-decoration: underline;
    color: #fff;
    line-height: 28px;
}
input[type="file"]::file-selector-button {
    background-color: #916b53;
    color: #fff;
    font-size: 16px;
    padding: 6px 10px;
    border: 0;
    cursor: pointer;
}
.date #birthday::-webkit-calendar-picker-indicator {
    filter: invert(1);
    font-size: 20px;
}
.checkbox input[type="checkbox"] {
    appearance: none; 
    background-color: transparent; 
    border: 2px solid white;
    width: 20px;
    height: 20px;
    cursor: pointer;
    position: relative; 
    top:5px;
    display: inline-block;
}

.checkbox input[type="checkbox"]:checked {
    background-color: transparent;
}

.checkbox input[type="checkbox"]:checked::after {
    content: '';
    position: absolute;
    top: 2px;
    left: 5px;
    width: 6px;
    height: 12px;
    border: solid white;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
}
@media(min-width:768px) and (max-width:1024px){
    .job-list{
        flex: 2;
    }
    .accordion{
        padding: 0 15px !important;
    }
    .apply-form div, .apply-form button{
        width: 100%;
    }
    .apply-form button{
        margin-right: 0;
    }
}
@media(max-width:767px){
    .accordion{
        padding:0 !important;
    }
    .job-container{
        flex-direction: column-reverse;
    }
    .apply-form div, .apply-form button{
        width: 100%;
    }
    .panel{
        padding: 15px 0;
    }
    .apply-form button{
        margin-right: 0;
    }
}
    </style>



    <div class="job-container">

        <div class="job-list" id="job-list">

            <!-- Jobs will be dynamically loaded here -->

        </div>



        <div class="location-filter">

            <h3>Location</h3>

            <?php

            $countries = ['Kuwait', 'Qatar', 'Bahrain', 'Oman', 'UAE'];

            foreach ($countries as $country):

            ?>

                <label>

                    <input type="checkbox" class="location-checkbox" value="<?php echo esc_attr($country); ?>"> <?php echo esc_html($country); ?>

                </label>

            <?php endforeach; ?>

        </div>

    </div>


<script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>

    <script>

        // Accordion Functionality

        jQuery(document).ready(function($) {

            // Load all jobs on page load

            loadJobs([]);



            // Location filtering

            $('.location-checkbox').on('change', function() {

                var selectedCountries = [];

                $('.location-checkbox:checked').each(function() {

                    selectedCountries.push($(this).val());

                });

                loadJobs(selectedCountries);

            });



            // Function to load jobs

            function loadJobs(selectedCountries) {

                $.ajax({

                    url: '<?php echo admin_url('admin-ajax.php'); ?>',

                    type: 'POST',

                    data: {

                        action: 'filter_jobs',

                        countries: selectedCountries

                    },

                    success: function(response) {

                        $('#job-list').html(response);

                        addAccordionListeners();

                    }

                });

            }



            // Add accordion listeners after jobs are loaded

            function addAccordionListeners() {

                const accordions = document.querySelectorAll('.accordion');

                accordions.forEach(accordion => {

                    accordion.addEventListener('click', function() {

                        this.classList.toggle('active');

                        const panel = this.nextElementSibling;

                        panel.style.display = panel.style.display === 'block' ? 'none' : 'block';

                    });

                });

            }

        });

    </script>

<?php

    return ob_get_clean();

}

add_shortcode('custom_search', 'custom_search_shortcode');



// Handle AJAX request to filter jobs
function filter_jobs_ajax() {
    global $wpdb;
    $countries = isset($_POST['countries']) ? $_POST['countries'] : [];

    // Correct query construction with concatenation
    $query = "SELECT * FROM " . $wpdb->prefix . "jobs WHERE status = 1";

    if (!empty($countries)) {
        $countries_placeholder = implode(',', array_fill(0, count($countries), '%s'));
        $query .= " AND country IN ($countries_placeholder)";
        $query .= " ORDER BY date_of_application DESC";
        $results = $wpdb->get_results($wpdb->prepare($query, ...$countries));
    } else {
        $query .= " ORDER BY date_of_application DESC";
        $results = $wpdb->get_results($query);
    }

    if ($results) {
        foreach ($results as $job) {
            $date_obj = new DateTime($job->date_of_application);
    $formatted_date = $date_obj->format('Y-m-d');

            echo '<div class="job-item" data-location="' . esc_attr($job->country) . '">
                    <button class="accordion">
                        <div class="profile"> 
                            <h2 class="title">' . esc_html($job->job_title) . '</h2>
                            <p class="location"><img src="https://i.ibb.co/HgzLxWL/location.png" alt="location"> ' . esc_html($job->location) . ', ' . esc_html($job->country) . '</p>
                            <p class="unit"><img src="https://i.ibb.co/K2mkyhC/people.png" alt="unit"> ' . esc_html($job->tagline) . '</p>
                        </div>
                        <div class="apply">
                        
                            <p class="date"><img src="https://i.ibb.co/kcyWdSB/event.png" alt="date"> ' . esc_html($formatted_date) . '</p>
                            <p class="btn-form"><a href="javascript:void(0);" class="btn">Details</a></p>
                        </div>
                    </button>
                    <div class="panel">
                        <h4>Job Brief</h4>
                        ' . wp_kses_post($job->job_brief) . '
                        <h4>Responsibilities</h4>
                        <p>' . wp_kses_post($job->responsibilities) . '</p>
                        <h4>Requirements</h4>
                        <p>' . wp_kses_post($job->requirement) .  '</p>
                        <form id = "forms" class="apply-form " method="post" action="'.admin_url('admin-ajax.php').'" enctype="multipart/form-data">
                            <input type="hidden" name="job_id" value="' . esc_attr($job->id) . '">
                            <input type="hidden" name="action" value="check_ajax_working">
                            <div class="input-box first"> 
                                <label for="text">First Name</label>  
                                <input type="text" name="first_name" required>
                            </div>    
                            <div class="input-box last">   
                                <label for="text">Last Name</label>
                                <input type="text" name="last_name" required>
                            </div>
                            <div class="input-box email">
                                <label for="text">Email</label>
                                <input type="email" name="email" required>
                            </div>
                            <div class="input-box phone"> 
                                <label for="phone">Phone Number</label>
                                <input type="text" id="phone" name="phone" pattern="[0-9]+" required title="Please enter a valid phone number containing only digits." max="10">
                            </div>
                            <div class="input-box date"> 
                                <label for="birthday">Date of Birth</label>
                                <input type="date" id="birthday" name="birthday" placeholder="DD/MM/YYYY" required>
                            </div>
                            <div class="input-box file">
                                <label for="text">Upload cv (limit:8Mb.)</label>
                                <input type="file" name="file" accept=".pdf,.doc,.docx" required>
                            </div>  
                            <!-- <div class="checkbox">
                                <input id="checkbox" type="checkbox" />
                                <label for="checkbox"> I agree with <a href="#">Terms and Conditions</a>.</label>
                            </div> -->
                            <button type="submit">Apply</button>
                        </form>
                    </div>
                </div>';
        }
        ?>
        <script>
            jQuery('.job-application-form').on('submit', function(e) {
                e.preventDefault();

                var $form = jQuery(this);
                var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
                jQuery.post($form.attr('action'), $form.serialize(), function(data) {
                    //alert('This is data returned from the server ' + data);
                }, 'json');
            });
        </script>
    <?php } else {
        echo '<p>No jobs found for the selected location(s).</p>';
    }

    wp_die();
}
add_action('wp_ajax_filter_jobs', 'filter_jobs_ajax');
add_action('wp_ajax_nopriv_filter_jobs', 'filter_jobs_ajax');


function enqueue_custom_ajax_script() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Use event delegation to ensure dynamically added forms are targeted
        $(document).on('submit', '#forms', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitButton = $form.find('button[type="submit"]'); // Target submit button
            var originalButtonText = $submitButton.text(); // Store original text

            // Disable button and change text
            $submitButton.prop('disabled', true).text('Submitting...').css('pointer-events', 'none');

            var formData = new FormData($form[0]); // Convert the form to FormData for file uploads
            
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: formData,
                processData: false, // Important for file uploads
                contentType: false, // Important for file uploads
                success: function(response) {
                    if (response.success) {
                        alert('Application submitted successfully.');
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('There was an error submitting the form.');
                },
                complete: function() {
                    // Re-enable button and restore text after request completes
                    $submitButton.prop('disabled', false).text(originalButtonText).css('pointer-events', 'all');
                }
            });
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'enqueue_custom_ajax_script');

// Handle job application form submission via AJAX
function handle_job_application_submission() {
    global $wpdb;
    
    // Sanitize and validate inputs
    $job_id = isset($_POST['job_id']) ? intval($_POST['job_id']) : 0;
    $first_name = sanitize_text_field($_POST['first_name']);
    $last_name = sanitize_text_field($_POST['last_name']);
    $email = sanitize_email($_POST['email']);
    $phone = sanitize_text_field($_POST['phone']);
    $birthday = sanitize_text_field($_POST['birthday']);
    
    // Handle file upload
    if (isset($_FILES['file']) && !empty($_FILES['file']['name'])) {
        $uploaded = media_handle_upload('file', 0);
        if (is_wp_error($uploaded)) {
            wp_send_json_error(['message' => 'File upload failed']);
        } else {
            $file_url = wp_get_attachment_url($uploaded);
        }
    } else {
        wp_send_json_error(['message' => 'File is required']);
    }

    // Insert data into the database
    $table_applications = $wpdb->prefix . 'job_applications';
    $inserted = $wpdb->insert($table_applications, array(
        'job_id' => $job_id,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email,
        'phone' => $phone,
        'birthday' => $birthday,
        'file' => $file_url
    ));

    // Check if insert was successful, otherwise output the error
    if ($inserted) {
		// Fetch the stored email address from the database  
		$table_addresses = $wpdb->prefix . 'jobs_mail_addresses';  
		$stored_address = $wpdb->get_var($wpdb->prepare("SELECT jobs_mail_address FROM $table_addresses WHERE id = %d", 1));  

		// Email addresses  
		$to = $stored_address;
		$applicant_email = $email; 

		// Prepare the email content  
		$subject = 'New Job Application: ' . $first_name . ' ' . $last_name;  
		$message = "You have received a new job application.\n\n";  
		$message .= "First Name: $first_name\n";  
		$message .= "Last Name: $last_name\n";  
		$message .= "Email: $email\n";  
		$message .= "Phone: $phone\n";  
		$message .= "Date of Birth: $birthday\n";  
		$message .= "Resume Link: $file_url\n";  

		// Send the email to the stored address  
		wp_mail($to, $subject, $message);  
		
		// Send a confirmation email to the applicant  
		$applicant_subject = 'Application Received';  
		$applicant_message = 'Thank you for your application, ' . $first_name . '. We will review your application and get back to you soon.';  

		wp_mail($applicant_email, $applicant_subject, $applicant_message);  
		
        wp_send_json_success(['message' => 'Application submitted successfully!']);
    } else {
        // Log the error message
        $wpdb_error = $wpdb->last_error;
        wp_send_json_error(['message' => 'Failed to submit application', 'error' => $wpdb_error , 'table' => $sql_applications]);
    }
    
    wp_die();
}

add_action('wp_ajax_check_ajax_working', 'handle_job_application_submission');
add_action('wp_ajax_nopriv_check_ajax_working', 'handle_job_application_submission');

?>
