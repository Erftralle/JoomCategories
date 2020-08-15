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

// Load class to avoid error when using the joomla updater
if(!class_exists('JoomInterface'))
{
  require_once JPATH_ROOT.'/components/com_joomgallery/interface.php';
}

/**
 * Helper class for module JoomCategories
 *
 */
class modJoomCatHelper extends JoomInterface
{
  /**
   * Get the categories from JoomGallery
   *
   * @param   object  $params     backend parameters
   * @param   string  $dberror    database error
   * @param   int     $module_id  the id of the module
   * @return  object  $jc_rows    category objects
   */
  public function getCats($params, &$dberror, $module_id)
  {
    // Read the parameters
    $this->getParams($params, $module_id);

    // Get JoomGallery styles
    $this->getPageHeader();

    // Create and include the dynamic css for default view
    // according to backend settings
    $this->renderCSS();

    $rootcat = $this->getConfig('rootcat');
    if($rootcat > 1)
    {
      $catlist = JoomHelper::getAllSubCategories($rootcat, false, true, false, $this->getConfig('showhidden') ? false : true);
    }

    // Category filter
    $catblacklist = array();
    $blacklist_cats = $this->getConfig('blacklist_cats');
    if(!empty($blacklist_cats))
    {
      $catblacklist_cfg = explode(',', $blacklist_cats);
      foreach($catblacklist_cfg as $cat)
      {
        if(!in_array( $cat, $catblacklist))
        {
          $catblacklist[] = $cat;
          $catblacklist = array_merge($catblacklist, JoomHelper::getAllSubCategories($cat, false, true, false, $this->getConfig('showhidden') ? false : true));
        }
      }
    }

    $authorisedViewLevels = implode(',', $this->_user->getAuthorisedViewLevels());

    $query = $this->_db->getQuery(true)
          ->select('ca.cid')
          ->select('ca.name')
          ->select('ca.description')
          ->select('ca.catpath')
          ->select('ca.alias')
          ->select('ca.thumbnail')
          ->select('ca.lft');
    if($this->getConfig('showrate') || $this->getConfig('catmode') == 1)
    {
      $query
          ->select('IF(SUM(jg.imgvotes) > 0, SUM('.JoomHelper::getSQLRatingClause('jg').') / count(jg.id), 0) AS catrating, SUM(jg.imgvotes) AS catimgvotes');
    }
    if($this->getConfig('showhits') || $this->getConfig('catmode') == 2)
    {
      $query
          ->select('SUM(jg.hits) AS cathits');
    }
    $query->from(_JOOM_TABLE_CATEGORIES.' AS ca')
          ->innerJoin(_JOOM_TABLE_IMAGES.' AS jg ON ca.cid = jg.catid')
          ->where('ca.published = 1')
          ->where('ca.access IN ('.$authorisedViewLevels.')');

    // Inheritance of category access must be considered here
    $allowed_cids = array_keys($this->_ambit->getCategoryStructure());
    if(!empty($allowed_cids))
    {
      $query
          ->where('ca.cid IN ('.implode(',', $allowed_cids).')');
    }

    if(!$this->getConfig('showhidden'))
    {
      $query
          ->where('ca.hidden    = 0')
          ->where('ca.in_hidden = 0');
    }
    // Category filter
    if(count($catblacklist) > 0)
    {
      $query
          ->where('ca.cid NOT IN ('.implode(',', $catblacklist ).')');
    }
    // Root category
    if($rootcat > 1)
    {
      if(count($catlist) > 0)
      {
        $query
          ->where('ca.cid IN ('.implode(',', $catlist).')');
      }
      else
      {
        // No category will have cid = 0, just to have a query
        // with no result categories
        $query
          ->where('ca.cid = 0');
      }
    }
    $query->where('jg.published = 1')
          ->where('jg.approved  = 1')
          ->where('jg.access IN ('.$authorisedViewLevels.')');;
    if(!$this->getConfig('showhidden'))
    {
        $query
          ->where('jg.hidden    = 0');
    }
    switch($this->getConfig('catmode'))
    {
      // Top rated
      case 1:
        $query
          ->group('ca.cid')
          ->order('catrating DESC');
        break;
      // Most viewed
      case 2:
        $query
          ->group('ca.cid')
          ->order('cathits DESC');
        break;
      // Random
      case 3:
        $query
          ->group('ca.cid')
          ->order('rand()');
        break;
      // Alphabetical ascending
      case 4:
        $query
          ->group('ca.cid')
          ->order('ca.name ASC');
        break;
      // Alphabetical descending
      case 5:
        $query
          ->group('ca.cid')
          ->order('ca.name DESC');
        break;
      // Ordering ascending
      case 6:
        $query
          ->group('ca.cid')
          ->order('ca.lft ASC');
        break;
      // Ordering descending
      case 7:
        $query
          ->group('ca.cid')
          ->order('ca.lft DESC');
        break;
      // Default, last added
      default:
        $query
          ->group('ca.cid')
          ->order('ca.cid DESC');
        break;
    }

    $this->_db->setQuery($query, 0, $this->getConfig('categorycount'));
    $dberror = '';

    try
    {
      $cat_rows = $this->_db->loadObjectList();
    }
    catch(RuntimeException $e)
    {
      $dberror = JText::_('MOD_JOOMCAT_DB_ERROR_LBL').': '.$e->getMessage();
    }

    return isset($cat_rows) ? $cat_rows : null;
  }

