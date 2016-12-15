jQuery(document).ready(function ($) {
    $('.generation-action button').click(function () {
        var result = '[myfxbook ';

        $('.attr-field').each(function () {
            var vl = $(this).val();
            if (vl.length && !$(this).closest('p.grope').is(':hidden')) {
                var nm = $(this).attr('name');
                switch (nm) {
                    case 'width':
                    case 'height':
                        vl = parseInt(vl, 10);
                        vl = isNaN(vl) ? "" : vl;
                        break;
                    case 'bgcolor':
                    case 'gridcolor':
                        vl = '#' + vl.replace("#", "");
                }
                result += nm + '="' + vl + '" ';
            }
        });

        result += '] Replace this text or remove It [/myfxbook]';
        $('.generation-result textarea').val(result);
    });

    $('#filter').click(function () {
        $(this).val(null);

        if ($(this).is(':checked')) {
            $(this).val(1);
        }
    });

    $('#type-list').change(function () {
        $('p.grope').hide();
        if ($(this).val() == 'get-calculator-form') {
            $('p.grope.calculate').show();
        } else {
            $('p.grope.graph').show();
        }
    });

    $('p.grope.calculate').hide();
});
