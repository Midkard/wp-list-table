<?php

namespace midkard\wp_list_table;

use WP_Query;

/**
 * List Table API
 *
 */
abstract class ListTable
{

    use TraitHierarchicalListTable;

    /**
     * Hold Table.
     *
     * @var Table
     */
    protected $table;

    /**
     * The current list of items.
     *
     * @var array
     */
    protected $items;

    /**
     * Parameters for displaing pagination.
     *
     * @var array
     */
    protected $pagination_args;

    /**
     * Page number to display
     *
     * @var int
     */
    private $page_num;

    /**
     * Holds the number of posts per page.
     *
     * @var int
     */
    protected $per_page;

    /**
     * Holds allowed query vars.
     *
     * @var array
     */
    protected $query_allowed = array('post_type', 'order', 'orderby', 'meta_key', 'paged', 's');

    /**
     * Query variables for setting up the WordPress Query Loop.
     *
     * @var array
     */
    protected $query_vars;

    /**
     * Whether the items should be displayed hierarchically or linearly.
     *
     * @var bool
     */
    protected $hierarchical_display = false;

    /**
     * User rights for edit
     *
     * @var string
     */
    protected $edit_rights = 'edit_post';

    /**
     * Unique table name
     *
     * @var string
     */
    protected $table_name;

    /**
     *
     * @param string $name
     *
     * @noinspection PhpUnusedParameterInspection
     */
    public function __construct($name)
    {

        $this->table_name = $name;

        add_action( 'wp_ajax_quick_edit_' . $name, [$this, 'ajaxQuickEdit'] );

        // Сохранение опции экрана per_page. Нужно вызывать до события 'admin_menu'
        global $wp_version;
        if ( version_compare( $wp_version, '5.5.0', '<' ) ) {
            add_filter( 'set-screen-option', function ($status, $option, $value) use ($name) {
                return ($option == $name . '_per_page') ? (int)$value : $status;
            }, 10, 3 );
        }
        add_filter( 'set_screen_option_' . $name . '_per_page', function ($status, $option, $value) {
            return (int)$value;
        }, 10, 3 );


    }

    public function init()
    {
        $this->table = $this->createTable();

        if ( !wp_doing_ajax() ) {
            add_screen_option( 'per_page', array(
                'label'   => 'Показывать на странице',
                'default' => 15,
                'option'  => $this->table_name . '_per_page', // название опции, будет записано в метаполе юзера
            ) );
        }

        $this->per_page = get_user_meta( get_current_user_id(), $this->table_name . '_per_page', true ) ?: 15;

    }

    /**
     *
     * @return Table
     */
    abstract protected function createTable();


    /**
     * Display the table
     *
     *
     */
    public function display()
    {
        $this->prepare();

        add_filter( 'the_title', 'esc_html' );

        $this->displayNavigation();
        $this->table->display( $this->items );
        $this->displayNavigation( 'bottom' );

        $this->enqueueScript();

    }

    protected function prepare()
    {
        $this->fillQueryVars();
        if ( empty( $this->query_vars['s'] ) && $this->hierarchical_display && method_exists( $this, 'prepareHierarchicalItems' ) ) {
            $this->prepareHierarchicalItems();
        } else {
            $this->prepareItems();
        }
        $this->setPaginationArgs();
    }

    /**
     * создает элементы таблицы
     *
     * @global WP_Query $wp_the_query
     */
    protected function prepareItems()
    {
        global $wp_the_query;

        $this->items                          = $wp_the_query->query( $this->query_vars );
        $this->pagination_args['total_items'] = $wp_the_query->found_posts;

    }

    /**
     *
     * @global WP_Query $wp_the_query
     */
    protected function setPaginationArgs()
    {
        if ( empty( $this->pagination_args ) ) {
            return;
        }
        $posts_count = $this->pagination_args['total_items'];

        $pages_count                          = ceil( $posts_count / $this->per_page );
        $this->pagination_args['total_pages'] = $pages_count;

        // Redirect if page number is invalid and headers are not already sent.
        if ( !headers_sent() && !wp_doing_ajax() && $pages_count > 0 && $this->getPagenum() > $pages_count ) {
            wp_redirect( add_query_arg( 'paged', $pages_count ) );
            exit;
        }
    }

