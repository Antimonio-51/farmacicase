/**
 * Script amministrativo FarmaciCase
 *
 * @since      1.0.0
 * @package    FarmaciCase
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Comportamento bottone test notifica
        $('#farmacicase_test_notification').click(function() {
            return confirm('Sei sicuro di voler inviare una notifica di test?');
        });
        
        // Inizializza tabs se presenti
        if ($('.fc-admin-tabs').length) {
            initTabs();
        }
        
        // Inizializza campi colore se presenti
        if ($('.fc-color-picker').length) {
            $('.fc-color-picker').wpColorPicker();
        }
    });
    
    /**
     * Inizializza tabs admin
     */
    function initTabs() {
        var $tabs = $('.fc-admin-tab');
        var $tabLinks = $('.fc-admin-tab-link');
        
        // Imposta la prima tab come attiva se nessuna è attiva
        if (!$tabLinks.filter('.active').length) {
            $tabLinks.first().addClass('active');
            $tabs.first().addClass('active');
        }
        
        // Eventi click
        $tabLinks.on('click', function(e) {
            e.preventDefault();
            
            var target = $(this).data('tab');
            
            // Aggiorna tab attiva
            $tabLinks.removeClass('active');
            $(this).addClass('active');
            
            // Aggiorna contenuto attivo
            $tabs.removeClass('active');
            $('#' + target).addClass('active');
            
            // Salva stato in localStorage per persistenza
            if (window.localStorage) {
                localStorage.setItem('farmacicase_active_tab', target);
            }
        });
        
        // Ripristina tab attiva da localStorage se possibile
        if (window.localStorage && localStorage.getItem('farmacicase_active_tab')) {
            var savedTab = localStorage.getItem('farmacicase_active_tab');
            var $savedLink = $tabLinks.filter('[data-tab="' + savedTab + '"]');
            
            if ($savedLink.length) {
                $savedLink.trigger('click');
            }
        }
    }
    
})(jQuery);