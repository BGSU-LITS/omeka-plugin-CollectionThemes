<?php
$pageTitle = __('Configure Theme for Collection #%s', $model->id);
echo head(array('title' => $pageTitle, 'bodyclass' => 'collections'));
echo js_tag('themes');
echo flash();
?>

<form method="post" enctype="multipart/form-data">
    <section class="seven columns alpha">
        <h2><?php echo __('Configure the &#8220;%s&#8221; Theme', html_escape($theme->title)); ?></h2>
        <p><?php echo __('Configurations apply to this theme only.'); ?></p>
        <?php echo $form; ?>
    </section>
    <section class="three columns omega">
        <div id="save" class="panel">
            <?php echo $this->formSubmit('submit', __('Save Changes'), array('class' => 'submit big green button')); ?>
        </div>
    </section>
</form>

<?php echo foot(); ?>