    /**
     * Display the navigation block.
     *
     * @param string $which
     *
     * @global string $typenow
     * @global string $plugin_page
     */
    protected function displayNavigation($which = 'top')
    {
        global $plugin_page, $typenow;

        echo '<form method="GET">';
        if ( !empty( $plugin_page ) ) {
            echo '<input type="hidden" name="page" value="' . $plugin_page . '">';
        }
        if ( !empty( $typenow ) ) {
            echo '<input type="hidden" name="post_type" value="' . $typenow . '">';
        }


        echo "<div class='tablenav'>";
        ob_start();
        $this->displayExtraNavigation( $which );
        $output = ob_get_clean();
        if ( !empty( $output ) ) {
            echo '<div class="alignleft actions">';
            echo $output;
            submit_button( __( 'Filter' ), 'primary', 'submit', false );
            echo '</div>';
        }

        $this->displayPagination( $which );
        if ( 'top' === $which ) {
            echo "<div class='alignright'>";
            $this->displaySearchBox();
            echo '</div>';
        }

        echo '</div>';

        echo '</form>';

    }


    /**
     * Display search input
     *
     */
    protected function displaySearchBox()
    {
        ?>
        <p class="search-box">
            <label class="screen-reader-text" for="post-search-input">Найти:</label>
            <input type="search" id="post-search-input" name="s"
                   value="<?= empty( $this->query_vars['s'] ) ? '' : $this->query_vars['s']; ?>">
            <input type="submit" id="search-submit" class="button" value="Найти">
        </p>
        <?php

    }


    /**
     * Display additional table navigation
     *
     * @param string $which
     */
    protected function displayExtraNavigation($which)
    {

    }


    /**
     * Display the pagination.
     *
     * @param string $which
     */
    protected function displayPagination($which = 'top')
    {
        if ( empty( $this->pagination_args ) ) {
            return;
        }

        $total_items     = $this->pagination_args['total_items'];
        $total_pages     = $this->pagination_args['total_pages'];
        $infinite_scroll = false;
        if ( isset( $this->pagination_args['infinite_scroll'] ) ) {
            $infinite_scroll = $this->pagination_args['infinite_scroll'];
        }

        $output = '<span class="displaying-num">' . sprintf( _n( '%s item', '%s items', $total_items ), number_format_i18n( $total_items ) ) . '</span>';

        $current     = $this->getPagenum();
        $current_url = $this->getCurrentUrl();

        $page_links = array();

        $total_pages_before = '<span class="paging-input">';
        $total_pages_after  = '</span></span>';

        $disable_first = $disable_last = $disable_prev = $disable_next = false;

        if ( $current == 1 ) {
            $disable_first = true;
            $disable_prev  = true;
        }
        if ( $current == 2 ) {
            $disable_first = true;
        }
        if ( $current == $total_pages ) {
            $disable_last = true;
            $disable_next = true;
        }
        if ( $current == $total_pages - 1 ) {
            $disable_last = true;
        }

        if ( $disable_first ) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
        } else {
            $page_links[] = "<a class='first-page button' href='" . esc_url( remove_query_arg( 'paged', $current_url ) ) . "'><span class='screen-reader-text'>" . __( 'First page' ) . "</span><span aria-hidden='true'>" . '&laquo;' . "</span></a>";
        }

