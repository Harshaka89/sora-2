<?php
/**
 * Plugin Loader - Yenolx Restaurant Reservation v1.5
 */

if (!defined('ABSPATH')) exit;

class YRR_Plugin_Loader {
    protected $actions;
    protected $filters;
    protected $shortcodes;
    
    public function __construct() {
        $this->actions = array();
        $this->filters = array();
        $this->shortcodes = array();
    }
    
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }
    
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }
    
    public function add_shortcode($tag, $component, $callback) {
        $this->shortcodes[$tag] = array(
            'component' => $component,
            'callback' => $callback
        );
    }
    
    private function add($hooks, $hook, $component, $callback, $priority, $accepted_args) {
        $hooks[$hook][] = array(
            'component' => $component,
            'callback' => $callback,
            'priority' => $priority,
            'accepted_args' => $accepted_args
        );
        
        return $hooks;
    }
    
    public function run() {
        foreach ($this->filters as $hook => $callbacks) {
            foreach ($callbacks as $callback) {
                add_filter($hook, array($callback['component'], $callback['callback']), $callback['priority'], $callback['accepted_args']);
            }
        }
        
        foreach ($this->actions as $hook => $callbacks) {
            foreach ($callbacks as $callback) {
                add_action($hook, array($callback['component'], $callback['callback']), $callback['priority'], $callback['accepted_args']);
            }
        }
        
        foreach ($this->shortcodes as $tag => $callback) {
            add_shortcode($tag, array($callback['component'], $callback['callback']));
        }
    }
}
?>
