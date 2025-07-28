<?php
if (!defined('ABSPATH')) exit;

// Fix undefined variables
$current_user = wp_get_current_user();
$is_super_admin = isset($is_super_admin) ? $is_super_admin : in_array('administrator', $current_user->roles);
$is_admin = isset($is_admin) ? $is_admin : ($is_super_admin || in_array('yrr_admin', $current_user->roles));

// Set defaults
$statistics = isset($statistics) ? $statistics : array('total' => 0, 'today' => 0, 'pending' => 0, 'confirmed' => 0);
$today_reservations = isset($today_reservations) ? $today_reservations : array();
$restaurant_status = isset($restaurant_status) ? $restaurant_status : '1';
$restaurant_name = isset($restaurant_name) ? $restaurant_name : get_bloginfo('name');

// Handle success messages
$message = '';
if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        case 'reservation_created':
            $message = '<div class="notice notice-success is-dismissible"><p>✅ Reservation created successfully!</p></div>';
            break;
        case 'error':
            $message = '<div class="notice notice-error is-dismissible"><p>❌ Error creating reservation. Please try again.</p></div>';
            break;
    }
}
?>

<div class="wrap">
    <?php echo $message; ?>
    
    <div style="max-width: 1400px; margin: 20px auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
        
        <!-- Header -->
        <div style="text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 3px solid #007cba;">
            <h1 style="font-size: 2.5rem; color: #2c3e50; margin: 0;">🍽️ <?php echo esc_html($restaurant_name); ?> - Dashboard</h1>
            <p style="color: #6c757d; margin: 10px 0 0 0;">Restaurant Management System v1.5.1</p>
        </div>
        
        <!-- Restaurant Status -->
        <div style="background: <?php echo $restaurant_status == '1' ? 'linear-gradient(135deg, #e8f5e8 0%, #f0fff4 100%)' : 'linear-gradient(135deg, #f8d7da 0%, #ffeaa7 100%)'; ?>; padding: 20px; border-radius: 15px; margin-bottom: 30px; text-align: center; border: 3px solid <?php echo $restaurant_status == '1' ? '#28a745' : '#dc3545'; ?>;">
            <h3 style="margin: 0; color: <?php echo $restaurant_status == '1' ? '#155724' : '#721c24'; ?>; font-size: 1.5rem;">
                <?php echo $restaurant_status == '1' ? '🟢 Restaurant is OPEN' : '🔴 Restaurant is CLOSED'; ?>
            </h3>
        </div>
        
        <!-- Statistics Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
            
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 15px; text-align: center; box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);">
                <div style="font-size: 2.5rem; font-weight: bold; margin-bottom: 10px;">
                    <?php echo intval($statistics['total'] ?? 0); ?>
                </div>
                <div style="font-size: 1.1rem; opacity: 0.9;">📋 Total Reservations</div>
            </div>
            
            <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 25px; border-radius: 15px; text-align: center; box-shadow: 0 5px 15px rgba(240, 147, 251, 0.4);">
                <div style="font-size: 2.5rem; font-weight: bold; margin-bottom: 10px;">
                    <?php echo intval($statistics['today'] ?? 0); ?>
                </div>
                <div style="font-size: 1.1rem; opacity: 0.9;">📅 Today's Bookings</div>
            </div>
            
            <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 25px; border-radius: 15px; text-align: center; box-shadow: 0 5px 15px rgba(79, 172, 254, 0.4);">
                <div style="font-size: 2.5rem; font-weight: bold; margin-bottom: 10px;">
                    <?php echo intval($statistics['pending'] ?? 0); ?>
                </div>
                <div style="font-size: 1.1rem; opacity: 0.9;">⏳ Pending Approval</div>
            </div>
            
            <div style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; padding: 25px; border-radius: 15px; text-align: center; box-shadow: 0 5px 15px rgba(67, 233, 123, 0.4);">
                <div style="font-size: 2.5rem; font-weight: bold; margin-bottom: 10px;">
                    <?php echo intval($statistics['confirmed'] ?? 0); ?>
                </div>
                <div style="font-size: 1.1rem; opacity: 0.9;">✅ Confirmed</div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
            
            <!-- Manual Reservation Form -->
            <div style="background: #f8f9fa; padding: 25px; border-radius: 15px; border: 2px solid #dee2e6;">
                <h3 style="margin: 0 0 20px 0; color: #495057;">🆕 Create Manual Reservation</h3>
                
                <form method="post" action="">
                    <?php wp_nonce_field('create_manual_reservation', 'manual_reservation_nonce'); ?>
                    <input type="hidden" name="create_manual_reservation" value="1">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Customer Name *</label>
                            <input type="text" name="customer_name" required 
                                   style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; box-sizing: border-box;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Email *</label>
                            <input type="email" name="customer_email" required 
                                   style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; box-sizing: border-box;">
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Phone</label>
                            <input type="text" name="customer_phone" 
                                   style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; box-sizing: border-box;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Party Size *</label>
                            <select name="party_size" required 
                                    style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; box-sizing: border-box;">
                                <?php for($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?> people</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Date *</label>
                            <input type="date" name="reservation_date" required min="<?php echo date('Y-m-d'); ?>"
                                   style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; box-sizing: border-box;">
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Time *</label>
                        <select name="reservation_time" required 
                                style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; box-sizing: border-box;">
                            <?php
                            for ($hour = 10; $hour <= 22; $hour++) {
                                for ($minute = 0; $minute < 60; $minute += 30) {
                                    $time = sprintf('%02d:%02d', $hour, $minute);
                                    $display = date('g:i A', strtotime($time));
                                    echo "<option value=\"{$time}\">{$display}</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Special Requests</label>
                        <textarea name="special_requests" rows="3" 
                                  style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; box-sizing: border-box; resize: vertical;"></textarea>
                    </div>
                    
                    <button type="submit" 
                            style="width: 100%; background: #28a745; color: white; border: none; padding: 15px; border-radius: 8px; font-size: 1.1rem; font-weight: bold; cursor: pointer;">
                        ✅ Create Reservation
                    </button>
                </form>
            </div>
            
            <!-- Today's Reservations -->
            <div style="background: #f8f9fa; padding: 25px; border-radius: 15px; border: 2px solid #dee2e6;">
                <h3 style="margin: 0 0 20px 0; color: #495057;">📅 Today's Reservations (<?php echo count($today_reservations); ?>)</h3>
                
                <?php if (empty($today_reservations)): ?>
                    <div style="text-align: center; color: #6c757d; padding: 20px;">
                        <div style="font-size: 3rem; margin-bottom: 10px;">📋</div>
                        <div>No reservations for today</div>
                    </div>
                <?php else: ?>
                    <div style="max-height: 400px; overflow-y: auto;">
                        <?php foreach ($today_reservations as $reservation): ?>
                            <div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 10px; border-left: 4px solid <?php 
                                echo $reservation->status === 'confirmed' ? '#28a745' : 
                                    ($reservation->status === 'pending' ? '#ffc107' : '#dc3545'); 
                            ?>;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <div style="font-weight: bold; color: #2c3e50;"><?php echo esc_html($reservation->customer_name); ?></div>
                                        <div style="color: #6c757d; font-size: 0.9rem;">
                                            <?php echo date('g:i A', strtotime($reservation->reservation_time)); ?> • 
                                            <?php echo intval($reservation->party_size); ?> people
                                        </div>
                                    </div>
                                    <div style="text-align: right;">
                                        <span style="background: <?php 
                                            echo $reservation->status === 'confirmed' ? '#e8f5e8' : 
                                                ($reservation->status === 'pending' ? '#fff3cd' : '#f8d7da'); 
                                        ?>; color: <?php 
                                            echo $reservation->status === 'confirmed' ? '#155724' : 
                                                ($reservation->status === 'pending' ? '#856404' : '#721c24'); 
                                        ?>; padding: 5px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: bold; text-transform: uppercase;">
                                            <?php echo esc_html($reservation->status); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
