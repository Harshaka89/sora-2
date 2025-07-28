<?php
if (!defined('ABSPATH')) exit;

// Safe defaults
$settings = $settings ?? array();
$available_slots = $available_slots ?? array();
$enabled_slots = explode(',', $settings['enabled_time_slots'] ?? '');
$max_bookings = $settings['max_bookings_per_slot'] ?? '5';
$booking_duration = $settings['booking_duration'] ?? '60';
$enable_slots = $settings['enable_time_slots'] ?? '1';

// Success message
if (isset($_GET['message']) && $_GET['message'] === 'slots_saved') {
    echo '<div class="notice notice-success is-dismissible"><p>✅ Time slots settings saved successfully!</p></div>';
}
?>

<div class="wrap">
    <div style="max-width: 1000px; margin: 20px auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
        
        <!-- Header -->
        <div style="text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 3px solid #28a745;">
            <h1 style="font-size: 2.5rem; color: #2c3e50; margin: 0; display: flex; align-items: center; justify-content: center; gap: 15px;">
                <span style="font-size: 3rem;">🕐</span>
                <span>Simple Time Slots Management</span>
            </h1>
            <p style="color: #6c757d; margin: 10px 0 0 0; font-size: 1.1rem;">Easy time slot selection for admin use</p>
        </div>
        
        <form method="post" action="">
            <?php wp_nonce_field('yrr_simple_slots', 'slots_nonce'); ?>
            <input type="hidden" name="save_simple_slots" value="1">
            
            <!-- Enable/Disable -->
            <div style="background: #e3f2fd; padding: 25px; border-radius: 15px; margin-bottom: 25px;">
                <h3 style="margin: 0 0 20px 0; color: #1976d2; display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 1.5rem;">⚙️</span>
                    <span>Time Slots Settings</span>
                </h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div>
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-weight: bold;">
                            <input type="checkbox" name="enable_time_slots" value="1" <?php checked($enable_slots, '1'); ?> style="width: 20px; height: 20px;">
                            <span>Enable Time Slots</span>
                        </label>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold;">Max Bookings Per Slot</label>
                        <input type="number" name="max_bookings_per_slot" value="<?php echo esc_attr($max_bookings); ?>" min="1" max="20" 
                               style="width: 100%; padding: 10px; border: 2px solid #e9ecef; border-radius: 8px;">
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold;">Booking Duration (minutes)</label>
                        <select name="booking_duration" style="width: 100%; padding: 10px; border: 2px solid #e9ecef; border-radius: 8px;">
                            <option value="30" <?php selected($booking_duration, '30'); ?>>30 minutes</option>
                            <option value="60" <?php selected($booking_duration, '60'); ?>>1 hour</option>
                            <option value="90" <?php selected($booking_duration, '90'); ?>>1.5 hours</option>
                            <option value="120" <?php selected($booking_duration, '120'); ?>>2 hours</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Time Slots Selection -->
            <div style="background: #e8f5e8; padding: 25px; border-radius: 15px; margin-bottom: 25px;">
                <h3 style="margin: 0 0 20px 0; color: #155724; display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 1.5rem;">📋</span>
                    <span>Select Available Time Slots</span>
                </h3>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <?php foreach ($available_slots as $time => $display): ?>
                        <label style="display: flex; align-items: center; gap: 10px; padding: 15px; background: white; border-radius: 10px; cursor: pointer; border: 2px solid #dee2e6; transition: all 0.3s ease;">
                            <input type="checkbox" name="enabled_slots[]" value="<?php echo esc_attr($time); ?>" 
                                   <?php checked(in_array($time, $enabled_slots)); ?>
                                   style="width: 18px; height: 18px;">
                            <span style="font-weight: bold; font-size: 1.1rem;"><?php echo esc_html($display); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                
                <!-- Quick Select Buttons -->
                <div style="margin-top: 20px; text-align: center; padding-top: 20px; border-top: 2px solid #dee2e6;">
                    <button type="button" onclick="selectAll()" style="background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 8px; margin: 0 10px; cursor: pointer; font-weight: bold;">
                        ✅ Select All
                    </button>
                    <button type="button" onclick="selectNone()" style="background: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 8px; margin: 0 10px; cursor: pointer; font-weight: bold;">
                        ❌ Select None
                    </button>
                    <button type="button" onclick="selectPrime()" style="background: #007cba; color: white; border: none; padding: 10px 20px; border-radius: 8px; margin: 0 10px; cursor: pointer; font-weight: bold;">
                        ⭐ Prime Times Only
                    </button>
                </div>
            </div>
            
            <!-- Save Button -->
            <div style="text-align: center; padding: 25px; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); border-radius: 15px;">
                <button type="submit" style="background: white; color: #28a745; border: none; padding: 18px 40px; border-radius: 12px; font-weight: bold; font-size: 1.2rem; cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                    💾 Save Time Slots Settings
                </button>
            </div>
        </form>
        
        <!-- Preview Section -->
        <div style="margin-top: 30px; padding: 25px; background: #f8f9fa; border-radius: 15px;">
            <h3 style="margin: 0 0 20px 0; color: #2c3e50; display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 1.5rem;">👁️</span>
                <span>Current Configuration Preview</span>
            </h3>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <h4 style="color: #28a745; margin: 0 0 15px 0;">✅ Enabled Time Slots:</h4>
                    <div id="enabled-preview" style="max-height: 200px; overflow-y: auto; padding: 15px; background: white; border-radius: 8px; border: 2px solid #28a745;">
                        <?php 
                        if (!empty($enabled_slots) && $enabled_slots[0] !== '') {
                            foreach ($enabled_slots as $slot) {
                                if (isset($available_slots[$slot])) {
                                    echo '<div style="padding: 5px 0; border-bottom: 1px solid #eee;">🕐 ' . esc_html($available_slots[$slot]) . '</div>';
                                }
                            }
                        } else {
                            echo '<div style="color: #6c757d; font-style: italic;">No time slots enabled</div>';
                        }
                        ?>
                    </div>
                </div>
                
                <div>
                    <h4 style="color: #6c757d; margin: 0 0 15px 0;">⚙️ Settings Summary:</h4>
                    <div style="padding: 15px; background: white; border-radius: 8px; border: 2px solid #6c757d;">
                        <div style="margin-bottom: 10px;"><strong>Status:</strong> <?php echo $enable_slots == '1' ? '✅ Enabled' : '❌ Disabled'; ?></div>
                        <div style="margin-bottom: 10px;"><strong>Max per Slot:</strong> <?php echo esc_html($max_bookings); ?> bookings</div>
                        <div style="margin-bottom: 10px;"><strong>Duration:</strong> <?php echo esc_html($booking_duration); ?> minutes</div>
                        <div><strong>Total Slots:</strong> <?php echo count(array_filter($enabled_slots)); ?> enabled</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function selectAll() {
    const checkboxes = document.querySelectorAll('input[name="enabled_slots[]"]');
    checkboxes.forEach(cb => cb.checked = true);
    updatePreview();
}

