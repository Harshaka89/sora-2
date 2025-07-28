<?php
/**
 * Operating Hours Model - Yenolx Restaurant Reservation v1.5.1
 */

if (!defined('ABSPATH')) exit;

// ✅ PROTECTION: Only declare class if it doesn't exist
if (!class_exists('YRR_Hours_Model')) {

class YRR_Hours_Model {
    private $table_name;
    private $wpdb;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'yrr_operating_hours';
    }
    
    public function get_hours_for_day($day) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE day_of_week = %s ORDER BY shift_name",
            $day
        ));
    }
    
    public function get_all_hours() {
        $results = $this->wpdb->get_results("SELECT * FROM {$this->table_name} ORDER BY FIELD(day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'), shift_name");
        
        $hours = array();
        foreach ($results as $row) {
            $hours[$row->day_of_week][$row->shift_name] = $row;
        }
        
        return $hours;
    }
    
    public function set_hours($day, $shift, $open_time, $close_time, $is_closed = false) {
        $data = array(
            'day_of_week' => $day,
            'shift_name' => $shift,
            'open_time' => $is_closed ? null : $open_time,
            'close_time' => $is_closed ? null : $close_time,
            'is_closed' => $is_closed ? 1 : 0
        );
        
        return $this->wpdb->replace($this->table_name, $data);
    }
    
    public function is_open_at($date, $time) {
        $day_of_week = strtolower(date('l', strtotime($date)));
        
        $hours = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
             WHERE day_of_week = %s AND is_closed = 0
             AND open_time <= %s AND close_time >= %s",
            $day_of_week, $time, $time
        ));
        
        return !empty($hours);
    }
    
    public function get_available_time_slots($date, $slot_duration = 30) {
        $day_of_week = strtolower(date('l', strtotime($date)));
        
        $hours = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
             WHERE day_of_week = %s AND is_closed = 0
             ORDER BY open_time",
            $day_of_week
        ));
        
        $slots = array();
        foreach ($hours as $shift) {
            if (!$shift->open_time || !$shift->close_time) continue;
            
            $current_time = strtotime($shift->open_time);
            $end_time = strtotime($shift->close_time);
            
            while ($current_time < $end_time) {
                $slot_time = date('H:i:s', $current_time);
                $slots[] = array(
                    'time' => $slot_time,
                    'display' => date('g:i A', $current_time),
                    'shift' => $shift->shift_name
                );
                $current_time += ($slot_duration * 60);
            }
        }
        
        return $slots;
    }
    


/**
 * ✅ TEMPORARY DEBUG: Check what data exists
 */
public function debug_hours_data() {
    $today = strtolower(date('l')); // monday
    
    echo "<div style='background: white; padding: 20px; margin: 20px; border: 2px solid #007cba;'>";
    echo "<h3>🔍 Debug Hours Data for Today ({$today})</h3>";
    
    // Check raw database data
    $raw_data = $this->wpdb->get_results("SELECT * FROM {$this->table_name}");
    echo "<h4>Raw Database Data:</h4>";
    echo "<pre>" . print_r($raw_data, true) . "</pre>";
    
    // Check today's hours
    $today_hours = $this->get_today_hours();
    echo "<h4>Today's Hours ({$today}):</h4>";
    echo "<pre>" . print_r($today_hours, true) . "</pre>";
    
    // Check if restaurant is closed
    $is_closed = $today_hours ? $today_hours->is_closed : 'NO DATA';
    echo "<h4>Is Closed Today:</h4>";
    echo "<p style='font-size: 1.2rem; font-weight: bold; color: " . ($is_closed ? '#dc3545' : '#28a745') . ";'>";
    echo $is_closed ? '🔴 YES (is_closed = 1)' : '🟢 NO (is_closed = 0)';
    echo "</p>";
    
    echo "</div>";
}





    // ✅ ADDED: Missing methods for settings page compatibility
    
    /**
     * Get today's operating hours (required by settings page)
     */
    public function get_today_hours() {
        $today = strtolower(date('l')); 
        return $this->get_day_hours($today);
    }
    
    /**
     * Get hours for a specific day (required by settings page)
     */
    public function get_day_hours($day) {
        $result = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
             WHERE day_of_week = %s 
             ORDER BY open_time ASC 
             LIMIT 1",
            $day
        ));
        
        if (!$result) {
            return (object) array(
                'day_of_week' => $day,
                'shift_name' => 'main',
                'open_time' => '10:00:00',
                'close_time' => '22:00:00',
                'is_closed' => 0
            );
        }
        
        return $result;
    }
    
    /**
     * Get simplified hours for settings page
     */
    public function get_simple_hours() {
        $all_hours = $this->get_all_hours();
        $simple_hours = array();
        
        $days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
        
        foreach ($days as $day) {
            if (isset($all_hours[$day])) {
                $first_shift = reset($all_hours[$day]);
                $simple_hours[$day] = $first_shift;
            } else {
                $simple_hours[$day] = (object) array(
                    'day_of_week' => $day,
                    'shift_name' => 'main',
                    'open_time' => '10:00:00',
                    'close_time' => '22:00:00',
                    'is_closed' => 0
                );
            }
        }
        
        return $simple_hours;
    }
    
    /**
     * Set simple hours (for compatibility)
     */
    public function set_simple_hours($day, $open_time, $close_time, $is_closed = 0) {
        return $this->set_hours($day, 'main', $open_time, $close_time, $is_closed);
    }
    
    /**
     * Check if restaurant is currently open
     */
    public function is_currently_open() {
        $date = current_time('Y-m-d');
        $time = current_time('H:i:s');
        
        return $this->is_open_at($date, $time);
    }
    
    /**
     * Get hours statistics for dashboard
     */
    public function get_basic_stats() {
        $hours = $this->get_simple_hours();
        $open_days = 0;
        $total_hours = 0;
        
        foreach ($hours as $day_hours) {
            if (!$day_hours->is_closed && $day_hours->open_time && $day_hours->close_time) {
                $open_days++;
                
                $open_time = strtotime($day_hours->open_time);
                $close_time = strtotime($day_hours->close_time);
                
                if ($close_time <= $open_time) {
                    $close_time += 24 * 3600;
                }
                
                $day_hours_count = ($close_time - $open_time) / 3600;
                $total_hours += $day_hours_count;
            }
        }
        
        return array(
            'open_days' => $open_days,
            'closed_days' => 7 - $open_days,
            'total_hours_per_week' => round($total_hours, 1),
            'avg_hours_per_day' => $open_days > 0 ? round($total_hours / $open_days, 1) : 0
        );
    }
}

} // ✅ END of class_exists check
