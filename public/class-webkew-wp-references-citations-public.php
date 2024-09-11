<?php

require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-webkew-wp-references-citations-admin.php';

class WebKew_WP_References_Citations_Public
{
    private $used_references;

    private $references;

    private $bibliography_style = "apa";

    private $citation_style = "author_year";

    public function __construct()
    {
        $this->used_references = [];
        $this->references = null;
        add_action('wp_enqueue_scripts', array($this, 'wwrc_enqueue_scripts'));
        add_filter('the_content', array($this, 'wwrc_process_citations_and_add_bibliography'));
    }

    public function wwrc_enqueue_scripts()
    {
        if (!is_singular()) return;
        $post_id = get_queried_object_id();
        $post_type = get_post_type($post_id);
        $options = get_option('webkew_wp_references_citations_options');
        $enabled_post_types = isset($options['wwrc_post_types']) ? array_keys($options['wwrc_post_types']) : array();

        if (in_array($post_type, $enabled_post_types)) {
            $this->bibliography_style = isset($options['wwrc_webkew_bibliography_style']) ? $options['wwrc_webkew_bibliography_style'] : 'apa';
            $this->citation_style = isset($options['wwrc_webkew_citation_style']) ? $options['wwrc_webkew_citation_style'] : 'author_year';

            wp_enqueue_style('webkew-wp-references-public',
                WWRC_WEBKEW_WP_REFERENCES_URL . 'public/css/webkew-wp-references-public.css',
                array(),
                '1.0'
            );
            wp_enqueue_script(
                'webkew-wp-references-public',
                WWRC_WEBKEW_WP_REFERENCES_URL . 'public/js/dist/webkew-wp-references-citations.js',
                array('jquery'),
                '1.0',
                true  // Load in footer
            );

            $this->references = get_post_meta($post_id, 'webkew_wp_references_field', true);
        }
    }

    public function wwrc_process_citations_and_add_bibliography($content)
    {
        if (!is_singular() || empty($this->references)) return $content;

        global $post;

        $this->used_references = [];
        $processed_content = $this->wwrc_replace_citations($content);

        // Add a placeholder for the bibliography
        $processed_content .= '<div id="webkew-wp-references-bibliography_' . $post->ID . '"><h2 class="wwrc-bibliography">' .
            __('Bibliography', 'webkew-wp-references-citations') . '</h2></div>';

        $data = array(
            'references' => $this->references,
            'usedReferences' => $this->used_references,
            'bibliographyStyle' => $this->bibliography_style,
            'postID' => $post->ID,
        );

        wp_add_inline_script('webkew-wp-references-public',
            'window.webkewReferencesData = window.webkewReferencesData || {}; ' .
            'window.webkewReferencesData[' . $post->ID . '] = ' . wp_json_encode($data) . ';',
            'before'
        );

        return $processed_content;
    }

    private function wwrc_replace_citations($content)
    {
        preg_match_all('/\\\\cite\{([^}]+)\}/', $content, $matches, PREG_SET_ORDER);
        $citations_count = 0;
        foreach ($matches as $index => $match) {
            if (count(explode(',', $match[1])) === 1) {
                $citations_count++;
                $replacement = $this->wwrc_format_citation($match[1], $citations_count);
                $this->used_references[] = $match[1];
                $content = str_replace($match[0], "(" . $replacement . ")", $content);
            } elseif (count(explode(',', $match[1])) > 1) {
                $replacement = [];
                $citations = array_map('trim', explode(',', $match[1]));
                foreach ($citations as $second_index => $citation) {
                    $citations_count++;
                    $replacement[] = $this->wwrc_format_citation($citation, $second_index + $citations_count);
                    $this->used_references[] = $citation;
                }
                $citations = "(" . implode('; ', $replacement) . ")";
                $content = str_replace($match[0], "$citations", $content);
            }
        }
        return $content;
    }

    private function wwrc_format_citation($key, $citations_count)
    {
        if (preg_match(
            '/@(\w+)\{' . preg_quote($key, '/') .
            ',.*?author\s*=\s*(\{|\")([^"}]+)(\}|\").*?year\s*=\s*(\{|\")(\d+)(\}|\")/is', $this->references, $match)) {
            $authors = explode(' and ', $match[3]);
            $year = $match[6];
            if (count($authors) > 1) {
                $author = explode(',', $authors[0])[0] . ' et al.';
            } else {
                $author = explode(',', $authors[0])[0];
            }

            if ($this->citation_style === WebKew_WP_References_Citations_Admin::WWRC_CITATION_STYLES[0]) { // Author_year
                return $this->return_citation_href_element($key, "{$author} {$year}");
            } elseif ($this->citation_style === WebKew_WP_References_Citations_Admin::WWRC_CITATION_STYLES[1]) { // Author
                return $this->return_citation_href_element($key, $author);
            } elseif ($this->citation_style === WebKew_WP_References_Citations_Admin::WWRC_CITATION_STYLES[2]) { //numerical
                return $this->return_citation_href_element($key, $citations_count);
            }
        }
        return "\\cite{$key}";
    }

    private function return_citation_href_element($key, $citation)
    {
        return "<a href='#$key' class='webkew-citation' data-key='$key'>$citation</a>";
    }
}