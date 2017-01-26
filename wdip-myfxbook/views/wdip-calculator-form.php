<div id="<?php echo $code; ?>" class="wdip-calc-wrapper">
    <div class="wdip-result">
        <div>
            <label>Total amount:</label>
            <span class="wdip-field-total" name="wdip_total_amount">$0.00</span>
        </div>
        <div>
            <label>Gain amount:</label>
            <span class="wdip-field-gain" name="wdip_gain_amount">$0.00</span>
        </div>
        <div>
            <label>Fee amount:</label>
            <span class="wdip-field-fee" name="wdip_fee_amount">$0.00</span>
        </div>
    </div>
    <div class="wdip-menu">
        <button class="show-graph">Show graph</button>
    </div>
    <div class="wdip-data">
        <form>
            <div class="wdip-field wdip-data-amount">
                <label>Amount:</label>
                <input type="text" name="amount" >
            </div>
            <div class="wdip-field wdip-data-date">
                <label>Start date:</label>
                <input type="text" name="start">
            </div>
            <div class="wdip-field wdip-data-fee">
                <label>Performance fee:</label>
                <select name="fee">
                    <option value="0.25">25%</option>
                </select>
            </div>
            <div class="wdip-field wdip-data-submit">
                <input type="submit" value="Calculate">
            </div>
        </form>
    </div>
</div>
<script>
    jQuery(document).ready(function ($) {
        $('#<?php echo $code; ?>').FXCalculator({
            fee: [<?php echo $attr['fee']; ?>],
            accID: '<?php echo $attr['id']; ?>',
            url: '<?php echo $admin_url; ?>',
            chart_options: JSON.parse('<?php echo $chart_options; ?>')
        });
    });
</script>