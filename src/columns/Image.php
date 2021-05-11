<?php


namespace midkard\wp_list_table\columns;


class Image extends Meta
{

    public function __construct($params)
    {
        $params['editType'] = 'image';
        parent::__construct( $params );
    }


    protected function getValue($object)
    {
        $img_id = parent::getValue( $object );
        $value  = '<input type="hidden" value="' . $img_id . '" autocomplete="off"/>';
        if ( $img_id ) {
            $value .= wp_get_attachment_image( $img_id );
        } else {
            $value .= '⊕';
        }

        return $value;
    }


}