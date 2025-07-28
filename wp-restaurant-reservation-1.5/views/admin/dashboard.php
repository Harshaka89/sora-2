<?php
if (!defined('ABSPATH')) exit;

// ✅ SAFE VARIABLE HANDLING WITH DEFAULTS
$restaurant_name = $restaurant_name ?? get_bloginfo('name') ?? 'Yenolx Restaurant';
$restaurant_status = $restaurant_status ?? '1';
$statistics = $statistics ?? array('total' => 0, 'confirmed' => 0, 'pending' => 0, 'today' => 0);
$today_reservations = $today_reservations ?? array();
$available_tables = $available_tables ?? array();

// Helper function for safe property access
function yrr_get_property_dash($object, $property, $default = '') {
    return (is_object($object) && property_exists($object, $property) && !empty($object->$property)) ? $object->$property : $default;
}

// Check user permissions
$current_user = wp_get_current_user();
$is_super_admin = in_array('administrator', $current_user->roles);
$is_admin = $is_super_admin || in_array('yrr_admin', $current_user->roles);

if (!$is_admin) {
    wp_die('You do not have sufficient permissions to access this page.');
}

// ✅ ENSURE STATISTICS IS ARRAY
if (!is_array($statistics)) {
    $statistics = array('total' => 0, 'confirmed' => 0, 'pending' => 0, 'today' => 0);
}

// ✅ ENSURE TODAY_RESERVATIONS IS ARRAY
if (!is_array($today_reservations)) {
    $today_reservations = array();
}
?>

