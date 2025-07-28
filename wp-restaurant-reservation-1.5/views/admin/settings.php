<?php
if (!defined('ABSPATH')) exit;

// Handle success messages
$message = '';
if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        case 'saved':
            $count = isset($_GET['count']) ? intval($_GET['count']) : 0;
            $message = '<div class="notice notice-success is-dismissible"><p>✅ Settings saved successfully! (' . $count . ' settings updated)</p></div>';
            break;
        case 'error':
            $message = '<div class="notice notice-error is-dismissible"><p>❌ Error saving settings. Please try again.</p></div>';
            break;
    }
}

// Get current settings safely
$settings = isset($settings) ? $settings : array();

// Get today's hours for display
$current_day = strtolower(date('l'));
$today_hours = null;
$service_info = array();

if (class_exists('YRR_Hours_Model')) {
    $hours_model = new YRR_Hours_Model();
    if (method_exists($hours_model, 'get_today_hours')) {
        $today_hours = $hours_model->get_today_hours();
    }
}

// Calculate service info with fallback
if ($today_hours && !empty($today_hours->open_time)) {
    $service_info = array(
        'open_time' => date('H:i', strtotime($today_hours->open_time)),
        'close_time' => date('H:i', strtotime($today_hours->close_time)),
        'is_closed' => $today_hours->is_closed ?? 0,
        'duration' => ''
    );
    
    if (!$service_info['is_closed']) {
        $open_minutes = (intval(substr($service_info['open_time'], 0, 2)) * 60) + intval(substr($service_info['open_time'], 3, 2));
        $close_minutes = (intval(substr($service_info['close_time'], 0, 2)) * 60) + intval(substr($service_info['close_time'], 3, 2));
        
        if ($close_minutes <= $open_minutes) {
            $close_minutes += 24 * 60;
        }
        
        $duration_minutes = $close_minutes - $open_minutes;
        $hours = floor($duration_minutes / 60);
        $minutes = $duration_minutes % 60;
        $service_info['duration'] = $hours . 'h ' . $minutes . 'm';
    }
} else {
    // Fallback service info
    $service_info = array(
        'open_time' => '10:00',
        'close_time' => '22:00',
        'is_closed' => 0,
        'duration' => '12h 0m'
    );
}
?>