  /**
   * Get the params setted in module backend and set module configuration
   *
   * @param   object    $params     backend parameters
   * @param   int       $module_id  module id
   */
  protected function getParams($params, $module_id)
  {
    $this->addConfig('shownumcomments', 0);
    $this->addConfig('showlastcomment', 0);
    $this->addConfig('showtitle', 0);
    $this->addConfig('showpicasnew', 0);
    $this->addConfig('showauthor', 0);
    $this->addConfig('catmode', $params->get('cfg_catmode', 0 ));
    $this->addConfig('showcategory', intval($params->get('cfg_showimgcat', 1)));
    $this->addConfig('showcatlink', intval($params->get('cfg_showimgcatlink', 1)));
    $this->addConfig('showhits', intval($params->get('cfg_showcathits', 0)));
    $this->addConfig('showrate', intval($params->get('cfg_showcatrate', 0)));
    $this->addConfig('showdescription', intval($params->get('cfg_showthumb', 2)) == 2 ? 0 : $params->get('cfg_showimgdescr', 0));
    $this->addConfig('showcatdescription', $params->get('cfg_showcatdescr', 0));
    $this->addConfig('showcatadditionalfields', $params->get('cfg_showcatadditionalfields', 0));
    $this->addConfig('categoryfilter', '');
    $this->addConfig('hidebackend', '');
    $this->addConfig('catlink', $params->get('cfg_showimglink', 'default') == 'joomcat-category' ? 1 : 0);
    $catcount = intval($params->get('cfg_count', 4));
    $this->addConfig('categorycount', ($catcount <= 0) ? 4 : $catcount);
    $this->addConfig('showthumb', intval($params->get('cfg_showthumb', 2)));
    $this->addConfig('showimglink', $params->get('cfg_showimglink', 'default'));
    if(   $this->getConfig('showimglink') != 'joomcat-none'
        && $this->getConfig('showimglink') != 'joomcat-category'
        && $this->getConfig('showimglink') != 'joomcat-slideshow'
        && $this->getConfig('showimglink') != 'default'
      )
    {
      $this->addConfig('openimage', $this->getConfig('showimglink'));
    }
    $this->addConfig('thumbnaildim', intval($params->get('cfg_thumbnaildim', 0)));
    $this->addConfig('thumbnaildimvalue', intval( $params->get('cfg_thumbnaildimvalue', 150 )));
    $this->addConfig('columns', intval($params->get('cfg_columns', 1)));
    $this->addConfig('imgposition', intval($params->get('cfg_imgposition', 1)));
    $this->addConfig('horalign', $params->get('cfg_horalign', 'center'));
    $this->addConfig('showtext', ($this->getConfig('showcategory') == 0 && $this->getConfig('showdescription') == 0
                                  && $this->getConfig('showcatdescription') == 0 && $this->getConfig('showrate') == 0
                                  && $this->getConfig('showhits') == 0
                                  && $this->getConfig('showcatadditionalfields') == 0
                                 ) ? 0 : 1);
    $this->addConfig('showhorruler', $params->get('cfg_showhorruler', 1));
    $this->addConfig('txtresetliststyle', $params->get('cfg_txtresetliststyle', 0));
    $this->addConfig('showshorttext', $params->get('cfg_showshorttext', 0));
    $this->addConfig('imgshowborder', $params->get('cfg_imgshowborder', 1));
    $this->addConfig('imgborderwidth', $params->get('cfg_imgborderwidth', '1px'));
    $this->addConfig('imgborderstyle', $params->get('cfg_imgborderstyle', 'solid'));
    $this->addConfig('imgbordercolor', $params->get('cfg_imgbordercolor', '#C3C3C3'));
    $this->addConfig('imgpadding', $params->get('cfg_imgpadding', '3px'));
    $this->addConfig('imgbgcolor', $params->get('cfg_imgbgcolor', '#FFFFFF'));
    $rootcat = $params->get('cfg_rootcat', 1 );
    if($rootcat < 1)
    {
      $rootcat = 1;
    }
    $this->addConfig('rootcat', $rootcat);
    if(is_array($params->get('cfg_blacklist_cats', '')))
    {
      $this->addConfig('blacklist_cats', implode(',', $params->get('cfg_blacklist_cats', '')));
    }
    else
    {
      $this->addConfig('blacklist_cats', '');
    }
    $itemid = intval($params->get('cfg_itemid', 0));
    if($itemid > 0)
    {
      $this->addConfig('Itemid', $itemid);
    }
    $this->addConfig('showvml', $params->get('cfg_showvml', 0 ));
    $this->addConfig('vmlalign', $params->get('cfg_vmlalign', 'right' ));
    $this->addConfig('vmltext', $params->get('cfg_vmltext', 'View more...' ));
    $this->addConfig('module_id', $module_id);
    $this->addConfig('csstag', 'joomcat' . $module_id. '_');
    $this->addConfig('group', 'jgmodjc'. $module_id);
    $this->addConfig('thumbnailimgtype', $params->get('cfg_thumbnailimgtype', 'thumb'));
    $this->addConfig('cropimg', $params->get('cfg_cropimg', 0));
    $this->addConfig('cropimgwidth', $params->get('cfg_cropimgwidth', 50));
    $this->addConfig('cropimgheight', $params->get('cfg_cropimgheight', 50));
    $this->addConfig('sldtimer', $params->get('cfg_sldjgsettings', 1) == 1 ?
                      $this->GetJConfig('jg_slideshow_timer') : $params->get('cfg_sldtimer', 6000));
    $this->addConfig('sldtransition', $params->get('cfg_sldjgsettings', 1) == 1 ?
                     $this->GetJConfig('jg_slideshow_transition') : $params->get('cfg_sldtransition', 0));
    $this->addConfig('sldtranstime', $params->get('cfg_sldjgsettings', 1) == 1 ?
                     $this->GetJConfig('jg_slideshow_transtime') : $params->get('cfg_sldtranstime', 2000));
    $this->addConfig('sldmaxdimauto', $params->get('cfg_sldjgsettings', 1) == 1 ?
                     $this->GetJConfig('jg_slideshow_maxdimauto') : $params->get('cfg_sldmaxdimauto', 0));
    $this->addConfig('sldwidth', $params->get('cfg_sldjgsettings', 1) == 1 ?
                     $this->GetJConfig('jg_slideshow_width') : $params->get('cfg_sldwidth', 640));
    $this->addConfig('sldheight', $params->get('cfg_sldjgsettings', 1) == 1 ?
                     $this->GetJConfig('jg_slideshow_heigth') : $params->get('cfg_sldheight', 480));
    $this->addConfig('sldinfopane', $params->get('cfg_sldjgsettings', 1) == 1 ?
                     $this->GetJConfig('jg_slideshow_infopane') : $params->get('cfg_sldinfopane', 0));
    $this->addConfig('sldcarousel', $params->get('cfg_sldjgsettings', 1) == 1 ?
                     $this->GetJConfig('jg_slideshow_carousel') : $params->get('cfg_sldcarousel', 0));
    $this->addConfig('sldarrows', $params->get('cfg_sldjgsettings', 1) == 1 ?
                     $this->GetJConfig('jg_slideshow_arrows') : $params->get('cfg_sldarrows', 0));
    $this->addConfig('sldimgsort', $params->get('cfg_sldimgsort', 'rand()'));
    $this->addConfig('sldmaximg', $params->get('cfg_sldmaximg', 0));
    $this->addConfig('showhidden', $params->get('cfg_showhidden', 0));
  }

