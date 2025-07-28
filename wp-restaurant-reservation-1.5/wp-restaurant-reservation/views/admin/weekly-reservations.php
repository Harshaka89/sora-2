<?php
if (!defined('ABSPATH')) exit;

// Get current week or requested week
$current_week_start = isset($_GET['week_start']) ? sanitize_text_field($_GET['week_start']) : date('Y-m-d', strtotime('monday this week'));
$week_end = date('Y-m-d', strtotime($current_week_start . ' +6 days'));

// Get reservations for the week
$reservation_model = new YRR_Reservation_Model();
$weekly_reservations = $reservation_model->get_by_date_range($current_week_start, $week_end);

// Organize reservations by date
$reservations_by_date = array();
foreach ($weekly_reservations as $reservation) {
    $date = $reservation->reservation_date;
    if (!isset($reservations_by_date[$date])) {
        $reservations_by_date[$date] = array();
    }
    $reservations_by_date[$date][] = $reservation;
}

function yrr_get_property_weekly($object, $property, $default = '') {
    return (property_exists($object, $property) && !empty($object->$property)) ? $object->$property : $default;
}
?>

<div class="wrap">
    <div style="max-width: 1600px; margin: 20px auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
        
        <!-- Header -->
        <div style="text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 3px solid #28a745;">
            <h1 style="font-size: 2.5rem; color: #2c3e50; margin: 0;">📅 Weekly Reservation View</h1>
            <p style="color: #6c757d; margin: 10px 0 0 0;">
                Week of <?php echo date('M j', strtotime($current_week_start)); ?> - <?php echo date('M j, Y', strtotime($week_end)); ?>
            </p>
        </div>
        
        <!-- Week Navigation -->
        <div style="text-align: center; margin-bottom: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px;">
            <div style="display: flex; justify-content: center; align-items: center; gap: 20px;">
                <?php 
                $prev_week = date('Y-m-d', strtotime($current_week_start . ' -7 days'));
                $next_week = date('Y-m-d', strtotime($current_week_start . ' +7 days'));
                ?>
                
                <a href="?page=yrr-weekly-reservations&week_start=<?php echo $prev_week; ?>" 
                   style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; font-weight: bold;">
                    ← Previous Week
                </a>
                
                <div style="font-size: 1.2rem; font-weight: bold; color: #2c3e50;">
                    <?php echo date('F j, Y', strtotime($current_week_start)); ?> - <?php echo date('F j, Y', strtotime($week_end)); ?>
                </div>
                
                <a href="?page=yrr-weekly-reservations&week_start=<?php echo $next_week; ?>" 
                   style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; font-weight: bold;">
                    Next Week →
                </a>
                
                <a href="?page=yrr-weekly-reservations" 
                   style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; font-weight: bold;">
                    Current Week
                </a>
            </div>
        </div>
        
        <!-- Weekly Grid -->
        <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 15px; margin-bottom: 30px;">
            <?php 
            $days = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
            for ($i = 0; $i < 7; $i++):
                $current_date = date('Y-m-d', strtotime($current_week_start . ' +' . $i . ' days'));
                $day_reservations = isset($reservations_by_date[$current_date]) ? $reservations_by_date[$current_date] : array();
                $is_today = $current_date === date('Y-m-d');
            ?>
                
                <div style="background: <?php echo $is_today ? '#e8f5e8' : 'white'; ?>; border: <?php echo $is_today ? '3px solid #28a745' : '2px solid #e9ecef'; ?>; border-radius: 12px; padding: 15px; min-height: 300px;">
                    <!-- Day Header -->
                    <div style="text-align: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid <?php echo $is_today ? '#28a745' : '#e9ecef'; ?>;">
                        <h3 style="margin: 0; color: <?php echo $is_today ? '#28a745' : '#2c3e50'; ?>; font-size: 1.2rem;">
                            <?php echo $days[$i]; ?>
                        </h3>
                        <div style="color: #6c757d; font-size: 0.9rem; margin-top: 5px;">
                            <?php echo date('M j', strtotime($current_date)); ?>
                            <?php if ($is_today): ?>
                                <span style="background: #28a745; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.7rem; margin-left: 5px;">TODAY</span>
                            <?php endif; ?>
                        </div>
                        <div style="margin-top: 8px; font-weight: bold; color: <?php echo $is_today ? '#28a745' : '#007cba'; ?>;">
                            <?php echo count($day_reservations); ?> reservation<?php echo count($day_reservations) !== 1 ? 's' : ''; ?>
                        </div>
                    </div>
                    
                    <!-- Reservations List -->
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <?php if (!empty($day_reservations)): ?>
                            <?php foreach ($day_reservations as $reservation): ?>
                                <?php 
                                $status = yrr_get_property_weekly($reservation, 'status', 'pending');
                                $status_colors = array(
                                    'confirmed' => '#28a745',
                                    'pending' => '#ffc107',
                                    'cancelled' => '#dc3545'
                                );
                                $text_color = $status === 'pending' ? '#000' : '#fff';
                                ?>
                                
                                <div style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 10px; font-size: 0.85rem;">
                                    <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 5px;">
                                        <div style="font-weight: bold; color: #2c3e50;">
                                            <?php echo date('g:i A', strtotime(yrr_get_property_weekly($reservation, 'reservation_time', '00:00:00'))); ?>
                                        </div>
                                        <span style="background: <?php echo $status_colors[$status] ?? '#6c757d'; ?>; color: <?php echo $text_color; ?>; padding: 2px 6px; border-radius: 10px; font-size: 0.7rem; font-weight: bold; text-transform: uppercase;">
                                            <?php echo esc_html($status); ?>
                                        </span>
                                    </div>
                                    
                                    <div style="color: #2c3e50; font-weight: bold; margin-bottom: 3px;">
                                        👤 <?php echo esc_html(yrr_get_property_weekly($reservation, 'customer_name', 'Unknown')); ?>
                                    </div>
                                    
                                    <div style="color: #6c757d; font-size: 0.8rem; margin-bottom: 3px;">
                                        📧 <?php echo esc_html(yrr_get_property_weekly($reservation, 'customer_email', 'No email')); ?>
                                    </div>
                                    
                                    <div style="display: flex; justify-content: between; align-items: center;">
                                        <span style="background: #e3f2fd; color: #1976d2; padding: 3px 8px; border-radius: 8px; font-weight: bold; font-size: 0.75rem;">
                                            👥 <?php echo intval(yrr_get_property_weekly($reservation, 'party_size', 1)); ?> guests
                                        </span>
                                        
                                        <?php 
                                        $table_id = yrr_get_property_weekly($reservation, 'table_id');
                                        if ($table_id): ?>
                                            <span style="color: #6c757d; font-size: 0.75rem;">
                                                🍽️ Table <?php echo esc_html($table_id); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div style="margin-top: 8px; text-align: center;">
                                        <button onclick="editReservation(<?php echo htmlspecialchars(json_encode($reservation)); ?>)" 
                                                style="background: #17a2b8; color: white; border: none; padding: 4px 8px; border-radius: 4px; font-size: 0.7rem; cursor: pointer;">
                                            ✏️ Edit
                                        </button>
                                    </div>
                                </div>
                                
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="text-align: center; padding: 20px; color: #6c757d; font-style: italic;">
                                No reservations
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
            <?php endfor; ?>
        </div>
        
        <!-- Week Summary -->
        <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; border: 2px solid #e9ecef;">
            <h4 style="margin: 0 0 15px 0; color: #2c3e50;">📊 Week Summary</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div style="text-align: center; padding: 15px; background: white; border-radius: 8px;">
                    <div style="font-size: 2rem; font-weight: bold; color: #007cba;">
                        <?php echo count($weekly_reservations); ?>
                    </div>
                    <div style="color: #6c757d;">Total Reservations</div>
                </div>
                
                <div style="text-align: center; padding: 15px; background: white; border-radius: 8px;">
                    <div style="font-size: 2rem; font-weight: bold; color: #28a745;">
                        <?php echo count(array_filter($weekly_reservations, function($r) { return $r->status === 'confirmed'; })); ?>
                    </div>
                    <div style="color: #6c757d;">Confirmed</div>
                </div>
                
                <div style="text-align: center; padding: 15px; background: white; border-radius: 8px;">
                    <div style="font-size: 2rem; font-weight: bold; color: #ffc107;">
                        <?php echo count(array_filter($weekly_reservations, function($r) { return $r->status === 'pending'; })); ?>
                    </div>
                    <div style="color: #6c757d;">Pending</div>
                </div>
                
                <div style="text-align: center; padding: 15px; background: white; border-radius: 8px;">
                    <div style="font-size: 2rem; font-weight: bold; color: #dc3545;">
                        <?php echo count(array_filter($weekly_reservations, function($r) { return $r->status === 'cancelled'; })); ?>
                    </div>
                    <div style="color: #6c757d;">Cancelled</div>
                </div>
            </div>
        </div>
        
        <!-- Navigation -->
        <div style="text-align: center; margin-top: 30px;">
            <a href="<?php echo admin_url('admin.php?page=yenolx-reservations'); ?>" 
               style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 10px; font-weight: bold; margin-right: 15px;">
                📊 Dashboard
            </a>
            <a href="<?php echo admin_url('admin.php?page=yrr-all-reservations'); ?>" 
               style="background: linear-gradient(135deg, #007cba 0%, #004d7a 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 10px; font-weight: bold;">
                📋 All Reservations
            </a>
        </div>
    </div>
</div>

<!-- Edit Modal (reuse from dashboard) -->
<div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 10000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 30px; border-radius: 20px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #e9ecef;">
            <h3 style="margin: 0;">✏️ Edit Reservation</h3>
            <button onclick="closeModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #6c757d;">×</button>
        </div>
        
        <form method="post" action="<?php echo admin_url('admin.php?page=yenolx-reservations'); ?>">
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
                <label style="display: block; margin-bottom: 8px; font-weight: bold;">Special Requests</label>
                <textarea id="edit_requests" name="special_requests" rows="3" placeholder="Any special requirements..." style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; box-sizing: border-box;"></textarea>
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
    document.getElementById('edit_requests').value = res.special_requests || '';
    
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
    div[style*="grid-template-columns: repeat(7, 1fr)"] {
        grid-template-columns: repeat(4, 1fr) !important;
    }
}

@media (max-width: 768px) {
    div[style*="grid-template-columns: repeat(7, 1fr)"] {
        grid-template-columns: 1fr !important;
    }
    
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
