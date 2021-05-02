<?php

namespace midkard\wp_list_table\columns;

use WP_Post;
use WP_Term;

/**
 * Description of ColumnTitle
 *
 * @author Midkard
 */
class Title extends Column {

    /**
     * Handles the title column output.
     *
     * @param WP_Post|WP_Term $object The current object.
     * @return mixed
     */
    protected function getValue($object) {
        
        if (isset($object->post_type)) {
            return $this->getPostTitle($object);
        } elseif (isset ($object->taxonomy)) {
            return $this->getTermTitle($object);
        }

        return '';
    }

    /**
     * Return post title
     *
     * @param WP_Post $post The current WP_Post object.
     * @return false|string
     * @noinspection HtmlUnknownTarget
     */
    protected function getPostTitle($post) {
        
        ob_start();

        $can_edit_post = current_user_can('edit_post', $post->ID);

        if ($can_edit_post && $post->post_status != 'trash') {
            $lock_holder = wp_check_post_lock($post->ID);

            if ($lock_holder) {
                $lock_holder = get_userdata($lock_holder);
                $locked_avatar = get_avatar($lock_holder->ID, 18);
                $locked_text = esc_html(sprintf(__('%s is currently editing'), $lock_holder->display_name));
            } else {
                $locked_avatar = $locked_text = '';
            }

            echo '<div class="locked-info"><span class="locked-avatar">' . $locked_avatar . '</span> <span class="locked-text">' . $locked_text . "</span></div>\n";
        }

        $pad = str_repeat('&#8212; ', $post->level ? : 0);
        echo "<strong>";

        $format = get_post_format($post->ID);
        if ($format) {
            $label = get_post_format_string($format);

            $format_class = 'post-state-format post-format-icon post-format-' . $format;

            $format_args = array(
                'post_format' => $format,
                'post_type' => $post->post_type
            );

            echo $this->getEditLink($format_args, $label . ':', $format_class);
        }

        $title = _draft_or_post_title($post);

        if ($can_edit_post && $post->post_status != 'trash') {
            printf(
                    '<a class="row-title" href="%s" aria-label="%s">%s%s</a>', get_edit_post_link($post->ID),
                    esc_attr(sprintf(__('&#8220;%s&#8221; (Edit)'), $title)), $pad, $title
            );
        } else {
            echo $pad . $title;
        }
        _post_states($post);

//		if ( isset( $parent_name ) ) {
//			$post_type_object = get_post_type_object( $post->post_type );
//			echo ' | ' . $post_type_object->labels->parent_item_colon . ' ' . esc_html( $parent_name );
//		}
        echo "</strong>\n";

        /* if ( ! is_post_type_hierarchical( $this->screen->post_type ) && 'excerpt' === $mode && current_user_can( 'read_post', $post->ID ) ) {
          if ( post_password_required( $post ) ) {
          echo '<span class="protected-post-excerpt">' . esc_html( get_the_excerpt() ) . '</span>';
          } else {
          echo esc_html( get_the_excerpt() );
          }
          } */
        return ob_get_clean();
    }

    /**
     * Return term title.
     *
     * @param WP_Term $term The current WP_Term object.
     * @return false|string
     * @noinspection HtmlUnknownTarget
     */
    protected function getTermTitle($term) {
        
        ob_start();

        $pad = str_repeat('&#8212; ', $term->level ? : 0);
        echo "<strong>";

        $title = $term->name;
        printf(
                '<a class="row-title" href="%s" aria-label="%s">%s%s</a>', get_edit_term_link($term->term_id),
                /* translators: %s: post title */ esc_attr(sprintf(__('&#8220;%s&#8221; (Edit)'), $title)), $pad, $title
        );

        echo "</strong>\n";

        return ob_get_clean();
    }
    
    /**
	 * Helper to create links to edit.php with params.
	 *
	 * @param array  $args  URL parameters for the link.
	 * @param string $label Link text.
	 * @param string $class Optional. Class attribute. Default empty string.
	 * @return string The formatted link string.
     */
	private function getEditLink( $args, $label, $class = '' ) {
		$url = add_query_arg( $args, 'edit.php' );

		$class_html = $aria_current = '';
		if ( ! empty( $class ) ) {
			 $class_html = sprintf(
				'class="%s"',
				esc_attr( $class )
			);

			if ( 'current' === $class ) {
				$aria_current = ' aria-current="page"';
			}
		}

        return '<a href="' . esc_url( $url ) . '" ' . $class_html . '' . $aria_current . '>' . $label . '</a>';
	}
    
}
