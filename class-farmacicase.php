<?php
/**
* La classe principale del plugin FarmaciCase.
*
* Questa classe definisce e coordina tutte le funzionalità del plugin.
*
* @since      1.0.0
* @package    FarmaciCase
*/

// Se questo file viene chiamato direttamente, interrompi
if (!defined('ABSPATH')) {
   exit;
}

/**
* Classe principale che coordina il plugin.
*/
class FarmaciCase {
   
   /**
    * L'istanza dell'API REST.
    *
    * @since    1.0.0
    * @access   protected
    * @var      FarmaciCase_API    $api    Gestisce tutte le API REST.
    */
   protected $api;
   
   /**
    * L'istanza dell'autenticazione.
    *
    * @since    1.0.0
    * @access   protected
    * @var      FarmaciCase_Auth    $auth    Gestisce autenticazione e permessi.
    */
   protected $auth;
   
   /**
    * L'istanza delle notifiche.
    *
    * @since    1.0.0
    * @access   protected
    * @var      FarmaciCase_Notifications    $notifications    Gestisce le notifiche.
    */
   protected $notifications;
   
   /**
    * Inizializza le classi principali del plugin.
    *
    * @since    1.0.0
    */
   public function __construct() {
       $this->load_dependencies();
       $this->set_locale();
       $this->init_components();
   }
   
   /**
    * Carica le dipendenze necessarie.
    *
    * @since    1.0.0
    * @access   private
    */
   private function load_dependencies() {
       // Già caricate nel file principale
   }
   
   /**
    * Definisce le impostazioni di localizzazione del plugin.
    *
    * @since    1.0.0
    * @access   private
    */
   private function set_locale() {
       // Già gestito nel file principale
   }
   
   /**
    * Inizializza i componenti del plugin.
    *
    * @since    1.0.0
    * @access   private
    */
   private function init_components() {
       // Inizializza l'API REST
       $this->api = new FarmaciCase_API();
       
       // Inizializza la gestione autenticazione
       $this->auth = new FarmaciCase_Auth();
       
       // Inizializza la gestione notifiche
       $this->notifications = new FarmaciCase_Notifications();
   }
   
   /**
    * Esegue il plugin.
    *
    * Avvia l'esecuzione effettiva del plugin registrando tutti 
    * i hook necessari per le API, l'autenticazione e le notifiche.
    *
    * @since    1.0.0
    */
   public function run() {
       // Registra gli endpoint API
       $this->api->register_routes();
       
       // Aggancia l'evento delle notifiche settimanali
       add_action('farmacicase_weekly_notifications', array($this->notifications, 'send_weekly_notifications'));
       
        // Aggiungi controllo iniziale connessione
        add_action('init', array($this, 'check_db_status'));

       // Registro gli script e stili per admin
       add_action('admin_enqueue_scripts', array($this, 'register_admin_assets'));
       
       // AJAX handlers per operazioni frontend
       add_action('wp_ajax_farmacicase_get_houses', array($this, 'ajax_get_houses'));
       add_action('wp_ajax_farmacicase_get_medications', array($this, 'ajax_get_medications'));
       add_action('wp_ajax_farmacicase_save_medication', array($this, 'ajax_save_medication'));
       add_action('wp_ajax_farmacicase_delete_medication', array($this, 'ajax_delete_medication'));
   }


   public function check_db_status() {
        global $wpdb;
        
        try {
            $wpdb->query('SELECT 1');
        } catch (Exception $e) {
            $this->notify_admin('Errore Database', $e->getMessage());
        }
    }
    
    private function notify_admin($subject, $message) {
        wp_mail(
            get_option('admin_email'),
            '[FarmaciCase Alert] ' . $subject,
            $message . "\n\n" . 'Data: ' . current_time('mysql')
        );
    }

   
   /**
    * Registra script e stili per l'admin.
    *
    * @since    1.0.0
    */
   public function register_admin_assets() {
       wp_enqueue_style('farmacicase-admin-css', FARMACICASE_PLUGIN_URL . 'admin/css/farmacicase-admin.css', array(), FARMACICASE_VERSION);
       wp_enqueue_script('farmacicase-admin-js', FARMACICASE_PLUGIN_URL . 'admin/js/farmacicase-admin.js', array('jquery'), FARMACICASE_VERSION, true);
   }
   
