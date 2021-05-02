<?php

namespace midkard\wp_list_table\columns;

use ErrorException;
use WP_Post;
use WP_Term;

/**
 * Description of ColumnACF
 *
 * @author Midkard
 */
class Meta extends Column {

	/** @var string */
	protected $fieldKey;

	/**
	 * Meta constructor.
	 *
	 * @param $params
	 *
	 * @throws ErrorException
	 */
	public function __construct( $params ) {
		parent::__construct( $params );

		$this->fieldKey = isset( $params['meta_field'] ) ? $params['meta_field'] : $params['key'];
		$this->orderBy  = 'meta_value';

	}

	/**
	 * Return array of query parameters
	 *
	 * @return array
	 */
	protected function getQueryParamsForSorting() {
		$params             = parent::getQueryParamsForSorting();
		$params['meta_key'] = $this->fieldKey;

		return $params;
	}

	/**
	 *
	 * @param WP_Term|WP_Post $object
	 *
	 * @return string
	 */
	protected function getValue( $object ) {

		if ( $object instanceof WP_Term ) {
			return get_term_meta( $object->term_id, $this->fieldKey, 1 );
		} elseif ( $object instanceof WP_Post ) {
			return get_post_meta( $object->ID, $this->fieldKey, 1 );
		}

		return 'Поле не предназначено для данного типа';

	}

	/**
	 * Update value of ACF Field and return new value
	 *
	 * @param WP_Term|WP_Post $object
	 * @param string $value
	 *
	 * @return string
	 */
	public function updateValue( $object, $value ) {
		if ( $object instanceof WP_Term ) {
			update_term_meta( $object->term_id, $this->fieldKey, $value );
		} elseif ( $object instanceof WP_Post ) {
			update_post_meta( $object->ID, $this->fieldKey, $value );
		}

		return $this->getValue( $object );
	}

}
