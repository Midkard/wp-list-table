<?php

namespace midkard\wp_list_table\columns;


/**
 * IColumnActions
 *
 * @author Midkard
 */
interface IColumnActions
{


    /**
     * Returns an array of ['display'=>'string to display', 'value' => 'action_value']
     * or nothing
     *
     * @return array|null
     */
    public function getActionsForAll();

    /**
     * Is can make $action
     *
     * @param string $action
     * @return boolean
     */
    public function isAvailableActionForAll($action);

    /**
     * Make something depend on $action
     *
     * @param string $action
     * @return boolean
     */
    public function makeActionForAll($action);

    /**
     * Returns an array of ['display'=>'string to display', 'value' => 'action_value', 'need_value' => true]
     * or nothing
     *
     * @return array|null
     */
    public function getGroupedActions();

    /**
     * Is can make $action
     *
     * @param string $action
     * @return boolean
     */
    public function isAvailableGroupedAction($action);

    /**
     * Make something depend on $action
     *
     * @param object $object
     * @param string $action
     * @param mixed $value
     * @return string|null
     */
    public function makeGroupedAction($object, $action, $value);

}