function selectNone() {
    const checkboxes = document.querySelectorAll('input[name="enabled_slots[]"]');
    checkboxes.forEach(cb => cb.checked = false);
    updatePreview();
}

function selectPrime() {
    const primeSlots = ['12:00:00', '12:30:00', '18:00:00', '18:30:00', '19:00:00', '19:30:00', '20:00:00'];
    const checkboxes = document.querySelectorAll('input[name="enabled_slots[]"]');
    
    checkboxes.forEach(cb => {
        cb.checked = primeSlots.includes(cb.value);
    });
    updatePreview();
}

function updatePreview() {
    const enabledPreview = document.getElementById('enabled-preview');
    const checkboxes = document.querySelectorAll('input[name="enabled_slots[]"]:checked');
    
    if (checkboxes.length === 0) {
        enabledPreview.innerHTML = '<div style="color: #6c757d; font-style: italic;">No time slots enabled</div>';
    } else {
        let html = '';
        checkboxes.forEach(cb => {
            const label = cb.closest('label').querySelector('span').textContent;
            html += '<div style="padding: 5px 0; border-bottom: 1px solid #eee;">🕐 ' + label + '</div>';
        });
        enabledPreview.innerHTML = html;
    }
}

// Add change listeners to update preview
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('input[name="enabled_slots[]"]');
    checkboxes.forEach(cb => {
        cb.addEventListener('change', updatePreview);
    });
});
</script>

<style>
/* Hover effects for time slot checkboxes */
label:has(input[name="enabled_slots[]"]) {
    transition: all 0.3s ease;
}

label:has(input[name="enabled_slots[]"]:checked) {
    background: #e8f5e8 !important;
    border-color: #28a745 !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.2);
}

label:has(input[name="enabled_slots[]"]):hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

@media (max-width: 768px) {
    div[style*="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr))"] {
        grid-template-columns: 1fr 1fr !important;
    }
    
    div[style*="grid-template-columns: 1fr 1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>