  /**
   * Create and include the dynamic css for default view
   * according to backend settings
   *
   */
  protected function renderCSS()
  {
    $containerwidth = floor(100 / $this->getConfig('columns'));
    $csstag         = $this->getConfig('csstag');
    $horalign       = "      text-align:" . $this->getConfig('horalign') . " !important;\n";
    $csscont        = "      float:left;\n";
    $cssimgborder   = '';
    $cssimg         = '';
    $csstxt         = '';

    $starrating_align = $this->getConfig('horalign');

    if($this->getConfig('showthumb') != 2)
    {
      switch ($this->getConfig('imgposition'))
      {
      	default:
      	case 1:
          // Image above text
          $cssimg = $horalign;
          $csstxt = $horalign . "      padding-top:0.5em;\n";
          break;
        case 2:
          // Image left from text
          if($this->getConfig('showtext') == 0)
          {
            $cssimg = $horalign;
          }
          else
          {
            $cssimg           = "      float:left;\n";
          	$csstxt           = "      float:left;\npadding-left:0.5em;\n";
          	$starrating_align = "left";
          }
          break;
        case 3:
          // Image right from text
          if($this->getConfig('showtext') == 0)
          {
            $cssimg = $horalign;
          }
          else
          {
        	  $cssimg           = "      float:right;\n";
            $csstxt           = "      float:right;\npadding-right:0.5em;\n";
            $starrating_align = "right";
          }
          break;
        case 4:
          // Image below text
        	$cssimg = $horalign;
          $csstxt = $horalign . "      padding-bottom:0.5em;\n";
          break;
      }

      if($this->getConfig('imgshowborder'))
      {
        $cssimgborder .= "      border: "
                      . $this->getConfig('imgborderwidth')
                      . " "
                      . $this->getConfig('imgborderstyle')
                      . " "
                      . $this->getConfig('imgbordercolor')
                      . ";\n      padding: "
                      . $this->getConfig('imgpadding')
                      . ";\n"
                      . "      background-color: "
                      . $this->getConfig('imgbgcolor')
                      . ";\n";
      }
    }
    else
    {
      // Thumb should not be displayed
      $csstxt = $horalign;
    }

    // Class to clear floats
    $css = "    .$csstag" . "clr {\n"
         . "      clear:both;\n"
         . "    }\n";
    // Row
    $css .= "    .$csstag" . "row {\n"
         . "      overflow:hidden;\n"
         . "      padding:0.5em 0;\n"
         // . "      border: 1px solid red;\n"
         . "    }\n";
    // Container for image and text
    $css .= "    .$csstag" . "imgct {\n"
         . "      width:" . $containerwidth . "% !important;\n"
         // . "      border: 1px solid green;\n"
         . $csscont
    	   . "    }\n";
    // Image
    if(!empty($cssimg))
    {
      $css .= "    .$csstag" . "img {\n"
           . $cssimg
           . "    }\n";
    }
    // Image border
    if(!empty( $cssimgborder))
    {
      $css .= "    .$csstag" . "img img{\n"
           . $cssimgborder
           . "    }\n";
    }
    // Text
    if (!empty($csstxt))
    {
      $css .= "    .$csstag" . "txt {\n"
           . $csstxt
           . "    }\n";
    }
    // Reset list style for text
    if($this->getConfig('txtresetliststyle') == 1)
    {
    	$css .= "    .$csstag" . "txt ul {\n"
    	     . "      line-height: 100% !important;\n"
           . "      margin:0 !important;\n"
           . "      padding:0 !important;\n"
    	     . "    }\n";

      $css .= "    .$csstag" . "txt li {\n"
           . "      background-image:none !important;\n"
           . "      list-style-type:none !important;\n"
           . "      list-style-image:none !important;\n"
           . "      margin:0 !important;\n"
           . "      padding: 0 0 0.5em 0 !important;\n"
           . "      line-height: 100% !important;\n"
           . "    }\n";
    }
    // Show additional link
    if($this->getConfig('showvml') > 0 )
    {
      $css .= "    .$csstag" . "vml {\n"
           . "      padding:0.5em 0.5em 0em 0.5em;\n"
           . "      text-align:" . $this->getConfig('vmlalign') . " !important;\n"
           . "    }\n";
    }
    if($this->getConfig('showimglink') == 'joomcat-slideshow')
    {
      // Slideshow style
      $css .= "    .$csstag" . "sld {\n"
           . "      background-color:#000;\n"
           . "      text-align:center;\n"
           . "      padding:0px;\n"
           . "      border:1px solid #000;\n"
           . "      position:absolute;\n"
           . "      z-index:1000;\n"
           . "      visibility:hidden;\n"
           . "    }\n";
    }
    // Rating with star graphic
    if($this->getJConfig('jg_ratingdisplaytype') == 1)
    {
      $css .= "    .$csstag" . "starrating {\n"
           . "      width:".(int)($this->getJConfig('jg_maxvoting') * 16)."px;\n"
           . "      background: url(".$this->getAmbit()->getIcon("star_gr.png").") 0 0 repeat-x;\n";
      switch($starrating_align)
      {
        case 'right':
          $css .= "      margin-left: auto;\n";
          break;
        case 'center':
          $css .= "      margin: 0 auto;\n";
          break;
        default:
          break;
      }
      $css .= "    }\n"
           . "    .$csstag" . "starrating div {\n"
           . "      height:16px;\n"
           . "      background: url(".$this->getAmbit()->getIcon("star_orange.png").") 0 0 repeat-x;\n"
           . "      margin-left: 0;\n"
           . "      margin-right: auto;\n"
           . "    }";
    }

    $doc = JFactory::getDocument();
    $doc->addStyleDeclaration($css);
  }

