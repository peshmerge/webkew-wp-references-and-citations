<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
class Webkewrc_References_Citations_Admin
{
    const  WEBKEWRC_CITATION_STYLES = ['author_year', 'Author', 'numerical'];
    const  WEBKEWRC_CITATION_STYLES_LABELS = ['(Author Year) Author year', '(Author) Author', '(NUMBER) Numerical'];
    const  WEBKEWRC_BIBLIOGRAPHY_STYLES = ['apa', 'vancouver', 'harvard'];

    public function webkewrc_add_plugin_menu()
    {
        add_menu_page(
            'WebKew WP References and Citations',
            'WebKew WP References',
            'manage_options',
            'webkew-wp-references-and-citations',
            array($this, 'webkewrc_display_plugin_page'),
            'dashicons-format-quote',
            15
        );
    }

    public function webkewrc_register_settings()
    {
        register_setting(
            'webkewrc_references_citations_options_group',
            'webkewrc_references_citations_options',
            array('sanitize_callback' => array($this, 'webkewrc_sanitize_options'))
        );
        add_settings_section(
            'webkewrc_references_main',
            esc_html__('Main Settings', 'webkew-wp-references-and-citations'),
            array($this, 'webkewrc_settings_section_callback'),
            'webkew-wp-references-and-citations'
        );
        add_settings_field(
            'webkewrc_post_types',
            esc_html__('Enabled Post Types', 'webkew-wp-references-and-citations'),
            array($this, 'webkewrc_post_types_callback'),
            'webkew-wp-references-and-citations',
            'webkewrc_references_main'
        );
        add_settings_field(
            'webkewrc_bibliography_style',
            esc_html__('Bibliography Style (Based on Citation-JS Style)', 'webkew-wp-references-and-citations'),
            array($this, 'webkewrc_bibliography_style_callback'),
            'webkew-wp-references-and-citations',
            'webkewrc_references_main'
        );

        add_settings_field(
            'webkewrc_webkew_citation_style',
            esc_html__('Citation Style (in the text)', 'webkew-wp-references-and-citations'),
            array($this, 'webkewrc_citation_style_callback'),
            'webkew-wp-references-and-citations',
            'webkewrc_references_main'
        );

        add_settings_field(
            'webkewrc_delete_data_on_uninstall',
            esc_html__('Delete data on uninstall', 'webkew-wp-references-and-citations'),
            array($this, 'webkewrc_delete_data_callback'),
            'webkew-wp-references-and-citations',
            'webkewrc_references_main'
        );
    }

    public function webkewrc_sanitize_options($input)
    {
        $sanitized_input = array();

        // Sanitize citation style
        if (isset($input['webkewrc_webkew_citation_style']) && in_array($input['webkewrc_webkew_citation_style'],
                self::WEBKEWRC_CITATION_STYLES)) {
            $sanitized_input['webkewrc_webkew_citation_style'] = sanitize_text_field($input['webkewrc_webkew_citation_style']);
        }

        // Sanitize bibliography style
        if (isset($input['webkewrc_bibliography_style']) && in_array($input['webkewrc_bibliography_style'],
                self::WEBKEWRC_BIBLIOGRAPHY_STYLES)) {
            $sanitized_input['webkewrc_bibliography_style'] = sanitize_text_field($input['webkewrc_bibliography_style']);
        }

        // Sanitize post types
        if (isset($input['webkewrc_post_types'])) {
            $sanitized_input['webkewrc_post_types'] = array_map('sanitize_text_field', $input['webkewrc_post_types']);
        }

        // Sanitize the delete data on uninstall option
        $sanitized_input['webkewrc_delete_data_on_uninstall'] = isset($input['webkewrc_delete_data_on_uninstall']) ? 1 : 0;

        return $sanitized_input;
    }

    public function webkewrc_settings_section_callback()
    {
        echo '<h3>' . esc_html__(
                'Select the post types where you want to enable references:',
                'webkew-wp-references-and-citations') . '</h3>';
    }


