jQuery(document).ready(function ($) {
    $('.generation-action button').click(function () {
        var result = '[myfxbook ';

        $('.attr-field').each(function () {
            var vl = $(this).val();
            if (vl.length) {
                var nm = $(this).attr('name');
                switch (nm) {
                    case 'width':
                    case 'height':
                    case 'fontsize':
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

});