   /**
    * Handler AJAX per ottenere le Case di Comunità.
    *
    * @since    1.0.0
    */
   public function ajax_get_houses() {
       // Verifica nonce
       check_ajax_referer('farmacicase_nonce', 'security');
       
       // Verifica permessi
       if (!$this->auth->can_view_houses()) {
           wp_send_json_error(array('message' => __('Permessi insufficienti', 'farmacicase')));
       }
       
       $user_id = get_current_user_id();
       $houses = $this->auth->get_user_houses($user_id);
       
       wp_send_json_success(array(
           'houses' => $houses
       ));
   }
   
   /**
    * Handler AJAX per ottenere i farmaci.
    *
    * @since    1.0.0
    */
   public function ajax_get_medications() {
       // Verifica nonce
       check_ajax_referer('farmacicase_nonce', 'security');
       
       // Verifica permessi
       if (!$this->auth->can_view_medications()) {
           wp_send_json_error(array('message' => __('Permessi insufficienti', 'farmacicase')));
       }
       
       // Ottieni parametri
       $house_id = isset($_REQUEST['house_id']) ? intval($_REQUEST['house_id']) : 0;
       
       // Verifica che l'utente possa vedere questa casa
       if ($house_id && !$this->auth->can_view_house(get_current_user_id(), $house_id)) {
           wp_send_json_error(array('message' => __('Non puoi visualizzare questa casa', 'farmacicase')));
       }
       
       global $wpdb;
       $table_prefix = $wpdb->prefix;
       
       $query = "SELECT * FROM {$table_prefix}fc_medications";
       $params = array();
       
       if ($house_id) {
           $query .= " WHERE house_id = %d";
           $params[] = $house_id;
       } else if (!$this->auth->is_admin()) {
           // Se non è admin, deve vedere solo le sue case
           $user_houses = $this->auth->get_user_houses(get_current_user_id());
           if (empty($user_houses)) {
               wp_send_json_error(array('message' => __('Nessuna casa assegnata', 'farmacicase')));
           }
           
           $house_ids = wp_list_pluck($user_houses, 'id');
           $placeholders = implode(',', array_fill(0, count($house_ids), '%d'));
           $query .= " WHERE house_id IN ($placeholders)";
           $params = array_merge($params, $house_ids);
       }
       
       if (!empty($params)) {
           $query = $wpdb->prepare($query, $params);
       }
       
       $medications = $wpdb->get_results($query);
       
       wp_send_json_success(array(
           'medications' => $medications
       ));
   }
   
   /**
    * Handler AJAX per salvare un farmaco.
    *
    * @since    1.0.0
    */
   public function ajax_save_medication() {
       // Verifica nonce
       check_ajax_referer('farmacicase_nonce', 'security');
       
       // Verifica permessi
       if (!$this->auth->can_manage_medications()) {
           wp_send_json_error(array('message' => __('Permessi insufficienti', 'farmacicase')));
       }
       
       // Ottieni parametri
       $medication_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
       $house_id = isset($_POST['house_id']) ? intval($_POST['house_id']) : 0;
       
       // Verifica che l'utente possa gestire questa casa
       if (!$this->auth->can_manage_house(get_current_user_id(), $house_id)) {
           wp_send_json_error(array('message' => __('Non puoi gestire questa casa', 'farmacicase')));
       }
       
       // Prepara dati
       $data = array(
           'house_id' => $house_id,
           'commercial_name' => sanitize_text_field($_POST['commercial_name']),
           'active_ingredient' => sanitize_text_field($_POST['active_ingredient']),
           'description' => sanitize_textarea_field($_POST['description']),
           'leaflet_url' => esc_url_raw($_POST['leaflet_url']),
           'package_count' => intval($_POST['package_count']),
           'total_quantity' => intval($_POST['total_quantity']),
           'expiration_date' => sanitize_text_field($_POST['expiration_date']),
           'min_quantity_alert' => intval($_POST['min_quantity_alert']),
           'updated_by' => get_current_user_id(),
           'updated_at' => current_time('mysql')
       );
       
       // Validazione
       if (empty($data['commercial_name']) || empty($data['active_ingredient'])) {
           wp_send_json_error(array('message' => __('Nome commerciale e principio attivo sono obbligatori', 'farmacicase')));
       }
       
       global $wpdb;
       $table_prefix = $wpdb->prefix;
       
       if ($medication_id) {
           // Aggiorna
           $result = $wpdb->update(
               "{$table_prefix}fc_medications",
               $data,
               array('id' => $medication_id)
           );
           
           $action = 'update';
       } else {
           // Inserisci
           $data['created_by'] = get_current_user_id();
           $data['created_at'] = current_time('mysql');
           
           $result = $wpdb->insert(
               "{$table_prefix}fc_medications",
               $data
           );
           
           $medication_id = $wpdb->insert_id;
           $action = 'create';
       }
       
       if ($result === false) {
           wp_send_json_error(array('message' => __('Errore nel salvare il farmaco', 'farmacicase')));
       }
       
       // Registra la modifica nello storico
       $this->record_medication_history($medication_id, $action, get_current_user_id());
       
       // Ottieni i dati aggiornati
       $medication = $wpdb->get_row($wpdb->prepare(
           "SELECT * FROM {$table_prefix}fc_medications WHERE id = %d",
           $medication_id
       ));
       
       wp_send_json_success(array(
           'medication' => $medication,
           'message' => __('Farmaco salvato con successo', 'farmacicase')
       ));
   }
   
