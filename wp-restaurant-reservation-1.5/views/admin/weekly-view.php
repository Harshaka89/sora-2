<?php
if (!defined('ABSPATH')) exit;

function rrs_get_property_weekly($object, $property, $default = '') {
    return (property_exists($object, $property) && !empty($object->$property)) ? $object->$property : $default;
}

$week_start = isset($_GET['week']) ? sanitize_text_field($_GET['week']) : date('Y-m-d', strtotime('monday this week'));
$week_end = date('Y-m-d', strtotime($week_start . ' +6 days'));

if (!isset($reservations)) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'rrs_reservations';
    $reservations = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE reservation_date BETWEEN %s AND %s ORDER BY reservation_date, reservation_time",
        $week_start, $week_end
    ));
}
?>

<div class="wrap">
    <div style="max-width: 1200px; margin: 20px auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
        <div style="text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 3px solid #667eea;">
            <h1 style="font-size: 2.5rem; color: #2c3e50; margin: 0;">📅 Weekly Reservations Overview</h1>
            <p style="color: #6c757d; margin: 10px 0 0 0;">Complete weekly view with detailed management</p>
        </div>
        
        <div style="text-align: center; margin: 20px 0; background: #f8f9fa; padding: 20px; border-radius: 15px;">
            <a href="?page=weekly-view&week=<?php echo date('Y-m-d', strtotime($week_start . ' -7 days')); ?>" 
               style="background: #6c757d; color: white; padding: 12px 20px; text-decoration: none; border-radius: 8px; margin-right: 15px;">← Previous Week</a>
            
            <span style="font-size: 1.3rem; font-weight: bold; color: #2c3e50; margin: 0 20px;">
                <?php echo date('M j', strtotime($week_start)); ?> - <?php echo date('M j, Y', strtotime($week_end)); ?>
            </span>
            
            <a href="?page=weekly-view&week=<?php echo date('Y-m-d', strtotime($week_start . ' +7 days')); ?>" 
               style="background: #6c757d; color: white; padding: 12px 20px; text-decoration: none; border-radius: 8px; margin-left: 15px;">Next Week →</a>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 15px; margin-top: 30px;">
            <?php 
            $weekly_data = array();
            if (!empty($reservations) && is_array($reservations)) {
                foreach ($reservations as $res) {
                    if (is_object($res) && property_exists($res, 'reservation_date')) {
                        $weekly_data[$res->reservation_date][] = $res;
                    }
                }
            }
            
            for ($i = 0; $i < 7; $i++):
                $current_date = date('Y-m-d', strtotime($week_start . " +{$i} days"));
                $day_reservations = isset($weekly_data[$current_date]) ? $weekly_data[$current_date] : array();
                $is_today = $current_date === date('Y-m-d');
                $day_name = date('l', strtotime($current_date));
            ?>
                <div style="background: white; border: 2px solid <?php echo $is_today ? '#007cba' : '#e9ecef'; ?>; border-radius: 12px; padding: 15px; min-height: 300px;">
                    <div style="text-align: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #e9ecef;">
                        <h3 style="margin: 0; font-size: 1rem; color: <?php echo $is_today ? '#007cba' : '#2c3e50'; ?>;"><?php echo $day_name; ?></h3>
                        <span style="font-size: 0.9rem; opacity: 0.8;"><?php echo date('M j', strtotime($current_date)); ?></span>
                        <?php if ($is_today): ?>
                            <div style="background: #007cba; color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.7rem; margin-top: 5px;">TODAY</div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($day_reservations)): ?>
                        <?php foreach ($day_reservations as $res): ?>
                            <?php if (!is_object($res)) continue; ?>
                            
                            <div style="background: <?php 
                                $status = rrs_get_property_weekly($res, 'status', 'pending');
                                echo $status === 'confirmed' ? '#e8f5e8' : ($status === 'pending' ? '#fff3cd' : '#f8d7da'); 
                            ?>; padding: 8px; margin-bottom: 8px; border-radius: 6px; border-left: 4px solid <?php 
                                echo $status === 'confirmed' ? '#28a745' : ($status === 'pending' ? '#ffc107' : '#dc3545'); 
                            ?>;">
                                
                                <div style="font-weight: bold; font-size: 0.85rem; color: #2c3e50;">
                                    ⏰ <?php echo date('g:i A', strtotime(rrs_get_property_weekly($res, 'reservation_time', '00:00:00'))); ?>
                                </div>
                                
                                <div style="font-size: 0.8rem; color: #495057;">
                                    👤 <?php echo esc_html(rrs_get_property_weekly($res, 'customer_name', 'Unknown')); ?>
                                </div>
                                
                                <div style="font-size: 0.75rem; color: #6c757d;">
                                    👥 <?php echo intval(rrs_get_property_weekly($res, 'party_size', 1)); ?> guests
                                    <?php 
                                    $table_number = rrs_get_property_weekly($res, 'table_number');
                                    if ($table_number): ?>
                                        • 🪑 <?php echo esc_html($table_number); ?>
                                    <?php endif; ?>
                                </div>
                                
                                <div style="text-align: center; margin-top: 5px;">
                                    <span style="background: <?php echo $status === 'confirmed' ? '#28a745' : ($status === 'pending' ? '#ffc107' : '#dc3545'); ?>; color: <?php echo $status === 'pending' ? '#000' : '#fff'; ?>; padding: 2px 6px; border-radius: 8px; font-size: 0.6rem; font-weight: bold; text-transform: uppercase;">
                                        <?php echo esc_html($status); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 20px; color: #6c757d;">
                            <div style="font-size: 2rem; margin-bottom: 8px; opacity: 0.3;">📅</div>
                            <div style="font-size: 0.8rem;">No reservations</div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="<?php echo admin_url('admin.php?page=reservations'); ?>" 
               style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 10px; font-weight: bold;">
                ← Back to Dashboard
            </a>
        </div>
    </div>
</div>

<style>
@media (max-width: 1200px) {
    div[style*="grid-template-columns: repeat(7, 1fr)"] {
        grid-template-columns: repeat(4, 1fr) !important;
    }
}

@media (max-width: 768px) {
    div[style*="grid-template-columns: repeat(7, 1fr)"] {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}

@media (max-width: 480px) {
    div[style*="grid-template-columns: repeat(7, 1fr)"] {
        grid-template-columns: 1fr !important;
    }
}
</style>