<div class="wrap">
    <?php echo $message; ?>
    
    <div style="max-width: 1400px; margin: 20px auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
        
        <!-- Header -->
        <div style="text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 3px solid #007cba;">
            <h1 style="font-size: 2.5rem; color: #2c3e50; margin: 0;">⚙️ Restaurant Settings</h1>
            <p style="color: #6c757d; margin: 10px 0 0 0;">Configure time slots, restaurant info, and booking rules</p>
        </div>

        <form method="post" action="">
            <?php wp_nonce_field('yrr_settings_save', 'settings_nonce'); ?>
            <input type="hidden" name="save_settings" value="1">
            
            <!-- Current Operating Status -->
            <div style="background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%); padding: 25px; border-radius: 15px; margin-bottom: 30px; border: 2px solid #1976d2;">
                <h3 style="margin: 0 0 20px 0; color: #1976d2; font-size: 1.4rem;">🏪 Current Operating Status</h3>
                
                <!-- Restaurant Status Toggle -->
                <div style="background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; text-align: center;">
                    <label style="display: block; margin-bottom: 15px; font-weight: bold; font-size: 1.2rem;">Restaurant Status</label>
                    <div style="display: flex; justify-content: center; gap: 20px; align-items: center;">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 15px 25px; border-radius: 10px; background: #e8f5e8; border: 2px solid #28a745;">
                            <input type="radio" name="restaurant_open" value="1" <?php checked($settings['restaurant_open'] ?? '1', '1'); ?>>
                            <span style="font-weight: bold; color: #155724; font-size: 1.1rem;">🟢 OPEN</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 15px 25px; border-radius: 10px; background: #f8d7da; border: 2px solid #dc3545;">
                            <input type="radio" name="restaurant_open" value="0" <?php checked($settings['restaurant_open'] ?? '1', '0'); ?>>
                            <span style="font-weight: bold; color: #721c24; font-size: 1.1rem;">🔴 CLOSED</span>
                        </label>
                    </div>
                </div>
                
                <!-- Today's Hours Display -->
                <div style="background: white; padding: 25px; border-radius: 15px; border: 3px solid #1976d2;">
                    <h4 style="margin: 0 0 20px 0; color: #1976d2; font-size: 1.3rem; text-align: center;">📅 Today's Hours (<?php echo ucfirst($current_day); ?>) - Auto Updated</h4>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; text-align: center;">
                        <div style="background: #e3f2fd; padding: 20px; border-radius: 10px;">
                            <div style="font-size: 1.1rem; font-weight: bold; color: #1976d2; margin-bottom: 10px;">🌅 Opens At</div>
                            <div style="font-size: 2rem; font-weight: bold; color: #2c3e50;">
                                <?php echo $service_info['is_closed'] ? 'CLOSED' : date('g:i A', strtotime($service_info['open_time'])); ?>
                            </div>
                        </div>
                        
                        <div style="background: #f3e5f5; padding: 20px; border-radius: 10px;">
                            <div style="font-size: 1.1rem; font-weight: bold; color: #1976d2; margin-bottom: 10px;">🌅 Closes At</div>
                            <div style="font-size: 2rem; font-weight: bold; color: #2c3e50;">
                                <?php echo $service_info['is_closed'] ? 'CLOSED' : date('g:i A', strtotime($service_info['close_time'])); ?>
                            </div>
                        </div>
                        
                        <div style="background: #e8f5e8; padding: 20px; border-radius: 10px;">
                            <div style="font-size: 1.1rem; font-weight: bold; color: #1976d2; margin-bottom: 10px;">⏰ Service Duration</div>
                            <div style="font-size: 2rem; font-weight: bold; color: #2c3e50;">
                                <?php echo $service_info['is_closed'] ? 'CLOSED' : $service_info['duration']; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div style="text-align: center; margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 10px;">
                        <span style="color: #856404; font-weight: bold;">
                            💡 Hours are managed in the <a href="<?php echo admin_url('admin.php?page=yrr-hours'); ?>" style="color: #856404;">Operating Hours</a> section
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Dynamic Time Slot Configuration -->
            <div style="background: linear-gradient(135deg, #e8f5e8 0%, #f0fff4 100%); padding: 25px; border-radius: 15px; margin-bottom: 30px; border: 2px solid #28a745;">
                <h3 style="margin: 0 0 20px 0; color: #155724; font-size: 1.4rem;">🕐 Dynamic Time Slot Configuration</h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #155724;">Reservation Time Slot Duration</label>
                        <select name="time_slot_duration" id="time_slot_duration" style="width: 100%; padding: 15px; border: 3px solid #28a745; border-radius: 10px; font-size: 1.2rem; font-weight: bold;">
                            <option value="15" <?php selected($settings['time_slot_duration'] ?? '60', '15'); ?>>15 minutes</option>
                            <option value="30" <?php selected($settings['time_slot_duration'] ?? '60', '30'); ?>>30 minutes</option>
                            <option value="45" <?php selected($settings['time_slot_duration'] ?? '60', '45'); ?>>45 minutes</option>
                            <option value="60" <?php selected($settings['time_slot_duration'] ?? '60', '60'); ?>>1 hour (Recommended)</option>
                            <option value="90" <?php selected($settings['time_slot_duration'] ?? '60', '90'); ?>>1.5 hours</option>
                            <option value="120" <?php selected($settings['time_slot_duration'] ?? '60', '120'); ?>>2 hours</option>
                            <option value="180" <?php selected($settings['time_slot_duration'] ?? '60', '180'); ?>>3 hours</option>
                            <option value="240" <?php selected($settings['time_slot_duration'] ?? '60', '240'); ?>>4 hours</option>
                            <option value="300" <?php selected($settings['time_slot_duration'] ?? '60', '300'); ?>>5 hours</option>
                        </select>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #155724;">Booking Buffer Time</label>
                        <select name="booking_buffer_minutes" style="width: 100%; padding: 15px; border: 3px solid #28a745; border-radius: 10px; font-size: 1.2rem; font-weight: bold;">
                            <option value="0" <?php selected($settings['booking_buffer_minutes'] ?? '60', '0'); ?>>No buffer</option>
                            <option value="30" <?php selected($settings['booking_buffer_minutes'] ?? '60', '30'); ?>>30 minutes before closing</option>
                            <option value="60" <?php selected($settings['booking_buffer_minutes'] ?? '60', '60'); ?>>1 hour before closing</option>
                            <option value="120" <?php selected($settings['booking_buffer_minutes'] ?? '60', '120'); ?>>2 hours before closing</option>
                        </select>
                    </div>
                </div>
                
                <!-- Live Time Slot Preview -->
                <div style="background: white; padding: 25px; border-radius: 15px; border: 3px solid #28a745; margin-top: 20px;">
                    <h4 style="margin: 0 0 20px 0; color: #155724; font-size: 1.3rem; text-align: center;">📋 Today's Available Time Slots (Live Preview)</h4>
                    
                    <!-- Slot Count Info -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 20px; text-align: center;">
                        <div style="background: #e3f2fd; padding: 15px; border-radius: 8px;">
                            <div style="font-weight: bold; color: #1976d2;">Selected Duration</div>
                            <div id="selected_duration_display" style="font-size: 1.5rem; font-weight: bold; color: #2c3e50;">1 hour</div>
                        </div>
                        <div style="background: #f3e5f5; padding: 15px; border-radius: 8px;">
                            <div style="font-weight: bold; color: #1976d2;">Available Slots</div>
                            <div id="slots_count_display" style="font-size: 1.5rem; font-weight: bold; color: #2c3e50;">0</div>
                        </div>
                        <div style="background: #e8f5e8; padding: 15px; border-radius: 8px;">
                            <div style="font-weight: bold; color: #1976d2;">Coverage</div>
                            <div id="coverage_display" style="font-size: 1.5rem; font-weight: bold; color: #2c3e50;">0%</div>
                        </div>
                    </div>
                    
                    <!-- Time Slots Grid -->
                    <div id="time_slots_preview" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 12px; max-height: 400px; overflow-y: auto; padding: 15px; background: #f8f9fa; border-radius: 10px; border: 2px solid #dee2e6;">
                        <div style="grid-column: 1/-1; text-align: center; padding: 20px; color: #007cba; font-size: 1.1rem;">🔄 Loading time slots...</div>
                    </div>
                    
                    <!-- Summary Info -->
                    <div id="slots_summary" style="margin-top: 20px; padding: 20px; background: #e8f5e8; border-radius: 10px; text-align: center; font-weight: bold; font-size: 1.1rem; border: 2px solid #28a745;">
                        Time slots will appear here based on your operating hours
                    </div>
                </div>
            </div>
            
            <!-- Restaurant Information -->
            <div style="background: linear-gradient(135deg, #fff3cd 0%, #fefefe 100%); padding: 25px; border-radius: 15px; margin-bottom: 30px; border: 2px solid #ffc107;">
                <h3 style="margin: 0 0 20px 0; color: #856404; font-size: 1.4rem;">🍽️ Restaurant Information</h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold;">Restaurant Name</label>
                        <input type="text" name="restaurant_name" 
                               value="<?php echo esc_attr($settings['restaurant_name'] ?? get_bloginfo('name')); ?>" 
                               style="width: 100%; padding: 15px; border: 2px solid #ffc107; border-radius: 10px; font-size: 1.1rem;">
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold;">Restaurant Email</label>
                        <input type="email" name="restaurant_email" 
                               value="<?php echo esc_attr($settings['restaurant_email'] ?? get_option('admin_email')); ?>" 
                               style="width: 100%; padding: 15px; border: 2px solid #ffc107; border-radius: 10px; font-size: 1.1rem;">
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold;">Maximum Party Size</label>
                        <select name="max_party_size" style="width: 100%; padding: 15px; border: 2px solid #ffc107; border-radius: 10px; font-size: 1.1rem;">
                            <?php for($i = 1; $i <= 20; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php selected($settings['max_party_size'] ?? '12', $i); ?>><?php echo $i; ?> people</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold;">Advance Booking Days</label>
                        <input type="number" name="max_advance_booking" min="1" max="365" 
                               value="<?php echo esc_attr($settings['max_advance_booking'] ?? '30'); ?>" 
                               style="width: 100%; padding: 15px; border: 2px solid #ffc107; border-radius: 10px; font-size: 1.1rem;">
                        <small style="color: #856404; margin-top: 5px; display: block;">How many days ahead customers can book</small>
                    </div>
                </div>
            </div>
            
            <!-- Automation & Notifications -->
            <div style="background: #f3e5f5; padding: 25px; border-radius: 15px; margin-bottom: 30px; border: 2px solid #9c27b0;">
                <h3 style="margin: 0 0 20px 0; color: #7b1fa2; font-size: 1.4rem;">🤖 Automation & Notifications</h3>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold;">Auto-Confirm Reservations</label>
                        <select name="auto_confirm_reservations" style="width: 100%; padding: 15px; border: 2px solid #9c27b0; border-radius: 10px; font-size: 1.1rem;">
                            <option value="0" <?php selected($settings['auto_confirm_reservations'] ?? '0', '0'); ?>>Manual approval required</option>
                            <option value="1" <?php selected($settings['auto_confirm_reservations'] ?? '0', '1'); ?>>Auto-confirm all reservations</option>
                        </select>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold;">Email Notifications</label>
                        <select name="email_notifications" style="width: 100%; padding: 15px; border: 2px solid #9c27b0; border-radius: 10px; font-size: 1.1rem;">
                            <option value="1" <?php selected($settings['email_notifications'] ?? '1', '1'); ?>>✅ Enabled - Send emails</option>
                            <option value="0" <?php selected($settings['email_notifications'] ?? '1', '0'); ?>>❌ Disabled - No emails</option>
                        </select>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold;">Enable Discount Coupons</label>
                        <select name="enable_coupons" style="width: 100%; padding: 15px; border: 2px solid #9c27b0; border-radius: 10px; font-size: 1.1rem;">
                            <option value="1" <?php selected($settings['enable_coupons'] ?? '1', '1'); ?>>✅ Enabled - Allow coupons</option>
                            <option value="0" <?php selected($settings['enable_coupons'] ?? '1', '0'); ?>>❌ Disabled - No coupons</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Save Button -->
            <div style="text-align: center; padding: 20px;">
                <button type="submit" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border: none; padding: 20px 40px; border-radius: 15px; font-size: 1.2rem; font-weight: bold; cursor: pointer; box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3); transition: all 0.3s ease;" 
                        onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(40, 167, 69, 0.5)'"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(40, 167, 69, 0.3)'">
                    💾 Save Settings
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Dynamic Time Slot JavaScript
function updateTimeSlotPreview() {
    const duration = parseInt(document.getElementById('time_slot_duration').value) || 60;
    
    // Update duration display
    const durationDisplay = document.getElementById('selected_duration_display');
    if (durationDisplay) {
        if (duration < 60) {
            durationDisplay.textContent = duration + ' minutes';
        } else {
            const hours = Math.floor(duration / 60);
            const minutes = duration % 60;
            if (minutes === 0) {
                durationDisplay.textContent = hours + (hours === 1 ? ' hour' : ' hours');
            } else {
                durationDisplay.textContent = hours + 'h ' + minutes + 'm';
            }
        }
    }
    
    // Show loading
    const container = document.getElementById('time_slots_preview');
    const countDiv = document.getElementById('slots_count_display');
    const summaryDiv = document.getElementById('slots_summary');
    
    if (container) {
        container.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 20px; color: #007cba; font-size: 1.1rem;">🔄 Calculating time slots...</div>';
    }
    
    // Make AJAX call to get time slots
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            'action': 'get_time_slot_preview',
            'duration': duration,
            'nonce': '<?php echo wp_create_nonce('yrr_ajax_nonce'); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data) {
            displayTimeSlots(data.data, duration);
        } else {
            displayNoSlots();
        }
    })
    .catch(error => {
        console.error('Error loading time slots:', error);
        displayNoSlots();
    });
}

