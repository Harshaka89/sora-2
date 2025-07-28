<?php
if (!defined('ABSPATH')) exit;

// Handle success messages
$message = '';
if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        case 'hours_saved':
            $count = isset($_GET['count']) ? intval($_GET['count']) : 0;
            $message = '<div class="notice notice-success is-dismissible"><p>✅ Operating hours saved successfully! (' . $count . ' days updated)</p></div>';
            break;
        case 'error':
            $message = '<div class="notice notice-error is-dismissible"><p>❌ Error saving hours. Please try again.</p></div>';
            break;
    }
}

// Set defaults for hours data
$hours = isset($hours) ? $hours : array();

// Days configuration
$days_config = array(
    'monday' => array('name' => 'Monday', 'icon' => '📅', 'color' => '#e3f2fd'),
    'tuesday' => array('name' => 'Tuesday', 'icon' => '📅', 'color' => '#f3e5f5'),
    'wednesday' => array('name' => 'Wednesday', 'icon' => '📅', 'color' => '#e8f5e8'),
    'thursday' => array('name' => 'Thursday', 'icon' => '📅', 'color' => '#fff3cd'),
    'friday' => array('name' => 'Friday', 'icon' => '🎉', 'color' => '#f8d7da'),
    'saturday' => array('name' => 'Saturday', 'icon' => '🌟', 'color' => '#e2e3e5'),
    'sunday' => array('name' => 'Sunday', 'icon' => '☀️', 'color' => '#d1ecf1')
);

// ✅ FIXED: Helper function to safely get hour properties (handles both objects and arrays)
function yrr_get_hour_value($hour_data, $property, $default = '') {
    if (is_object($hour_data) && property_exists($hour_data, $property)) {
        return $hour_data->$property;
    } elseif (is_array($hour_data) && isset($hour_data[$property])) {
        return $hour_data[$property];
    }
    return $default;
}

// Helper function to format time for display
function yrr_format_time($time) {
    if (empty($time) || $time === '00:00:00') return '';
    return date('H:i', strtotime($time));
}
?>

