<?php

class WebKew_WP_References_Citations_Admin
{
    const  WWRC_CITATION_STYLES = ['author_year', 'Author', 'numerical'];
    const  WWRC_CITATION_STYLES_LABELS = ['(Author Year) Author year', '(Author) Author', '(NUMBER) Numerical'];
    const  WWRC_BIBLIOGRAPHY_STYLES = ['apa', 'vancouver', 'harvard'];

    public function wwrc_add_plugin_menu()
    {
        add_menu_page(
            'WebKew WP References and Citations',
            'WebKew WP References',
            'manage_options',
            'webkew-wp-references-citations',
            array($this, 'wwrc_display_plugin_page'),
            'dashicons-format-quote',
            15
        );
    }

    public function wwrc_register_settings()
    {
        register_setting('webkew_wp_references_citations_options_group', 'webkew_wp_references_citations_options');
        add_settings_section(
            'wwrc_webkew_references_main',
            'Main Settings',
            array($this, 'wwrc_settings_section_callback'),
            'webkew-wp-references-citations'
        );
        add_settings_field(
            'wwrc_post_types',
            'Enabled Post Types',
            array($this, 'wwrc_post_types_callback'),
            'webkew-wp-references-citations',
            'wwrc_webkew_references_main'
        );
        add_settings_field(
            'wwrc_webkew_bibliography_style',
            'Bibliography Style (Based on Citation-JS Style)',
            array($this, 'wwrc_webkew_bibliography_style_callback'),
            'webkew-wp-references-citations',
            'wwrc_webkew_references_main'
        );

        add_settings_field(
            'wwrc_webkew_citation_style',
            'Citation Style (in the text)',
            array($this, 'wwrc_webkew_citation_style_callback'),
            'webkew-wp-references-citations',
            'wwrc_webkew_references_main'
        );

        add_settings_field(
            'wwrc_delete_data_on_uninstall',
            'Delete data on uninstall',
            array($this, 'wwrc_delete_data_callback'),
            'webkew-wp-references-citations',
            'wwrc_webkew_references_main'
        );
    }

    public function wwrc_settings_section_callback()
    {
        echo '<h3>' . esc_html__(
                'Select the post types where you want to enable references:',
                'webkew-wp-references-citations') . '</h3>';
    }


    public function wwrc_webkew_citation_style_callback()
    {
        $options = get_option('webkew_wp_references_citations_options');
        $current_style = isset($options['wwrc_webkew_citation_style']) ? $options['wwrc_webkew_citation_style'] : 'author_year';

        echo "<select name='webkew_wp_references_citations_options[wwrc_webkew_citation_style]'>";
        foreach (self::WWRC_CITATION_STYLES as $index => $style) {
            $selected = ($current_style === $style) ? 'selected' : '';
            echo "<option value='" . esc_attr($style) . "'" . esc_attr($selected) . ">" . esc_html(ucfirst(self::WWRC_CITATION_STYLES_LABELS[$index])) .
                "</option>";
        }
        echo "</select>";
    }

    public function wwrc_webkew_bibliography_style_callback()
    {
        $options = get_option('webkew_wp_references_citations_options');
        $current_style = isset($options['wwrc_webkew_bibliography_style']) ? $options['wwrc_webkew_bibliography_style'] : self::WWRC_BIBLIOGRAPHY_STYLES[0];

        echo "<select name='webkew_wp_references_citations_options[wwrc_webkew_bibliography_style]'>";
        foreach (self::WWRC_BIBLIOGRAPHY_STYLES as $style) {
            $selected = ($current_style === $style) ? 'selected' : '';
            echo "<option value='" . esc_attr($style) . "'" . esc_attr($selected) . ">" . esc_html(ucfirst($style)) .
                "</option>";
        }
        echo "</select>";
    }

    public function wwrc_post_types_callback()
    {
        $options = get_option('webkew_wp_references_citations_options');
        $post_types = get_post_types(['public' => true], 'objects');
        // Filter our the attachment post type
        unset($post_types['attachment']);
        foreach ($post_types as $post_type) {
            $checked = isset($options['wwrc_post_types'][$post_type->name]) ? 'checked' : '';
            echo "<label><input type='checkbox' name='webkew_wp_references_citations_options[wwrc_post_types][" .
                esc_attr($post_type->name) . "]' value='1'" . esc_attr($checked) . ">" .
                esc_html($post_type->label) . "</label><br>";
        }
    }
    public function wwrc_delete_data_callback()
    {
        $options = get_option('webkew_wp_references_citations_options');
        $checked = isset($options['wwrc_delete_data_on_uninstall']) ? 'checked' : '';
        echo "<label><input type='checkbox' name='webkew_wp_references_citations_options[wwrc_delete_data_on_uninstall]' value='1'" .
            esc_attr($checked) . "> Delete all plugin data when uninstalling</label>";
    }

    public function wwrc_display_plugin_page()
    {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__("WebKew WP References and Citations Settings", "webkew-wp-references-citations"); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('webkew_wp_references_citations_options_group');
                do_settings_sections('webkew-wp-references-citations');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function wwrc_add_references_meta_box()
    {
        $options = get_option('webkew_wp_references_citations_options');
        $enabled_post_types = isset($options['wwrc_post_types']) ? array_keys($options['wwrc_post_types']) : array();

        foreach ($enabled_post_types as $post_type) {
            add_meta_box(
                'webkew_references_meta_box',
                'References',
                array($this, 'wwrc_render_references_meta_box'),
                $post_type,
                'normal',
                'high'
            );
        }
    }

    public function wwrc_render_references_meta_box($post)
    {
        $references = get_post_meta($post->ID, 'webkew_wp_references_field', true);
        wp_nonce_field('webkew_references_meta_box', 'webkew_references_meta_box_nonce');
        ?>
        <textarea name="webkew_wp_references_field" id="webkew_wp_references_field" rows="10" style="width: 100%;">
            <?php echo esc_textarea($references); ?></textarea>
        <p>Enter BibTeX references here, one per entry.</p>
        <?php
    }

    public function wwrc_save_references_meta_box($post_id)
    {
        if (!isset($_POST['webkew_references_meta_box_nonce']) || !wp_verify_nonce(sanitize_key(
                $_POST['webkew_references_meta_box_nonce']), 'webkew_references_meta_box')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!empty(sanitize_textarea_field(wp_unslash($_POST['webkew_wp_references_field'])))) {
            update_post_meta(
                $post_id,
                'webkew_wp_references_field',
                sanitize_textarea_field(wp_unslash($_POST['webkew_wp_references_field'])));
        } else {
            delete_post_meta($post_id, 'webkew_wp_references_field');
        }
    }
}