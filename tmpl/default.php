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
defined('_JEXEC') or die('Restricted access'); ?>

<?php if(!$jg_installed) : ?>
  <p class="alert alert-warning">
<?php   echo JText::sprintf('MOD_JOOMCAT_JOOMGALLERY_NOT_INSTALLED_LBL', $jg_minversion); ?>
  </p>
<?php elseif($dberror != "") : ?>
  <p class="alert alert-error">
<?php   echo $dberror; ?>
  </p>
<?php elseif(empty($cat_rows)) : ?>
  <p class="alert alert-info">
<?php   echo JText::_('MOD_JOOMCAT_NO_CATEGORIES_FOUND_LBL'); ?>
  </p>
  <?php else :
        // CSS classes for row, clear, slideshow and image link
        $csstag = $jc_obj->getConfig('csstag');
        $rowclass = $csstag . "row";
        $clearclass = $csstag . "clr";
?>
  <!-- open joomcat div -->
  <div class="joomcat<?php echo $params->get('moduleclass_sfx'); ?>">
    <!-- open first joomcat row div -->
    <div class="<?php echo $rowclass; ?>">
<?php
        $count_img_per_row = 0;
        $count_img = 0;
        foreach($cat_rows as $row) :
          if($count_img_per_row >= $jc_obj->getConfig('columns')) : ?>
    <!-- close joomcat row div -->
    </div>
    <div class="<?php echo $clearclass; ?>"></div>
<?php       if($jc_obj->getConfig('showhorruler')) : ?>
    <hr/>
<?php       endif;
            $count_img_per_row = 0; ?>
    <!-- open joomcat row div -->
    <div class="<?php echo $rowclass; ?>">
<?php     endif;
          // get all images of this category sorted randomly
          $maximg = $jc_obj->getConfig('sldmaximg');
          $img_rows = $jc_obj->getPicsByCategory($row->cid, null, $jc_obj->getConfig('sldimgsort'), $maximg <= 0 ? null : $maximg, 0);
          // preset with random image
          $k = rand(0, count($img_rows) - 1);
          $img_row = $img_rows[$k];
          if($jc_obj->getConfig('showthumb') == 1 && $row->thumbnail != 0) :
            // show thumbnail configured in JoomGallery category manager
            $showhidden = $jc_obj->getConfig('showhidden');
            $jc_obj->addConfig('showhidden', 1);
            $cat_img_row = $jc_obj->getPicture($row->thumbnail);
            $jc_obj->addConfig('showhidden', $showhidden);
            if(isset($cat_img_row->id)) :
              $img_row = $cat_img_row;
            endif;
          endif; ?>
      <!-- open joomcat image container div -->
      <div class="<?php echo $csstag;?>imgct">
<?php     if($jc_obj->getConfig('showthumb') == 2) : ?>
        <div class="<?php echo $csstag;?>txt">
<?php       echo $jc_obj->displayTextElement($row, $img_row); ?>
        </div>
<?php     else : ?>
<?php       if($jc_obj->getConfig('imgposition') <= 3) : ?>
        <div class="<?php echo $csstag;?>img">
<?php       echo $jc_obj->displayImgElement($row, $img_row, $img_rows) . "\n"; ?>
        </div>
        <div class="<?php echo $csstag;?>txt">
<?php       echo $jc_obj->displayTextElement($row, $img_row); ?>
        </div>
<?php       else : ?>
        <div class="<?php echo $csstag;?>txt">
<?php       echo $jc_obj->displayTextElement($row, $img_row); ?>
        </div>
        <div class="<?php echo $csstag;?>img">
<?php       echo $jc_obj->displayImgElement($row, $img_row, $img_rows) . "\n"; ?>
        </div>
<?php       endif ?>
<?php     endif; ?>
      <!-- close joomcat image container div -->
      </div>
<?php     $count_img_per_row++;
        endforeach; ?>
    <!-- close last joomcat row div -->
    </div>
    <div class="<?php echo $clearclass; ?>"></div>
<?php   if($jc_obj->getConfig('showvml') > 0) : ?>
    <div class="<?php echo $csstag.'vml'; ?>">
      <a href="<?php echo $jc_obj->getViewMoreLink() ?>"><?php echo $jc_obj->getConfig('vmltext'); ?></a>
    </div>
<?php   endif; ?>
  <!-- close joomcat div -->
  </div>
<?php endif; ?>