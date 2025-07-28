<?php
if (!defined('ABSPATH')) exit;

// Get current date or requested date
$current_date = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : date('Y-m-d');

// Get table schedule data
$tables_model = new YRR_Tables_Model();
$schedule = $tables_model->get_all_tables_schedule($current_date);
$time_slots = $tables_model->get_time_slots(null, $current_date);

function get_booking_color($status) {
    switch ($status) {
        case 'confirmed': return '#28a745';
        case 'pending': return '#ffc107';
        case 'cancelled': return '#dc3545';
        default: return '#6c757d';
    }
}
?>

<div class="wrap">
    <div style="max-width: 1800px; margin: 20px auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
        
        <!-- Header -->
        <div style="text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 3px solid #007cba;">
            <h1 style="font-size: 2.5rem; color: #2c3e50; margin: 0;">📅 Table Schedule & Time Slots</h1>
            <p style="color: #6c757d; margin: 10px 0 0 0;">Visual table booking schedule for <?php echo date('F j, Y', strtotime($current_date)); ?></p>
        </div>
        
        <!-- Date Navigation -->
        <div style="text-align: center; margin-bottom: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px;">
            <div style="display: flex; justify-content: center; align-items: center; gap: 20px;">
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
            <h4 style="margin: 0 0 10px 0;">📋 Booking Status Legend</h4>
            <div style="display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;">
                <span style="background: #28a745; color: white; padding: 5px 15px; border-radius: 15px; font-weight: bold;">✅ Confirmed</span>
                <span style="background: #ffc107; color: black; padding: 5px 15px; border-radius: 15px; font-weight: bold;">⏳ Pending</span>
                <span style="background: #dc3545; color: white; padding: 5px 15px; border-radius: 15px; font-weight: bold;">❌ Cancelled</span>
                <span style="background: #f8f9fa; color: #333; padding: 5px 15px; border-radius: 15px; font-weight: bold; border: 2px solid #dee2e6;">🆓 Available</span>
            </div>
        </div>
        
        <!-- Time Slots Grid -->
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; min-width: 1200px;">
                <thead>
                    <tr style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                        <th style="padding: 15px; text-align: left; font-weight: bold; min-width: 120px;">Table</th>
                        <?php foreach ($time_slots as $slot): ?>
                            <th style="padding: 10px 5px; text-align: center; font-weight: bold; min-width: 80px; font-size: 0.9rem;">
                                <?php echo $slot['formatted_time']; ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($schedule as $table_data): ?>
                        <?php 
                        $table = $table_data['table'];
                        $bookings = $table_data['bookings'];
                        
                        // Create booking lookup by time
                        $booking_lookup = array();
                        foreach ($bookings as $booking) {
                            $booking_time = date('H:i', strtotime($booking->reservation_time));
                            $booking_lookup[$booking_time] = $booking;
                        }
                        ?>
                        
                        <tr style="border-bottom: 1px solid #dee2e6;">
                            <!-- Table Info -->
                            <td style="padding: 15px; background: #f8f9fa; font-weight: bold; border-right: 2px solid #dee2e6;">
                                <div style="font-size: 1.2rem; color: #2c3e50; margin-bottom: 5px;">
                                    🍽️ <?php echo esc_html($table->table_number); ?>
                                </div>
                                <div style="font-size: 0.9rem; color: #6c757d;">
                                    👥 <?php echo intval($table->capacity); ?> seats
                                </div>
                                <div style="font-size: 0.8rem; color: #6c757d; margin-top: 3px;">
                                    📍 <?php echo esc_html($table->location); ?>
                                </div>
                            </td>
                            
                            <!-- Time Slot Cells -->
                            <?php foreach ($time_slots as $slot): ?>
                                <?php 
                                $slot_time = $slot['time'];
                                $has_booking = isset($booking_lookup[$slot_time]);
                                $booking = $has_booking ? $booking_lookup[$slot_time] : null;
                                ?>
                                
                                <td style="padding: 5px; text-align: center; height: 60px; position: relative;">
                                    <?php if ($has_booking): ?>
                                        <!-- Booked Slot -->
                                        <div onclick="showBookingDetails(<?php echo htmlspecialchars(json_encode($booking)); ?>)"
                                             style="background: <?php echo get_booking_color($booking->status); ?>; color: white; padding: 8px 4px; border-radius: 8px; cursor: pointer; font-size: 0.75rem; font-weight: bold; height: 100%; display: flex; flex-direction: column; justify-content: center;">
                                            <div style="margin-bottom: 2px;"><?php echo esc_html(substr($booking->customer_name, 0, 10)); ?></div>
                                            <div style="font-size: 0.7rem; opacity: 0.9;">👥 <?php echo intval($booking->party_size); ?></div>
                                        </div>
                                    <?php else: ?>
                                        <!-- Available Slot -->
                                        <div onclick="quickBook('<?php echo $table->id; ?>', '<?php echo $current_date; ?>', '<?php echo $slot_time; ?>')"
                                             style="background: #f8f9fa; border: 2px dashed #dee2e6; padding: 8px 4px; border-radius: 8px; cursor: pointer; font-size: 0.7rem; color: #6c757d; height: 100%; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">
                                            <span>🆓<br>Available</span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Quick Stats -->
        <div style="margin-top: 30px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <?php
            $total_bookings = 0;
            $confirmed_bookings = 0;
            $pending_bookings = 0;
            
            foreach ($schedule as $table_data) {
                foreach ($table_data['bookings'] as $booking) {
                    $total_bookings++;
                    if ($booking->status === 'confirmed') $confirmed_bookings++;
                    if ($booking->status === 'pending') $pending_bookings++;
                }
            }
            ?>
            
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; text-align: center;">
                <div style="font-size: 2rem; font-weight: bold;"><?php echo count($schedule); ?></div>
                <div>Total Tables</div>
            </div>
            
            <div style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 20px; border-radius: 10px; text-align: center;">
                <div style="font-size: 2rem; font-weight: bold;"><?php echo $confirmed_bookings; ?></div>
                <div>Confirmed Bookings</div>
            </div>
            
            <div style="background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%); color: white; padding: 20px; border-radius: 10px; text-align: center;">
                <div style="font-size: 2rem; font-weight: bold;"><?php echo $pending_bookings; ?></div>
                <div>Pending Bookings</div>
            </div>
            
            <div style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); color: white; padding: 20px; border-radius: 10px; text-align: center;">
                <div style="font-size: 2rem; font-weight: bold;"><?php echo $total_bookings; ?></div>
                <div>Total Bookings</div>
            </div>
        </div>
    </div>