<div class="wrap">
    <div style="max-width: 1400px; margin: 20px auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
        
        <!-- ✅ HEADER WITH ROLE INDICATOR -->
        <div style="text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 3px solid #667eea;">
            <h1 style="font-size: 2.5rem; color: #2c3e50; margin: 0; display: flex; align-items: center; justify-content: center; gap: 15px;">
                <span style="font-size: 3rem;">🏪</span>
                <span><?php echo esc_html($restaurant_name); ?> Dashboard</span>
            </h1>
            <p style="color: #6c757d; margin: 10px 0 0 0; font-size: 1.1rem;">Yenolx Restaurant Reservation System v1.5.1</p>
            <div style="margin-top: 15px; display: flex; justify-content: center; gap: 15px; align-items: center; flex-wrap: wrap;">
                <span style="background: <?php echo $restaurant_status == '1' ? '#28a745' : '#dc3545'; ?>; color: white; padding: 8px 16px; border-radius: 20px; font-weight: bold; display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 1.2rem;"><?php echo $restaurant_status == '1' ? '🟢' : '🔴'; ?></span>
                    <span><?php echo $restaurant_status == '1' ? 'OPEN' : 'CLOSED'; ?></span>
                </span>
                <span style="background: <?php echo $is_super_admin ? '#dc3545' : '#007cba'; ?>; color: white; padding: 8px 16px; border-radius: 20px; font-weight: bold; display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 1.2rem;"><?php echo $is_super_admin ? '👑' : '👤'; ?></span>
                    <span><?php echo $is_super_admin ? 'SUPER ADMIN' : 'ADMIN'; ?></span>
                </span>
            </div>
        </div>
        
        <!-- ✅ SUCCESS/ERROR MESSAGES -->
        <?php if (isset($_GET['message'])): ?>
            <div style="padding: 15px; margin: 20px 0; border-radius: 8px; border: 2px solid; <?php
                $msg = '';
                switch($_GET['message']) {
                    case 'reservation_created':
                        echo 'background: #d4edda; color: #155724; border-color: #28a745;';
                        $msg = '✅ Manual reservation created successfully!';
                        break;
                    case 'updated':
                        echo 'background: #d4edda; color: #155724; border-color: #28a745;';
                        $msg = '✅ Reservation updated successfully!';
                        break;
                    case 'confirmed':
                        echo 'background: #d4edda; color: #155724; border-color: #28a745;';
                        $msg = '✅ Reservation confirmed successfully!';
                        break;
                    case 'cancelled':
                        echo 'background: #fff3cd; color: #856404; border-color: #ffc107;';
                        $msg = '⚠️ Reservation cancelled successfully!';
                        break;
                    case 'deleted':
                        echo 'background: #d4edda; color: #155724; border-color: #28a745;';
                        $msg = '✅ Reservation deleted successfully!';
                        break;
                    case 'missing_fields':
                        echo 'background: #f8d7da; color: #721c24; border-color: #dc3545;';
                        $msg = '❌ Missing required fields. Please fill in all required information.';
                        break;
                    case 'invalid_nonce':
                        echo 'background: #f8d7da; color: #721c24; border-color: #dc3545;';
                        $msg = '❌ Security check failed. Please try again.';
                        break;
                    case 'db_error':
                        echo 'background: #f8d7da; color: #721c24; border-color: #dc3545;';
                        $msg = '❌ Database error occurred. Please check if the reservations table exists.';
                        break;
                    case 'exception_error':
                        echo 'background: #f8d7da; color: #721c24; border-color: #dc3545;';
                        $msg = '❌ System error occurred. Please check the error logs for details.';
                        break;
                    default:
                        echo 'background: #f8d7da; color: #721c24; border-color: #dc3545;';
                        $msg = '❌ An error occurred. Please check the system logs for details.';
                }
            ?>">
                <h4 style="margin: 0;"><?php echo $msg; ?></h4>
            </div>
        <?php endif; ?>

        <!-- ✅ STATISTICS CARDS -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 15px; text-align: center; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                <div style="font-size: 3rem; margin-bottom: 10px;">📊</div>
                <div style="font-size: 2.5rem; font-weight: bold; margin-bottom: 5px;"><?php echo number_format(intval($statistics['total'] ?? 0)); ?></div>
                <div style="font-size: 1.1rem; opacity: 0.9;">Total Reservations</div>
            </div>
            
            <div style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 25px; border-radius: 15px; text-align: center; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                <div style="font-size: 3rem; margin-bottom: 10px;">✅</div>
                <div style="font-size: 2.5rem; font-weight: bold; margin-bottom: 5px;"><?php echo number_format(intval($statistics['confirmed'] ?? 0)); ?></div>
                <div style="font-size: 1.1rem; opacity: 0.9;">Confirmed Today</div>
            </div>
            
            <div style="background: linear-gradient(135deg, #ffc107 0%, #ff8c00 100%); color: white; padding: 25px; border-radius: 15px; text-align: center; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                <div style="font-size: 3rem; margin-bottom: 10px;">⏳</div>
                <div style="font-size: 2.5rem; font-weight: bold; margin-bottom: 5px;"><?php echo number_format(intval($statistics['pending'] ?? 0)); ?></div>
                <div style="font-size: 1.1rem; opacity: 0.9;">Pending Approval</div>
            </div>
            
            <div style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); color: white; padding: 25px; border-radius: 15px; text-align: center; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                <div style="font-size: 3rem; margin-bottom: 10px;">📅</div>
                <div style="font-size: 2.5rem; font-weight: bold; margin-bottom: 5px;"><?php echo number_format(count($today_reservations)); ?></div>
                <div style="font-size: 1.1rem; opacity: 0.9;">Today's Bookings</div>
            </div>
        </div>
        
        <!-- ✅ QUICK ACTIONS -->
        <div style="margin-bottom: 30px; padding: 25px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 15px; border: 3px solid #dee2e6;">
            <h3 style="margin: 0 0 20px 0; color: #2c3e50; display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 1.5rem;">🚀</span>
                <span>Quick Actions</span>
            </h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <!-- Manual Reservation Button -->
                <button onclick="showManualReservationModal()" 
                   style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 15px 20px; border: none; border-radius: 10px; text-align: center; font-weight: bold; cursor: pointer; font-size: 1rem; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 8px;"
                   onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 15px rgba(40,167,69,0.3)'"
                   onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                    <span style="font-size: 1.2rem;">➕</span>
                    <span>Create Manual Reservation</span>
                </button>
                
                <a href="<?php echo admin_url('admin.php?page=yrr-all-reservations'); ?>" 
                   style="background: linear-gradient(135deg, #007cba 0%, #004d7a 100%); color: white; padding: 15px 20px; text-decoration: none; border-radius: 10px; text-align: center; font-weight: bold; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s ease;"
                   onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 15px rgba(0,123,186,0.3)'"
                   onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                    <span style="font-size: 1.2rem;">📋</span>
                    <span>All Reservations</span>
                </a>
                
                <?php if ($is_super_admin): ?>
                <a href="<?php echo admin_url('admin.php?page=yrr-tables'); ?>" 
                   style="background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%); color: white; padding: 15px 20px; text-decoration: none; border-radius: 10px; text-align: center; font-weight: bold; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s ease;"
                   onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 15px rgba(111,66,193,0.3)'"
                   onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                    <span style="font-size: 1.2rem;">🍽️</span>
                    <span>Manage Tables</span>
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=yrr-hours'); ?>" 
                   style="background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%); color: white; padding: 15px 20px; text-decoration: none; border-radius: 10px; text-align: center; font-weight: bold; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s ease;"
                   onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 15px rgba(255,193,7,0.3)'"
                   onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                    <span style="font-size: 1.2rem;">⏰</span>
                    <span>Operating Hours</span>
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=yrr-pricing'); ?>" 
                   style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; padding: 15px 20px; text-decoration: none; border-radius: 10px; text-align: center; font-weight: bold; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s ease;"
                   onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 15px rgba(220,53,69,0.3)'"
                   onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                    <span style="font-size: 1.2rem;">💰</span>
                    <span>Pricing Rules</span>
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=yrr-coupons'); ?>" 
                   style="background: linear-gradient(135deg, #fd7e14 0%, #e8590c 100%); color: white; padding: 15px 20px; text-decoration: none; border-radius: 10px; text-align: center; font-weight: bold; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s ease;"
                   onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 15px rgba(253,126,20,0.3)'"
                   onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                    <span style="font-size: 1.2rem;">🎫</span>
                    <span>Discount Coupons</span>
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=yrr-settings'); ?>" 
                   style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%); color: white; padding: 15px 20px; text-decoration: none; border-radius: 10px; text-align: center; font-weight: bold; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s ease;"
                   onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 15px rgba(108,117,125,0.3)'"
                   onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                    <span style="font-size: 1.2rem;">⚙️</span>
                    <span>Settings</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- ✅ TODAY'S RESERVATIONS -->
        <div style="background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-bottom: 30px;">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px;">
                <h3 style="margin: 0; font-size: 1.8rem; display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 2rem;">📅</span>
                    <span>Today's Reservations (<?php echo date('M j, Y'); ?>)</span>
                </h3>
            </div>
            
            <div style="padding: 20px;">
                <?php if (!empty($today_reservations) && is_array($today_reservations)): ?>
                    
                    <div style="display: grid; gap: 15px;">
                        <?php foreach ($today_reservations as $reservation): ?>
                            <?php if (!is_object($reservation)) continue; ?>
                            
                            <div style="padding: 20px; border: 2px solid #e9ecef; border-radius: 10px; background: white; display: grid; grid-template-columns: auto 1fr auto auto; gap: 20px; align-items: center; transition: all 0.3s ease;" onmouseover="this.style.boxShadow='0 4px 15px rgba(0,0,0,0.1)'" onmouseout="this.style.boxShadow='none'">
                                
                                <!-- Status Badge -->
                                <div>
                                    <?php 
                                    $status = yrr_get_property_dash($reservation, 'status', 'pending');
                                    $status_colors = array(
                                        'confirmed' => '#28a745',
                                        'pending' => '#ffc107',
                                        'cancelled' => '#dc3545'
                                    );
                                    $text_color = $status === 'pending' ? '#000' : '#fff';
                                    ?>
                                    <span style="background: <?php echo $status_colors[$status] ?? '#6c757d'; ?>; color: <?php echo $text_color; ?>; padding: 10px 15px; border-radius: 20px; font-size: 0.9rem; font-weight: bold; text-transform: uppercase;">
                                        <?php echo esc_html($status); ?>
                                    </span>
                                </div>
                                
                                <!-- Customer Info -->
                                <div>
                                    <div style="font-weight: bold; font-size: 1.2rem; color: #2c3e50; margin-bottom: 5px;">
                                        👤 <?php echo esc_html(yrr_get_property_dash($reservation, 'customer_name', 'Unknown Customer')); ?>
                                    </div>
                                    <div style="color: #6c757d; font-size: 0.9rem; margin-bottom: 3px;">
                                        📧 <?php echo esc_html(yrr_get_property_dash($reservation, 'customer_email', 'No email')); ?>
                                    </div>
                                    <div style="color: #6c757d; font-size: 0.9rem;">
                                        📞 <?php echo esc_html(yrr_get_property_dash($reservation, 'customer_phone', 'No phone')); ?>
                                    </div>
                                </div>
                                
                                <!-- Reservation Details -->
                                <div style="text-align: center;">
                                    <div style="font-weight: bold; font-size: 1.3rem; color: #007cba; margin-bottom: 5px;">
                                        <?php echo date('g:i A', strtotime(yrr_get_property_dash($reservation, 'reservation_time', '00:00:00'))); ?>
                                    </div>
                                    <div style="background: #e3f2fd; color: #1976d2; padding: 5px 10px; border-radius: 10px; font-weight: bold;">
                                        👥 <?php echo intval(yrr_get_property_dash($reservation, 'party_size', 1)); ?> guests
                                    </div>
                                    <?php 
                                    $table_id = yrr_get_property_dash($reservation, 'table_id');
                                    if ($table_id): ?>
                                        <div style="margin-top: 5px; color: #6c757d; font-size: 0.9rem;">
                                            🍽️ Table <?php echo esc_html($table_id); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Actions -->
                                <div style="display: flex; gap: 8px; flex-direction: column;">
                                    <?php $reservation_id = yrr_get_property_dash($reservation, 'id'); ?>
                                    <?php if ($reservation_id && $status === 'pending'): ?>
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=yenolx-reservations&action=confirm&id=' . $reservation_id), 'reservation_action'); ?>" 
                                           style="background: #28a745; color: white; padding: 8px 12px; text-decoration: none; border-radius: 5px; font-size: 0.8rem; font-weight: bold; text-align: center;">
                                            ✅ Confirm
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($reservation_id): ?>
                                        <button onclick="editReservation(<?php echo htmlspecialchars(json_encode($reservation), ENT_QUOTES, 'UTF-8'); ?>)" 
                                                style="background: #17a2b8; color: white; border: none; padding: 8px 12px; border-radius: 5px; font-size: 0.8rem; font-weight: bold; cursor: pointer;">
                                            ✏️ Edit
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Special Requests -->
                            <?php 
                            $special_requests = yrr_get_property_dash($reservation, 'special_requests');
                            if ($special_requests): ?>
                                <div style="padding: 10px 20px; background: rgba(0,123,186,0.05); border-radius: 8px; margin-top: -5px; font-size: 0.9rem; border-left: 4px solid #007cba;">
                                    <strong>💬 Special Requests:</strong> <?php echo esc_html($special_requests); ?>
                                </div>
                            <?php endif; ?>
                            
                        <?php endforeach; ?>
                    </div>
                    
                <?php else: ?>
                    
                    <!-- No Reservations Today -->
                    <div style="text-align: center; padding: 40px; color: #6c757d;">
                        <div style="font-size: 4rem; margin-bottom: 20px; opacity: 0.3;">📅</div>
                        <h3 style="margin: 0 0 15px 0;">No Reservations Today</h3>
                        <p>No reservations scheduled for today. Create a manual reservation or check back later!</p>
                        <button onclick="showManualReservationModal()" 
                                style="background: #28a745; color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: bold; margin-top: 15px; cursor: pointer;">
                            ➕ Create Manual Reservation
                        </button>
                    </div>
                    
                <?php endif; ?>
            </div>
        </div>
        
        <!-- ✅ SYSTEM HEALTH CHECK -->
        <div style="margin-top: 30px; padding: 20px; background: #e8f5e8; border-radius: 10px; border-left: 5px solid #28a745;">
            <h4 style="margin: 0 0 15px 0; color: #155724; display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 1.3rem;">🔧</span>
                <span>System Health Status</span>
            </h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; font-size: 0.9rem;">
                <?php
                global $wpdb;
                $tables = array('yrr_settings', 'yrr_reservations', 'yrr_tables', 'yrr_operating_hours', 'yrr_pricing_rules', 'yrr_coupons');
                $all_good = true;
                
                foreach ($tables as $table) {
                    $full_table_name = $wpdb->prefix . $table;
                    $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $full_table_name)) == $full_table_name;
                    if (!$exists) $all_good = false;
                    echo '<div>' . ($exists ? '✅' : '❌') . ' ' . ucfirst(str_replace('yrr_', '', $table)) . '</div>';
                }
                ?>
                <div><?php echo $all_good ? '✅' : '⚠️'; ?> Overall Status: <?php echo $all_good ? 'Healthy' : 'Needs Attention'; ?></div>
                <div>👤 Access Level: <?php echo $is_super_admin ? 'Super Admin' : 'Admin'; ?></div>
                <div>🕐 Last Check: <?php echo current_time('H:i:s'); ?></div>
            </div>
        </div>
    </div>
