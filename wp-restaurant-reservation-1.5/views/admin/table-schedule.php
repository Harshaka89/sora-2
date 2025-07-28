<?php
if (!defined('ABSPATH')) exit;

$current_date = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : date('Y-m-d');

// Get tables and bookings
global $wpdb;
$tables = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}yrr_tables ORDER BY table_number");
$bookings = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}yrr_reservations WHERE reservation_date = %s AND status IN ('confirmed', 'pending') ORDER BY reservation_time",
    $current_date
));

// Organize bookings by table and time
$table_schedule = array();
foreach ($tables as $table) {
    $table_schedule[$table->id] = array(
        'table' => $table,
        'bookings' => array()
    );
}

foreach ($bookings as $booking) {
    if (isset($table_schedule[$booking->table_id])) {
        $time_slot = date('H:i', strtotime($booking->reservation_time));
        $table_schedule[$booking->table_id]['bookings'][$time_slot] = $booking;
    }
}

// Generate time slots
$time_slots = array();
for ($hour = 10; $hour <= 22; $hour++) {
    for ($minute = 0; $minute < 60; $minute += 30) {
        if ($hour == 22 && $minute > 0) break;
        $time_slots[] = sprintf('%02d:%02d', $hour, $minute);
    }
}
?>

<div class="wrap">
    <div style="max-width: 1800px; margin: 20px auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
        
        <!-- Header -->
        <div style="text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 3px solid #007cba;">
            <h1 style="font-size: 2.5rem; color: #2c3e50; margin: 0;">📅 Table Schedule & Customer Management</h1>
            <p style="color: #6c757d; margin: 10px 0 0 0;">Add customers directly to tables - <?php echo date('F j, Y', strtotime($current_date)); ?></p>
        </div>
        
        <!-- Date Navigation -->
        <div style="text-align: center; margin-bottom: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px;">
            <div style="display: flex; justify-content: center; align-items: center; gap: 20px; flex-wrap: wrap;">
                <?php 
                $prev_date = date('Y-m-d', strtotime($current_date . ' -1 day'));
                $next_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
                ?>
                
                <a href="?page=yrr-table-schedule&date=<?php echo $prev_date; ?>" 
                   style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; font-weight: bold;">
                    ← Previous Day
                </a>
                
                <input type="date" value="<?php echo $current_date; ?>" 
                       onchange="window.location.href='?page=yrr-table-schedule&date='+this.value"
                       style="padding: 10px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 1.1rem;">
                
                <a href="?page=yrr-table-schedule&date=<?php echo $next_date; ?>" 
                   style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; font-weight: bold;">
                    Next Day →
                </a>
                
                <a href="?page=yrr-table-schedule" 
                   style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; font-weight: bold;">
                    Today
                </a>
            </div>
        </div>
        
        <!-- Legend -->
        <div style="margin-bottom: 20px; padding: 15px; background: #e3f2fd; border-radius: 10px; text-align: center;">
            <h4 style="margin: 0 0 10px 0;">📋 Table Status & Actions</h4>
            <div style="display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;">
                <span style="background: #28a745; color: white; padding: 5px 15px; border-radius: 15px; font-weight: bold;">✅ Confirmed</span>
                <span style="background: #ffc107; color: black; padding: 5px 15px; border-radius: 15px; font-weight: bold;">⏳ Pending</span>
                <span style="background: #f8f9fa; color: #333; padding: 5px 15px; border-radius: 15px; font-weight: bold; border: 2px solid #28a745; cursor: pointer;">➕ Click to Add Customer</span>
            </div>
        </div>
        
        <!-- Schedule Grid -->
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; min-width: 1200px;">
                <thead>
                    <tr style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                        <th style="padding: 15px; text-align: left; font-weight: bold; min-width: 140px;">Table Info</th>
                        <?php foreach ($time_slots as $slot): ?>
                            <th style="padding: 10px 5px; text-align: center; font-weight: bold; min-width: 90px; font-size: 0.9rem;">
                                <?php echo date('g:i A', strtotime($slot)); ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($table_schedule as $table_data): ?>
                        <?php 
                        $table = $table_data['table'];
                        $bookings = $table_data['bookings'];
                        ?>
                        
                        <tr style="border-bottom: 2px solid #dee2e6;">
                            <!-- Table Info -->
                            <td style="padding: 20px; background: #f8f9fa; font-weight: bold; border-right: 3px solid #dee2e6;">
                                <div style="font-size: 1.3rem; color: #2c3e50; margin-bottom: 8px;">
                                    🍽️ <?php echo esc_html($table->table_number); ?>
                                </div>
                                <div style="background: #e3f2fd; color: #1976d2; padding: 5px 10px; border-radius: 8px; font-size: 0.9rem; margin-bottom: 5px;">
                                    👥 <?php echo intval($table->capacity); ?> seats
                                </div>
                                <div style="font-size: 0.8rem; color: #6c757d;">
                                    📍 <?php echo esc_html($table->location); ?>
                                </div>
                            </td>
                            
                            <!-- Time Slot Cells -->
                            <?php foreach ($time_slots as $slot): ?>
                                <?php 
                                $has_booking = isset($bookings[$slot]);
                                $booking = $has_booking ? $bookings[$slot] : null;
                                ?>
                                
                                <td style="padding: 8px; text-align: center; height: 80px; position: relative;">
                                    <?php if ($has_booking): ?>
                                        <!-- Booked Slot -->
                                        <div onclick="showCustomerDetails(<?php echo htmlspecialchars(json_encode($booking)); ?>)"
                                             style="background: <?php echo $booking->status === 'confirmed' ? '#28a745' : '#ffc107'; ?>; color: <?php echo $booking->status === 'confirmed' ? 'white' : 'black'; ?>; padding: 10px 6px; border-radius: 10px; cursor: pointer; font-size: 0.8rem; font-weight: bold; height: 100%; display: flex; flex-direction: column; justify-content: center; transition: all 0.3s ease;"
                                             onmouseover="this.style.transform='scale(1.05)'"
                                             onmouseout="this.style.transform='scale(1)'">
                                            <div style="margin-bottom: 3px;"><?php echo esc_html(substr($booking->customer_name, 0, 12)); ?></div>
                                            <div style="font-size: 0.7rem; opacity: 0.9;">👥 <?php echo intval($booking->party_size); ?></div>
                                            <div style="font-size: 0.6rem; opacity: 0.8;"><?php echo ucfirst($booking->status); ?></div>
                                        </div>
                                    <?php else: ?>
                                        <!-- Available Slot - Add Customer -->
                                        <div onclick="addCustomerToTable('<?php echo $table->id; ?>', '<?php echo esc_js($table->table_number); ?>', <?php echo $table->capacity; ?>, '<?php echo $current_date; ?>', '<?php echo $slot; ?>')"
                                             style="background: #f8f9fa; border: 2px dashed #28a745; padding: 10px 6px; border-radius: 10px; cursor: pointer; font-size: 0.75rem; color: #28a745; height: 100%; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;"
                                             onmouseover="this.style.background='#e8f5e8'; this.style.borderColor='#155724'; this.style.transform='scale(1.05)'"
                                             onmouseout="this.style.background='#f8f9fa'; this.style.borderColor='#28a745'; this.style.transform='scale(1)'">
                                            <div style="text-align: center;">
                                                <div style="font-size: 1.2rem; margin-bottom: 2px;">➕</div>
                                                <div style="font-weight: bold;">Add Customer</div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Customer Details Modal -->
