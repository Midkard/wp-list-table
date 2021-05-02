<?php

namespace midkard\wp_list_table\columns;

use WP_Post;

/**
 * Description of ColumnUri
 *
 * @author Midkard
 * @noinspection PhpUnused
 */
class PostUri extends Column{
    
    protected $editType = 'permalink';

    /**
     * @param WP_Post $post
     * @return mixed|string
     */
    public function getValue($post) {
        return '<div class=edit-slug-box>' . $this->getSamplePermalinkHtml($post->ID) . '</div>';
    }
    
    /**
     * Update value of slug and return new value
     * 
     * @param WP_Post $post
     * @param string $value
     * @return string
     */
    public function updateValue($post, $value) {
        $params = array(
            'ID' => $post->ID,
            'post_name' => $value,
        );
        wp_update_post($params);
        
        $post = get_post($post);

        return $this->getValue($post);
    }
    
    /**
     * Returns the HTML of the sample permalink slug editor.
     *
     * @param int    $id        Post ID or post object.
     * @return string The HTML of the sample permalink slug editor.
     */
    private function getSamplePermalinkHtml($id) {
        $post = get_post($id);
        if (!$post)
            return '';

        list($permalink, $post_name) = get_sample_permalink($post->ID);

        $view_link = false;
        $preview_target = '';

        if (current_user_can('read_post', $post->ID)) {
            if ('draft' === $post->post_status || empty($post->post_name)) {
                $view_link = get_preview_post_link($post);
                $preview_target = " target='wp-preview-{$post->ID}'";
            } else {
                if ('publish' === $post->post_status || 'attachment' === $post->post_type) {
                    $view_link = get_permalink($post);
                } else {
                    // Allow non-published (private, future) to be viewed at a pretty permalink, in case $post->post_name is set
                    $view_link = str_replace(array('%pagename%', '%postname%'), $post->post_name, $permalink);
                }
            }
        }

        $return = '';
        // Permalinks without a post/page name placeholder don't have anything to edit
        if (false === strpos($permalink, '%postname%') && false === strpos($permalink, '%pagename%')) {

            if (false !== $view_link) {
                $display_link = urldecode($view_link);
                $return .= '<a class="sample-permalink" href="' . esc_url($view_link) . '" ' . $preview_target . '>' . esc_html($display_link) . "</a>\n";
            } else {
                $return .= '<span class="sample-permalink">' . $permalink . "</span>\n";
            }

            // Encourage a pretty permalink setting
            if ('' == get_option('permalink_structure') && current_user_can('manage_options') && !( 'page' == get_option('show_on_front') && $id == get_option('page_on_front') )) {
                $return .= '<span id="change-permalinks"><a href="options-permalink.php" class="button button-small" target="_blank">' . __('Change Permalinks') . "</a></span>\n";
            }
        } else {
            if (mb_strlen($post_name) > 34) {
                $post_name_abridged = mb_substr($post_name, 0, 16) . '&hellip;' . mb_substr($post_name, -16);
            } else {
                $post_name_abridged = $post_name;
            }

            $post_name_html = '<span class="editable-post-name">' . esc_html($post_name_abridged) . '</span>';
            $display_link = str_replace(array('%pagename%', '%postname%'), $post_name_html, esc_html(urldecode($permalink)));

            $return .= '<span class="sample-permalink"><a href="' . esc_url($view_link) . '" ' . $preview_target . '>' . $display_link . "</a></span>\n";
            $return .= '&lrm;'; // Fix bi-directional text display defect in RTL languages.
            if ($this->quickEdit) {
                $return .= '<span class="edit-slug-buttons"><button type="button" class="edit-slug button button-small hide-if-no-js" aria-label="' . __('Edit permalink') . '">' . __('Edit') . "</button></span>\n";
            }            
            $return .= '<span class="editable-post-name-full hidden">' . esc_html($post_name) . "</span>\n";
        }

        return $return;
    }

}
