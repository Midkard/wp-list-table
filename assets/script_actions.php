<script>
(function ( $ ) {
    $('#actions_gouped_form').submit(function(e){
        let $select = $(this.elements.actionGrouped);
        if ('none' === $select.val()) {
            return false;
        }
        
        let $rows = $('th.check-column input:checked');
        if (! $rows.length ) {
            return false;
        }
        this.elements.objects.value = JSON.stringify($rows.toArray().map(getRowObject));

        var $option = $select.find(':selected');
        if ($option.data('needValue')) {
            var value = prompt('Введите новое значение', this.elements.value.value);
            if (null === value) {
                return false;
            }
            this.elements.value.value = value;
        }

    });
})( jQuery );
</script>