<div id="customerDetailsModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 10000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 30px; border-radius: 20px; width: 90%; max-width: 500px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #e9ecef;">
            <h3 style="margin: 0;">👤 Customer Details</h3>
            <button onclick="closeCustomerModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #6c757d;">×</button>
        </div>
        
        <div id="customerDetailsContent">
            <!-- Content populated by JavaScript -->
        </div>
        
        <div style="text-align: center; margin-top: 20px; padding-top: 15px; border-top: 2px solid #e9ecef;">
            <button onclick="closeCustomerModal()" style="background: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: bold;">Close</button>
        </div>
    </div>
</div>

<!-- Add Customer Modal -->
<div id="addCustomerModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 10000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 30px; border-radius: 20px; width: 90%; max-width: 600px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #e9ecef;">
            <h3 style="margin: 0;">➕ Add Customer to Table</h3>
            <button onclick="closeAddCustomerModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #6c757d;">×</button>
        </div>
        
        <div style="background: #e8f5e8; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
            <h4 style="margin: 0 0 10px 0; color: #155724;">🍽️ Table & Time Slot</h4>
            <div id="addCustomerTableInfo">
                <!-- Content populated by JavaScript -->
            </div>
        </div>
        
        <form method="post" action="">
            <?php wp_nonce_field('add_customer_to_table', 'add_customer_nonce'); ?>
            <input type="hidden" id="add_table_id" name="table_id">
            <input type="hidden" id="add_date" name="reservation_date">
            <input type="hidden" id="add_time" name="reservation_time">
            <input type="hidden" name="add_customer_action" value="1">
            <input type="hidden" name="status" value="confirmed">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">Customer Name *</label>
                    <input type="text" name="customer_name" required style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">Party Size *</label>
                    <select name="party_size" required style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; box-sizing: border-box;">
                        <?php for($i = 1; $i <= 12; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?> <?php echo $i == 1 ? 'guest' : 'guests'; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">Email *</label>
                    <input type="email" name="customer_email" required style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">Phone *</label>
                    <input type="tel" name="customer_phone" required style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; box-sizing: border-box;">
                </div>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 8px; font-weight: bold;">Special Requests</label>
                <textarea name="special_requests" rows="3" style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; box-sizing: border-box;"></textarea>
            </div>
            
            <div style="text-align: right; padding-top: 20px; border-top: 2px solid #e9ecef;">
                <button type="button" onclick="closeAddCustomerModal()" style="background: #6c757d; color: white; border: none; padding: 12px 24px; border-radius: 8px; margin-right: 15px; cursor: pointer; font-weight: bold;">Cancel</button>
                <button type="submit" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: bold;">➕ Add Customer</button>
            </div>
        </form>
    </div>
</div>

<script>
function showCustomerDetails(booking) {
    const modal = document.getElementById('customerDetailsModal');
    const content = document.getElementById('customerDetailsContent');
    
    const statusColor = booking.status === 'confirmed' ? '#28a745' : '#ffc107';
    
    content.innerHTML = `
        <div style="text-align: center; margin-bottom: 20px;">
            <div style="background: ${statusColor}; color: white; padding: 10px 20px; border-radius: 20px; display: inline-block; font-weight: bold; text-transform: uppercase; margin-bottom: 15px;">
                ${booking.status}
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
            <div><strong>Customer:</strong><br>${booking.customer_name}</div>
            <div><strong>Party Size:</strong><br>👥 ${booking.party_size} guests</div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
            <div><strong>Date:</strong><br>${booking.reservation_date}</div>
            <div><strong>Time:</strong><br>${booking.reservation_time}</div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
            <div><strong>Email:</strong><br>${booking.customer_email}</div>
            <div><strong>Phone:</strong><br>${booking.customer_phone}</div>
        </div>
        
        ${booking.special_requests ? `<div style="margin-bottom: 15px;"><strong>Special Requests:</strong><br>${booking.special_requests}</div>` : ''}
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="admin.php?page=yenolx-reservations" style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; font-weight: bold;">View All Reservations</a>
        </div>
    `;
    
    modal.style.display = 'flex';
}

function closeCustomerModal() {
    document.getElementById('customerDetailsModal').style.display = 'none';
}

function addCustomerToTable(tableId, tableName, capacity, date, time) {
    document.getElementById('add_table_id').value = tableId;
    document.getElementById('add_date').value = date;
    document.getElementById('add_time').value = time + ':00';
    
    document.getElementById('addCustomerTableInfo').innerHTML = `
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
            <div><strong>Table:</strong><br>🍽️ ${tableName}</div>
            <div><strong>Capacity:</strong><br>👥 ${capacity} seats</div>
            <div><strong>Date & Time:</strong><br>📅 ${date}<br>🕐 ${time}</div>
        </div>
    `;
    
    document.getElementById('addCustomerModal').style.display = 'flex';
}

function closeAddCustomerModal() {
    document.getElementById('addCustomerModal').style.display = 'none';
}

// Close modals when clicking outside
document.addEventListener('DOMContentLoaded', function() {
    ['customerDetailsModal', 'addCustomerModal'].forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    if (modalId === 'customerDetailsModal') closeCustomerModal();
                    else if (modalId === 'addCustomerModal') closeAddCustomerModal();
                }
            });
        }
    });
});
</script>
