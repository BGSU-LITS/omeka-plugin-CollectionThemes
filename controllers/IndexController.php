<?php
/**
 * Omeka Collection Themes Plugin: Index Controller
 *
 * @author John Kloor <kloor@bgsu.edu>
 * @copyright 2015 Bowling Green State University Libraries
 * @license MIT
 */

/**
 * Omeka Collection Themes Plugin: Index Controller Class
 *
 * @package CollectionThemes
 */
class CollectionThemes_IndexController extends Zend_Controller_Action
{
    /**
     * Initialize the CollectionTheme model for this controller.
     */
    public function init()
    {
        $this->_helper->db->setDefaultModelName('CollectionTheme');
    }

    /**
     * Action to configure a theme for a collection.
     */
    public function configAction()
    {
        // Get the model by ID, and the theme for that model.
        $model = $this->_helper->db->findById();
        $themeName = $model->theme;

        // A theme name must be specified to be configurable.
        if (!$themeName) {
            // Notify user.
            $this->_helper->flashMessenger(
                __(
                    'You must specifically select a theme in order to'.
                    ' configure it.'
                ),
                'error'
            );

            // Redirect to editing the collection.
            $this->_helper->redirector->gotoUrl(
                '/collections/edit/'. $model->id
            );

            return;
        }

        // Get the options previously specified for the theme.
        $themeOptions = $model->getThemeOptions();

        // Create a new form to configure the theme.
        $form = new Omeka_Form_ThemeConfiguration(array(
            'themeName' => $themeName,
            'themeOptions' => $themeOptions
        ));

        $form->removeDecorator('Form');

        // Get the theme and the configuration file for the theme.
        $theme = Theme::getTheme($themeName);
        $themeConfigIni = $theme->path. DIRECTORY_SEPARATOR. 'config.ini';

        if (file_exists($themeConfigIni) && is_readable($themeConfigIni)) {
            // Determine which fields, if any, should not be displayed.
            $excludeFields = array();

            try {
                $plugins = new Zend_Config_Ini($themeConfigIni, 'plugins');
                $excludeFields = explode(',', $plugins->exclude_fields);
            } catch (Exception $e) {
            }

            // Remove any excluded fields from the form.
            foreach ($excludeFields as $excludeField) {
                $form->removeElement(trim($excludeField));
            }
        }

        // If the user posted the form, attempt to store the options.
        if ($this->getRequest()->isPost()) {
            // Create a new helper for theme options, and process the form.
            $helper = new Omeka_Controller_Action_Helper_ThemeConfiguration;
            $newOptions = $helper->processForm($form, $_POST, $themeOptions);

            if ($newOptions) {
                // If the options are valid, save them to the model.
                $model->setThemeOptions($newOptions);
                $model->save();

                // Notify user, and redirect to collection.
                $this->_helper->_flashMessenger(
                    __('The theme settings were successfully saved!'),
                    'success'
                );

                $this->_helper->redirector->gotoUrl(
                    '/collections/show/'. $model->id
                );
            } else {
                // Notify user there was an error.
                $this->_helper->_flashMessenger(
                    __('There was an error on the form. Please try again.'),
                    'error'
                );
            }
        }

        // Provide model, form and theme to the view.
        $this->view->assign(compact('model', 'form', 'theme'));
    }
}
