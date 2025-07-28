<?php
if (!defined('ABSPATH')) exit;

if (!class_exists('YRR_Hours_Model')) {

class YRR_Hours_Model {
    private $wpdb;
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'yrr_operating_hours';
    }
    
    /**
     * Get all operating hours - WORKING FUNCTION
     */
    public function get_all_hours() {
        $results = $this->wpdb->get_results(
            "SELECT * FROM {$this->table_name} ORDER BY 
             FIELD(day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')"
        );
        
        $hours = array();
        $days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
        
        foreach ($days as $day) {
            $found = false;
            foreach ($results as $result) {
                if ($result->day_of_week === $day) {
                    $hours[$day] = $result;
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $hours[$day] = (object) array(
                    'day_of_week' => $day,
                    'open_time' => '10:00:00',
                    'close_time' => '22:00:00',
                    'is_closed' => 0
                );
            }
        }
        
        return $hours;
    }
    
    /**
     * Set hours for specific day - WORKING FUNCTION
     */
    public function set_hours($day, $open_time, $close_time, $is_closed = 0) {
        $data = array(
            'day_of_week' => $day,
            'open_time' => $open_time,
            'close_time' => $close_time,
            'is_closed' => intval($is_closed),
            'updated_at' => current_time('mysql')
        );
        
        $existing = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT id FROM {$this->table_name} WHERE day_of_week = %s",
            $day
        ));
        
        if ($existing) {
            $result = $this->wpdb->update(
                $this->table_name,
                $data,
                array('day_of_week' => $day)
            );
            return $result !== false;
        } else {
            $data['created_at'] = current_time('mysql');
            $result = $this->wpdb->insert($this->table_name, $data);
            return $result !== false;
        }
    }
    
    /**
     * Get today's hours - WORKING FUNCTION
     */
    public function get_today_hours() {
        $today = strtolower(date('l'));
        return $this->get_day_hours($today);
    }
    
    /**
     * Get hours for specific day - WORKING FUNCTION
     */
    public function get_day_hours($day) {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE day_of_week = %s",
            $day
        ));
    }
    
    /**
     * Check if restaurant is open - WORKING FUNCTION
     */
    public function is_open($day, $time) {
        $hours = $this->get_day_hours($day);
        
        if (!$hours || $hours->is_closed) {
            return false;
        }
        
        $current_time = strtotime($time);
        $open_time = strtotime($hours->open_time);
        $close_time = strtotime($hours->close_time);
        
        // Handle overnight service
        if ($close_time <= $open_time) {
            $close_time += 24 * 3600;
            if ($current_time < $open_time) {
                $current_time += 24 * 3600;
            }
        }
        
        return $current_time >= $open_time && $current_time <= $close_time;
    }
    
    /**
     * Get basic statistics - WORKING FUNCTION
     */
    public function get_basic_stats() {
        $hours = $this->get_all_hours();
        $open_days = 0;
        
        foreach ($hours as $day_hours) {
            if (!$day_hours->is_closed) {
                $open_days++;
            }
        }
        
        return array(
            'open_days' => $open_days,
            'closed_days' => 7 - $open_days
        );
    }
}

}
