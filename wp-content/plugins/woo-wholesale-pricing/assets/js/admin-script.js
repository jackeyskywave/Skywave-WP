jQuery(document).ready( function ($) {
    jQuery('#rejected_note').hide();
    function rejected_note() {
          console.log('test');
        if ( jQuery('#rejected').is(':checked') ) {
     
            jQuery('#rejected_note').show();
        } else {
        
            jQuery('#rejected_note').hide();
        }
        
    }
    
    jQuery("#rejected,#active").click(function(){
    rejected_note();
    });		

    rejected_note();
    
    jQuery( "#section1" ).show();
    jQuery( ".section1" ).click(function() {
        
        jQuery( "#wwp-global-settings .tab-content .tab-pane" ).hide();
        localStorage.setItem('activeTabGeneral', 'section1' );
        jQuery( "#section1" ).show();
        select_activeTabGeneral (localStorage.getItem('activeTabGeneral'));
    });
    
    jQuery( ".section2" ).click(function() {
        
        jQuery( "#wwp-global-settings .tab-content .tab-pane" ).hide();
        localStorage.setItem('activeTabGeneral', 'section2' );
        jQuery( "#section2" ).show();
        select_activeTabGeneral (localStorage.getItem('activeTabGeneral'));
    });
    
    
    jQuery( ".section3" ).click(function() {
       
        jQuery( "#wwp-global-settings .tab-content .tab-pane" ).hide();
        localStorage.setItem('activeTabGeneral', 'section3' );
        jQuery( "#section3" ).show();
        select_activeTabGeneral (localStorage.getItem('activeTabGeneral'));
    });
    
    jQuery( ".section4" ).click(function() {
        
        jQuery( "#wwp-global-settings .tab-content .tab-pane" ).hide();
        localStorage.setItem('activeTabGeneral', 'section4' );
        jQuery( "#section4" ).show();
        select_activeTabGeneral (localStorage.getItem('activeTabGeneral'));
    });
    select_activeTabGeneral (localStorage.getItem('activeTabGeneral'));
    
});


function select_activeTabGeneral (activeTab) { 
 
   if( activeTab ) {
    jQuery('#wwp-global-settings  ul.nav-tabs li a').removeClass('active');
    jQuery('body.toplevel_page_wwp_wholesale  ul.nav-tabs li.' + activeTab +' a').addClass('active');
    jQuery( "#wwp-global-settings .tab-content .tab-pane" ).hide();
    jQuery( '#'+ activeTab +'' ).show();
   } else {
    jQuery('body.toplevel_page_wwp_wholesale  ul.nav-tabs li.section1 a').addClass('active');
    localStorage.setItem('activeTabGeneral', 'section1' );
   }

   console.log(activeTab);
     
}