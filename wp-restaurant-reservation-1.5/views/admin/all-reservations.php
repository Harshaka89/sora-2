<?php
/**
 * All Reservations View - MVC Pattern
 * Complete reservations management interface
 */

if (!defined('ABSPATH')) exit;

// Helper function for safe property access
function rrs_get_property_all($object, $property, $default = '') {
    return (property_exists($object, $property) && !empty($object->$property)) ? $object->$property : $default;
}
?>

<div class="wrap">
    <div style="max-width: 1400px; margin: 20px auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
        
        <!-- Header -->
        <div style="text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 3px solid #667eea;">
            <h1 style="font-size: 2.5rem; color: #2c3e50; margin: 0;">📋 All Reservations</h1>
            <p style="color: #6c757d; margin: 10px 0 0 0;">Complete reservation management and filtering</p>
        </div>
        <!-- Add Manual Reservation Button -->
<div style="text-align: center; margin-bottom: 20px;">
    <button onclick="showManualReservationModal()" 
            style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border: none; padding: 15px 30px; border-radius: 10px; font-size: 1.1rem; font-weight: bold; cursor: pointer;">
        ➕ Create Manual Reservation
    </button>
</div>

        <!-- Filters -->
        <div style="background: #f8f9fa; padding: 25px; border-radius: 15px; margin-bottom: 30px; border: 2px solid #e9ecef;">
            <h3 style="margin: 0 0 20px 0; color: #2c3e50;">🔍 Filter Reservations</h3>
            
            <form method="get" action="">
                <input type="hidden" name="page" value="all-reservations">
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px;">
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #2c3e50;">Search Customer</label>
                        <input type="text" name="search" value="<?php echo esc_attr($search ?? ''); ?>" 
                               placeholder="Name, email, or phone"
                               style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; box-sizing: border-box;">
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #2c3e50;">Status</label>
                        <select name="status" style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; box-sizing: border-box;">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php selected($status_filter ?? '', 'pending'); ?>>Pending</option>
                            <option value="confirmed" <?php selected($status_filter ?? '', 'confirmed'); ?>>Confirmed</option>
                            <option value="cancelled" <?php selected($status_filter ?? '', 'cancelled'); ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #2c3e50;">Date From</label>
                        <input type="date" name="date_from" value="<?php echo esc_attr($date_from ?? ''); ?>" 
                               style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; box-sizing: border-box;">
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #2c3e50;">Date To</label>
                        <input type="date" name="date_to" value="<?php echo esc_attr($date_to ?? ''); ?>" 
                               style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; box-sizing: border-box;">
                    </div>
                </div>
                
                <div style="text-align: center;">
                    <button type="submit" style="background: linear-gradient(135deg, #007cba 0%, #004d7a 100%); color: white; border: none; padding: 12px 30px; border-radius: 8px; font-weight: bold; cursor: pointer; margin-right: 10px;">
                        🔍 Filter Reservations
                    </button>
                    <a href="?page=all-reservations" style="background: #6c757d; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">
                        🔄 Clear Filters
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Reservations Count -->
        <div style="background: #e3f2fd; padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: center; border-left: 5px solid #2196f3;">
            <h4 style="margin: 0; color: #1976d2;">
                📊 Found <?php echo is_array($reservations) ? count($reservations) : 0; ?> reservations
                <?php if (!empty($search) || !empty($status_filter) || !empty($date_from) || !empty($date_to)): ?>
                    matching your filters
                <?php endif; ?>
            </h4>
        </div>
        
        <!-- Reservations Table -->
        <div style="background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
            <?php if (!empty($reservations) && is_array($reservations)): ?>
                
                <!-- Table Header -->
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; display: grid; grid-template-columns: auto 1fr auto auto auto auto; gap: 15px; font-weight: bold; align-items: center;">
                    <div>Status</div>
                    <div>Customer Details</div>
                    <div>Date</div>
                    <div>Time</div>
                    <div>Party</div>
                    <div>Actions</div>
                </div>
                
                <!-- Table Body -->
                <?php foreach ($reservations as $index => $reservation): ?>
                    <?php if (!is_object($reservation)) continue; ?>
                    
                    <div style="padding: 20px; display: grid; grid-template-columns: auto 1fr auto auto auto auto; gap: 15px; align-items: center; border-bottom: 1px solid #e9ecef; <?php echo $index % 2 == 0 ? 'background: #f8f9fa;' : 'background: white;'; ?>">
                        
                        <!-- Status Badge -->
                        <div>
                            <?php 
                            $status = rrs_get_property_all($reservation, 'status', 'pending');
                            $status_colors = array(
                                'confirmed' => '#28a745',
                                'pending' => '#ffc107',
                                'cancelled' => '#dc3545'
                            );
                            $text_color = $status === 'pending' ? '#000' : '#fff';
                            ?>
                            <span style="background: <?php echo $status_colors[$status] ?? '#6c757d'; ?>; color: <?php echo $text_color; ?>; padding: 8px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: bold; text-transform: uppercase;">
                                <?php echo esc_html($status); ?>
                            </span>
                        </div>
                        
                        <!-- Customer Details -->
                        <div>
                            <div style="font-weight: bold; font-size: 1.1rem; color: #2c3e50; margin-bottom: 5px;">
                                👤 <?php echo esc_html(rrs_get_property_all($reservation, 'customer_name', 'Unknown Customer')); ?>
                            </div>
                            <div style="color: #6c757d; font-size: 0.9rem; margin-bottom: 3px;">
                                📧 <?php echo esc_html(rrs_get_property_all($reservation, 'customer_email', 'No email')); ?>
                            </div>
                            <div style="color: #6c757d; font-size: 0.9rem; margin-bottom: 3px;">
                                📞 <?php echo esc_html(rrs_get_property_all($reservation, 'customer_phone', 'No phone')); ?>
                            </div>
                            <div style="color: #6c757d; font-size: 0.8rem;">
                                🏷️ <?php echo esc_html(rrs_get_property_all($reservation, 'reservation_code', 'No code')); ?>
                            </div>
                        </div>
                        
                        <!-- Date -->
                        <div style="text-align: center;">
                            <div style="font-weight: bold; color: #2c3e50;">
                                <?php echo date('M j', strtotime(rrs_get_property_all($reservation, 'reservation_date', date('Y-m-d')))); ?>
                            </div>
                            <div style="color: #6c757d; font-size: 0.9rem;">
                                <?php echo date('Y', strtotime(rrs_get_property_all($reservation, 'reservation_date', date('Y-m-d')))); ?>
                            </div>
                        </div>
                        
                        <!-- Time -->
                        <div style="text-align: center;">
                            <div style="font-weight: bold; color: #2c3e50;">
                                <?php echo date('g:i A', strtotime(rrs_get_property_all($reservation, 'reservation_time', '00:00:00'))); ?>
                            </div>
                        </div>
                        
                        <!-- Party Size -->
                        <div style="text-align: center;">
                            <div style="background: #e3f2fd; color: #1976d2; padding: 8px 12px; border-radius: 10px; font-weight: bold;">
                                👥 <?php echo intval(rrs_get_property_all($reservation, 'party_size', 1)); ?>
                            </div>
                        </div>
                        
                        <!-- Actions -->
                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                            <?php $reservation_id = rrs_get_property_all($reservation, 'id'); ?>
                            <?php if ($reservation_id): ?>
                                
                                <?php if ($status === 'pending'): ?>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=reservations&action=confirm&id=' . $reservation_id), 'reservation_action'); ?>" 
                                       style="background: #28a745; color: white; padding: 6px 10px; text-decoration: none; border-radius: 5px; font-size: 0.8rem; font-weight: bold;">
                                        ✅ Confirm
                                    </a>
                                <?php endif; ?>
                                
                                <button onclick="editReservation(<?php echo htmlspecialchars(json_encode($reservation)); ?>)" 
                                        style="background: #17a2b8; color: white; border: none; padding: 6px 10px; border-radius: 5px; font-size: 0.8rem; font-weight: bold; cursor: pointer;">
                                    ✏️ Edit
                                </button>
                                
                                <?php if ($status !== 'cancelled'): ?>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=all-reservations&action=cancel&id=' . $reservation_id), 'reservation_action'); ?>" 
                                       onclick="return confirm('Cancel this reservation?')" 
                                       style="background: #dc3545; color: white; padding: 6px 10px; text-decoration: none; border-radius: 5px; font-size: 0.8rem; font-weight: bold;">
                                        ❌ Cancel
                                    </a>
                                <?php endif; ?>
                                
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=all-reservations&action=delete&id=' . $reservation_id), 'reservation_action'); ?>" 
                                   onclick="return confirm('Delete this reservation permanently?')" 
                                   style="background: #6c757d; color: white; padding: 6px 10px; text-decoration: none; border-radius: 5px; font-size: 0.8rem; font-weight: bold;">
                                    🗑️ Delete
                                </a>
                                
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Special Requests Row -->
                    <?php 
                    $special_requests = rrs_get_property_all($reservation, 'special_requests');
                    $notes = rrs_get_property_all($reservation, 'notes');
                    if ($special_requests || $notes): ?>
                        <div style="padding: 10px 20px; background: rgba(0,123,186,0.05); border-bottom: 1px solid #e9ecef; font-size: 0.9rem;">
                            <?php if ($special_requests): ?>
                                <div style="margin-bottom: 5px;">
                                    <strong>💬 Special Requests:</strong> <?php echo esc_html($special_requests); ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($notes): ?>
                                <div>
                                    <strong>📝 Staff Notes:</strong> <?php echo esc_html($notes); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                <?php endforeach; ?>
                
            <?php else: ?>
                
                <!-- Empty State -->
                <div style="text-align: center; padding: 60px 20px; color: #6c757d;">
                    <div style="font-size: 4rem; margin-bottom: 20px; opacity: 0.3;">📋</div>
                    <h3 style="margin: 0 0 15px 0;">No Reservations Found</h3>
                    <?php if (!empty($search) || !empty($status_filter) || !empty($date_from) || !empty($date_to)): ?>
                        <p>No reservations match your current filters.</p>
                        <a href="?page=all-reservations" style="background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold; margin-top: 15px; display: inline-block;">
                            🔄 Clear Filters
                        </a>
                    <?php else: ?>
                        <p>No reservations have been made yet.</p>
                        <a href="<?php echo admin_url('admin.php?page=reservations'); ?>" style="background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold; margin-top: 15px; display: inline-block;">
                            📊 Go to Dashboard
                        </a>
                    <?php endif; ?>
                </div>
                
            <?php endif; ?>
        </div>
        
        <!-- Navigation -->
        <div style="text-align: center; margin-top: 30px;">
            <a href="<?php echo admin_url('admin.php?page=reservations'); ?>" 
               style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 10px; font-weight: bold; margin-right: 15px;">
                📊 Dashboard
            </a>
            <a href="<?php echo admin_url('admin.php?page=weekly-view'); ?>" 
               style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 10px; font-weight: bold;">
                📅 Weekly View
            </a>
        </div>
    </div>
