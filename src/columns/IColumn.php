<?php

namespace midkard\wp_list_table\columns;

/**
 * Interface of Column
 *
 * @author Midkard
 */
interface IColumn
{

    /**
     * Return html for cell
     *
     * @param object $item
     */
    public function displayCell($item);

    /**
     * Html of cell for table thead
     *
     * @return void
     */
    public function displayHeader();

    /**
     * Return some properties of column for js
     *
     * @return array
     */
    public function getJSObject();

    /**
     *
     * @return string
     */
    public function getKey();

    /**
     * @return bool
     */
    public function isHidden();

    /**
     * Update value of field and return new value
     *
     * @param object $object
     * @param string $value
     *
     * @return string new html value
     */
    public function updateValue($object, $value);
}