   /**
    * Handler AJAX per eliminare un farmaco.
    *
    * @since    1.0.0
    */
   public function ajax_delete_medication() {
       // Verifica nonce
       check_ajax_referer('farmacicase_nonce', 'security');
       
       // Verifica permessi
       if (!$this->auth->can_manage_medications()) {
           wp_send_json_error(array('message' => __('Permessi insufficienti', 'farmacicase')));
       }
       
       // Ottieni parametri
       $medication_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
       
       if (!$medication_id) {
           wp_send_json_error(array('message' => __('ID farmaco mancante', 'farmacicase')));
       }
       
       global $wpdb;
       $table_prefix = $wpdb->prefix;
       
       // Verifica che l'utente possa gestire questa casa
       $medication = $wpdb->get_row($wpdb->prepare(
           "SELECT * FROM {$table_prefix}fc_medications WHERE id = %d",
           $medication_id
       ));
       
       if (!$medication) {
           wp_send_json_error(array('message' => __('Farmaco non trovato', 'farmacicase')));
       }
       
       if (!$this->auth->can_manage_house(get_current_user_id(), $medication->house_id)) {
           wp_send_json_error(array('message' => __('Non puoi gestire questa casa', 'farmacicase')));
       }
       
       // Registra l'eliminazione nello storico prima di eliminare
       $this->record_medication_history($medication_id, 'delete', get_current_user_id());
       
       // Elimina
       $result = $wpdb->delete(
           "{$table_prefix}fc_medications",
           array('id' => $medication_id)
       );
       
       if ($result === false) {
           wp_send_json_error(array('message' => __('Errore nell\'eliminare il farmaco', 'farmacicase')));
       }
       
       wp_send_json_success(array(
           'message' => __('Farmaco eliminato con successo', 'farmacicase')
       ));
   }
   
   /**
    * Registra una modifica nello storico farmaci.
    *
    * @since    1.0.0
    * @param    int       $medication_id    ID del farmaco.
    * @param    string    $action           Tipo di azione (create, update, delete).
    * @param    int       $user_id          ID utente che ha eseguito l'azione.
    */
   private function record_medication_history($medication_id, $action, $user_id) {
       global $wpdb;
       $table_prefix = $wpdb->prefix;
       
       // Ottieni dettagli farmaco
       $medication = $wpdb->get_row($wpdb->prepare(
           "SELECT * FROM {$table_prefix}fc_medications WHERE id = %d",
           $medication_id
       ));
       
       if (!$medication && $action !== 'delete') {
           return;
       }
       
       // Prepara dettagli
       $details = json_encode($medication);
       
       // Inserisci nella tabella storico
       $wpdb->insert(
           "{$table_prefix}fc_medication_history",
           array(
               'medication_id' => $medication_id,
               'action' => $action,
               'user_id' => $user_id,
               'details' => $details,
               'created_at' => current_time('mysql')
           )
       );
   }
}