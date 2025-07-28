<?php
if (!defined('ABSPATH')) exit;
?>

<div class="wrap">
    <div style="max-width: 1200px; margin: 20px auto; background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
        
        <div style="text-align: center; margin-bottom: 40px; padding-bottom: 25px; border-bottom: 4px solid #667eea;">
            <h1 style="font-size: 2.5rem; color: #2c3e50; margin: 0;">⚙️ Yenolx Restaurant Settings v1.5</h1>
            <p style="color: #6c757d; margin: 15px 0 0 0; font-size: 1.1rem;">Complete restaurant management configuration</p>
        </div>
        
        <?php if (isset($_GET['message']) && $_GET['message'] == 'saved'): ?>
            <div style="background: #d4edda; color: #155724; padding: 15px; margin: 20px 0; border-radius: 8px; border: 2px solid #28a745;">
                <h3 style="margin: 0 0 10px 0;">✅ Settings Saved Successfully!</h3>
                <p style="margin: 0;">
                    <?php echo isset($_GET['count']) ? intval($_GET['count']) : 0; ?> settings saved to database.
                    <?php if (isset($_GET['error_count']) && $_GET['error_count'] > 0): ?>
                        <br><span style="color: #856404;">⚠️ <?php echo intval($_GET['error_count']); ?> errors occurred.</span>
                    <?php endif; ?>
                    <br><strong>Updated:</strong> <?php echo date('Y-m-d H:i:s'); ?>
                </p>
            </div>
        <?php endif; ?>
        
        <!-- Current Settings Display -->
        <div style="background: #e3f2fd; padding: 20px; border-radius: 10px; margin-bottom: 30px; border-left: 5px solid #2196f3;">
            <h3 style="margin: 0 0 15px 0; color: #1976d2;">📊 Current Settings Status</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <strong>Status:</strong> <?php echo ($settings['restaurant_open'] ?? '1') == '1' ? '🟢 OPEN' : '🔴 CLOSED'; ?>
                </div>
                <div>
                    <strong>Restaurant:</strong> <?php echo esc_html($settings['restaurant_name'] ?? get_bloginfo('name')); ?>
                </div>
                <div>
                    <strong>Email:</strong> <?php echo esc_html($settings['restaurant_email'] ?? get_option('admin_email')); ?>
                </div>
                <div>
                    <strong>Phone:</strong> <?php echo !empty($settings['restaurant_phone']) ? esc_html($settings['restaurant_phone']) : '📞 Not set'; ?>
                </div>
                <div>
                    <strong>Address:</strong> <?php echo !empty($settings['restaurant_address']) ? esc_html($settings['restaurant_address']) : '📍 Not set'; ?>
                </div>
                <div>
                    <strong>Base Price:</strong> <?php echo esc_html($settings['currency_symbol'] ?? '$') . number_format(floatval($settings['base_price_per_person'] ?? 0), 2); ?>/person
                </div>
            </div>
        </div>
        
        <form method="post" action="">
            <?php wp_nonce_field('yrr_settings_save', 'settings_nonce'); ?>
            
            <!-- Restaurant Status -->
            <div style="margin-bottom: 40px; padding: 30px; background: #f8f9fa; border-radius: 15px; border: 3px solid #e9ecef;">
                <h2 style="color: #007cba; font-size: 1.6rem; margin: 0 0 25px 0; border-bottom: 3px solid #007cba; padding-bottom: 15px;">
                    🔄 Restaurant Status
                </h2>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <label style="display: flex; align-items: center; gap: 15px; font-size: 1.2rem; font-weight: bold; padding: 20px; border-radius: 10px; cursor: pointer; color: #28a745; background: white; border: 3px solid #28a745;">
                        <input type="radio" name="restaurant_open" value="1" <?php checked(($settings['restaurant_open'] ?? '1'), '1'); ?> style="transform: scale(2);">
                        <span>🟢 OPEN - Accept Reservations</span>
                    </label>
                    
                    <label style="display: flex; align-items: center; gap: 15px; font-size: 1.2rem; font-weight: bold; padding: 20px; border-radius: 10px; cursor: pointer; color: #dc3545; background: white; border: 3px solid #dc3545;">
                        <input type="radio" name="restaurant_open" value="0" <?php checked(($settings['restaurant_open'] ?? '1'), '0'); ?> style="transform: scale(2);">
                        <span>🔴 CLOSED - Stop Reservations</span>
                    </label>
                </div>
            </div>
            
            <!-- Restaurant Information -->
            <div style="margin-bottom: 40px; padding: 30px; background: #f8f9fa; border-radius: 15px; border: 3px solid #e9ecef;">
                <h2 style="color: #007cba; font-size: 1.6rem; margin: 0 0 25px 0; border-bottom: 3px solid #007cba; padding-bottom: 15px;">
                    🏪 Restaurant Information
                </h2>
                
                <!-- Restaurant Name -->
                <div style="margin-bottom: 25px;">
                    <label style="display: block; margin-bottom: 10px; font-weight: bold; font-size: 1.1rem; color: #2c3e50;">🏷️ Restaurant Name *</label>
                    <input type="text" name="restaurant_name" value="<?php echo esc_attr($settings['restaurant_name'] ?? get_bloginfo('name')); ?>" 
                           style="width: 100%; padding: 15px; border: 3px solid #e9ecef; border-radius: 10px; font-size: 1.1rem; box-sizing: border-box;" required>
                </div>
                
                <!-- Contact Information Grid -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 25px;">
                    <div>
                        <label style="display: block; margin-bottom: 10px; font-weight: bold; font-size: 1.1rem; color: #2c3e50;">📧 Contact Email *</label>
                        <input type="email" name="restaurant_email" value="<?php echo esc_attr($settings['restaurant_email'] ?? get_option('admin_email')); ?>" 
                               style="width: 100%; padding: 15px; border: 3px solid #e9ecef; border-radius: 10px; font-size: 1.1rem; box-sizing: border-box;" required>
                        <small style="color: #6c757d; display: block; margin-top: 5px;">For reservation notifications</small>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 10px; font-weight: bold; font-size: 1.1rem; color: #2c3e50;">📞 Phone Number</label>
                        <input type="tel" 
                               name="restaurant_phone" 
                               value="<?php echo esc_attr($settings['restaurant_phone'] ?? ''); ?>" 
                               placeholder="+1 (555) 123-4567"
                               style="width: 100%; padding: 15px; border: 3px solid #e9ecef; border-radius: 10px; font-size: 1.1rem; box-sizing: border-box;">
                        <small style="color: #6c757d; display: block; margin-top: 5px;">Customer contact number</small>
                    </div>
                </div>
                
                <!-- Address Field -->
                <div style="margin-bottom: 25px;">
                    <label style="display: block; margin-bottom: 10px; font-weight: bold; font-size: 1.1rem; color: #2c3e50;">📍 Restaurant Address</label>
                    <input type="text" name="restaurant_address" value="<?php echo esc_attr($settings['restaurant_address'] ?? ''); ?>" 
                           placeholder="123 Main Street, City, State 12345"
                           style="width: 100%; padding: 15px; border: 3px solid #e9ecef; border-radius: 10px; font-size: 1.1rem; box-sizing: border-box;">
                    <small style="color: #6c757d; display: block; margin-top: 5px;">Full restaurant address</small>
                </div>
            </div>
            
            <!-- Booking Configuration -->
            <div style="margin-bottom: 40px; padding: 30px; background: #e8f5e8; border-radius: 15px; border: 3px solid #28a745;">
                <h2 style="color: #28a745; font-size: 1.6rem; margin: 0 0 25px 0; border-bottom: 3px solid #28a745; padding-bottom: 15px;">
                    📋 Booking Configuration
                </h2>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px;">
                    <div>
                        <label style="display: block; margin-bottom: 10px; font-weight: bold; font-size: 1.1rem; color: #2c3e50;">👥 Maximum Party Size *</label>
                        <input type="number" name="max_party_size" value="<?php echo esc_attr($settings['max_party_size'] ?? '12'); ?>" 
                               min="1" max="50" required
                               style="width: 100%; padding: 15px; border: 3px solid #e9ecef; border-radius: 10px; text-align: center; font-weight: bold; font-size: 1.3rem; box-sizing: border-box;">
                        <small style="color: #6c757d; display: block; margin-top: 5px;">Largest group you can serve</small>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 10px; font-weight: bold; font-size: 1.1rem; color: #2c3e50;">⏰ Time Slot Duration</label>
                        <select name="booking_time_slots" style="width: 100%; padding: 15px; border: 3px solid #e9ecef; border-radius: 10px; font-size: 1.1rem; box-sizing: border-box;">
                            <option value="15" <?php selected($settings['booking_time_slots'] ?? '30', '15'); ?>>15 minutes</option>
                            <option value="30" <?php selected($settings['booking_time_slots'] ?? '30', '30'); ?>>30 minutes</option>
                            <option value="60" <?php selected($settings['booking_time_slots'] ?? '30', '60'); ?>>1 hour</option>
                        </select>
                        <small style="color: #6c757d; display: block; margin-top: 5px;">Booking time intervals</small>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 10px; font-weight: bold; font-size: 1.1rem; color: #2c3e50;">📅 Advance Booking Days</label>
                        <input type="number" name="max_booking_advance_days" value="<?php echo esc_attr($settings['max_booking_advance_days'] ?? '60'); ?>" 
                               min="1" max="365"
                               style="width: 100%; padding: 15px; border: 3px solid #e9ecef; border-radius: 10px; text-align: center; font-size: 1.1rem; box-sizing: border-box;">
                        <small style="color: #6c757d; display: block; margin-top: 5px;">Days in advance customers can book</small>
                    </div>
                </div>
            </div>
            
            <!-- Pricing Configuration -->
            <div style="margin-bottom: 40px; padding: 30px; background: #fff3cd; border-radius: 15px; border: 3px solid #ffc107;">
                <h2 style="color: #856404; font-size: 1.6rem; margin: 0 0 25px 0; border-bottom: 3px solid #ffc107; padding-bottom: 15px;">
                    💰 Pricing Configuration
                </h2>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 25px;">
                    <div>
                        <label style="display: block; margin-bottom: 10px; font-weight: bold; font-size: 1.1rem; color: #2c3e50;">💵 Base Price Per Person</label>
                        <input type="number" name="base_price_per_person" value="<?php echo esc_attr($settings['base_price_per_person'] ?? '0.00'); ?>" 
                               min="0" step="0.01"
                               style="width: 100%; padding: 15px; border: 3px solid #e9ecef; border-radius: 10px; text-align: center; font-size: 1.1rem; box-sizing: border-box;">
                        <small style="color: #6c757d; display: block; margin-top: 5px;">Base reservation fee per guest</small>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 10px; font-weight: bold; font-size: 1.1rem; color: #2c3e50;">💱 Currency Symbol</label>
                        <input type="text" name="currency_symbol" value="<?php echo esc_attr($settings['currency_symbol'] ?? '$'); ?>" 
                               maxlength="3"
                               style="width: 100%; padding: 15px; border: 3px solid #e9ecef; border-radius: 10px; text-align: center; font-size: 1.3rem; box-sizing: border-box;">
                        <small style="color: #6c757d; display: block; margin-top: 5px;">Currency symbol for pricing</small>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 10px; font-weight: bold; font-size: 1.1rem; color: #2c3e50;">⏱️ Booking Buffer</label>
                        <select name="booking_buffer_minutes" style="width: 100%; padding: 15px; border: 3px solid #e9ecef; border-radius: 10px; font-size: 1.1rem; box-sizing: border-box;">
                            <option value="0" <?php selected($settings['booking_buffer_minutes'] ?? '15', '0'); ?>>No buffer</option>
                            <option value="15" <?php selected($settings['booking_buffer_minutes'] ?? '15', '15'); ?>>15 minutes</option>
                            <option value="30" <?php selected($settings['booking_buffer_minutes'] ?? '15', '30'); ?>>30 minutes</option>
                            <option value="60" <?php selected($settings['booking_buffer_minutes'] ?? '15', '60'); ?>>1 hour</option>
                        </select>
                        <small style="color: #6c757d; display: block; margin-top: 5px;">Time between bookings for cleaning</small>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 10px; font-weight: bold; font-size: 1.1rem; color: #2c3e50;">🍽️ Max Dining Duration</label>
                        <select name="max_dining_duration" style="width: 100%; padding: 15px; border: 3px solid #e9ecef; border-radius: 10px; font-size: 1.1rem; box-sizing: border-box;">
                            <option value="90" <?php selected($settings['max_dining_duration'] ?? '120', '90'); ?>>90 minutes</option>
                            <option value="120" <?php selected($settings['max_dining_duration'] ?? '120', '120'); ?>>2 hours</option>
                            <option value="150" <?php selected($settings['max_dining_duration'] ?? '120', '150'); ?>>2.5 hours</option>
                            <option value="180" <?php selected($settings['max_dining_duration'] ?? '120', '180'); ?>>3 hours</option>
                        </select>
                        <small style="color: #6c757d; display: block; margin-top: 5px;">Maximum time per reservation</small>
                    </div>
                </div>
            </div>
            
            <!-- Save Button -->
            <div style="text-align: center; padding-top: 40px; border-top: 4px solid #e9ecef;">
                <button type="submit" name="save_settings" value="1" 
                        style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border: none; padding: 25px 60px; border-radius: 15px; font-size: 1.4rem; font-weight: bold; cursor: pointer; transition: all 0.3s ease;">
                    💾 Save All Settings
                </button>
                <p style="margin-top: 15px; color: #6c757d;">
                    All changes will be saved immediately to the database.<br>
                    <strong>Enhanced validation:</strong> Phone numbers, addresses, and pricing will be validated.
                </p>
            </div>
        </form>
        
        <!-- System Status -->
        <div style="margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 10px; border: 2px solid #dee2e6;">
            <h4 style="margin: 0 0 15px 0; color: #495057;">🔧 System Status & Debug</h4>
            <?php
            global $wpdb;
            $tables_status = array();
            $tables = array('yrr_settings', 'yrr_reservations', 'yrr_tables', 'yrr_operating_hours', 'yrr_pricing_rules');
            
            foreach ($tables as $table) {
                $full_table_name = $wpdb->prefix . $table;
                $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'") == $full_table_name;
                $count = $exists ? $wpdb->get_var("SELECT COUNT(*) FROM $full_table_name") : 0;
                $tables_status[] = $table . ': ' . ($exists ? "✅ ($count records)" : '❌ MISSING');
            }
            ?>
            <div style="font-family: monospace; font-size: 0.9rem; line-height: 1.6;">
                <p><strong>📊 Database Tables:</strong></p>
                <?php foreach ($tables_status as $status): ?>
                    <p style="margin: 2px 0; padding-left: 20px;">• <?php echo $status; ?></p>
                <?php endforeach; ?>
                <p><strong>📱 Phone Support:</strong> ✅ ENABLED</p>
                <p><strong>📍 Address Support:</strong> ✅ ENABLED</p>
                <p><strong>💰 Dynamic Pricing:</strong> ✅ ENABLED</p>
                <p><strong>🍽️ Table Management:</strong> ✅ ENABLED</p>
                <p><strong>⏰ Operating Hours:</strong> ✅ ENABLED</p>
                <p><strong>🕒 Last Check:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
                <p><strong>🔧 Debug Mode:</strong> <a href="?page=yrr-settings&yrr_debug=1" style="color: #007cba;">Enable Debug View</a></p>
            </div>
        </div>
    </div>
</div>

<style>
@media (max-width: 768px) {
    div[style*="grid-template-columns: 1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
    div[style*="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr))"] {
        grid-template-columns: 1fr !important;
    }
    div[style*="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr))"] {
        grid-template-columns: 1fr !important;
    }
}

button:hover {
    transform: translateY(-3px) !important;
    box-shadow: 0 8px 25px rgba(40,167,69,0.4) !important;
}

input:focus, select:focus {
    border-color: #667eea !important;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1) !important;
    outline: none !important;
}

input[type="tel"] {
    font-family: monospace;
}
</style>
