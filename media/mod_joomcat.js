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
var JoomCatModule = new Class({
  /***********/
  /* Options */
  /***********/
  options:{
      div: '',
      classname: '',
      delay:9000,
      fadeDuration: 500,
      showArrows: true,
      showCarousel: true,
      textShowCarousel: 'Pictures',
      showInfopane: true,
      manualData:[],
      maxWidth: 400,
      maxHeight: 300,
      errorMessage: '',
      baseURL: '',
      catTitle: ''
  },
  /********/
  /* Init */
  /********/  
  initialize: function(options){
    this.setOptions(options);

    // add slideshow container div to the document body
    $(document.body).adopt(new Element('div', {'id': this.options.div, 'class': this.options.classname}));
    this.slideshow = $(this.options.div);
    this.slideshow.setStyles({
      'visibility'  : 'hidden',
      'display'     : 'none',
      'opacity'     : '0'
    });

    // add slideshow control container to the document body
    $(document.body).adopt(new Element('div', {'id': this.options.div + '_control'}));
    this.slideshowcontrol = $(this.options.div + '_control');
    this.slideshowcontrol.setStyles({
      'visibility'        : 'hidden',
      'display'           : 'none',
      'height'            : '25px',
      'width'             : this.options.maxWidth + 'px',
      'background-color'  : '#202020',
      'position'          : 'absolute',
      'z-index'           : '1000',
      'border'            : '1px solid #000',
      'text-align'        : 'center',
      'opacity'           : '0'
    });
    
    // add the category title to the slideshow control container
    this.catTitle = new Element('div', {'id': this.options.div + '_control_title'}).inject(this.slideshowcontrol);
    this.catTitle.setStyles({
      'display'       : 'inline-block',
      'padding'       : '0px 0px 0px 5px',
      'font-size'     : '12px',
      'line-height'   : '25px',
      'color'         : '#FFF',
      'float'         : 'left', // just IE7 needs that to display closeLink inline
      'width'         : (this.options.maxWidth - 30) + 'px' // just IE7 needs that to display closeLink inline
    });
    this.catTitle.set('html', this.options.catTitle);
    
    // add a close link to the slideshow control container
    this.closeLink = new Element('a', {'id': this.options.div + '_control_close', 'href': '#'}).inject(this.slideshowcontrol);
    this.closeLink.setStyles({
      'float'         : 'right',
      'width'         : '25px',
      'height'        : '25px',
      'background'    : 'transparent url(' + this.options.baseURL + 'media/mod_joomcat/close.png) no-repeat center'
    });

    // add an overlay div to the document body
    $(document.body).adopt(new Element('div', {'id': this.options.div + '_overlay'}));
    this.overlay = $(this.options.div + '_overlay');
    this.overlay.setStyles({
      'display'         : 'none',
      'position'        : 'absolute',
      'left'            : '0',
      'width'           : '100%',
      'background-color': '#000',
      'z-index'         : '999',
      'cursor'          : 'pointer', 
      'visibility'      : 'hidden',
      'opacity'         : '0'
    });
            
    /**************/
    /*** Events ***/    
    /**************/
    // clicking in browser window fades out the slideshow if visible
    // document for IE
    document.addEvent('click', function(e){
      this.close();
    }.bind(this));
    
    // resize of browser window
    window.addEvent('resize', function(e){
      if (this.slideshow.getStyles('visibility').visibility != 'hidden'){
        this.positionOverlay();
        this.positionSlideshow();
        this.positionSlideshowControl();
      }
    }.bind(this));
    
    // when scrolling in browser window slideshow should follow 
    window.addEvent('scroll', function(e){
      if (this.slideshow.getStyles('visibility').visibility != 'hidden'){
        this.positionOverlay();
        this.positionSlideshow();
        this.positionSlideshowControl();
      }
    }.bind(this));
      
    // click on slideshow div doesn't fade it out
    this.slideshow.addEvent('click', function(e){
      e.stop();
    }.bind(this));
    
    // click on slideshow control div doesn't fade it out
    this.slideshowcontrol.addEvent('click', function(e){
      e.stop();      
    }.bind(this));
    
    // click on slideshow control close link
    this.closeLink.addEvent('click', function(e){
      this.close();      
    }.bind(this));        
    /********* ********/
    /*** End events ***/    
    /******************/
  },
  /*******************/
  /* Close slideshow */
  /*******************/
  close: function(){
    if (this.slideshow.getStyles('visibility').visibility != 'hidden'){
      // stop slideshow
      this.gallery.clearTimer();
      // stop info zone timer
      // if(this.options.showInfopane){
      //   this.gallery.slideInfoZone.clearTimer();
      // }
      // fade out slideshow
      this.fadeSlideshow(false);
      // remove gallery class from slideshow element
      this.slideshow.removeClass(this.gallery.options.baseClass);
    }    
  },
  /*********************************************************************/
  /* Get inner width and height of browser window dependent on browser */
  /*********************************************************************/
  getInnerWidthHeight: function(){
    var Width = 0;
    var Height = 0;
    if(typeof(window.innerWidth) == 'number'){
      // Non-IE
      Width = window.innerWidth;
      Height = window.innerHeight;
    } else if(document.documentElement && (document.documentElement.clientWidth || document.documentElement.clientHeight)){
      // IE 6+ in 'standards compliant mode'
      Width = document.documentElement.clientWidth;
      Height = document.documentElement.clientHeight;
    } else if(document.body && (document.body.clientWidth || document.body.clientHeight)){
      // IE 4 compatible
      Width = document.body.clientWidth;
      Height = document.body.clientHeight;
    }
    return[Width, Height];
  },
  /************************************************************/
  /* Get scroll offset of browser window dependent on browser */
  /************************************************************/
  getScrollXY: function(){
    var scrOfX = 0;
    var scrOfY = 0;
    if(typeof(window.pageYOffset) == 'number'){
      // Netscape compliant
      scrOfY = window.pageYOffset;
      scrOfX = window.pageXOffset;
    } else if(document.body && (document.body.scrollLeft || document.body.scrollTop)){
      // DOM compliant
      scrOfY = document.body.scrollTop;
      scrOfX = document.body.scrollLeft;
    } else if(document.documentElement && (document.documentElement.scrollLeft || document.documentElement.scrollTop)){
      // IE6 standards compliant mode
      scrOfY = document.documentElement.scrollTop;
      scrOfX = document.documentElement.scrollLeft;
    }
    return [scrOfX, scrOfY];
  },
  /********************************/
  /* Positioning of the slideshow */
  /********************************/
  positionSlideshow: function(){
    this.slideshowwidth   = this.options.maxWidth;
    this.slideshowheight  = this.options.maxHeight;
    this.slideshowtop     = this.getScrollXY()[1] + parseInt((this.getInnerWidthHeight()[1] / 2), 10) - parseInt((this.slideshowheight / 2), 10);
    this.slideshowleft    = this.getScrollXY()[0] + parseInt((this.getInnerWidthHeight()[0] / 2), 10) - parseInt((this.slideshowheight / 2), 10);

    this.slideshow.setStyles({
      'top'     : this.slideshowtop+'px',
      'left'    : this.slideshowleft+'px',
      'width'   : this.slideshowwidth+'px',
      'height'  : this.slideshowheight+'px'      
    });        
  },
  /****************************************/
  /* Positioning of the slideshow control */
  /****************************************/
  positionSlideshowControl: function(){
    this.slideshowcontrolwidth   = this.options.maxWidth;
    this.slideshowcontrolheight  = /* parseInt((this.closeLink.getStyle('height')), 10); Opera returns zero here */ 25;
    this.slideshowcontroltop     = this.getScrollXY()[1] 
                                 + parseInt((this.getInnerWidthHeight()[1] / 2), 10)
                                 - parseInt((this.slideshowheight / 2), 10)
                                 - this.slideshowcontrolheight;
    this.slideshowcontrolleft    = this.getScrollXY()[0]
                                 + parseInt((this.getInnerWidthHeight()[0] / 2), 10)
                                 - parseInt((this.slideshowheight / 2), 10);

    this.slideshowcontrol.setStyles({
      'top'     : this.slideshowcontroltop+'px',
      'left'    : this.slideshowcontrolleft+'px',
      'width'   : this.slideshowcontrolwidth+'px',
      'height'  : this.slideshowcontrolheight+'px'      
    });
  },
  /********************************/
  /* Positioning of the overlay   */
  /****s***************************/
  positionOverlay: function() {
    this.overlay.setStyles({top: this.getScrollXY()[1], height: this.getInnerWidthHeight()[1]});
  },  
  /*************************/
  /* Fade in/out slideshow */
  /*************************/  
  fadeSlideshow: function(fadeIn){
    if(fadeIn){
      // fading in slideshow
      var fadeOverlayfx = new Fx.Morph(this.overlay, {
        duration: 500,
        wait: true,
        transition: Fx.Transitions.Quad.easeOut
      }).addEvent('onComplete', function(){
        var fadeSlideshowControlfx = new Fx.Morph(this.slideshowcontrol, {
          duration: 500,
          wait: true,
          transition: Fx.Transitions.Quad.easeOut          
        });
        var fadeSlideshowfx = new Fx.Morph(this.slideshow, {
          duration: 500,
          wait: true,
          transition: Fx.Transitions.Quad.easeOut
        }).addEvent('onComplete', function(){  
          // clear slideshow container
          this.slideshow.innerHTML = '';        
          // create a new gallery object and start showing a slideshow
          this.gallery = new gallery( this.slideshow, {
                                        timed: true,
                                        delay: this.options.delay,
                                        fadeDuration: this.options.fadeDuration,
                                        showArrows: this.options.showArrows,
                                        showCarousel: this.options.showCarousel,
                                        textShowCarousel: this.options.textShowCarousel,
                                        showInfopane: this.options.showInfopane,
                                        embedLinks: false,
                                        manualData: this.options.manualData,
                                        preloader: false,
                                        populateData: false,
                                        maxWidth:this.options.maxWidth,
                                        maxHeight:this.options.maxHeight
          });
        }.bind(this));     
        this.slideshow.setStyles({
          'display'    : 'block',
          'visibility' : 'visible'
        });
        this.slideshowcontrol.setStyles({
          'display'    : 'block',
          'visibility' : 'visible'
        });
        fadeSlideshowfx.start({
          'opacity' : [0, 1]
        });
        fadeSlideshowControlfx.start({
          'opacity' : [0, 1]
        });
      }.bind(this));
      this.overlay.setStyles({
        'visibility' : 'visible',
        'display'    : 'block'
      });
      fadeOverlayfx.start({
        'opacity' : [0,0.7]
      });      
    }
    else{
      // fading out slideshow
      var fadeSlideshowControlfx = new Fx.Morph(this.slideshowcontrol, {
        duration: 500,
        wait: true,
        transition: Fx.Transitions.Quad.easeOut
      });
      var fadeSlideshowfx = new Fx.Morph(this.slideshow, {
        duration: 500,
        wait: true,
        transition: Fx.Transitions.Quad.easeOut
      }).addEvent('onComplete', function() {
        var fadeOverlayfx = new Fx.Morph(this.overlay, {
          duration: 500,
          wait: true,
          transition: Fx.Transitions.Quad.easeOut
        }).addEvent('onComplete', function() {
          this.overlay.setStyles({
            'display'    : 'none',
            'visibility' : 'hidden'
          });  
        }.bind(this));
        this.slideshow.setStyles({
          'visibility' : 'hidden',
          'display'    : 'none'
        });
        this.slideshowcontrol.setStyles({
          'display'    : 'none',
          'visibility' : 'hidden'
        });
        fadeOverlayfx.start({
          'opacity' : [0.7, 0]
        });
      }.bind(this));
      fadeSlideshowfx.start({
        'opacity' : [1,0]
      });
      fadeSlideshowControlfx.start({
        'opacity' : [1,0]
      });
    }
  },
  /*******************/
  /* Start slideshow */
  /*******************/
  startSlideshow: function(){
    if(this.options.errorMessage == ''){
      // position the slideshow
      this.positionSlideshow(); 
      // position slideshow control
      this.positionSlideshowControl();
      // position overlay
      this.positionOverlay();      
      // fade in slideshow
      this.fadeSlideshow(true);
    }
    else{
      alert(this.options.errorMessage); 
    }      
  }
});
JoomCatModule.implement(new Options);