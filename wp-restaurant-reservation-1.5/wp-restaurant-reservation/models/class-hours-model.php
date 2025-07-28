<?php
/**
 * Operating Hours Model - Yenolx Restaurant Reservation v1.5
 */

if (!defined('ABSPATH')) exit;

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
}
?>