  /**
   * Creates HTML for description of one image.
   *
   * @param   object  $cat      Category information
   * @param   object  $img      Image information
   * @param   boolean $longText Flag to force output of descriptive attributes in text output
   * @return  string  $output   HTML of thumb description
   */
  protected function displayDescription($cat, $img, $longText = true)
  {
    // Get the router
    $router = $this->_mainframe->getRouter();
    // Get current values of vars 'option' and 'Itemid'
    $option = $router->getVar('option');
    $Itemid = $router->getVar('Itemid');
    // Set vars 'option' and 'Itemid'
    $router->setVar('option', _JOOM_OPTION);
    $router->setVar('Itemid', $this->getJoomId(false));

    $output = "<ul>\n";

    if($this->getConfig('showcategory'))
    {
      $output .= "  <li>";
      if($longText)
      {
        $output .= JText::_('COM_JOOMGALLERY_COMMON_CATEGORY') . ": ";
      }
      if($this->getConfig('showcatlink'))
      {
        $output .= '<a href="' . JRoute::_('index.php?view=category&catid=' . $cat->cid) . '">';
      }
      $output .= $cat->name;

      if($this->getConfig('showcatlink'))
      {
        $output .= "</a>";
      }
      $output .= "</li>\n";
    }
    if($this->getConfig('showrate'))
    {
      if(isset($cat->catrating))
      {
        // Patch property rating and imgvotes into object because it is needed that way in JHTML::_('joomgallery.rating', ... )
        $help1 = $img->rating;
        $help2 = $img->imgvotes;
        $img->rating = $cat->catrating;
        $img->imgvotes = $cat->catimgvotes;

        // Output rating
        $output .= "  <li>". JHTML::_('joomgallery.rating', $img, !$longText, $this->getConfig('csstag') . 'starrating') . "</li>\n";

        // Restore original values
        $img->rating = $help1;
        $img->imgvotes = $help2;
      }
    }
    if($this->getConfig('showhits'))
    {
      if(isset($cat->cathits))
      {
        if($longText)
        {
          $output .= "  <li>" . JText::sprintf('COM_JOOMGALLERY_COMMON_HITS_VAR', $cat->cathits) . "</li>\n";
        }
        else
        {
          $output .= "  <li>" . $cat->cathits . "</li>\n";
        }
      }
    }
    if($this->getConfig('showcatdescription'))
    {
      if(!empty($cat->description))
      {
        $output .=  "  <li>";
        if($longText)
        {
          $output .=  JText::_('COM_JOOMGALLERY_COMMON_DESCRIPTION') . ": ";
        }
        $output .= $cat->description . "</li>\n";
      }
    }
    if($this->getConfig('showdescription'))
    {
      if(!empty($img->imgtext))
      {
        $output .= "  <li>";
        if($longText)
        {
          $output .=  JText::_('COM_JOOMGALLERY_COMMON_DESCRIPTION') . ": ";
        }
        $output .= $img->imgtext . "</li>\n";
      }
    }

    // Display fields of additional plugins
    if($this->getConfig('showcatadditionalfields'))
    {
      $results = $this->_mainframe->triggerEvent('onJoomAfterDisplayCatThumb', array($cat->cid));
      if(!$longText)
      {
        $results = preg_replace('/<li>(.*?):/', '<li>', $results);
      }
      $output .= trim(implode('', $results));
    }

    $output .= "</ul>\n";

    $routervars = $router->getVars();
    if(is_null($option))
    {
      // Delete the var from array
      unset($routervars['option']);
    }
    else
    {
      $routervars['option'] = $option;
    }
    if(is_null($Itemid))
    {
      unset($routervars['Itemid']);
    }
    else
    {
      $routervars['Itemid'] = $Itemid;
    }
    $router->setVars($routervars, false);

    return $output;
  }

