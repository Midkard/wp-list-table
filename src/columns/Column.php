<?php

namespace midkard\wp_list_table\columns;

use ErrorException;
use WP_Post;
use WP_Term;

/**
 * Description of Column
 *
 * @author Midkard
 */
class Column implements IColumn
{


    /** @var string */
    private $key;

    /** @var string */
    protected $displayName;

    /** @var bool */
    protected $sortable = false;

    /** @var bool */
    protected $hidden = false;

    /** @var string */
    protected $orderBy;

    /** @var bool */
    protected $quickEdit = false;

    /** @var string */
    protected $editType;

    /** @var bool|null */
    private $sorted;

    /**
     *
     * @param array $params
     *
     * @throws ErrorException
     */
    function __construct($params)
    {
        if ( !$params['key'] ) {
            throw new ErrorException( 'Key parameter is needed for column creation' );
        }
        foreach ($params as $key => $value) {
            $this->{$key} = $value;
        }

    }

    /**
     * Return html for cell
     *
     * @param WP_Post|WP_Term $item
     */
    public function displayCell($item)
    {
        $attributes = '';
        // Comments column uses HTML in the display name with screen reader text.
        // Instead of using esc_attr(), we strip tags to get closer to a user-friendly string.
        foreach ($this->cellAttributes() as $key => $value) {
            $attributes .= $key . '="' . esc_attr( $value ) . '" ';
        }

        $value = $this->getValue( $item );

        echo "<td $attributes>";
        echo $value;
        echo "</td>";
    }

    /**
     * Html of cell for table thead
     *
     * @return void
     */
    public function displayHeader()
    {
        $current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
        $current_url = remove_query_arg( 'paged', $current_url );

        if ( $this->sortable ) {
            $params = $this->getQueryParamsForSorting();
            if ( $this->isSorted() ) {
                $params['order'] = 'asc' === $_GET['order'] ? 'desc' : 'asc';
            } else {
                $params['order'] = 'desc';
            }
            $column_display_name = '<a href="' . esc_url( add_query_arg( $params, $current_url ) ) . '"><span>' . $this->displayName . '</span><span class="sorting-indicator"></span></a>';
        } else {
            $column_display_name = $this->displayName;
        }

        $classes = $this->getHeaderClasses();
        if ( !empty( $classes ) ) {
            $class = "class='" . implode( ' ', $classes ) . "'";
        } else {
            $class = '';
        }

        echo "<th $class>$column_display_name</th>";
    }

    /**
     * Return some properties of column for js
     *
     * @return array
     */
    public function getJSObject()
    {
        $field_to_json = [];
        if ( !empty( $this->editType ) ) {
            $field_to_json['type'] = $this->editType;
        }

        return $field_to_json;
    }

    /**
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     *
     * @return array
     */
    protected function cellAttributes()
    {
        return [
            'data-column' => $this->key,
            'class'       => implode( ' ', $this->cellClasses() ),
        ];
    }

    /**
     *
     * @param object $item
     *
     * @return mixed
     */
    protected function getValue($item)
    {
        return $item->{$this->key};
    }

    /**
     *
     * @return string[]
     */
    protected function cellClasses()
    {
        $classes = [$this->key, "column-$this->key"];

        if ( $this->hidden ) {
            $classes[] = 'hidden';
        }

        if ( $this->quickEdit ) {
            $classes[] = 'quick-edit';
        }

        return $classes;
    }

    /**
     *
     * @return string[]
     */
    protected function getHeaderClasses()
    {
        $classes = ['manage-column', "column-$this->key"];

        if ( $this->hidden ) {
            $classes[] = 'hidden';
        }

        if ( $this->sortable ) {
            if ( $this->isSorted() ) {
                $classes[] = 'sorted';
                $classes[] = 'desc' === $_GET['order'] ? 'desc' : 'asc';
            } else {
                $classes[] = 'sortable';
                $classes[] = 'asc';
            }
        }

        return $classes;
    }

    /**
     *
     * @return string
     */
    protected function getOrderBy()
    {
        return $this->orderBy ? $this->orderBy : $this->key;

    }

    /**
     *
     *
     * @return bool
     */
    protected function isSorted()
    {
        if ( isset( $this->sorted ) ) {
            return $this->sorted;
        }

        $params = $this->getQueryParamsForSorting();
        foreach ($params as $key => $value) {
            if ( empty( $_GET[$key] ) || $_GET[$key] !== $value ) {
                $this->sorted = false;

                return false;
            }
        }
        $this->sorted = true;

        return true;

    }

    /**
     * @return bool
     */
    public function isHidden()
    {
        return $this->hidden;
    }

    /**
     * Return array of query parameters. Without query parameter 'order'.
     *
     * @return array
     */
    protected function getQueryParamsForSorting()
    {
        $orderby = $this->getOrderBy();

        return compact( 'orderby' );
    }

    /**
     * Update value of field and return new value
     *
     * @param WP_Post|WP_Term $object
     * @param string $value
     *
     * @return string new html value
     *
     */
    public function updateValue($object, $value)
    {
        return 'update of field is restricted';
    }

}
