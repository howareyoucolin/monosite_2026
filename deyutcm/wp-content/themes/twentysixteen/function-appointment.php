<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

function appointments_admin_menu_page() {
    add_menu_page(
        'Appointments',       // Page title
        'Appointments',       // Menu title in sidebar
        'read',               // Capability (allows subscribers to access)
        'appointments-admin-page', // Menu slug
        'appointments_admin_page_content', // Callback function
        'dashicons-calendar', // Icon
        25 // Position in menu order
    );
}
add_action('admin_menu', 'appointments_admin_menu_page');

function appointments_admin_page_content() {
    global $wpdb;
    $table_name = 'appointments'; // Use table with WordPress prefix

    // Fetch all records from the appointments table
    $appointments = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");

    // Include Bootstrap CSS (if not already included in admin)
    echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">';

    ?>
    <div class="wrap">
        <h1 class="mb-4">Appointments</h1>
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr>
                        <!-- <th>ID</th> -->
                        <th>Date</th>
                        <th>Time</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <!-- <th>Email</th> -->
                        <th>Message</th>
                        <!-- <th>Checked</th> -->
                        <th>Submit Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($appointments)) : ?>
                        <?php foreach ($appointments as $appointment) : ?>
                            <tr>
                                <!-- <td><?php echo esc_html($appointment->id); ?></td> -->
                                <td><?php echo esc_html($appointment->date); ?></td>
                                <td><?php echo esc_html($appointment->time); ?></td>
                                <td><?php echo esc_html($appointment->name); ?></td>
                                <td><?php echo esc_html($appointment->phone); ?></td>
                                <!-- <td><?php echo esc_html($appointment->email ?: '-'); ?></td> -->
                                <td><?php echo esc_html($appointment->other ?: '-'); ?></td>
                                <!-- <td><?php echo $appointment->checked ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>'; ?></td> -->
                                <td><?php echo esc_html($appointment->created_at); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="9" class="text-center">No appointments found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}
