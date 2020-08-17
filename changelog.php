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

// no direct access
defined('_JEXEC') or die('Restricted access');
?>

CHANGELOG MOD_JOOMCAT (since Version 2.0 BETA )

Legende / Legend:

* -> Security Fix
# -> Bug Fix
+ -> Addition
^ -> Change
- -> Removed
! -> Note

-------------------------------------------------------------------------------
MOD_JOOMCAT version: 3.0.4
-------------------------------------------------------------------------------
20200817
# Fix wrong version number in script.php

-------------------------------------------------------------------------------
MOD_JOOMCAT version: 3.0.3
-------------------------------------------------------------------------------
20200815
^ Improving category selection
# Loading JoomInterface class in helper.php to avoid error when using the joomla
  updater

-------------------------------------------------------------------------------
MOD_JOOMCAT version: 3.0.2
-------------------------------------------------------------------------------
20200709
# Fix database error occurring with MySQL 8
^ Remove obsolete repository SVN tags
- Remove duplicate LISCENSE file

-------------------------------------------------------------------------------
MOD_JOOMCAT version: 3.0.1
-------------------------------------------------------------------------------
20190403
^ Change update server

-------------------------------------------------------------------------------
MOD_JOOMCAT version: 3.0 BETA2
-------------------------------------------------------------------------------
20130720
^ Inline styles for thumbnails
^ Minor optimizations and changes

-------------------------------------------------------------------------------
MOD_JOOMCAT version: 3.0 BETA
-------------------------------------------------------------------------------
20130310
^ Minor optimizations and changes

20130215
^ DS -> '/'
+ Use Bootstrap classes in XML file
+ Use of new openimage form field
^ JS adaptions
+ Option to show additional category fields

-------------------------------------------------------------------------------
MOD_JOOMCAT version: 2.1
-------------------------------------------------------------------------------
20121218
+ Layout option added in module configuration
+ Title attribute added for thumbnail links to slideshow and category view
  (title attribute here set to category name instead of image title)
# Workaround to allow hidden images as category thumbnails in case
 'Thumbnail display mode'is set to 'Manual setting' and 'Show hidden content'
  is set to 'No'
^ Minor changes, code style

-------------------------------------------------------------------------------
MOD_JOOMCAT version: 2.0
-------------------------------------------------------------------------------
20120908
+ dutch translation - thanks to the dutch translation team at
  www.transifex.com/projects/p/joomgallery-extensions/language/nl_NL/

-------------------------------------------------------------------------------
MOD_JOOMCAT version: 2.0 BETA6
-------------------------------------------------------------------------------
20120521
# User access rights for images were not checked in SQL query.
^ STRICT standards

-------------------------------------------------------------------------------
MOD_JOOMCAT version: 2.0 BETA5
-------------------------------------------------------------------------------
20120502
+ Additional options for the module parameter sort mode

-------------------------------------------------------------------------------
MOD_JOOMCAT version: 2.0 BETA4
-------------------------------------------------------------------------------
20120308
^ CSS improvements

-------------------------------------------------------------------------------
MOD_JOOMCAT version: 2.0 BETA3
-------------------------------------------------------------------------------
20120126
^ Javascript adaptions to become campatible with Mootools 1.4.2

-------------------------------------------------------------------------------
MOD_JOOMCAT version: 2.0 BETA2
-------------------------------------------------------------------------------
20120120
# If 'Thumbnail display mode' was set to 'Manual setting' the selected
  thumbnail had to be in the category itself, selecting thumbnails from other
  categories caused a complete false module output for some cases.

-------------------------------------------------------------------------------
MOD_JOOMCAT version: 2.0 BETA
-------------------------------------------------------------------------------
20111012
^ assets moved to media/mod_joomcat
+ Update server added

20110924
^ Changes due to JoomGallery's new category structure and the
  use of JTableNested for _JOOM_TABLE_CATEGORIES
^ Javascript adaptions to become campatible with Mootools 1.3
^ Language constants renamed (Joomla! standards)

20110508
^ Adaptions for Joomla 1.6! compatibility (language files, XML files, parameters
  , JDatabaseQuery)
