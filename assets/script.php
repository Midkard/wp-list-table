<style>
    span.placeholder {
        opacity: 0.5;
    }
</style>
<script>

(function ( $ ) {
    let columns = {};
    let editEnabled = false;
    /**
     * Handle the editing of the quick edid fields. Create the required HTML elements and update the changes via AJAX.
     *
     * @param {Element} el 
     * @returns void
     */
    function quickEdit( el ) {
        if (editEnabled) {
            return;
        }
        editEnabled = true;

        const $cell = $( el );
        const buttonsHtml = '<div><button type="button" class="save button button-small">ОК</button> <button type="button" class="cancel button-link">Отмена</button></div>';

        // Save current content to revert to when cancelling.
        let revert_e = $cell.html();
        $cell.find('.placeholder').remove();
        let oldValue = $cell.html();

        //Retrieve column
        let column;
        let columnKey = $cell.data('column');
        if ( columns[columnKey] ) {
            column = columns[columnKey];
        } else if ( tableColumns[columnKey] ) {
            column = JSON.parse(tableColumns[columnKey]);
            columns[columnKey] = column;
        }

        let $valueArea;
        let imageFrame;
        if ( column ) {
            let newHtml, choosed;
            switch (column.type) {
                case 'select':
                case 'radio':
                    newHtml = '<select id="new-value">';
                    for (let key in column.choices) {
                        if (!column.choices.hasOwnProperty(key)) continue;
                        let selected = (column.choices[key] === oldValue) || (key === oldValue) ? 'selected' : '';
                        newHtml += `<option value='${key}' ${selected}>${column.choices[key]}</option>`;
                    }
                    newHtml +='</select>';
                    newHtml += buttonsHtml;
                    $cell.html(newHtml);
                    $valueArea = $cell.children( 'select' );
                    break;

                case 'checkbox':
                    newHtml = '<select id="new-value" multiple style="width: 100%; max-width:200px">';
                    choosed = oldValue.split(',').map( function(el) { return el.trim(); } );
                    for (let key in column.choices) {
                        if (!column.choices.hasOwnProperty(key)) continue;
                        let selected = (~choosed.indexOf(column.choices[key])) || (~choosed.indexOf(key)) ? 'selected' : '';
                        newHtml += `<option value='${key}' ${selected}>${column.choices[key]}</option>`;
                    }
                    newHtml +='</select>';
                    newHtml += buttonsHtml;
                    $cell.html(newHtml);
                    $valueArea = $cell.children( 'select' ); 
                    $valueArea.multipleSelect();
                    break;

                case 'textarea':
                    newHtml = '<textarea style="width: 100%" id="new-value" rows="4"></textarea>';
                    newHtml += buttonsHtml;
                    $cell.html(newHtml);
                    $valueArea = $cell.children( 'textarea' );
                    $valueArea.val(oldValue);
                    break;

                case 'taxonomy':
                    newHtml = '<textarea data-wp-taxonomy="post_tag" style="width: 100%" id="new-value" class="tax_input_post_tag"></textarea>';
                    newHtml += buttonsHtml;
                    $cell.html(newHtml);
                    $valueArea = $cell.children( 'textarea' );
                    $valueArea.val(oldValue).wpTagsSuggest({taxonomy: column.name});
                    break;

                case 'onoff':
                    $valueArea = $cell.find( 'input' );
                    save();
                    return;
                case 'image':
                    if (! imageFrame) {
                        imageFrame = wp.media({
                            title: 'Выберите изображение для СЕО',
                            button: {
                                text: 'Выбрать'
                            },
                            multiple: false,
                        });
                        imageFrame.on('select', function () {
                            let attachment = imageFrame.state().get('selection').first().toJSON();
                            $valueArea.val(attachment.id);
                            save();
                        });
                        imageFrame.on('close', function () {
                            save();
                        });
                    }
                    $valueArea = $cell.children( 'input' );
                    oldValue = $valueArea.val();
                    imageFrame.open();
                    return;

                case 'permalink':                    
                    editPermalink($cell);
                    $cell.find('.edit-slug-buttons').html(buttonsHtml);
                    oldValue = $cell.find( '.editable-post-name-full' ).text();
                    $valueArea = $cell.find( 'input' );
                    break;
                default:
                    break;

            }
        }
        //default
        if (! $valueArea) {
            let newHtml = '<input type="text" id="new-value" value="' + oldValue + '" autocomplete="off" style="width: 100%"/>';
            newHtml += buttonsHtml;
            $cell.html(newHtml);
            $valueArea = $cell.children( 'input' );
        }

        $cell.find( 'input, textarea, select' ).focus();

        // Save changes.
        $cell.find( '.save' ).click( save );
        // Cancel editing.
        $cell.find( '.cancel' ).click( cancel );

        $cell.find( 'input, textarea, select' ).blur( function(e) {
            if ($cell.find( '.cancel, .save' ).is(e.relatedTarget)) {
                return false;
            } else {
                save();
            }
        });

        function cancel() {
            $cell.html( revert_e );
            setTimeout(function(){
                editEnabled = false;
            },200);            
        }

        function save() {

            let newVal = $valueArea.val();
            if (column && 'onoff' === column.type) {
                newVal = $valueArea[0].checked;
            }

            if ( (! column || 'onoff' !== column.type) && newVal === oldValue ) {
                cancel();
                return;
            }
            const object = getRowObject($cell);

            $.post(
                    ajaxurl,
                    {
                        action: 'quick_edit_<?php echo $this->table_name;?>',
                        type: object.type,
                        id: object.id,
                        column: $cell.data('column'),
                        value: newVal,
                        quickeditnonce: '<?php echo wp_create_nonce( '_quick_edit' );?>'
                    },
                    function ( data ) {
                        $cell.html( data );
                        editEnabled = false;
                    }
            );            
        }
    }

    /**
     * Handle the editing of the post_name. Create the required HTML elements and update the changes via AJAX.
     *
     * @summary Permalink aka slug aka post_name editing
     *
     * @returns void
     */
    function editPermalink( $cell ) {

        let full = $cell.find( '.editable-post-name-full' );
        // Deal with Twemoji in the post-name.
        full.find( 'img' ).replaceWith( function () {
            return this.alt;
        } );
        full = full.html();

        let permalink = $cell.find( '.sample-permalink' );
        permalink.html( permalink.find( 'a' ).html() );

        // If more than 1/4th of 'full' is '%', make it empty.
        let c = 0;
        for ( let i = 0; i < full.length; ++i ) {
            if ( '%' === full.charAt( i ) )
                c++;
        }
        let slug_value = (c > full.length / 4) ? '' : full;

        let $el = $cell.find( '.editable-post-name' );        
        $el.html( '<input type="text" id="new-value" value="' + slug_value + '" autocomplete="off" />' );

    }

    $( function () {
        window.tableColumns = window.tableColumns || [];
        $( document ).on( 'click', 'td.quick-edit .edit-slug', function (e) {
            quickEdit( $(this).closest('td')[0] );
            e.stopPropagation();
        } );
        $( document ).on('click', 'td.quick-edit', function () {
            quickEdit( this );
        } );
    } );
})( jQuery );

    /**
     * @summary Gets the id for a the post that you want to quick edit from the row
     * in the quick edit table.
     *
     *
     * @param   {Element} o DOM row object to get the id for.
     * @returns {Object}   The post id extracted from the table row in the object.
     */
    function getRowObject( o ) {

        const id = jQuery(o).closest('tr').attr('id'),
            parts = id.split('#');
        return {
            type: parts[0],
            id: parts[parts.length - 1]
        };
    }
</script>