  /**
   * Prepares a slideshow to be ready to launch
   *
   * @param   string  $imgelement     html for thumbnail element representing category
   *                                  (for link adaption)
   * @param   string  $slideshowclass identifier for slideshow class for CSS
   * @param   string  $slideshowid    identifier for slideshow for CSS
   * @param   int     $catid          category id of slideshow images
   * @param   string  $catname        name of category
   * @param   object  $img_rows       array of image objects from database
   */
  protected function prepareSlideshow(&$imgelement, $slideshowclass, $slideshowid, $catid, $catname, $img_rows)
  {
    $doc = JFactory::getDocument();

    static $firstcall = true;

    $maxwidth = 0;
    $maxheight = 0;
    $number = 0;
    $script = '';
    $transition = 'fade';

    // Check, if we are in JoomGallery's detail view
    $viewIsJoomGalleryDetailView = false;
    $errorMessage = '';
    $option = JRequest::getVar('option', '', '', 'string');
    $view   = JRequest::getVar('view', '', '', 'string');
    if($option == 'com_joomgallery' && $view == 'detail')
    {
      $viewIsJoomGalleryDetailView = true;
      $errorMessage = addslashes(JText::_('MOD_JOOMCAT_ERRORMSG_SLD_NOT_AVAILABLE'));
    }

    if($firstcall)
    {
      // Include javascripts
      JHtml::_('behavior.framework', true);
      $doc->addScript(JURI::base() . 'media/mod_joomcat/mod_joomcat.js');

      if(!$viewIsJoomGalleryDetailView)
      {
        $doc->addStyleSheet($this->getAmbit()->getScript('smoothgallery/css/jd.gallery.css'));
        $doc->addScript($this->getAmbit()->getScript('smoothgallery/scripts/jd.gallery.js'));

        // No include if standard effects 'fade/crossfade/fadebg' chosen
        switch ($this->getConfig('sldtransition'))
        {
          case 0:
            $transition = 'fade';
            break;
          case 1:
            $transition = 'fadeslideleft';
            $doc->addScript($this->getAmbit()->getScript('smoothgallery/scripts/jd.gallery.transitions.js'));
            break;
          case 2:
            $transition = 'crossfade';
            break;
          case 3:
            $transition = 'continuoushorizontal';
            $doc->addScript($this->getAmbit()->getScript('smoothgallery/scripts/jd.gallery.transitions.js'));
            break;
          case 4:
            $transition = 'continuousvertical';
            $doc->addScript($this->getAmbit()->getScript('smoothgallery/scripts/jd.gallery.transitions.js'));
            break;
          case 5:
            $transition = 'fadebg';
            break;
          default:
            $transition = 'fade';
            break;
        }
      }

      $script = "    function catImage(image, thumbnail, linkTitle, link, title, description, number)\n"
              . "    {\n"
              . "      this.image = image;\n"
              . "      this.thumbnail = thumbnail;\n"
              . "      this.linkTitle = linkTitle;\n"
              . "      this.link =link;\n"
              . "      this.title = title;\n"
              . "      this.description = description;\n"
              . "      this.transition = '$transition';\n"
              . "      this.number = number;\n"
              . "    }\n";
      $firstcall = false;
    }

    $script .= "    var catImages" . $this->getConfig('module_id') . "_$catid = new Array();\n";

    // Get the router
    $router = JFactory::getApplication()->getRouter();
    // Get current values of vars 'option' and 'Itemid'
    $option = $router->getVar('option');
    $Itemid = $router->getVar('Itemid');
    // Set vars 'option' and 'Itemid'
    $router->setVar('option', 'com_joomgallery');
    $router->setVar('Itemid', $this->getJoomId(false));

    $script .= "    /* <![CDATA[ */\n";
    foreach($img_rows as $row)
    {
      // Description
      if($row->imgtext != '')
      {
        $description = JoomHelper::fixForJS($row->imgtext);
        // $description = preg_replace('/[\n\t\r]*/', '', htmlentities($row->imgtext, ENT_QUOTES, 'UTF-8'));
      }
      else
      {
        $description = '&nbsp;';
      }
      if($this->getConfig('sldmaxdimauto'))
      {
        // Get dimensions of image for calculating the max. width/height
        // of all images
        $dimensions = getimagesize($this->getAmbit()->getImg('img_path', $row));
        if($dimensions[0] > $maxwidth)
        {
          $maxwidth = $dimensions[0];
        }
        if($dimensions[1] > $maxheight)
        {
          $maxheight = $dimensions[1];
        }
      }

      $script .= "    catImages" . $this->getConfig('module_id') . "_$catid" . "[$number] = new catImage(\n"
              .  "      '" . str_replace('&amp;', '&', $this->GetAmbit()->getImg('img_url', $row)) . "',//image\n"
              .  "      '" . $this->GetAmbit()->getImg('thumb_url', $row) . "',//thumbnail\n"
              .  "      '" . JoomHelper::fixForJS($row->imgtitle). "',//linkTitle\n"
              .  "      '" . str_replace('&amp;', '&', $this->GetAmbit()->getImg('img_url', $row)) . "',//link\n"
              .  "      '" . JoomHelper::fixForJS($row->imgtitle). "',//title\n"
              .  "      '$description',//description\n"
              .  "      $number//number\n"
              .  "    );\n";

      $number++;
    }
    $script .= "    /* ]]> */\n";

    // Reset var 'option'
    $router->setVar('option', $option);
    $router->setVar('Itemid', $Itemid);

    if (!$this->getConfig('sldmaxdimauto'))
    {
      $maxwidth = $this->getConfig('sldwidth');
      $maxheight = $this->getConfig('sldheight');
    }

    $script .= "    var jsJoomCatModule" . $this->getConfig('module_id') . "_$catid = null;\n"
            .  "    window.addEvent('domready', function(){\n"
            .  "      jsJoomCatModule" . $this->getConfig('module_id') . "_$catid = new JoomCatModule({\n"
            .  "        div: '$slideshowid',\n"
            .  "        classname: '$slideshowclass',\n"
            .  "        delay: " .$this->GetConfig('sldtimer') . ",\n"
            .  "        fadeDuration: " . $this->GetConfig('sldtranstime') . ",\n"
            .  "        showArrows: " . $this->GetConfig('sldarrows') . ",\n"
            .  "        showCarousel: " . $this->GetConfig('sldcarousel') . ",\n"
            .  "        textShowCarousel: '" . JText::_('COM_JOOMGALLERY_DETAIL_SLIDESHOW_IMAGES') . "',\n"
            .  "        showInfopane: " . $this->GetConfig('sldinfopane') . ",\n"
            .  "        manualData: catImages" . $this->getConfig('module_id') . "_$catid,\n"
            .  "        maxWidth: $maxwidth,\n"
            .  "        maxHeight: $maxheight,\n"
            .  "        errorMessage: '$errorMessage',\n"
            .  "        baseURL: '" . JURI::base() . "',\n"
            .  "        catTitle: '" . JoomHelper::fixForJS($catname) . "'\n"
            .  "      });\n"
            .  "    });\n";

    $doc->addScriptDeclaration($script);

    // Prepare image link for slideshow
    if(preg_match('/<img ([^>]*)>/', $imgelement, $matches) > 0)
    {
      $replace = '<a href="javascript:void(0);" title="'.$catname.'" onclick="jsJoomCatModule'.$this->getConfig('module_id').'_'.$catid.'.startSlideshow()">'.$matches[0].'</a>';
      $imgelement = preg_replace('/<img ([^>]*)>/', $replace, $imgelement);
    }
  }