<div class="wrap">
    <?php echo $message; ?>
    
    <div style="max-width: 1400px; margin: 20px auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
        
        <!-- Header -->
        <div style="text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 3px solid #007cba;">
            <h1 style="font-size: 2.5rem; color: #2c3e50; margin: 0;">🕐 Operating Hours Management</h1>
            <p style="color: #6c757d; margin: 10px 0 0 0;">Set weekly operating hours and manage availability</p>
        </div>
        
        <!-- Current Operating Status -->
        <div style="background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%); padding: 20px; border-radius: 15px; margin-bottom: 30px; border: 2px solid #1976d2;">
            <h3 style="margin: 0 0 15px 0; color: #1976d2; font-size: 1.4rem; text-align: center;">📊 Current Operating Status</h3>
            
            <?php
            $current_day = strtolower(date('l')); // Current day (monday, tuesday, etc.)
            $today_hours = isset($hours[$current_day]) ? $hours[$current_day] : null;
            $is_open_today = $today_hours ? !yrr_get_hour_value($today_hours, 'is_closed', 0) : false;
            ?>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; text-align: center;">
                <div style="background: white; padding: 20px; border-radius: 10px; border: 2px solid #1976d2;">
                    <div style="font-size: 1.1rem; font-weight: bold; color: #1976d2; margin-bottom: 10px;">Today (<?php echo ucfirst($current_day); ?>)</div>
                    <div style="font-size: 1.5rem; font-weight: bold; color: <?php echo $is_open_today ? '#28a745' : '#dc3545'; ?>;">
                        <?php echo $is_open_today ? '🟢 OPEN' : '🔴 CLOSED'; ?>
                    </div>
                </div>
                
                <div style="background: white; padding: 20px; border-radius: 10px; border: 2px solid #1976d2;">
                    <div style="font-size: 1.1rem; font-weight: bold; color: #1976d2; margin-bottom: 10px;">Hours Today</div>
                    <div style="font-size: 1.2rem; font-weight: bold; color: #2c3e50;">
                        <?php if ($is_open_today && $today_hours): ?>
                            <?php echo date('g:i A', strtotime(yrr_get_hour_value($today_hours, 'open_time', '10:00:00'))); ?> - 
                            <?php echo date('g:i A', strtotime(yrr_get_hour_value($today_hours, 'close_time', '22:00:00'))); ?>
                        <?php else: ?>
                            Closed
                        <?php endif; ?>
                    </div>
                </div>
                
                <div style="background: white; padding: 20px; border-radius: 10px; border: 2px solid #1976d2;">
                    <div style="font-size: 1.1rem; font-weight: bold; color: #1976d2; margin-bottom: 10px;">Next Change</div>
                    <div style="font-size: 1.2rem; font-weight: bold; color: #2c3e50;">
                        <?php
                        // Find next day with different status
                        $days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
                        $current_day_index = array_search($current_day, $days);
                        $next_change = 'Same schedule';
                        
                        for ($i = 1; $i <= 7; $i++) {
                            $next_day_index = ($current_day_index + $i) % 7;
                            $next_day = $days[$next_day_index];
                            $next_day_hours = isset($hours[$next_day]) ? $hours[$next_day] : null;
                            $next_day_open = $next_day_hours ? !yrr_get_hour_value($next_day_hours, 'is_closed', 0) : false;
                            
                            if ($next_day_open !== $is_open_today) {
                                $next_change = ucfirst($next_day) . ' (' . ($next_day_open ? 'Opens' : 'Closes') . ')';
                                break;
                            }
                        }
                        echo $next_change;
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Hours Form -->
        <form method="post" action="">
            <?php wp_nonce_field('yrr_hours_save', 'hours_nonce'); ?>
            <input type="hidden" name="save_hours" value="1">
            
            <div style="background: #f8f9fa; padding: 25px; border-radius: 15px; border: 2px solid #dee2e6;">
                <h3 style="margin: 0 0 25px 0; color: #495057; font-size: 1.4rem; text-align: center;">📅 Weekly Schedule Configuration</h3>
                
                <?php foreach ($days_config as $day => $config): ?>
                    <?php 
                    // ✅ FIXED: Use helper function to safely get hour properties
                    $day_hours = isset($hours[$day]) ? $hours[$day] : null;
                    $is_closed = yrr_get_hour_value($day_hours, 'is_closed', 0);
                    $open_time = yrr_format_time(yrr_get_hour_value($day_hours, 'open_time', '10:00:00'));
                    $close_time = yrr_format_time(yrr_get_hour_value($day_hours, 'close_time', '22:00:00'));
                    
                    // Set default times if empty
                    if (empty($open_time)) $open_time = '10:00';
                    if (empty($close_time)) $close_time = '22:00';
                    ?>
                    
                    <div style="background: <?php echo $config['color']; ?>; padding: 20px; margin-bottom: 15px; border-radius: 12px; border: 2px solid #dee2e6; <?php echo $day === $current_day ? 'border-color: #28a745; border-width: 3px;' : ''; ?>">
                        <div style="display: grid; grid-template-columns: 180px 1fr 1fr 1fr 150px 120px; gap: 20px; align-items: center;">
                            
                            <!-- Day Name -->
                            <div style="font-weight: bold; font-size: 1.2rem; color: #2c3e50; display: flex; align-items: center; gap: 8px;">
                                <span style="font-size: 1.5rem;"><?php echo $config['icon']; ?></span>
                                <div>
                                    <?php echo $config['name']; ?>
                                    <?php if ($day === $current_day): ?>
                                        <div style="font-size: 0.8rem; color: #28a745; font-weight: bold;">TODAY</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Open Time -->
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #155724; font-size: 1rem;">🌅 Opening Time</label>
                                <input type="time" 
                                       name="<?php echo $day; ?>_open" 
                                       id="<?php echo $day; ?>_open"
                                       value="<?php echo esc_attr($open_time); ?>" 
                                       <?php echo $is_closed ? 'disabled' : ''; ?>
                                       onchange="updateDayStatus('<?php echo $day; ?>')"
                                       style="width: 100%; padding: 12px; border: 3px solid #28a745; border-radius: 8px; box-sizing: border-box; font-size: 1.1rem; font-weight: bold; text-align: center; <?php echo $is_closed ? 'opacity: 0.5;' : ''; ?>">
                            </div>
                            
                            <!-- Close Time -->
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #721c24; font-size: 1rem;">🌅 Closing Time</label>
                                <input type="time" 
                                       name="<?php echo $day; ?>_close" 
                                       id="<?php echo $day; ?>_close"
                                       value="<?php echo esc_attr($close_time); ?>" 
                                       <?php echo $is_closed ? 'disabled' : ''; ?>
                                       onchange="updateDayStatus('<?php echo $day; ?>')"
                                       style="width: 100%; padding: 12px; border: 3px solid #dc3545; border-radius: 8px; box-sizing: border-box; font-size: 1.1rem; font-weight: bold; text-align: center; <?php echo $is_closed ? 'opacity: 0.5;' : ''; ?>">
                            </div>
                            
                            <!-- Service Duration -->
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #1976d2; font-size: 1rem;">⏰ Duration</label>
                                <div id="<?php echo $day; ?>_duration" style="padding: 12px; background: white; border: 3px solid #1976d2; border-radius: 8px; text-align: center; font-weight: bold; font-size: 1.1rem; color: #1976d2;">
                                    <!-- Will be calculated by JavaScript -->
                                </div>
                            </div>
                            
                            <!-- Status Display -->
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #495057; font-size: 1rem;">Status</label>
                                <div id="<?php echo $day; ?>_status" style="text-align: center; font-weight: bold; padding: 8px; border-radius: 8px; font-size: 1rem;">
                                    <!-- Will be updated by JavaScript -->
                                </div>
                            </div>
                            
                            <!-- Closed Checkbox -->
                            <div style="text-align: center;">
                                <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #dc3545; font-size: 1rem;">Closed</label>
                                <label style="display: flex; align-items: center; gap: 8px; justify-content: center; cursor: pointer; padding: 10px; background: white; border-radius: 8px; border: 2px solid #dc3545;">
                                    <input type="checkbox" 
                                           name="<?php echo $day; ?>_closed" 
                                           id="<?php echo $day; ?>_closed"
                                           value="1" 
                                           <?php checked($is_closed, 1); ?>
                                           onchange="updateDayStatus('<?php echo $day; ?>')"
                                           style="transform: scale(1.5);">
                                    <span style="font-weight: bold; color: #dc3545; font-size: 1rem;">❌</span>
                                </label>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Save Button -->
            <div style="text-align: center; margin-top: 30px; padding: 20px;">
                <button type="submit" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border: none; padding: 20px 40px; border-radius: 15px; font-size: 1.3rem; font-weight: bold; cursor: pointer; box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4); transition: all 0.3s ease;" 
                        onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 12px 35px rgba(40, 167, 69, 0.6)'"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 8px 25px rgba(40, 167, 69, 0.4)'">
                    💾 Save Operating Hours
                </button>
                <div style="margin-top: 15px; color: #6c757d; font-size: 1rem;">
                    Changes will take effect immediately and update your reservation availability
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function updateDayStatus(day) {
    const openInput = document.getElementById(day + '_open');
    const closeInput = document.getElementById(day + '_close');
    const closedCheckbox = document.getElementById(day + '_closed');
    const statusDiv = document.getElementById(day + '_status');
    const durationDiv = document.getElementById(day + '_duration');
    
    if (!openInput || !closeInput || !closedCheckbox || !statusDiv || !durationDiv) return;
    
    if (closedCheckbox.checked) {
        // Day is closed
        statusDiv.innerHTML = '<span style="color: #dc3545; background: #f8d7da; padding: 8px 12px; border-radius: 15px;">🔴 CLOSED</span>';
        durationDiv.innerHTML = '<span style="color: #dc3545;">Closed</span>';
        
        openInput.disabled = true;
        closeInput.disabled = true;
        openInput.style.opacity = '0.5';
        closeInput.style.opacity = '0.5';
    } else {
        // Day is open
        openInput.disabled = false;
        closeInput.disabled = false;
        openInput.style.opacity = '1';
        closeInput.
