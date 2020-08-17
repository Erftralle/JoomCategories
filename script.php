<?php
/**
* Module JoomCategories for JoomGallery
* by JoomGallery::Project Team
* @package JoomGallery
* @copyright JoomGallery::Project Team
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*
* This program is free software; you can redistribute it and/or modify it under
* the terms of the GNU General Public License as published by the Free Software
* Foundation, either version 2 of the License, or (at your option) any later
* version.
*
* This program is distributed in the hope that it will be useful, but WITHOUT
* ANY WARRANTY, without even the implied warranty of MERCHANTABILITY or FITNESS
* FOR A PARTICULAR PURPOSE.
* See the GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License along with
* this program; if not, write to the Free Software Foundation, Inc.,
* 51 Franklin St, Fifth Floor, Boston, MA 02110, USA
*/

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

/**
 * Installer srcipt file for module JoomCategories called by the installer of Joomla!
 *
 * @since   3.1.0
 */
class mod_joomcatInstallerScript
{
  /**
   * The JoomCategories version we are updating from
   *
   * @var    string
   * @since  3.1.0
   */
  protected $fromVersion = null;

  /**
   * Function to clean a CSV list
   *
   * @param   string  $csv_list  CSV list
   * @return  string  Cleaned CSV list
   * @since   3.1.0
   */
  protected function cleanCSV($csv_list)
  {
    $search[0]  = '/[^0-9,]/m';
    $search[1]  = '/,{2,}/m';
    $search[2]  = '/,+$/m';
    $search[3]  = '/^,+/m';
    $replace[0] = ',';
    $replace[1] = ',';
    $replace[2] = '';
    $replace[3] = '';

    $csv_list = preg_replace($search, $replace, trim($csv_list));

    return $csv_list;
  }

  /**
   * Get a variable from the manifest file (actually, from the manifest cache).
   *
   * @param   string  $name  Name of the variable
   * @return  mixed   Value of the variable
   * @since   3.1.0
   */
  protected function getParam($name)
  {
    $db    = JFactory::getDbo();
    $query = $db->getQuery(true);

    $query->select($db->quoteName('manifest_cache'))
          ->from($db->quoteName('#__extensions'))
          ->where($db->quoteName('element') . ' = ' . $db->quote('mod_joomcat'));
    $db->setQuery($query);

    $manifest = json_decode($db->loadResult(), true);

    return $manifest[$name];
  }

  /**
   * Function to act prior to installation process begins
   *
   * @param   string      $action     Which action is happening (install|uninstall|discover_install|update)
   * @param   JInstaller  $installer  The class calling this method
   * @return  boolean     True on success
   * @since   3.1.0
   */
  public function preflight($action, $installer)
  {
    if($action === 'update')
    {
      // Get the current module version we are updating from
      if(!empty($this->getParam('version')))
      {
        $this->fromVersion = $this->getParam('version');

        return true;
      }

      return false;
    }

    return true;
  }

  /**
   * Called after any type of action
   *
   * @param   string      $action     Which action is happening (install|uninstall|discover_install|update)
   * @param   JInstaller  $installer  The class calling this method
   * @return  boolean     True on success
   * @since   3.1.0
   */
  public function postflight($action, $installer)
  {
    if($action === 'update')
    {
      if(!empty($this->fromVersion) && version_compare($this->fromVersion, '3.0.3', 'lt'))
      {
        // Get all JoomCategories modules
        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select($db->quoteName('id'))
              ->from($db->quoteName('#__modules'))
              ->where($db->quoteName('module') . ' = ' . $db->quote('mod_joomcat'));
        $db->setQuery($query);

        if(!empty($rows = $db->loadObjectList()))
        {
          $table = JTable::getInstance('module');

          // Convert the parameter 'cfg_blacklist_cats' from CSV list to an array
          foreach($rows as $row)
          {
            $table->load($row->id);
            $params = new JRegistry;
            $params->loadString($table->params, 'JSON');

            $cfg_blacklist_cats = $params->get('cfg_blacklist_cats');

            if(!empty($cfg_blacklist_cats) && is_string($cfg_blacklist_cats))
            {
              $params->set('cfg_blacklist_cats', explode(',', $this->cleanCSV($cfg_blacklist_cats)));
              $table->bind(array('params' => $params->toString()));

              if(!$table->check())
              {
                return false;
              }

              if(!$table->store())
              {
                return false;
              }
            }
          }
        }
      }
    }

    return true;
  }
}