  /**
   * Returns the the View more... link optionally shown at the modules bottom
   * @return   string    $link
   */
  public function getViewMoreLink()
  {
    $link = '';
    $rootcat = $this->getConfig('rootcat');
    if($rootcat == 1 || $this->getConfig('showvml') == 1)
    {
      $link = JRoute::_('index.php?option=com_joomgallery&view=gallery'.$this->getJoomId());
    }
    else
    {
      $link = JRoute::_('index.php?option=com_joomgallery&view=category&catid='.$rootcat.$this->getJoomId());
    }

    return $link;
  }
  /**
   * Returns the text element to show for a category
   *
   * @param   object    $cat    category row object
   * @param   object    $img    image row object
   * @return  string    $html
   */
  public function displayTextElement($cat, $img)
  {
    $html = '';
    if($this->getConfig('showtext') == 1)
    {
      $html = $this->displayDescription($cat, $img, ($this->getConfig('showshorttext') == 1 ? false : true));
    }

    return $html;
  }

  /**
   * Returns the image element to show for a category
   *
   * @param   object    $cat      category row object
   * @param   object    $img      image row object
   * @param   object    $imgs     array of image row objects for slideshow
   * @return  string    $html
   */
  public function displayImgElement($cat, $img, $imgs)
  {
    $dim            = '';
    $cropimgpos     = null;
    $cropimgwidth   = null;
    $cropimgheight  = null;

    if($this->getConfig('cropimg'))
    {
      // Center
      $cropimgpos     = 2;
      $cropimgwidth   = (int) $this->getConfig('cropimgwidth');
      $cropimgheight  = (int) $this->getConfig('cropimgheight');
    }
    else
    {
      // Get thumbnail dimensions
      $dim = '';
      switch($this->getConfig('thumbnaildim'))
      {
         case 1:
           $dim = 'style="height:' . $this->getConfig('thumbnaildimvalue') . 'px"';
           break;
         case 2:
           $dim = 'style="width:' . $this->getConfig('thumbnaildimvalue') . 'px"' ;
           break;
         default:
           break;
      }
    }

    // Is the image linked ?
    $linked = true;
    if($this->getConfig('showimglink') == 'joomcat-none' || $this->getConfig('showimglink') == 'joomcat-slideshow' )
    {
      $linked = false;
    }
    $html = $this->displayThumbnail($cat, $img, $linked, null, null, $dim, $this->getConfig('thumbnailimgtype'), $cropimgpos, $cropimgwidth, $cropimgheight);

    // Remove some JoomGallery's CSS styles
    $html = str_replace(" class=\"jg_catelem_photo\"", "", $html);
    $html = str_replace(" class=\"jg_photo\"", "", $html);

    if($this->getConfig('showimglink') == 'joomcat-slideshow')
    {
      $slideshowclass = $this->getConfig('csstag') . 'sld';
      $slideshowid = $slideshowclass . '_' . $cat->cid;
      // Prepare the slideshow
      $this->prepareSlideshow($html, $slideshowclass, $slideshowid, $cat->cid, $cat->name, $imgs);
    }

    return $html;
  }

