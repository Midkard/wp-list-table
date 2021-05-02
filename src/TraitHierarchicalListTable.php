<?php

namespace midkard\wp_list_table;

use WP_Post;

/**
 * Для работы у объектов должно быть поле post_parent и ID.
 * Добавляет объектам level. Пагинацию осуществляет самостоятельно.
 *
 * @author Midkard
 */
trait TraitHierarchicalListTable
{

    /**
     *  Adds level to hierarchical items
     */
    protected function prepareHierarchicalItems()
    {
        //hierarchical
        $items = $this->getHierarchicalItems();
        if ( empty( $items ) ) {
            return;
        }
        $this->pagination_args['total_items'] = count( $items );

        $start = ($this->getPagenum() - 1) * $this->per_page;
        $items = array_slice( $items, $start, $this->per_page );


        $item       = $items[0];
        $my_parents = array();
        while ($parent = $this->getParent( $item )) {
            $my_parents[] = $parent;

            $item = $parent;
        }
        $items = array_merge( array_reverse( $my_parents ), $items );

        $parents = [];
        //level
        foreach ($items as $item) {
            if ( !$this->getParentId( $item ) ) {
                $item->level = 0;
                $parents     = [];
                continue;
            }
            $level = array_search( $this->getParentId( $item ), $parents );
            if ( false === $level ) {
                $parents[] = $this->getParentId( $item );
                $level     = count( $parents );
            } else {
                $level++;
            }
            if ( $level < count( $parents ) ) {
                $parents = array_slice( $parents, 0, $level );
            }
            $item->level = $level;
        }

        $this->items = $items;

    }

    /**
     *
     * @return array of objects
     */
    protected function getHierarchicalItems()
    {
        $args                   = $this->query_vars;
        $args['posts_per_page'] = -1;
        $pages                  = get_posts( $args );

        // Build a hash of ID -> children.
        $children = array();
        foreach ((array)$pages as $page) {
            $children[intval( $page->post_parent )][] = $page;
        }

        $page_list = array();

        // Start the search by looking at immediate children.
        if ( isset( $children[0] ) ) {
            // Always start at the end of the stack in order to preserve original `$pages` order.
            $to_look = array_reverse( $children[0] );
            unset( $children[0] );

            while ($to_look) {
                $p           = array_pop( $to_look );
                $page_list[] = $p;
                if ( isset( $children[$p->ID] ) ) {
                    foreach (array_reverse( $children[$p->ID] ) as $child) {
                        // Append to the `$to_look` stack to descend the tree.
                        $to_look[] = $child;
                    }
                    unset( $children[$p->ID] );
                }
            }
        }

        //Orphan pages
        foreach ($children as $orphans) {
            foreach ($orphans as $op) {
                $page_list[] = $op;
            }
        }

        return $page_list;
    }

    /**
     * Return parent object, if exists
     *
     * @param WP_Post $item
     * @return WP_Post|null
     */
    protected function getParent($item)
    {
        $parent_id = $item->post_parent;
        if ( !$parent_id ) {
            return null;
        }
        $my_parent = get_post( $parent_id );
        if ( !is_object( $my_parent ) ) {
            return null;
        }
        return $my_parent;
    }

    /**
     * Return parent object id
     *
     * @param WP_Post $item
     * @return int
     */
    protected function getParentId($item)
    {
        return $item->post_parent;
    }

}