function displayTimeSlots(slots, duration) {
    const container = document.getElementById('time_slots_preview');
    const countDiv = document.getElementById('slots_count_display');
    const summaryDiv = document.getElementById('slots_summary');
    
    if (!container) return;
    
    if (slots.length === 0) {
        displayNoSlots();
        return;
    }
    
    let slotsHTML = '';
    slots.forEach((slot, index) => {
        const bgColor = index % 4 === 0 ? '#e3f2fd' : 
                       index % 4 === 1 ? '#f3e5f5' : 
                       index % 4 === 2 ? '#e8f5e8' : '#fff3cd';
        const borderColor = index % 4 === 0 ? '#1976d2' : 
                           index % 4 === 1 ? '#7b1fa2' : 
                           index % 4 === 2 ? '#388e3c' : '#f57c00';
        
        slotsHTML += `
            <div style="background: ${bgColor}; padding: 12px; border-radius: 8px; text-align: center; font-size: 1rem; font-weight: bold; border: 2px solid ${borderColor}; transition: all 0.2s ease; cursor: pointer;" 
                 onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 4px 15px rgba(0,0,0,0.2)'" 
                 onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='none'">
                ${slot.display}
            </div>
        `;
    });
    
    container.innerHTML = slotsHTML;
    
    // Update count display
    if (countDiv) {
        countDiv.textContent = slots.length;
    }
    
    // Calculate coverage and summary
    const serviceDuration = <?php echo !$service_info['is_closed'] ? 
        ((intval(substr($service_info['close_time'], 0, 2)) * 60 + intval(substr($service_info['close_time'], 3, 2))) - 
         (intval(substr($service_info['open_time'], 0, 2)) * 60 + intval(substr($service_info['open_time'], 3, 2)))) : 720; ?>;
    
    const totalPossibleSlots = Math.floor(serviceDuration / duration);
    const coverage = Math.round((slots.length / totalPossibleSlots) * 100);
    
    document.getElementById('coverage_display').textContent = coverage + '%';
    
    if (summaryDiv) {
        let summaryText = `✅ ${slots.length} time slots available today`;
        if (slots.length > 0) {
            summaryText += ` | First: ${slots[0].display} | Last: ${slots[slots.length-1].display}`;
        }
        summaryDiv.innerHTML = `<span style="color: #28a745;">${summaryText}</span>`;
    }
}

