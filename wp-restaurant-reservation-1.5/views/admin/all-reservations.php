<?php
if (!defined('ABSPATH')) exit;

// Pagination setup
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;
$offset = ($current_page - 1) * $per_page;

// Get total count
$total_reservations = $this->reservation_model->get_total_count();
$total_pages = ceil($total_reservations / $per_page);

// Get paginated reservations
$reservations = $this->reservation_model->get_paginated($per_page, $offset);

// Handle success messages
$message = '';
if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        case 'deleted':
            $message = '<div class="notice notice-success is-dismissible"><p>✅ Reservation deleted successfully!</p></div>';
            break;
        case 'updated':
            $message = '<div class="notice notice-success is-dismissible"><p>✅ Reservation updated successfully!</p></div>';
            break;
    }
}
?>

<div class="wrap">
    <?php echo $message; ?>
    
    <div style="max-width: 1400px; margin: 20px auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
        
        <!-- Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 3px solid #007cba;">
            <div>
                <h1 style="font-size: 2.5rem; color: #2c3e50; margin: 0;">📋 All Reservations</h1>
                <p style="color: #6c757d; margin: 5px 0 0 0;">Manage all restaurant reservations - Total: <?php echo $total_reservations; ?></p>
            </div>
            <div>
                <a href="<?php echo admin_url('admin.php?page=yenolx-reservations'); ?>" 
                   style="background: #007cba; color: white; padding: 12px 20px; border-radius: 8px; text-decoration: none; font-weight: bold;">
                    + New Reservation
                </a>
            </div>
        </div>
        
        <!-- Filters -->
        <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
            <form method="get" action="">
                <input type="hidden" name="page" value="yrr-all-reservations">
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Status</label>
                        <select name="status" style="width: 100%; padding: 8px; border: 2px solid #ddd; border-radius: 5px;">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php selected($_GET['status'] ?? '', 'pending'); ?>>Pending</option>
                            <option value="confirmed" <?php selected($_GET['status'] ?? '', 'confirmed'); ?>>Confirmed</option>
                            <option value="cancelled" <?php selected($_GET['status'] ?? '', 'cancelled'); ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Date From</label>
                        <input type="date" name="date_from" value="<?php echo esc_attr($_GET['date_from'] ?? ''); ?>" 
                               style="width: 100%; padding: 8px; border: 2px solid #ddd; border-radius: 5px;">
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Date To</label>
                        <input type="date" name="date_to" value="<?php echo esc_attr($_GET['date_to'] ?? ''); ?>" 
                               style="width: 100%; padding: 8px; border: 2px solid #ddd; border-radius: 5px;">
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Search</label>
                        <input type="text" name="search" value="<?php echo esc_attr($_GET['search'] ?? ''); ?>" 
                               placeholder="Customer name or email"
                               style="width: 100%; padding: 8px; border: 2px solid #ddd; border-radius: 5px;">
                    </div>
                    
                    <div>
                        <button type="submit" style="background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-weight: bold;">
                            🔍 Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Reservations Table -->
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <thead>
                    <tr style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                        <th style="padding: 15px; text-align: left; font-weight: bold;">Code</th>
                        <th style="padding: 15px; text-align: left; font-weight: bold;">Customer</th>
                        <th style="padding: 15px; text-align: left; font-weight: bold;">Contact</th>
                        <th style="padding: 15px; text-align: center; font-weight: bold;">Party Size</th>
                        <th style="padding: 15px; text-align: center; font-weight: bold;">Date & Time</th>
                        <th style="padding: 15px; text-align: center; font-weight: bold;">Table</th>
                        <th style="padding: 15px; text-align: center; font-weight: bold;">Status</th>
                        <th style="padding: 15px; text-align: center; font-weight: bold;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reservations)): ?>
                        <tr>
                            <td colspan="8" style="padding: 40px; text-align: center; color: #6c757d; font-size: 1.1rem;">
                                <div style="opacity: 0.7;">
                                    📋 No reservations found matching your criteria
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reservations as $index => $reservation): ?>
                            <tr style="border-bottom: 1px solid #eee; <?php echo $index % 2 === 0 ? 'background: #f9f9f9;' : 'background: white;'; ?>">
                                <td style="padding: 15px; font-weight: bold; color: #007cba;">
                                    <?php echo esc_html($reservation->reservation_code); ?>
                                </td>
                                <td style="padding: 15px;">
                                    <div style="font-weight: bold; color: #2c3e50;"><?php echo esc_html($reservation->customer_name); ?></div>
                                    <div style="font-size: 0.9rem; color: #6c757d;"><?php echo esc_html($reservation->customer_email); ?></div>
                                </td>
                                <td style="padding: 15px; color: #6c757d;">
                                    <?php echo esc_html($reservation->customer_phone); ?>
                                </td>
                                <td style="padding: 15px; text-align: center;">
                                    <span style="background: #e3f2fd; color: #1976d2; padding: 5px 10px; border-radius: 15px; font-weight: bold;">
                                        <?php echo intval($reservation->party_size); ?> people
                                    </span>
                                </td>
                                <td style="padding: 15px; text-align: center;">
                                    <div style="font-weight: bold; color: #2c3e50;">
                                        <?php echo date('M j, Y', strtotime($reservation->reservation_date)); ?>
                                    </div>
                                    <div style="color: #007cba;">
                                        <?php echo date('g:i A', strtotime($reservation->reservation_time)); ?>
                                    </div>
                                </td>
                                <td style="padding: 15px; text-align: center;">
                                    <?php if ($reservation->table_id): ?>
                                        <span style="background: #e8f5e8; color: #155724; padding: 5px 10px; border-radius: 15px; font-weight: bold;">
                                            Table <?php echo esc_html($this->tables_model->get_table_number($reservation->table_id)); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #ffc107;">Not Assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 15px; text-align: center;">
                                    <?php
                                    $status_colors = array(
                                        'pending' => array('bg' => '#fff3cd', 'color' => '#856404', 'icon' => '⏳'),
                                        'confirmed' => array('bg' => '#e8f5e8', 'color' => '#155724', 'icon' => '✅'),
                                        'cancelled' => array('bg' => '#f8d7da', 'color' => '#721c24', 'icon' => '❌')
                                    );
                                    $status = $status_colors[$reservation->status] ?? $status_colors['pending'];
                                    ?>
                                    <span style="background: <?php echo $status['bg']; ?>; color: <?php echo $status['color']; ?>; padding: 8px 12px; border-radius: 15px; font-weight: bold; text-transform: uppercase;">
                                        <?php echo $status['icon']; ?> <?php echo esc_html($reservation->status); ?>
                                    </span>
                                </td>
                                <td style="padding: 15px; text-align: center;">
                                    <div style="display: flex; gap: 8px; justify-content: center;">
                                        <button onclick="editReservation(<?php echo $reservation->id; ?>)" 
                                                style="background: #17a2b8; color: white; border: none; padding: 6px 12px; border-radius: 5px; cursor: pointer; font-size: 0.9rem;">
                                            ✏️ Edit
                                        </button>
                                        <?php if ($reservation->status === 'pending'): ?>
                                            <button onclick="confirmReservation(<?php echo $reservation->id; ?>)" 
                                                    style="background: #28a745; color: white; border: none; padding: 6px 12px; border-radius: 5px; cursor: pointer; font-size: 0.9rem;">
                                                ✅ Confirm
                                            </button>
                                        <?php endif; ?>
                                        <button onclick="deleteReservation(<?php echo $reservation->id; ?>)" 
                                                style="background: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 5px; cursor: pointer; font-size: 0.9rem;">
                                            🗑️ Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- ✅ PAGINATION -->
        <?php if ($total_pages > 1): ?>
            <div style="margin-top: 30px; display: flex; justify-content: center; align-items: center; gap: 10px;">
                <?php
                $base_url = admin_url('admin.php?page=yrr-all-reservations');
                $query_params = $_GET;
                unset($query_params['page'], $query_params['paged']);
                
                if (!empty($query_params)) {
                    $base_url .= '&' . http_build_query($query_params);
                }
                ?>
                
                <!-- Previous Page -->
                <?php if ($current_page > 1): ?>
                    <a href="<?php echo $base_url . '&paged=' . ($current_page - 1); ?>" 
                       style="background: #007cba; color: white; padding: 10px 15px; border-radius: 5px; text-decoration: none; font-weight: bold;">
                        ← Previous
                    </a>
                <?php endif; ?>
                
                <!-- Page Numbers -->
                <?php
                $start_page = max(1, $current_page - 2);
                $end_page = min($total_pages, $current_page + 2);
                
                for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                    <?php if ($i == $current_page): ?>
                        <span style="background: #28a745; color: white; padding: 10px 15px; border-radius: 5px; font-weight: bold;">
                            <?php echo $i; ?>
                        </span>
                    <?php else: ?>
                        <a href="<?php echo $base_url . '&paged=' . $i; ?>" 
                           style="background: #f8f9fa; color: #007cba; padding: 10px 15px; border-radius: 5px; text-decoration: none; border: 2px solid #007cba;">
                            <?php echo $i; ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <!-- Next Page -->
                <?php if ($current_page < $total_pages): ?>
                    <a href="<?php echo $base_url . '&paged=' . ($current_page + 1); ?>" 
                       style="background: #007cba; color: white; padding: 10px 15px; border-radius: 5px; text-decoration: none; font-weight: bold;">
                        Next →
                    </a>
                <?php endif; ?>
                
                <!-- Page Info -->
                <div style="margin-left: 20px; color: #6c757d; font-weight: bold;">
                    Page <?php echo $current_page; ?> of <?php echo $total_pages; ?> 
                    (<?php echo $total_reservations; ?> total reservations)
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function editReservation(id) {
    // Add edit functionality
    alert('Edit reservation ID: ' + id);
}

function confirmReservation(id) {
    if (confirm('Are you sure you want to confirm this reservation?')) {
        window.location.href = '<?php echo admin_url('admin.php?page=yrr-all-reservations&action=confirm&id='); ?>' + id + '&_wpnonce=<?php echo wp_create_nonce('reservation_action'); ?>';
    }
}

function deleteReservation(id) {
    if (confirm('Are you sure you want to delete this reservation? This action cannot be undone.')) {
        window.location.href = '<?php echo admin_url('admin.php?page=yrr-all-reservations&action=delete&id='); ?>' + id + '&_wpnonce=<?php echo wp_create_nonce('reservation_action'); ?>';
    }
}
</script>
