<?php
/**
* Plugin Name: FarmaciCase
* Description: Sistema per il monitoraggio dei farmaci nelle Case di Comunità
* Version: 1.0.0
* Author: Your Name
* Text Domain: farmacicase
* Domain Path: /languages
* License: GPL-2.0+
*/

// Se questo file viene chiamato direttamente, interrompi
if (!defined('ABSPATH')) {
   exit;
}

// Definizioni costanti
define('FARMACICASE_VERSION', '1.0.0');
define('FARMACICASE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FARMACICASE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FARMACICASE_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
* Funzione di attivazione del plugin.
* Crea tabelle database e ruoli utente.
*/
function farmacicase_activate() {
   require_once FARMACICASE_PLUGIN_DIR . 'includes/class-farmacicase-db.php';
   $db = new FarmaciCase_DB();
   $db->create_tables();
   

   // Forza la ricreazione delle tabelle
    if (!$db->create_tables()) {
        wp_die(__('Impossibile creare le tabelle del database. Controlla i log per dettagli.', 'farmacicase'));
    }
    
   // Crea ruoli personalizzati
   add_role(
       'fc_admin',
       __('FarmaciCase Admin', 'farmacicase'),
       array(
           'read' => true,
           'fc_manage_all' => true,
           'fc_manage_houses' => true,
           'fc_manage_users' => true,
           'fc_manage_medications' => true,
           'fc_view_reports' => true
       )
   );
   
   add_role(
       'fc_responsabile',
       __('FarmaciCase Responsabile', 'farmacicase'),
       array(
           'read' => true,
           'fc_manage_medications' => true,
           'fc_view_reports' => true
       )
   );
   
   add_role(
       'fc_medico',
       __('FarmaciCase Medico', 'farmacicase'),
       array(
           'read' => true,
           'fc_view_medications' => true
       )
   );
   
   // Pianifica l'evento settimanale per le notifiche
   if (!wp_next_scheduled('farmacicase_weekly_notifications')) {
       // Imposta per lunedì alle 7:00
       wp_schedule_event(strtotime('next monday 7am'), 'weekly', 'farmacicase_weekly_notifications');
   }
   
   // Imposta la versione del plugin per futuri aggiornamenti
   update_option('farmacicase_version', FARMACICASE_VERSION);
   
   // Pulisci i rewrite rules
   flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'farmacicase_activate');

/**
* Funzione di disattivazione del plugin.
*/
function farmacicase_deactivate() {
   // Rimuove l'evento cron
   wp_clear_scheduled_hook('farmacicase_weekly_notifications');
   
   // Pulisci i rewrite rules
   flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'farmacicase_deactivate');

/**
* Carica i file di traduzione.
*/
function farmacicase_load_textdomain() {
   load_plugin_textdomain('farmacicase', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'farmacicase_load_textdomain');

/**
* Include i file necessari e inizializza il plugin.
*/
function farmacicase_init() {
   // Carica le classi principali
   require_once FARMACICASE_PLUGIN_DIR . 'includes/class-farmacicase.php';
   require_once FARMACICASE_PLUGIN_DIR . 'includes/class-farmacicase-db.php';
   require_once FARMACICASE_PLUGIN_DIR . 'includes/class-farmacicase-auth.php';
   require_once FARMACICASE_PLUGIN_DIR . 'includes/class-farmacicase-api.php';
   require_once FARMACICASE_PLUGIN_DIR . 'includes/class-farmacicase-notifications.php';
   
   // Inizializza il plugin
   $farmacicase = new FarmaciCase();
   $farmacicase->run();
}
add_action('plugins_loaded', 'farmacicase_init');

/**
* Registra gli shortcode.
*/
function farmacicase_shortcode() {
   // Verifica se l'utente è loggato
   if (!is_user_logged_in()) {
       return farmacicase_render_login_form();
   }
   
   // Carica l'auth manager
   require_once FARMACICASE_PLUGIN_DIR . 'includes/class-farmacicase-auth.php';
   $auth = new FarmaciCase_Auth();
   
   // Ottieni il ruolo dell'utente
   $user_id = get_current_user_id();
   $role = $auth->get_user_role($user_id);
   
   // Carica l'interfaccia appropriata
   switch ($role) {
       case 'admin':
           return farmacicase_render_admin_interface();
       case 'responsabile':
           return farmacicase_render_responsabile_interface();
       case 'medico':
           return farmacicase_render_medico_interface();
       default:
           return '<div class="fc-error">' . 
                  __('Non hai i permessi per accedere a questa applicazione.', 'farmacicase') . 
                  '</div>';
   }
}
add_shortcode('farmacicase', 'farmacicase_shortcode');

/**
* Registra lo shortcode per il form di login.
*/
function farmacicase_login_shortcode() {
   return farmacicase_render_login_form();
}
add_shortcode('farmacicase_login', 'farmacicase_login_shortcode');

/**
* Renderizza il form di login.
*/
function farmacicase_render_login_form() {
   ob_start();
   include FARMACICASE_PLUGIN_DIR . 'public/partials/login-form.php';
   return ob_get_clean();
}

/**
* Renderizza l'interfaccia admin.
*/
function farmacicase_render_admin_interface() {
   // Carica CSS e JS
   wp_enqueue_style('farmacicase-main-css', FARMACICASE_PLUGIN_URL . 'public/css/farmacicase-main.css', array(), FARMACICASE_VERSION);
   wp_enqueue_script('farmacicase-main-js', FARMACICASE_PLUGIN_URL . 'public/js/farmacicase-main.js', array('jquery'), FARMACICASE_VERSION, true);
   
   // Aggiungi dati per JavaScript
   wp_localize_script('farmacicase-main-js', 'farmacicase_data', array(
       'ajax_url' => admin_url('admin-ajax.php'),
       'nonce' => wp_create_nonce('farmacicase_nonce'),
       'user_role' => 'admin'
   ));
   
   ob_start();
   include FARMACICASE_PLUGIN_DIR . 'public/partials/admin-interface.php';
   return ob_get_clean();
}

/**
* Renderizza l'interfaccia responsabile.
*/
function farmacicase_render_responsabile_interface() {
   // Carica CSS e JS
   wp_enqueue_style('farmacicase-main-css', FARMACICASE_PLUGIN_URL . 'public/css/farmacicase-main.css', array(), FARMACICASE_VERSION);
   wp_enqueue_script('farmacicase-main-js', FARMACICASE_PLUGIN_URL . 'public/js/farmacicase-main.js', array('jquery'), FARMACICASE_VERSION, true);
   
   // Aggiungi dati per JavaScript
   wp_localize_script('farmacicase-main-js', 'farmacicase_data', array(
       'ajax_url' => admin_url('admin-ajax.php'),
       'nonce' => wp_create_nonce('farmacicase_nonce'),
       'user_role' => 'responsabile'
   ));
   
   ob_start();
   include FARMACICASE_PLUGIN_DIR . 'public/partials/responsabile-interface.php';
   return ob_get_clean();
}

/**
* Renderizza l'interfaccia medico.
*/
function farmacicase_render_medico_interface() {
   // Carica CSS e JS
   wp_enqueue_style('farmacicase-main-css', FARMACICASE_PLUGIN_URL . 'public/css/farmacicase-main.css', array(), FARMACICASE_VERSION);
   wp_enqueue_script('farmacicase-main-js', FARMACICASE_PLUGIN_URL . 'public/js/farmacicase-main.js', array('jquery'), FARMACICASE_VERSION, true);
   
   // Aggiungi dati per JavaScript
   wp_localize_script('farmacicase-main-js', 'farmacicase_data', array(
       'ajax_url' => admin_url('admin-ajax.php'),
       'nonce' => wp_create_nonce('farmacicase_nonce'),
       'user_role' => 'medico'
   ));
   
   ob_start();
   include FARMACICASE_PLUGIN_DIR . 'public/partials/medico-interface.php';
   return ob_get_clean();
}

/**
* Aggiunge la pagina di menu nel pannello admin di WordPress
*/
function farmacicase_admin_menu() {
   add_menu_page(
       __('FarmaciCase', 'farmacicase'),
       __('FarmaciCase', 'farmacicase'),
       'manage_options',
       'farmacicase',
       'farmacicase_admin_page',
       'dashicons-pills',
       30
   );
   
   add_submenu_page(
       'farmacicase',
       __('Impostazioni', 'farmacicase'),
       __('Impostazioni', 'farmacicase'),
       'manage_options',
       'farmacicase-settings',
       'farmacicase_settings_page'
   );
}
add_action('admin_menu', 'farmacicase_admin_menu');

/**
* Renderizza la pagina di amministrazione principale.
*/
function farmacicase_admin_page() {
   // Carica CSS e JS per admin
   wp_enqueue_style('farmacicase-admin-css', FARMACICASE_PLUGIN_URL . 'admin/css/farmacicase-admin.css', array(), FARMACICASE_VERSION);
   wp_enqueue_script('farmacicase-admin-js', FARMACICASE_PLUGIN_URL . 'admin/js/farmacicase-admin.js', array('jquery'), FARMACICASE_VERSION, true);
   
   echo '<div class="wrap">';
   echo '<h1>' . __('FarmaciCase - Dashboard', 'farmacicase') . '</h1>';
   echo '<p>' . __('Benvenuto nel sistema di gestione dei farmaci per le Case di Comunità.', 'farmacicase') . '</p>';
   
   echo '<div class="fc-admin-dashboard">';
   echo '<div class="fc-admin-card">';
   echo '<h2>' . __('Accesso rapido', 'farmacicase') . '</h2>';
   echo '<p><a href="' . esc_url(site_url('/farmacicase/')) . '" class="button button-primary">' . __('Vai all\'applicazione', 'farmacicase') . '</a></p>';
   echo '</div>';
   echo '</div>';
   
   echo '</div>';
}

/**
* Renderizza la pagina delle impostazioni.
*/
function farmacicase_settings_page() {
   // Carica i CSS e JS per admin
   wp_enqueue_style('farmacicase-admin-css', FARMACICASE_PLUGIN_URL . 'admin/css/farmacicase-admin.css', array(), FARMACICASE_VERSION);
   wp_enqueue_script('farmacicase-admin-js', FARMACICASE_PLUGIN_URL . 'admin/js/farmacicase-admin.js', array('jquery'), FARMACICASE_VERSION, true);
   
   include FARMACICASE_PLUGIN_DIR . 'admin/partials/settings-page.php';
}

/**
* Registra le impostazioni del plugin
*/
function farmacicase_register_settings() {
   register_setting('farmacicase_settings', 'farmacicase_email_sender');
   register_setting('farmacicase_settings', 'farmacicase_notification_time');
   register_setting('farmacicase_settings', 'farmacicase_expiration_days', array(
       'type' => 'integer',
       'default' => 60,
       'sanitize_callback' => 'absint',
   ));
}
add_action('admin_init', 'farmacicase_register_settings');