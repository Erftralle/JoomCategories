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

// No direct access
defined('_JEXEC') or die('Restricted access');

$jg_installed  = null;
$jg_ifpath     = JPATH_ROOT.'/components/com_joomgallery/interface.php';
$jg_minversion = '3.0';

if(file_exists($jg_ifpath))
{
  // Include JoomGallery's interface class
  require_once $jg_ifpath;

  // Include the helper functions only once
  require_once dirname(__FILE__).'/helper.php';

  // Create an instance of the helper object
  $jc_obj = new modJoomCatHelper();

  // Check gallery version
  if(version_compare($jc_obj->getGalleryVersion(), $jg_minversion, '>='))
  {
    // Correct version of JoomGallery seems to be installed
    $jg_installed = true;

    // Get the categories from JoomGallery
    $cat_rows = $jc_obj->getCats($params, $dberror, $module->id);
  }
}

// Show the categories
require JModuleHelper::getLayoutPath('mod_joomcat', $params->get('layout', 'default'));