  /**
   * Creates HTML for linked thumbnail of one image with display options and style just like in JG.
   *
   * @param   object  $cat            Category information
   * @param   object  $img            Image information
   * @param   boolean $linked         If true, we will link the thumbnail, defaults to true
   * @param   string  $class          Optional, addional css class name which is assigned to the img tag
   * @param   string  $div            Optional css class name which is assigned to a div around the img tag
   * @param   string  $extra          Optional, adddional HTML code, which is placed in the img tag
   * @param   string  $type           Optional, image type the link shall open (thumb, img, orig)
   * @param   int     $cropimgpos     Optional, crop position
   * @param   int     $cropimgwidth   Optional, crop width
   * @param   int     $cropimgheight  Optional, crop height
   * @return  string  HTML displaying thumbnail (linked, like configured in JG if $linked = true)
   */
  protected function displayThumbnail($cat, $img, $linked = true, $class = null, $div = null, $extra = null, $type = false, $cropimgpos = null, $cropimgwidth = null, $cropimgheight = null )
  {
    $output = '';

    if($img->id != '')
    {
      // Get the router
      $router = $this->_mainframe->getRouter();
      // Get current values of vars 'option' and 'Itemid'
      $option = $router->getVar('option');
      $Itemid = $router->getVar('Itemid');
      // Set vars 'option' and 'Itemid'
      $router->setVar('option', 'com_joomgallery');
      $router->setVar('Itemid', $this->getJoomId(false));

      if($div)
      {
        $output .= '<div class="'.$div.'">';
      }
      if($linked)
      {
        // Check for link to category
        if(isset($this->_config['catlink']) && $this->_config['catlink'] == 1)
        {
          $link  = JRoute::_('index.php?&view=category&catid='.$cat->cid);
          $link .= '" title="'.htmlspecialchars($cat->name, ENT_COMPAT, 'UTF-8');
        }
        else
        {
          $link  = JHTML::_('joomgallery.openimage', $this->_config['openimage'], $img, false, $this->getConfig('group'));
          $link .= '" title="'.htmlspecialchars($img->imgtitle, ENT_COMPAT, 'UTF-8');
        }

        $output .= '  <a href="'.$link.'" class="jg_catelem_photo">';
      }
      if($class)
      {
        $class = ' '.$class;
      }
      if($extra)
      {
        $extra = ' '.$extra;
      }
      $imgtype = 'thumb_url';
      if($type != false)
      {
        $imgtype = $type . '_url';
      }
      $output   .= '    <img src="'.$this->_ambit->getImg($imgtype, $img, null, 0, true, $cropimgwidth, $cropimgheight, $cropimgpos).'" class="jg_photo'.$class.'" alt="'.$img->imgtitle.'"'.$extra.' />';
      if($linked)
      {
        $output .= '  </a>';
      }
      if($div)
      {
        $output .= '</div>';
      }
      $routervars = $router->getVars();
      if(is_null($option))
      {
        // Delete the var from array
        unset($routervars['option']);
      }
      else
      {
        $routervars['option'] = $option;
      }
      if(is_null($Itemid))
      {
        unset($routervars['Itemid']);
      }
      else
      {
        $routervars['Itemid'] = $Itemid;
      }
      $router->setVars($routervars, false);
    }
    else
    {
      $output .= "    &nbsp;\n";
    }

    return $output;
  }
}