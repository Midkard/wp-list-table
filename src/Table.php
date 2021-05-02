<?php

namespace midkard\wp_list_table;

/**
 * Description of Table
 *
 * @author Midkard
 */
class Table
{

    /** @var columns\IColumn[] */
    protected $columns;

    function __construct(array $columns)
    {
        $this->columns = $columns;
    }


    public function display($items)
    {
        echo
        <<<EOT
<table class="wp-list-table widefat striped fixed">
    <thead>
    <tr>
EOT;
        foreach ($this->columns as $column) {
            $column->displayHeader();
        }
        echo
        <<<EOT
    </tr>
    </thead>

    <tbody id="the-list">
EOT;
        if ( count( $items ) ) {
            foreach ($items as $item) {
                $this->displayRow( $item );
            }
        } else {
            $this->displayEmpty();
        }
        echo
        <<<EOT
    </tbody>

    <tfoot>
    <tr>
    </tr>
    </tfoot>

</table>
EOT;
    }

    protected function displayEmpty()
    {
        $count = 0;
        foreach ($this->columns as $column) {
            if ( !$column->isHidden() ) {
                $count++;
            }
        }
        echo
            "<tr class='no-items'>" .
            "   <td class='colspanchange' colspan='$count'>" .
            "       " . __( 'No items found.' ) .
            "   </td>" .
            "</tr>";
    }

    /**
     *
     * @param object $item
     */
    protected function displayRow($item)
    {
        $row_id = '';
        if ( isset( $item->post_type ) ) {
            $row_id = 'post#' . $item->ID;
        } elseif ( isset( $item->taxonomy ) ) {
            $row_id = 'term#' . $item->term_id;
        }

        echo "<tr id='$row_id'>";

        foreach ($this->columns as $column) {
            $column->displayCell( $item );
        }
        echo '</tr>';
    }

    public function printJs()
    {
        $js = '<script type="text/javascript"> window.tableColumns = {';
        foreach ($this->columns as $column) {
            $object = $column->getJSObject();
            if ( empty( $object ) ) {
                continue;
            }
            $json = json_encode( $object );
            $js   .= "'{$column->getKey()}' : '$json',";
        }
        $js .= '};</script>';

        echo $js;
    }

    /**
     *
     *
     * @param string $key
     * @return columns\IColumn
     *
     */
    public function getColumnByKey($key)
    {
        foreach ($this->columns as $column) {
            if ( $column->getKey() === $key ) {
                return $column;
            }
        }
        return null;
    }

    /**
     *
     * @return columns\IColumn[]
     *
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     *
     *
     * @param columns\IColumn $column
     * @param mixed $position Optional. 'begin', 'end' or number. 'end' by default.
     *
     * @return bool
     */
    public function addColumn($column, $position = 'end')
    {
        if ( $this->getColumnByKey( $column->getKey() ) ) {
            return false;
        }
        if ( is_numeric( $position ) ) {
            $length = count( $this->columns );
            if ( $position >= $length ) {
                $position = 'end';
            } elseif ( $position <= 0 ) {
                $position = 'begin';
            } else {
                $columns   = array_slice( $this->columns, 0, $position );
                $columns[] = $column;
                array_push( $columns, array_slice( $this->columns, $position ) );
                $this->columns = $columns;
            }
        }
        if ( 'begin' === $position ) {
            array_unshift( $this->columns, $column );
        }
        if ( 'end' === $position ) {
            $this->columns[] = $column;
        }

        return true;

    }

}
