<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

class Webkewrc_References_Citations
{
    protected $admin;
    protected $public;

    public function __construct()
    {
        $this->webkewrc_load_dependencies();
        $this->webkewrc_define_admin_hooks();
    }

    private function webkewrc_load_dependencies()
    {
        $this->admin = new Webkewrc_References_Citations_Admin();
        $this->public = new Webkewrc_References_Citations_Public();
    }

    private function webkewrc_define_admin_hooks()
    {
        add_action('admin_menu', array($this->admin, 'webkewrc_add_plugin_menu'));
        add_action('admin_init', array($this->admin, 'webkewrc_register_settings'));
        add_action('add_meta_boxes', array($this->admin, 'webkewrc_add_references_meta_box'));
        add_action('save_post', array($this->admin, 'webkewrc_save_references_meta_box'));
    }

    public function run()
    {
        // The hooks are now set up in the constructors, so we don't need to do anything here
    }
}