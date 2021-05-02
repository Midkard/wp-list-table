<?php

namespace midkard\wp_list_table\columns;

use ErrorException;
use WP_Post;

/**
 * Represents non-hierarchical taxonomies.
 *
 * @author Midkard
 * @noinspection PhpUnused
 */
class PostTerm extends Column
{

    /** @var string Taxonomy name */
    protected $taxonomy;


    public function __construct($params)
    {
        parent::__construct( $params );

        if ( !$params['taxonomy'] ) {
            throw new ErrorException( 'Taxonomy parameter is needed for column creation' );
        }

        $this->taxonomy = $params['taxonomy'];
    }

    /**
     *
     * @param WP_Post $post
     * @return string
     */
    protected function getValue($post)
    {
        $terms = get_the_terms( $post->ID, $this->taxonomy );
        if ( is_array( $terms ) ) {
            $value = array();
            foreach ($terms as $term) {
                $label   = esc_html( sanitize_term_field( 'name', $term->name, $term->term_id, $this->taxonomy, 'display' ) );
                $value[] = $label;
            }
            /* translators: used between list items, there is a space after the comma */
            $value = join( __( ', ' ), $value );
        } else {
            $value = '';
        }

        return $value;
    }

    /**
     * Update value of taxonomy and return new value
     *
     * @param WP_Post $post
     * @param string $value
     * @return string
     */
    public function updateValue($post, $value)
    {
        $params                               = array(
            'post_ID' => $post->ID
        );
        $params['tax_input'][$this->taxonomy] = $value;
        edit_post( $params );

        $post = get_post( $post );

        return $this->getValue( $post );
    }

    public function getJSObject()
    {
        $field_to_json         = parent::getJSObject();
        $field_to_json['type'] = 'taxonomy';
        $field_to_json['name'] = $this->taxonomy;
        return $field_to_json;
    }


}