function displayNoSlots() {
    const container = document.getElementById('time_slots_preview');
    const countDiv = document.getElementById('slots_count_display');
    const summaryDiv = document.getElementById('slots_summary');
    
    if (container) {
        container.innerHTML = '<div style="grid-column: 1/-1; text-align: center; color: #dc3545; padding: 30px; font-weight: bold; font-size: 1.1rem;">🔴 Restaurant is closed today or no valid hours set</div>';
    }
    
    if (countDiv) {
        countDiv.textContent = '0';
    }
    
    document.getElementById('coverage_display').textContent = '0%';
    
    if (summaryDiv) {
        summaryDiv.innerHTML = '<span style="color: #dc3545;">❌ No time slots available - Check operating hours</span>';
    }
}

// Initialize on page load and when duration changes
document.addEventListener('DOMContentLoaded', function() {
    updateTimeSlotPreview();
    
    const durationSelect = document.getElementById('time_slot_duration');
    if (durationSelect) {
        durationSelect.addEventListener('change', updateTimeSlotPreview);
    }
});

// Auto-hide success messages
setTimeout(function() {
    const notices = document.querySelectorAll('.notice.is-dismissible');
    notices.forEach(function(notice) {
        notice.style.opacity = '0';
        setTimeout(function() {
            notice.style.display = 'none';
        }, 300);
    });
}, 5000);
</script>

<style>
@media (max-width: 768px) {
    div[style*="grid-template-columns: 1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
    div[style*="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr))"] {
        grid-template-columns: 1fr !important;
    }
}
</style>
