<?php

class WebKew_WP_References
{
    protected $admin;
    protected $public;

    public function __construct()
    {
        $this->wwrc_load_dependencies();
        $this->wwrc_define_admin_hooks();
    }

    private function wwrc_load_dependencies()
    {
        $this->admin = new WebKew_WP_References_Citations_Admin();
        $this->public = new WebKew_WP_References_Citations_Public();
    }

    private function wwrc_define_admin_hooks()
    {
        add_action('admin_menu', array($this->admin, 'wwrc_add_plugin_menu'));
        add_action('admin_init', array($this->admin, 'wwrc_register_settings'));
        add_action('add_meta_boxes', array($this->admin, 'wwrc_add_references_meta_box'));
        add_action('save_post', array($this->admin, 'wwrc_save_references_meta_box'));
    }

    public function run()
    {
        // The hooks are now set up in the constructors, so we don't need to do anything here
    }
}