</div>

<!-- ✅ MANUAL RESERVATION MODAL -->
<div id="manualReservationModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 10000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 30px; border-radius: 20px; width: 90%; max-width: 700px; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #e9ecef;">
            <h3 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 1.5rem;">➕</span>
                <span>Create Manual Reservation</span>
            </h3>
            <button onclick="closeManualReservationModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #6c757d;">×</button>
        </div>
        
        <form method="post" action="">
            <?php wp_nonce_field('create_manual_reservation', 'manual_reservation_nonce'); ?>
            <input type="hidden" name="create_manual_reservation" value="1">
            
            <!-- Customer Information -->
            <div style="background: #e3f2fd; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                <h4 style="margin: 0 0 15px 0; color: #1976d2; display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 1.2rem;">👤</span>
                    <span>Customer Information</span>
                </h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold;">Customer Name *</label>
                        <input type="text" name="customer_name" required 
                               style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold;">Email Address *</label>
                        <input type="email" name="customer_email" required 
                               style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; box-sizing: border-box;">
                    </div>
                </div>
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">Phone Number *</label>
                    <input type="tel" name="customer_phone" required 
                           style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; box-sizing: border-box;">
                </div>
            </div>
            
            <!-- Reservation Details -->
            <div style="background: #e8f5e8; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                <h4 style="margin: 0 0 15px 0; color: #155724; display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 1.2rem;">📅</span>
                    <span>Reservation Details</span>
                </h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold;">Date *</label>
                        <input type="date" name="reservation_date" required min="<?php echo date('Y-m-d'); ?>"
                               style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold;">Time *</label>
                        <input type="time" name="reservation_time" required 
                               style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold;">Party Size *</label>
                        <select name="party_size" required style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; box-sizing: border-box;">
                            <?php for($i = 1; $i <= 20; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?> <?php echo $i == 1 ? 'Guest' : 'Guests'; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">Special Requests</label>
                    <textarea name="special_requests" rows="3" placeholder="Any special requirements..." 
                              style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; box-sizing: border-box;"></textarea>
                </div>
            </div>
            
            <!-- Admin Options -->
            <div style="background: #fff3cd; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                <h4 style="margin: 0 0 15px 0; color: #856404; display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 1.2rem;">⚙️</span>
                    <span>Admin Options</span>
                </h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold;">Initial Status</label>
                        <select name="initial_status" style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; box-sizing: border-box;">
                            <option value="confirmed">✅ Confirmed</option>
                            <option value="pending">⏳ Pending</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold;">Admin Notes</label>
                        <input type="text" name="admin_notes" placeholder="Internal notes..." 
                               style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; box-sizing: border-box;">
                    </div>
                </div>
            </div>
            
            <div style="text-align: right; padding-top: 20px; border-top: 2px solid #e9ecef;">
                <button type="button" onclick="closeManualReservationModal()" 
                        style="background: #6c757d; color: white; border: none; padding: 12px 24px; border-radius: 8px; margin-right: 15px; cursor: pointer; font-weight: bold;">
                    Cancel
                </button>
                <button type="submit" 
                        style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: bold;">
                    ➕ Create Reservation
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ✅ EDIT MODAL -->
<div id="editModal" class="yrr-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 10000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 30px; border-radius: 20px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #e9ecef;">
            <h3 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 1.5rem;">✏️</span>
                <span>Edit Reservation</span>
            </h3>
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
// ✅ MODAL FUNCTIONS
function showManualReservationModal() {
    document.getElementById('manualReservationModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeManualReservationModal() {
    document.getElementById('manualReservationModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

function editReservation(res) {
    // Safely populate edit form
    document.getElementById('edit_id').value = res.id || '';
    document.getElementById('edit_name').value = res.customer_name || '';
    document.getElementById('edit_email').value = res.customer_email || '';
    document.getElementById('edit_phone').value = res.customer_phone || '';
    document.getElementById('edit_party').value = res.party_size || '1';
    document.getElementById('edit_date').value = res.reservation_date || '';
    document.getElementById('edit_time').value = res.reservation_time || '';
    document.getElementById('edit_requests').value = res.special_requests || '';
    
    document.getElementById('editModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// ✅ CLOSE MODALS WHEN CLICKING OUTSIDE
document.addEventListener('DOMContentLoaded', function() {
    const manualModal = document.getElementById('manualReservationModal');
    const editModal = document.getElementById('editModal');
    
    if (manualModal) {
        manualModal.addEventListener('click', function(e) {
            if (e.target === this) closeManualReservationModal();
        });
    }
    
    if (editModal) {
        editModal.addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    }
    
    // Close modals with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeManualReservationModal();
            closeModal();
        }
    });
});
</script>

<style>
/* ✅ RESPONSIVE DESIGN */
@media (max-width: 1200px) {
    div[style*="grid-template-columns: auto 1fr auto auto"] {
        grid-template-columns: 1fr !important;
        gap: 10px !important;
        text-align: center !important;
    }
}

@media (max-width: 768px) {
    div[style*="grid-template-columns: 1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
    
    div[style*="grid-template-columns: 1fr 1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
    
    div[style*="display: flex; justify-content: center; gap: 15px; align-items: center; flex-wrap: wrap;"] {
        flex-direction: column !important;
        gap: 10px !important;
    }
}

/* ✅ HOVER ANIMATIONS */
button:hover, a[style*="background:"]:hover {
    transform: translateY(-2px) !important;
    transition: all 0.3s ease !important;
}

/* ✅ MODAL BACKDROP */
.yrr-modal {
    backdrop-filter: blur(5px);
}
</style>
