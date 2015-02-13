<div class="field">
    <label for="theme">
        <?php echo __('Theme'); ?>
    </label>
    <div class="inputs">
        <?php echo $view->formSelect('theme', $theme, array('id' => 'theme'), $themes); ?>
        <input type="submit" id="configure-theme" name="configure-theme" value="<?php echo __('Configure'); ?>">
    </div>
</div>

<style>
    #configure-theme {
        margin-top: 10px !important;
    }
</style>

<script>
    jQuery(function($) {
        if ($('#theme').val() === '') {
            $('#configure-theme').hide();
        }

        $('#theme').change(function() {
            if ($(this).val() === '') {
                $('#configure-theme').hide();
            } else {
                $('#configure-theme').show();
            }
        });
    });
</script>