        if ( $disable_prev ) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
        } else {
            $page_links[] = "<a class='prev-page button' href='" . esc_url( add_query_arg( 'paged', max( 1, $current - 1 ), $current_url ) ) . "'><span class='screen-reader-text'>" . __( 'Previous page' ) . "</span><span aria-hidden='true'>" . '&lsaquo;' . "</span></a>";
        }

        if ( 'bottom' === $which ) {
            $html_current_page  = $current;
            $total_pages_before = '<span class="screen-reader-text">' . __( 'Current Page' ) . '</span><span id="table-paging" class="paging-input"><span class="tablenav-paging-text">';
        } else {
            $html_current_page = sprintf( "%s<input class='current-page' id='current-page-selector' type='text' name='paged' value='%s' size='%d' aria-describedby='table-paging' /><span class='tablenav-paging-text'>",
                '<label for="current-page-selector" class="screen-reader-text">' . __( 'Current Page' ) . '</label>',
                $current,
                strlen( $total_pages )
            );
        }
        $html_total_pages = "<span class='total-pages'>" . number_format_i18n( $total_pages ) . "</span>";
        $page_links[]     = $total_pages_before . sprintf( _x( '%1$s of %2$s', 'paging' ), $html_current_page, $html_total_pages ) . $total_pages_after;

        if ( $disable_next ) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
        } else {
            $page_links[] = "<a class='next-page button' href='" . esc_url( add_query_arg( 'paged', min( $total_pages, $current + 1 ), $current_url ) ) . "'><span class='screen-reader-text'>" . __( 'Next page' ) . "</span><span aria-hidden='true'>" . '&rsaquo;' . "</span></a>";
        }

        if ( $disable_last ) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>';
        } else {
            $page_links[] = "<a class='last-page button' href='" . esc_url( add_query_arg( 'paged', $total_pages, $current_url ) ) . "'><span class='screen-reader-text'>" . __( 'Last page' ) . "</span><span aria-hidden='true'>" . '&raquo;' . "</span></a>";
        }

        $pagination_links_class = 'pagination-links';
        if ( !empty( $infinite_scroll ) ) {
            $pagination_links_class .= ' hide-if-js';
        }
        $output .= "\n<span class='$pagination_links_class'>" . join( "\n", $page_links ) . '</span>';

        if ( $total_pages ) {
            $page_class = $total_pages < 2 ? ' one-page' : '';
        } else {
            $page_class = ' no-pages';
        }

        echo "<div class='tablenav-pages{$page_class}'>$output</div>";

    }

    /**
     * Update field via ajax
     *
     *
     * @noinspection PhpUnused
     */
    function ajaxQuickEdit()
    {

        check_ajax_referer( '_quick_edit', 'quickeditnonce' );

        if ( !isset( $_POST['id'] ) ) {
            wp_die( 'No id' );
        }
        if ( !isset( $_POST['type'] ) ) {
            wp_die( 'No type' );
        }
        $type = $_POST['type'];
        $id   = $_POST['id'];

        if ( 'post' === $type || 'page' === $type ) {
            $id = intval( $id );
            if ( !current_user_can( $this->edit_rights, $id ) ) {
                wp_die( __( 'Sorry, you are not allowed to edit this post.' ) );
            }

            if ( $last = wp_check_post_lock( $id ) ) {
                $last_user      = get_userdata( $last );
                $last_user_name = $last_user ? $last_user->display_name : __( 'Someone' );
                printf( $_POST['post_type'] == 'page' ? __( 'Saving is disabled: %s is currently editing this page.' ) : __( 'Saving is disabled: %s is currently editing this post.' ), esc_html( $last_user_name ) );
                wp_die();
            }
        } else {
            if ( !current_user_can( $this->edit_rights, $id ) ) {
                wp_die( __( 'Sorry, you are not allowed to edit this post.' ) );
            }
        }

        $this->init();

        $key    = $_POST['column'];
        $column = $this->table->getColumnByKey( $key );
        if ( !$column || !isset( $_POST['value'] ) ) {
            wp_die( 'Invalid column key' );
        }

        $object = $this->getObject( $type, $id );
        if ( empty( $object ) ) {
            wp_die( 'Unknown object' );
        }
        $new_value = $column->updateValue( $object, $_POST['value'] );
        wp_die( $new_value );
    }

    /**
     * Return object for update
     *
     * @param string $type
     * @param string $id
     *
     * @return object|null
     */
    protected function getObject($type, $id)
    {
        $id = intval( $id );
        if ( 'post' === $type ) {
            $object = get_post( $id );
        } elseif ( 'term' === $type ) {
            $object = get_term( $id );
        }

        return $object ?? null;
    }

    /**
     * Fill the query variables from $_POST and $_GET based on query_allowed.
     *
     */
    protected function fillQueryVars()
    {
        foreach ($this->query_allowed as $wpvar) {
            if ( isset( $_POST[$wpvar] ) ) {
                $this->query_vars[$wpvar] = $_POST[$wpvar];
            } elseif ( isset( $_GET[$wpvar] ) ) {
                $this->query_vars[$wpvar] = $_GET[$wpvar];
            }

            if ( !empty( $this->query_vars[$wpvar] ) ) {
                if ( !is_array( $this->query_vars[$wpvar] ) ) {
                    $this->query_vars[$wpvar] = (string)$this->query_vars[$wpvar];
                } else {
                    foreach ($this->query_vars[$wpvar] as $vkey => $v) {
                        if ( !is_object( $v ) ) {
                            $this->query_vars[$wpvar][$vkey] = (string)$v;
                        }
                    }
                }
            }
        }

        $this->query_vars['post_type']      = $this->query_vars['post_type'] ?? 'post';
        $this->query_vars['posts_per_page'] = $this->per_page;

    }

    /**
     * Return number of current page.
     *
     * @return int
     */
    protected function getPagenum()
    {
        if ( !isset( $this->page_num ) ) {
            $this->page_num = isset( $this->query_vars['paged'] ) ? (int)$this->query_vars['paged'] : 1;
        }

        return $this->page_num;
    }

    /**
     * Return current page.
     *
     * @return string
     */
    protected function getCurrentUrl()
    {
        $removable_query_args = wp_removable_query_args();

        $current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );

        $current_url = remove_query_arg( $removable_query_args, $current_url );

        return remove_query_arg( ['submit', '_wpnonce'], $current_url );
    }

    /**
     * Add scripts and styles needed by plugin.
     */
    protected function enqueueScript()
    {
        wp_enqueue_script( 'tags-suggest' );
        $this->table->printJs();

        require_once dirname( __DIR__ ) . '/assets/script.php';
    }

}
