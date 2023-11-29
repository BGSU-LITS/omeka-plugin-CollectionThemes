<?php
/**
 * Omeka Collection Themes Plugin: Collection Theme Record
 *
 * @author John Kloor <kloor@bgsu.edu>
 * @copyright 2015 Bowling Green State University Libraries
 * @license MIT
 */

/**
 * Omeka Collection Themes Plugin: Collection Theme Record Class
 *
 * @package CollectionThemes
 */
class CollectionTheme extends Omeka_Record_AbstractRecord
{
    /**
     * @var string Folder name of the theme for the collection.
     */
    public $theme;

    /**
     * @var string Themes options for the collection.
     */
    public $theme_options;

    /**
     * Set the theme options.
     *
     * @param array $themeOptions Options for the theme.
     * @param string|null $themeName Which theme the options are for.
     */
    public function setThemeOptions($themeOptions, $themeName = null)
    {
        // Unless a theme is specified, get the name of the theme.
        if ($themeName === null) {
            $themeName = $this->theme;
        }

        // If a theme name is specified, add the options for that
        // theme to the array of all theme options.
        if ($themeName) {
            $themeOptionsArray = unserialize($this->theme_options);
            $themeOptionsArray[$themeName] = $themeOptions;
        }

        // Store all of the theme options.
        $this->theme_options = serialize($themeOptionsArray);
    }

    /**
     * Get the options for a specific theme.
     *
     * @param string|null $themeName Which theme to get options for.
     * @return array Options for the theme.
     */
    public function getThemeOptions($themeName = null)
    {
        // Unless a theme is specified, get the name of the theme.
        if ($themeName === null) {
            $themeName = $this->theme;
        }

        // If a theme and options for that theme are available, return them.
        if ($themeName && is_string($this->theme_options)) {
            $themeOptionsArray = unserialize($this->theme_options);

            if (is_array($themeOptionsArray)) {
                if (!empty($themeOptionsArray[$themeName])) {
                    return $themeOptionsArray[$themeName];
                }
            }
        }

        // Otherwise return an empty array.
        return array();
    }
}
