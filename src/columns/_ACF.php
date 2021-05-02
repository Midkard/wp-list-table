<?php

namespace midkard\wp_list_table\columns;

/**
 * Temporary unavailable
 *
 * @author Midkard
 */
class ACF extends Column{
    
    /** @var string */
    protected $fieldKey;
    
    private $field;
    
    public function __construct($params) {
        parent::__construct($params);
        
        $this->fieldKey = isset($params['custom_field']) ? $params['custom_field'] : $params['key'];
           
    }
    
    /**
     * Return array of query parameters
     * 
     * @return array
     */
    protected function getQueryParamsForSorting() {
        $params = parent::getQueryParamsForSorting();
        $params['meta_key'] = $this->fieldKey;
        $params['orderby'] = 'meta_value';
        if ( 'number' === acf_get_field($this->fieldKey)['type']) {
            $params['orderby'] = 'meta_value_num';
        }
        
        return $params;
    }
    
    /**
     * 
     * @param object $object
     * @return string
     */
    protected function getValue($object) {

        $value_orig = get_field($this->fieldKey, $object);
        $field = $this->getField($object);

        if ('select' === $field['type'] || 'radio' === $field['type']) {
            $result = isset($value_orig['label']) ? $value_orig['label'] : $value_orig;
        }
        if ('checkbox' === $field['type'] && is_array($value_orig)) {
            foreach ($value_orig as $value) {
                $result[] = isset($value['label']) ? $value['label'] : $value;
            }
            $result = implode(',', $result);
        }
        if (empty($result)) {
            $result = $value_orig;
        }
        
        return $result;

    }
    
    /**
     * Update value of ACF Field and return new value
     * 
     * @param object $object
     * @param string $value
     * @return type Description
     */
    public function updateValue($object, $value) {
        update_field($this->fieldKey, $value, $object);
        return $this->getValue($object);
    }
    
    /**
     * 
     * @return string
     */
    public function getJSObject() {
        $field_to_json = parent::getJSObject();
        $field = $this->getField();

        if (empty($field_to_json['type']) && !empty($field['type'])) {
            $field_to_json['type'] = $field['type'];
        }
        if (!empty($field['choices'])) {
            $field_to_json['choices'] = $field['choices'];
        }
        
        return $field_to_json;
    }
    
    /**
     * 
     * @param object|null $object
     * @return array
     */
    protected function getField($object = null) {
        if (empty($this->field)) {
            if (isset($object)) {
                $this->field = get_field_object($this->fieldKey, $object, false, false);
            } else {
                $this->field = acf_get_field($this->fieldKey);
            }
        }
        
        return $this->field;
    }

}
