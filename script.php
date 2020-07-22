<?php
/****************************************************************************************\
**   JoomGallery 3                                                                      **
**   By: JoomGallery::ProjectTeam                                                       **
**   Copyright (C) 2020  JoomGallery::ProjectTeam                                       **
**   Based on: JoomGallery 1.0.0 by JoomGallery::ProjectTeam                            **
**   Released under GNU GPL Public License                                              **
**   License: http://www.gnu.org/copyleft/gpl.html or have a look                       **
**   at administrator/components/com_joomgallery/LICENSE.TXT                            **
\****************************************************************************************/

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

/**
 * Install method
 * is called by the installer of Joomla!
 *
 * @access  protected
 * @return  void
 * @since   3.1.0
 */
class mod_joomcatInstallerScript
{
  /**
   * Preflight method
   *
   * Is called afore installation and update processes
   *
   * @param   $type   string  'install', 'discover_install', or 'update'
   * @return  boolean False if installation or update shall be prevented, true otherwise
   * @since   3.1.0
   */
  function preflight($parent) 
  {
    // Get the old version
    $db    = JFactory::getDbo();
    $query = $db->getQuery(true);

    $query->select($db->quoteName('manifest_cache'))
          ->from($db->quoteName('#__extensions'))
          ->where($db->quoteName('element') . ' = ' . $db->quote('mod_joomcat'));

    $db->setQuery($query);
    $result = $db->loadResult();

    $decode = json_decode($result);
    $oldRelease = $decode->version;

    // Version check
    if(version_compare($oldRelease, '3.1.0', '<'))
    {
      $query = $db->getQuery(true);
      $query->select($db->quoteName('params'))
            ->from($db->quoteName('#__modules'))
            ->where($db->quoteName('module') . ' = ' . $db->quote('mod_joomcat'));

      $db->setQuery($query);

      $cat_rows = $db->loadResult();
      $decode   = json_decode($cat_rows);
      $oldcfg_blacklist_cats = $decode->cfg_blacklist_cats;

      $startpos   = stripos($cat_rows, 'cfg_blacklist_cats') + 21;
      $endpos     = stripos($cat_rows, "cfg_showhidden", $startpos) - 3;
      $beginn     = substr($cat_rows, 0, $startpos-1);
      $ende       = substr($cat_rows, $endpos+1);
      $newstring  = '[';
      $array_cats = explode(',', $oldcfg_blacklist_cats);
      foreach ($array_cats as $element) 
      {
        $newstring .= '"' . (int) $element . '",';
      }
      // remove last ','
      $newstring = substr($newstring, 0, strlen($newstring)-1);
      $newstring .= ']';

      $endstring = $beginn . $newstring . $ende;

      $query = $db->getQuery(true);
      $query->update($db->quoteName('#__modules'))
            ->set($db->quoteName('params') . ' = ' . $db->quote($endstring))
            ->where($db->quoteName('module') . ' = ' . $db->quote('mod_joomcat'));
      $db->setQuery($query)->execute();

      echo 'Update sucessfully';
    }
  }
}