</div>

<!-- Booking Details Modal -->
<div id="bookingDetailsModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 10000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 30px; border-radius: 20px; width: 90%; max-width: 500px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #e9ecef;">
            <h3 style="margin: 0;">📋 Booking Details</h3>
            <button onclick="closeBookingModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #6c757d;">×</button>
        </div>
        
        <div id="bookingDetailsContent">
            <!-- Content will be populated by JavaScript -->
        </div>
        
        <div style="text-align: center; margin-top: 20px; padding-top: 15px; border-top: 2px solid #e9ecef;">
            <button onclick="closeBookingModal()" style="background: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: bold;">Close</button>
        </div>
    </div>
</div>

<!-- Quick Book Modal -->
<div id="quickBookModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 10000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 30px; border-radius: 20px; width: 90%; max-width: 500px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #e9ecef;">
            <h3 style="margin: 0;">⚡ Quick Book Table</h3>
            <button onclick="closeQuickBookModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #6c757d;">×</button>
        </div>
        
        <form method="post" action="<?php echo admin_url('admin.php?page=yenolx-reservations'); ?>">
            <?php wp_nonce_field('create_manual_reservation', 'manual_reservation_nonce'); ?>
            <input type="hidden" name="create_manual_reservation" value="1">
            <input type="hidden" id="quick_table_id" name="table_id">
            <input type="hidden" id="quick_date" name="reservation_date">
            <input type="hidden" id="quick_time" name="reservation_time">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">Customer Name *</label>
                    <input type="text" name="customer_name" required style="width: 100%; padding: 10px; border: 2px solid #e9ecef; border-radius: 8px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">Party Size *</label>
                    <select name="party_size" required style="width: 100%; padding: 10px; border: 2px solid #e9ecef; border-radius: 8px; box-sizing: border-box;">
                        <?php for($i = 1; $i <= 12; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?> <?php echo $i == 1 ? 'guest' : 'guests'; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">Email *</label>
                    <input type="email" name="customer_email" required style="width: 100%; padding: 10px; border: 2px solid #e9ecef; border-radius: 8px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">Phone *</label>
                    <input type="tel" name="customer_phone" required style="width: 100%; padding: 10px; border: 2px solid #e9ecef; border-radius: 8px; box-sizing: border-box;">
                </div>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 8px; font-weight: bold;">Special Requests</label>
                <textarea name="special_requests" rows="2" style="width: 100%; padding: 10px; border: 2px solid #e9ecef; border-radius: 8px; box-sizing: border-box;"></textarea>
            </div>
            
            <div style="text-align: right; padding-top: 15px; border-top: 2px solid #e9ecef;">
                <button type="button" onclick="closeQuickBookModal()" style="background: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 8px; margin-right: 10px; cursor: pointer; font-weight: bold;">Cancel</button>
                <button type="submit" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: bold;">📅 Book Table</button>
            </div>
        </form>
    </div>
</div>

<script>
function showBookingDetails(booking) {
    const modal = document.getElementById('bookingDetailsModal');
    const content = document.getElementById('bookingDetailsContent');
    
    const statusColors = {
        'confirmed': '#28a745',
        'pending': '#ffc107', 
        'cancelled': '#dc3545'
    };
    
    const statusColor = statusColors[booking.status] || '#6c757d';
    
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
            <a href="<?php echo admin_url('admin.php?page=yenolx-reservations'); ?>" style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; font-weight: bold;">View All Reservations</a>
        </div>
    `;
    
    modal.style.display = 'flex';
}

function closeBookingModal() {
    document.getElementById('bookingDetailsModal').style.display = 'none';
}

function quickBook(tableId, date, time) {
    document.getElementById('quick_table_id').value = tableId;
    document.getElementById('quick_date').value = date;
    document.getElementById('quick_time').value = time;
    document.getElementById('quickBookModal').style.display = 'flex';
}

function closeQuickBookModal() {
    document.getElementById('quickBookModal').style.display = 'none';
}

// Close modals when clicking outside
document.getElementById('bookingDetailsModal').addEventListener('click', function(e) {
    if (e.target === this) closeBookingModal();
});

document.getElementById('quickBookModal').addEventListener('click', function(e) {
    if (e.target === this) closeQuickBookModal();
});

// Hover effects for available slots
document.addEventListener('DOMContentLoaded', function() {
    const availableSlots = document.querySelectorAll('div[onclick^="quickBook"]');
    availableSlots.forEach(slot => {
        slot.addEventListener('mouseenter', function() {
            this.style.background = '#e3f2fd';
            this.style.borderColor = '#007cba';
        });
        slot.addEventListener('mouseleave', function() {
            this.style.background = '#f8f9fa';
            this.style.borderColor = '#dee2e6';
        });
    });
});
</script>

<style>
@media (max-width: 1200px) {
    table {
        font-size: 0.8rem;
    }
    
    th, td {
        padding: 5px !important;
        min-width: 60px !important;
    }
}

@media (max-width: 768px) {
    div[style*="grid-template-columns: 1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>
