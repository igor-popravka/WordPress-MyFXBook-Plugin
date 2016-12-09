<div id="<?= $id; ?>" class="wdip-myfxbook-chart"></div>
<script>
    /* <![CDATA[ */
    if (typeof WDIPMyFxBook == 'undefined') {
        var WDIPMyFxBook = new (function () {
            var options = [];
            return {
                add: function (id, opt) {
                    options.push({
                        id: id,
                        opt: opt
                    });
                },
                each: function (callbak) {
                    for (var i in options) {
                        var o = options[i];
                        callbak(o.id, o.opt);
                    }
                }
            }
        })();
    }
    WDIPMyFxBook.add("<?= $id; ?>", <?= json_encode($options); ?>);
    /* ]]> */
</script>