<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-webkewrc-references-citations-admin.php';

class Webkewrc_References_Citations_Public
{
    /**
     * @var array
     */
    private $used_references;

    /**
     * @var array
     */
    private $references;

    private $bibliography_style = Webkewrc_References_Citations_Admin::WEBKEWRC_BIBLIOGRAPHY_STYLES[0];

    private $citation_style = Webkewrc_References_Citations_Admin::WEBKEWRC_CITATION_STYLES[0];

    public function __construct()
    {
        $this->used_references = [];
        $this->references = null;
        add_action('wp_enqueue_scripts', array($this, 'webkewrc_enqueue_scripts'));
        add_filter('the_content', array($this, 'webkewrc_process_citations_and_add_bibliography'));
    }

    public function webkewrc_enqueue_scripts()
    {
        if (!is_singular()) return;
        $post_id = get_queried_object_id();
        $post_type = get_post_type($post_id);
        $options = get_option('webkewrc_references_citations_options');
        $enabled_post_types = isset($options['webkewrc_post_types']) ? array_keys($options['webkewrc_post_types']) : array();

        if (in_array($post_type, $enabled_post_types)) {
            $this->bibliography_style = isset($options['webkewrc_bibliography_style']) ? $options['webkewrc_bibliography_style'] : 'apa';
            $this->citation_style = isset($options['webkewrc_webkew_citation_style']) ? $options['webkewrc_webkew_citation_style'] : 'author_year';

            wp_enqueue_style('webkew-wp-references-citations-public',
                WEBKEWRC_REFERENCES_URL . 'public/css/webkew-wp-references-citations-public.css',
                array(),
                '1.0.0'
            );
            wp_enqueue_script(
                'webkew-wp-references-citations-public',
                WEBKEWRC_REFERENCES_URL . 'public/js/dist/webkew-wp-references-citations-public.js',
                array('jquery'),
                '1.0.0',
                true  // Load in footer
            );
            // By doing this we ensure the injection of the bibliography data happens before the loading of the script
            add_action('wp_footer', array($this, 'inject_the_bibliography_data_in_the_footer'));
        }
    }

    public function webkewrc_process_citations_and_add_bibliography($content)
    {
        $this->references = wp_kses_post(get_post_meta(get_the_ID(), 'webkewrc_wp_references_field', true));
        if (!is_singular() || empty($this->references)) return $content;

        global $post;

        $this->used_references = [];
        $processed_content = $this->webkewrc_replace_citations($content);

        // Add a placeholder for the bibliography
        $processed_content .= '<div id="webkew-wp-references-bibliography_' . esc_attr($post->ID)
            . '" class="webkewrc-bibliography"> <h2 class="webkewrc-bibliography_title">'
            . esc_html__('Bibliography', 'webkew-wp-references-and-citations') . '</h2></div>';

        return $processed_content;
    }

    public function inject_the_bibliography_data_in_the_footer()
    {
        global $post;

        if (!is_singular() || empty(get_post_meta(get_the_ID(), 'webkewrc_wp_references_field', true))) {
            return;
        }

        $data = array(
            'references' => $this->references,
            'usedReferences' => $this->used_references,
            'bibliographyStyle' => $this->bibliography_style,
            'postID' => $post->ID,
        );

        wp_add_inline_script('webkew-wp-references-citations-public',
            'window.webkewReferencesData = window.webkewReferencesData || {}; ' . "\n" .
            'window.webkewReferencesData[' . esc_js($post->ID) . '] = '
            . wp_json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ';',
            'before'
        );
    }

    /**
     * This function searches the content of the loaded (custom) post / page for keywords \cite{KEY}. In addition,it
     * decides whether we are dealing with a single or multi-author citation.
     * Each found instance is added to the list used_references which is used later to verify whether the used citations
     * have corresponding bibtex entries in the webkewrc_wp_references_field that belongs to the current (custom) post / page
     *
     * @param $content
     * @return string
     */
    private function webkewrc_replace_citations($content)
    {
        preg_match_all('/\\\\cite\{([^}]+)\}/', $content, $matches, PREG_SET_ORDER);
        $citations_count = 0;
        foreach ($matches as $index => $match) {
            if (count(explode(',', $match[1])) === 1) {
                $citations_count++;
                $replacement = $this->webkewrc_format_citation($match[1], $citations_count);
                $this->used_references[] = $match[1];
                $content = str_replace($match[0], "(" . $replacement . ")", $content);
            } elseif (count(explode(',', $match[1])) > 1) {
                $replacement = [];
                $citations = array_map('trim', explode(',', $match[1]));
                foreach ($citations as $second_index => $citation) {
                    $citations_count++;
                    $replacement[] = $this->webkewrc_format_citation($citation, $second_index + $citations_count);
                    $this->used_references[] = $citation;
                }
                $citations = "(" . implode('; ', $replacement) . ")";
                $content = str_replace($match[0], "$citations", $content);
            }
        }
        return $content;
    }

    /**
     * It checks whether each given found citation in the content has corresponding bibtex entry and try to extract
     * entry_key, author(s) name(s), and the publication year!
     * Each found instance will be replaced with the corresponding citation style as defined in the settings of the plugin.
     *
     * If the passed instance is not found within the list of references of the current (custom) post/page, the function
     * will return the citation as is \cite{KEY}.
     *
     * @param $key
     * @param $citations_count
     * @return string
     */
    private function webkewrc_format_citation($key, $citations_count)
    {
        $entry_pattern = "/@(\\w+){\\s*([^,]+),\\s*([\\s\\S]+?)}\\s*(?=@|$)/";
        // Regex patterns to extract author and year fields
        $author_pattern = "/author\\s*=\\s*{([^}]+)}/";
        $year_pattern = "/year\\s*=\\s*{?([0-9]{4})}?/i";

        if (preg_match_all($entry_pattern, $this->references, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $entry_key = trim($match[2]);
                $fields = $match[3];
                // Check if the current entry matches the search key
                if ($entry_key === $key) {
                    // Extract author and year
                    preg_match($author_pattern, $fields, $author_match);
                    preg_match($year_pattern, $fields, $year_match);
                    $author = isset($author_match[1]) ? $author_match[1] : "N/A";

                    $authors = explode(' and ', $author);
                    if (count($authors) > 1) {
                        $author = explode(',', $authors[0])[0] . ' et al.';
                    } else {
                        $author = explode(',', $authors[0])[0];
                    }
                    $year = isset($year_match[1]) ? $year_match[1] : "N/A";
                    if ($this->citation_style === Webkewrc_References_Citations_Admin::WEBKEWRC_CITATION_STYLES[0]) { // Author_year
                        return $this->return_citation_href_element($key, "{$author} {$year}");
                    } elseif ($this->citation_style === Webkewrc_References_Citations_Admin::WEBKEWRC_CITATION_STYLES[1]) { // Author
                        return $this->return_citation_href_element($key, $author);
                    } elseif ($this->citation_style === Webkewrc_References_Citations_Admin::WEBKEWRC_CITATION_STYLES[2]) { //numerical
                        return $this->return_citation_href_element($key, $citations_count);
                    }
                }
            }
        }
        return "\\cite{$key}";
    }

    private function return_citation_href_element($key, $citation)
    {
        return "<a href='#" . esc_attr($key) . "' class='webkew-citation' data-key='" . esc_attr($key) . "'>" . esc_html($citation) . "</a>";
    }
}