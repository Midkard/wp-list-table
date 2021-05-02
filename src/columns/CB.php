<?php

namespace midkard\wp_list_table\columns;

/**
 * Field for group checkboxes
 *
 * @author Midkard
 */
class CB implements IColumn
{

    /** @var string */
    private $key = 'CB';

    /** @var bool */
    protected $hidden = false;

    /**
     * Return html for cell
     *
     * @param object $item
     */
    public function displayCell($item)
    {

        echo "<th scope='row' class='check-column'>";
        echo '<input type="checkbox">';
        echo "</th>";
    }

    /**
     * Html of cell for table thead
     *
     * @return void
     */
    public function displayHeader()
    {
        echo '<td id="cb" class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-1">Выделить все</label><input id="cb-select-all-1" type="checkbox"></td>';
    }

    /**
     * Return some properties of column for js
     *
     * @return array
     */
    public function getJSObject()
    {
        return [];
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
     * @return bool
     */
    public function isHidden()
    {
        return $this->hidden;
    }


    /**
     * Update value of field and return new value
     *
     * @param object $object
     * @param string $value
     *
     * @return string new html value
     */
    public function updateValue($object, $value)
    {
        return false;
    }
}