</div>

<!-- Edit Reservation Modal -->
<div id="editModal" class="rrs-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 10000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 30px; border-radius: 20px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #e9ecef;">
            <h3 style="margin: 0;">✏️ Edit Reservation</h3>
            <button onclick="closeModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #6c757d;">×</button>
        </div>
        
        <form method="post" action="<?php echo admin_url('admin.php?page=reservations'); ?>">
            <?php wp_nonce_field('edit_reservation', 'edit_nonce'); ?>
            <input type="hidden" id="edit_id" name="reservation_id">
            <input type="hidden" name="edit_reservation" value="1">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">Customer Name *</label>
                    <input type="text" id="edit_name" name="customer_name" required style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">Email Address *</label>
                    <input type="email" id="edit_email" name="customer_email" required style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; box-sizing: border-box;">
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">Phone Number *</label>
                    <input type="tel" id="edit_phone" name="customer_phone" required style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">Party Size *</label>
                    <select id="edit_party" name="party_size" required style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; box-sizing: border-box;">
                        <?php for($i = 1; $i <= 20; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?> <?php echo $i == 1 ? 'Guest' : 'Guests'; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">Reservation Date *</label>
                    <input type="date" id="edit_date" name="reservation_date" required style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">Reservation Time *</label>
                    <input type="time" id="edit_time" name="reservation_time" required style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; box-sizing: border-box;">
                </div>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 8px; font-weight: bold;">Table Number</label>
                <input type="text" id="edit_table" name="table_number" placeholder="e.g., T1, Table 5" style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; box-sizing: border-box;">
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 8px; font-weight: bold;">Special Requests</label>
                <textarea id="edit_requests" name="special_requests" rows="3" placeholder="Any special requirements..." style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; box-sizing: border-box;"></textarea>
            </div>
            
            <div style="margin-bottom: 25px;">
                <label style="display: block; margin-bottom: 8px; font-weight: bold;">Staff Notes</label>
                <textarea id="edit_notes" name="notes" rows="2" placeholder="Internal notes for staff..." style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; box-sizing: border-box;"></textarea>
            </div>
            
            <div style="text-align: right; padding-top: 20px; border-top: 2px solid #e9ecef;">
                <button type="button" onclick="closeModal()" style="background: #6c757d; color: white; border: none; padding: 12px 24px; border-radius: 8px; margin-right: 15px; cursor: pointer; font-weight: bold;">Cancel</button>
                <button type="submit" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: bold;">💾 Update Reservation</button>
            </div>
        </form>
    </div>
</div>

<script>
function editReservation(res) {
    document.getElementById('edit_id').value = res.id || '';
    document.getElementById('edit_name').value = res.customer_name || '';
    document.getElementById('edit_email').value = res.customer_email || '';
    document.getElementById('edit_phone').value = res.customer_phone || '';
    document.getElementById('edit_party').value = res.party_size || '1';
    document.getElementById('edit_date').value = res.reservation_date || '';
    document.getElementById('edit_time').value = res.reservation_time || '';
    document.getElementById('edit_table').value = res.table_number || '';
    document.getElementById('edit_requests').value = res.special_requests || '';
    document.getElementById('edit_notes').value = res.notes || '';
    
    document.getElementById('editModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

<style>
@media (max-width: 1200px) {
    div[style*="grid-template-columns: auto 1fr auto auto auto auto"] {
        grid-template-columns: 1fr !important;
        gap: 10px !important;
    }
    
    div[style*="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr))"] {
        grid-template-columns: 1fr 1fr !important;
    }
}

@media (max-width: 768px) {
    div[style*="grid-template-columns: 1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}

button:hover, a[style*="background:"]:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
}
</style>
