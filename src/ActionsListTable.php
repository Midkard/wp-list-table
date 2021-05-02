<?php

namespace midkard\wp_list_table;

/**
 * Actions List Table API.
 *
 * @noinspection PhpUnused
 */
abstract class ActionsListTable extends ListTable
{


    /**
     * User rights for using the table
     *
     * @var string
     */
    protected $all_action_rights = 'administrator';

    /**
     * User rights for using the table
     *
     * @var string
     */
    protected $grouped_action_rights = 'administrator';

    /**
     *
     * @var columns\IColumnActions[]
     */
    private $action_columns;

    public function init()
    {
        parent::init();

        $groupedActions = [];
        foreach ($this->table->getColumns() as $column) {
            if ( $column instanceof columns\IColumnActions ) {
                array_push( $groupedActions, $column->getGroupedActions() );
            }
        }
        if ( !empty( $groupedActions ) ) {
            $this->table->addColumn( new columns\CB(), 'begin' );
        }

        $this->makeActions();
    }


    protected function getCurrentUrl()
    {
        return remove_query_arg( ['actionAll', 'actionGrouped', '_wp_http_referer'], parent::getCurrentUrl() );
    }


    /**
     * Display the table
     *
     *
     */
    public function display()
    {
        $this->prepare();

        add_filter( 'the_title', 'esc_html' );

        $this->displayActionsForAll();
        $this->displayGroupedActions();

        $this->displayNavigation();
        $this->table->display( $this->items );
        $this->displayNavigation( 'bottom' );

        $this->enqueueScript();

    }

    /**
     * Display action block.
     *
     */
    protected function displayActionsForAll()
    {
        $current_url = $this->getCurrentUrl();
        $request     = @parse_url( $current_url );
        wp_parse_str( $request['query'], $query_args );

        echo '<form id="actions_form" method="POST">';
        wp_nonce_field( 'table_action_all', 'table_action_all_nonce' );
        foreach ($query_args as $key => $value) {
            echo '<input type="hidden" name="' . $key . '" value="' . $value . '">';
        }

        ob_start();
        $this->displaySelectForAll();
        $output = ob_get_clean();

        if ( !empty( $output ) ) {
            echo '<div class="alignleft actions">';
            echo $output;
            submit_button( 'Применить', 'primary', 'submit_action', false );
            echo '</div>';
        }

        echo '</form>';

    }

    /**
     * Display actions for each element in database
     *
     * @return void
     */
    protected function displaySelectForAll()
    {
        $actions = [];
        foreach ($this->getActionColumns() as $column) {
            $columnActions = $column->getActionsForAll();
            if ( !empty( $columnActions ) ) {
                $actions = array_merge( $actions, $columnActions );
            }
        }
        if ( empty( $actions ) ) {
            return;
        }

        echo '<select name="actionAll">';
        echo '<option value="none">Применить ко всем</option>';
        foreach ($actions as $action) {
            echo '<option value="' . $action['value'] . '">' . $action['display'] . '</option>';
        }
        echo '</select>';
    }

    /**
     * Display action block.
     *
     */
    protected function displayGroupedActions()
    {
        $current_url = $this->getCurrentUrl();
        $request     = @parse_url( $current_url );
        wp_parse_str( $request['query'], $query_args );

        echo '<form id="actions_gouped_form" method="POST">';
        wp_nonce_field( 'table_action_grouped', 'table_action_grouped_nonce' );
        echo '<input type="hidden" name="objects">';
        echo '<input type="hidden" name="value">';
        foreach ($query_args as $key => $value) {
            echo '<input type="hidden" name="' . $key . '" value="' . $value . '">';
        }

        ob_start();
        $this->displaySelectGrouped();
        $output = ob_get_clean();

        if ( !empty( $output ) ) {
            echo '<div class="alignleft actions">';
            echo $output;
            submit_button( 'Применить', 'primary', 'submit_grouped_action', false );
            echo '</div>';
        }

        echo '</form>';

    }

    /**
     * Display actions for selected element
     *
     * @return void
     */
    protected function displaySelectGrouped()
    {
        $actions = [];
        foreach ($this->getActionColumns() as $column) {
            $columnActions = $column->getGroupedActions();
            if ( !empty( $columnActions ) ) {
                $actions = array_merge( $actions, $columnActions );
            }
        }
        if ( empty( $actions ) ) {
            return;
        }

        echo '<select name="actionGrouped">';
        echo '<option value="none">Применить к выбранным</option>';
        foreach ($actions as $action) {
            echo "<option value='{$action['value']}' data-need-value='{$action['need_value']}'>{$action['display']}</option>";
        }
        echo '</select>';
    }

    /**
     * Make actions
     *
     * @return void
     */
    protected function makeActions()
    {
        if ( isset( $_REQUEST['actionAll'] ) ) {
            $this->makeAllActions();
        }
        if ( isset( $_REQUEST['actionGrouped'] ) ) {
            $this->makeGroupedActions();
        }
    }

    /**
     * Make actions
     *
     * @return void|bool
     */
    protected function makeAllActions()
    {
        if ( !check_admin_referer( 'table_action_all', 'table_action_all_nonce' ) ) {
            return;
        }
        if ( !current_user_can( $this->all_action_rights ) || 'none' === $_REQUEST['actionAll'] ) {
            return;
        }

        foreach ($this->getActionColumns() as $column) {
            if ( $column->isAvailableActionForAll( $_REQUEST['actionAll'] ) ) {
                return $column->makeActionForAll( $_REQUEST['actionAll'] );
            }
        }

    }

    /**
     * Make actions
     *
     * @return void|bool
     */
    protected function makeGroupedActions()
    {
        if ( !check_admin_referer( 'table_action_grouped', 'table_action_grouped_nonce' ) ) {
            return;
        }
        if ( !current_user_can( $this->grouped_action_rights ) || 'none' === $_REQUEST['actionGrouped'] ) {
            return;
        }

        $active_column = false;
        foreach ($this->getActionColumns() as $column) {
            if ( $column->isAvailableGroupedAction( $_POST['actionGrouped'] ) ) {
                $active_column = $column;
                break;
            }
        }

        if ( empty( $active_column ) || empty( $_POST['objects'] ) ) {
            return;
        }
        $objects = json_decode( wp_unslash( $_POST['objects'] ), true );
        $value   = $_POST['value'];

        foreach ($objects as $object) {
            $item = $this->getObject( $object['type'], $object['id'] );
            if ( !$item ) {
                continue;
            }
            $active_column->makeGroupedAction( $item, $_POST['actionGrouped'], $value );
        }

    }

    /**
     *
     * @return columns\IColumnActions[]
     */
    protected function getActionColumns()
    {
        if ( !isset( $this->action_columns ) ) {
            $this->action_columns = [];
            foreach ($this->table->getColumns() as $column) {
                if ( $column instanceof columns\IColumnActions ) {
                    $this->action_columns[] = $column;
                }

            }
        }

        return $this->action_columns;
    }

    protected function enqueueScript()
    {
        parent::enqueueScript();
        require_once dirname( __DIR__ ) . '/assets/script_actions.php';
    }

}