    public function webkewrc_citation_style_callback()
    {
        $options = get_option('webkewrc_references_citations_options');
        $current_style = $options['webkewrc_webkew_citation_style'] ?? 'author_year';

        echo "<select name='webkewrc_references_citations_options[webkewrc_webkew_citation_style]'>";
        foreach (self::WEBKEWRC_CITATION_STYLES as $index => $style) {
            $selected = ($current_style === $style) ? 'selected="selected"' : '';
            echo "<option value='" . esc_attr($style) . "'" . esc_attr($selected) . ">" . esc_html(ucfirst(self::WEBKEWRC_CITATION_STYLES_LABELS[$index])) .
                "</option>";
        }
        echo "</select>";
    }

    public function webkewrc_bibliography_style_callback()
    {
        $options = get_option('webkewrc_references_citations_options');
        $current_style = isset($options['webkewrc_bibliography_style']) ? $options['webkewrc_bibliography_style'] : self::WEBKEWRC_BIBLIOGRAPHY_STYLES[0];

        echo "<select name='webkewrc_references_citations_options[webkewrc_bibliography_style]'>";
        foreach (self::WEBKEWRC_BIBLIOGRAPHY_STYLES as $style) {
            $selected = ($current_style === $style) ? 'selected' : '';
            echo "<option value='" . esc_attr($style) . "'" . esc_attr($selected) . ">" . esc_html(ucfirst($style)) .
                "</option>";
        }
        echo "</select>";
    }

    public function webkewrc_post_types_callback()
    {
        $options = get_option('webkewrc_references_citations_options');
        $post_types = get_post_types(['public' => true], 'objects');
        // Filter our the attachment post type
        unset($post_types['attachment']);
        foreach ($post_types as $post_type) {
            $checked = isset($options['webkewrc_post_types'][$post_type->name]) ? 'checked' : '';
            echo "<label><input type='checkbox' name='webkewrc_references_citations_options[webkewrc_post_types][" .
                esc_attr($post_type->name) . "]' value='1'" . esc_attr($checked) . ">" .
                esc_html($post_type->label) . "</label><br>";
        }
    }

    public function webkewrc_delete_data_callback()
    {
        $options = get_option('webkewrc_references_citations_options');
        $checked = isset($options['webkewrc_delete_data_on_uninstall']) ? 'checked' : '';
        echo "<label><input type='checkbox' name='webkewrc_references_citations_options[webkewrc_delete_data_on_uninstall]' value='1'" .
            esc_attr($checked) . ">" . esc_html__('Delete all plugin data when uninstalling', 'webkew-wp-references-and-citations') . "</label>";
    }

    public function webkewrc_display_plugin_page()
    {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('WebKew WP References and Citations Settings', 'webkew-wp-references-and-citations'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('webkewrc_references_citations_options_group');
                do_settings_sections('webkew-wp-references-and-citations');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function webkewrc_add_references_meta_box()
    {
        $options = get_option('webkewrc_references_citations_options');
        $enabled_post_types = isset($options['webkewrc_post_types']) ? array_keys($options['webkewrc_post_types']) : array();

        foreach ($enabled_post_types as $post_type) {
            add_meta_box(
                'webkew_references_meta_box',
                esc_html__('References', 'webkew-wp-references-and-citations'),
                array($this, 'webkewrc_render_references_meta_box'),
                $post_type,
                'normal',
                'high'
            );
        }
    }

    public function webkewrc_render_references_meta_box($post)
    {
        $references = get_post_meta($post->ID, 'webkewrc_wp_references_field', true);
        wp_nonce_field('webkew_references_meta_box', 'webkew_references_meta_box_nonce');
        echo '<p>' . esc_html__('Enter BibTeX references here, one per entry.', 'webkew-wp-references-and-citations') . '</p>';
        ?>
        <textarea name="webkewrc_wp_references_field" id="webkewrc_wp_references_field" rows="10"
                  style="width: 100%;"><?php echo esc_textarea($references); ?></textarea><?php
    }

    public function webkewrc_save_references_meta_box($post_id)
    {
        if (!isset($_POST['webkew_references_meta_box_nonce']) || !wp_verify_nonce(sanitize_key(
                $_POST['webkew_references_meta_box_nonce']), 'webkew_references_meta_box')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!empty(sanitize_textarea_field(wp_unslash($_POST['webkewrc_wp_references_field'])))) {
            update_post_meta(
                $post_id,
                'webkewrc_wp_references_field',
                sanitize_textarea_field(wp_unslash($_POST['webkewrc_wp_references_field'])));
        } else {
            delete_post_meta($post_id, 'webkewrc_wp_references_field');
        }
    }
}