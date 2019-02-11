<?php
/**
 * Omeka Collection Themes Plugin
 *
 * @author John Kloor <kloor@bgsu.edu>
 * @copyright 2015 Bowling Green State University Libraries
 * @license MIT
 */

/**
 * Omeka Collection Themes Plugin: Plugin Class
 *
 * @package CollectionThemes
 */
class CollectionThemesPlugin extends Omeka_Plugin_AbstractPlugin
{
    /**
     * @var array Plugin hooks.
     */
    protected $_hooks = array(
        'install',
        'uninstall',
        'admin_collections_panel_fields',
        'admin_collections_show_sidebar',
        'after_save_collection'
    );

    /**
     * @var array Plugin filters.
     */
    protected $_filters = array(
        'public_theme_name',
        'theme_options'
    );

    /**
     * Hook to plugin installation.
     *
     * Creates table for the CollectionTheme record.
     */
    public function hookInstall()
    {
        $db = $this->_db;
        $db->query(
            "CREATE TABLE IF NOT EXISTS `{$db->CollectionTheme}` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `theme` varchar(30),
                `theme_options` text,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"
        );
    }

    /**
     * Hook to plugin uninstallation.
     *
     * Drops table for the CollectionTheme record.
     */
    public function hookUninstall()
    {
        $db = $this->_db;
        $db->query(
            "DROP TABLE IF EXISTS `{$db->CollectionTheme}`;"
        );
    }

    /**
     * Hook to panel fields for editing a collection in the admin interface.
     *
     * @param array $args Provides record and view.
     */
    public function hookAdminCollectionsPanelFields($args)
    {
        $themes = $this->getThemes();
        $record = $args['record'];
        $view = $args['view'];

        // Determine theme for the collection record.
        $model = $this->_db->getTable('CollectionTheme')->find($record->id);
        $theme = $model ? $model->theme : '';

        include 'views/admin/panel_fields.php';
    }

    /**
     * Hook to the sidebar when showing a collection in the admin interface.
     *
     * @param array $args Provides collection.
     */
    public function hookAdminCollectionsShowSidebar($args)
    {
        $themes = $this->getThemes();
        $record = $args['collection'];
        $view = $args['view'];

        // Determine theme for the collection record.
        $model = $this->_db->getTable('CollectionTheme')->find($record->id);
        $theme = $model ? $model->theme : '';

        include 'views/admin/show_sidebar.php';
    }

    /**
     * Hook to save the theme for a collection after the collection is saved.
     *
     * @param array $args Provides record and post.
     */
    public function hookAfterSaveCollection($args)
    {
        // Look to see if there is already a theme assigned to the collection.
        $record = $args['record'];
        $model = $this->_db->getTable('CollectionTheme')->find($record->id);

        // If not, create a new theme model for the collection.
        if (empty($model)) {
            $model = new CollectionTheme;
            $model->id = $record->id;
        }

        // Default the model to having no specific theme.
        $model->theme = '';

        // If a theme was specified, use that for the collection.
        if (!empty($args['post']['theme'])) {
            $model->theme = $args['post']['theme'];
        }

        $model->save();

        // If the configure button was used to submit the form,
        // redirect to theme configuration form.
        if (!empty($args['post']['configure-theme'])) {
            $redirector = Zend_Controller_Action_HelperBroker::
                getStaticHelper('Redirector');

            $redirector->gotoSimple(
                'config',
                'index',
                'collection-themes',
                array('id' => $record->id)
            );
        }
    }

    /**
     * Filter the public theme to use the theme for the collection.
     *
     * @param string $themeName Name of the default public theme.
     * @return string Name of the public theme to use.
     */
    public function filterPublicThemeName($themeName)
    {
        // Use the theme for the collection if available.
        $model = $this->getModel();

        if ($model && $model->theme) {
            $themeName = $model->theme;
        }

        return $themeName;
    }

    /**
     * Filter the public theme options to use options for the collection.
     *
     * @param string $themeOptions Default options for the public theme.
     * @param array $args Unused.
     * @return string Options to be used for the theme.
     */
    public function filterThemeOptions($themeOptions, $args)
    {
        // Use the theme for the collection if available.
        $model = $this->getModel();

        if ($model && $model->theme) {
            // Get options for the theme, and return in correct format.
            $modelThemeOptions = $model->getThemeOptions();

            if (!empty($modelThemeOptions)) {
                return serialize($modelThemeOptions);
            }
        }

        // Provide default options if necessary.
        return $themeOptions;
    }

    /**
     * Get the CollectionTheme model for the collection being viewed.
     *
     * @return CollectionTheme|null Model for the collection being viewed.
     */
    private function getModel()
    {
        // Determine the controller and action for the request.
        $request = Zend_Controller_Front::getInstance()->getRequest();
        $controller = $request->getControllerName();
        $action = $request->getActionName();

        // Other records to theme.
        $other = array('items', 'files', 'exhibits', 'page', 'results');

        if ($controller == 'collections' && $action == 'show') {
            // When showing a collection, use the ID.
            $id = $request->getParam('id');
        } elseif (in_array($controller, $other)) {
            // For non-collection controllers, the ID can be a get parameter.
            $id = $request->getParam('collection');

            // Or, the collection ID can come from the files or item shown.
            if (empty($id) && $action == 'show') {
                // Get item ID.
                $item_id = $request->getParam('id');

                if ($controller == 'files') {
                    $file = get_db()->getTable('File')->find($item_id);
                    $item_id = $file->item_id;
                }

                // Get item by that ID.
                $item = get_db()->getTable('Item')->find($item_id);

                if (!empty($item)) {
                    // Get collection ID from the item.
                    $id = $item->collection_id;
                }
            }
        }

        // If no collection ID is available, return null.
        if (empty($id)) {
            return null;
        }

        // Return the CollectionTheme model if found.
        return get_db()->getTable('CollectionTheme')->find($id);
    }

    /**
     * Get a list of possible themes the user may select among.
     *
     * @return array List of themes titles keyed by the theme name.
     */
    private function getThemes()
    {
        // Get list of all themes.
        $themes = apply_filters('browse_themes', Theme::getAllThemes());

        // Change value from object to the title of the theme.
        foreach ($themes as $dir => $theme) {
            $title = empty($theme->title) ? $dir : $theme->title;
            $themes[$dir] = $title;
        }

        // Return themes with default first option of the Current Public Theme.
        return array('' => __('Current Public Theme')) + $themes;
    }
}
