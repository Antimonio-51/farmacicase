<?php
/**
 * Gestione delle API REST per FarmaciCase
 *
 * Questa classe registra e gestisce tutti gli endpoint REST per il plugin.
 *
 * @since      1.0.0
 * @package    FarmaciCase
 */

// Se questo file viene chiamato direttamente, interrompi
if (!defined('ABSPATH')) {
    exit;
}

class FarmaciCase_API {

    /**
     * Registra tutte le rotte API REST.
     *
     * @since    1.0.0
     */
    public function register_routes() {
        add_action('rest_api_init', array($this, 'register_endpoints'));
    }
    
    /**
     * Definisce tutti gli endpoint REST.
     *
     * @since    1.0.0
     */
    public function register_endpoints() {
        // Namespace per tutte le API
        $namespace = 'farmacicase/v1';
        
        // Endpoint per Case di Comunità
        register_rest_route($namespace, '/houses', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_houses'),
            'permission_callback' => array($this, 'check_permission'),
            'args' => array(
                'state' => array(
                    'default' => '',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'status' => array(
                    'default' => '',
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));
        
        register_rest_route($namespace, '/houses', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_house'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'name' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'address' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'city' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'state' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'zip' => array(
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'phone' => array(
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'email' => array(
                    'sanitize_callback' => 'sanitize_email'
                ),
                'status' => array(
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));
        
        register_rest_route($namespace, '/houses/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_house'),
            'permission_callback' => array($this, 'check_view_house_permission'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                )
            )
        ));
        
        register_rest_route($namespace, '/houses/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_house'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ),
                'name' => array(
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'address' => array(
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'city' => array(
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'state' => array(
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'zip' => array(
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'phone' => array(
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'email' => array(
                    'sanitize_callback' => 'sanitize_email'
                ),
                'status' => array(
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));
        
        register_rest_route($namespace, '/houses/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_house'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                )
            )
        ));
        
        // Endpoint per Farmaci
        register_rest_route($namespace, '/medications', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_medications'),
            'permission_callback' => array($this, 'check_permission'),
            'args' => array(
                'house_id' => array(
                    'default' => 0,
                    'sanitize_callback' => 'absint'
                ),
                'search' => array(
                    'default' => '',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'active_ingredient' => array(
                    'default' => '',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'expiring_soon' => array(
                    'default' => false,
                    'sanitize_callback' => function($param) {
                        return filter_var($param, FILTER_VALIDATE_BOOLEAN);
                    }
                ),
                'low_quantity' => array(
                    'default' => false,
                    'sanitize_callback' => function($param) {
                        return filter_var($param, FILTER_VALIDATE_BOOLEAN);
                    }
                )
            )
        ));
        
        register_rest_route($namespace, '/medications', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_medication'),
            'permission_callback' => array($this, 'check_manage_medication_permission'),
            'args' => array(
                'house_id' => array(
                    'required' => true,
                    'sanitize_callback' => 'absint'
                ),
                'commercial_name' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'active_ingredient' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'description' => array(
                    'sanitize_callback' => 'sanitize_textarea_field'
                ),
                'leaflet_url' => array(
                    'sanitize_callback' => 'esc_url_raw'
                ),
                'package_count' => array(
                    'required' => true,
                    'sanitize_callback' => 'absint'
                ),
                'total_quantity' => array(
                    'required' => true,
                    'sanitize_callback' => 'absint'
                ),
                'expiration_date' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'min_quantity_alert' => array(
                    'required' => true,
                    'sanitize_callback' => 'absint'
                )
            )
        ));
        
        register_rest_route($namespace, '/medications/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_medication'),
            'permission_callback' => array($this, 'check_view_medication_permission'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                )
            )
        ));
        
        register_rest_route($namespace, '/medications/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_medication'),
            'permission_callback' => array($this, 'check_manage_medication_permission'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ),
                'house_id' => array(
                    'sanitize_callback' => 'absint'
                ),
                'commercial_name' => array(
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'active_ingredient' => array(
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'description' => array(
                    'sanitize_callback' => 'sanitize_textarea_field'
                ),
                'leaflet_url' => array(
                    'sanitize_callback' => 'esc_url_raw'
                ),
                'package_count' => array(
                    'sanitize_callback' => 'absint'
                ),
                'total_quantity' => array(
                    'sanitize_callback' => 'absint'
                ),
                'expiration_date' => array(
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'min_quantity_alert' => array(
                    'sanitize_callback' => 'absint'
                )
            )
        ));
        
        register_rest_route($namespace, '/medications/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_medication'),
            'permission_callback' => array($this, 'check_manage_medication_permission'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                )
            )
        ));
        
        // Endpoint per Utenti
        register_rest_route($namespace, '/users', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_users'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'role' => array(
                    'default' => '',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'house_id' => array(
                    'default' => 0,
                    'sanitize_callback' => 'absint'
                )
            )
        ));
        
        register_rest_route($namespace, '/users', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_user'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'email' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_email'
                ),
                'first_name' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'last_name' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'role' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'phone' => array(
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'house_ids' => array(
                    'default' => array(),
                    'sanitize_callback' => function($param) {
                        return array_map('absint', (array) $param);
                    }
                ),
                'password' => array(
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));
        
        register_rest_route($namespace, '/users/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_user'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                )
            )
        ));
        
        register_rest_route($namespace, '/users/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_user'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ),
                'email' => array(
                    'sanitize_callback' => 'sanitize_email'
                ),
                'first_name' => array(
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'last_name' => array(
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'role' => array(
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'phone' => array(
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'house_ids' => array(
                    'sanitize_callback' => function($param) {
                        return array_map('absint', (array) $param);
                    }
                ),
                'password' => array(
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));
        
        register_rest_route($namespace, '/users/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_user'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                )
            )
        ));
    }
    
    /**
     * Verifica se l'utente è autenticato (permessi di base).
     *
     * @since    1.0.0
     * @return   bool    True se l'utente ha i permessi, false altrimenti.
     */
    public function check_permission() {
        return is_user_logged_in();
    }
    
 /**
     * Verifica se l'utente ha permessi di amministratore.
     *
     * @since    1.0.0
     * @return   bool    True se l'utente ha i permessi di admin, false altrimenti.
     */
    public function check_admin_permission() {
        if (!is_user_logged_in()) {
            return false;
        }
        
        // Carica la classe di autenticazione
        require_once FARMACICASE_PLUGIN_DIR . 'includes/class-farmacicase-auth.php';
        $auth = new FarmaciCase_Auth();
        
        return $auth->is_admin(get_current_user_id());
    }
    
    /**
     * Verifica se l'utente può visualizzare una casa specifica.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    La richiesta REST.
     * @return   bool               True se l'utente può visualizzare la casa, false altrimenti.
     */
    public function check_view_house_permission($request) {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $house_id = $request->get_param('id');
        
        // Carica la classe di autenticazione
        require_once FARMACICASE_PLUGIN_DIR . 'includes/class-farmacicase-auth.php';
        $auth = new FarmaciCase_Auth();
        
        return $auth->can_view_house(get_current_user_id(), $house_id);
    }
    
    /**
     * Verifica se l'utente può visualizzare un farmaco specifico.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    La richiesta REST.
     * @return   bool               True se l'utente può visualizzare il farmaco, false altrimenti.
     */
    public function check_view_medication_permission($request) {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $medication_id = $request->get_param('id');
        
        // Ottieni la casa associata al farmaco
        global $wpdb;
        $table_prefix = $wpdb->prefix;
        
        $house_id = $wpdb->get_var($wpdb->prepare(
            "SELECT house_id FROM {$table_prefix}fc_medications WHERE id = %d",
            $medication_id
        ));
        
        if (!$house_id) {
            return false;
        }
        
        // Carica la classe di autenticazione
        require_once FARMACICASE_PLUGIN_DIR . 'includes/class-farmacicase-auth.php';
        $auth = new FarmaciCase_Auth();
        
        return $auth->can_view_house(get_current_user_id(), $house_id);
    }
    
    /**
     * Verifica se l'utente può gestire un farmaco specifico.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    La richiesta REST.
     * @return   bool               True se l'utente può gestire il farmaco, false altrimenti.
     */
    public function check_manage_medication_permission($request) {
        if (!is_user_logged_in()) {
            return false;
        }
        
        // Per le richieste POST (creazione nuovi farmaci), controlla il parametro house_id
        if ($request->get_method() === 'POST') {
            $house_id = $request->get_param('house_id');
            
            // Carica la classe di autenticazione
            require_once FARMACICASE_PLUGIN_DIR . 'includes/class-farmacicase-auth.php';
            $auth = new FarmaciCase_Auth();
            
            return $auth->can_manage_house(get_current_user_id(), $house_id);
        }
        
        // Per PUT e DELETE, ottieni l'ID farmaco dal percorso
        $medication_id = $request->get_param('id');
        
        // Ottieni la casa associata al farmaco
        global $wpdb;
        $table_prefix = $wpdb->prefix;
        
        $house_id = $wpdb->get_var($wpdb->prepare(
            "SELECT house_id FROM {$table_prefix}fc_medications WHERE id = %d",
            $medication_id
        ));
        
        if (!$house_id) {
            return false;
        }
        
        // Carica la classe di autenticazione
        require_once FARMACICASE_PLUGIN_DIR . 'includes/class-farmacicase-auth.php';
        $auth = new FarmaciCase_Auth();
        
        return $auth->can_manage_house(get_current_user_id(), $house_id);
    }
    
    /**
     * Ottiene tutte le Case di Comunità disponibili.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    La richiesta REST.
     * @return   WP_REST_Response   Risposta contenente le case.
     */
    public function get_houses($request) {
        global $wpdb;
        $table_prefix = $wpdb->prefix;
        
        // Parametri di filtro
        $state = $request->get_param('state');
        $status = $request->get_param('status');
        
        // Carica la classe di autenticazione
        require_once FARMACICASE_PLUGIN_DIR . 'includes/class-farmacicase-auth.php';
        $auth = new FarmaciCase_Auth();
        
        $user_id = get_current_user_id();
        
        // Query base
        $query = "SELECT * FROM {$table_prefix}fc_houses";
        $where_clauses = array();
        $params = array();
        
        // Filtro per stato
        if (!empty($state)) {
            $where_clauses[] = "state = %s";
            $params[] = $state;
        }
        
        // Filtro per status
        if (!empty($status)) {
            $where_clauses[] = "status = %s";
            $params[] = $status;
        }
        
        // Se non è admin, mostra solo le case assegnate
        if (!$auth->is_admin($user_id)) {
            $user_houses = $auth->get_user_houses($user_id);
            if (empty($user_houses)) {
                return rest_ensure_response(array());
            }
            
            $house_ids = array_column($user_houses, 'id');
            $placeholders = implode(',', array_fill(0, count($house_ids), '%d'));
            $where_clauses[] = "id IN ($placeholders)";
            $params = array_merge($params, $house_ids);
        }
        
        // Combina le condizioni WHERE
        if (!empty($where_clauses)) {
            $query .= " WHERE " . implode(' AND ', $where_clauses);
        }
        
        // Ordina per nome
        $query .= " ORDER BY name ASC";
        
        // Prepara la query se ci sono parametri
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }
        
        $houses = $wpdb->get_results($query);
        
        return rest_ensure_response($houses);
    }
    
    /**
     * Ottiene una specifica Casa di Comunità.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    La richiesta REST.
     * @return   WP_REST_Response   Risposta contenente la casa.
     */
    public function get_house($request) {
        global $wpdb;
        $table_prefix = $wpdb->prefix;
        
        $house_id = $request->get_param('id');
        
        $house = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_prefix}fc_houses WHERE id = %d",
            $house_id
        ));
        
        if (empty($house)) {
            return new WP_Error(
                'farmacicase_no_house',
                __('Casa di Comunità non trovata', 'farmacicase'),
                array('status' => 404)
            );
        }
        
        return rest_ensure_response($house);
    }
    
    /**
     * Crea una nuova Casa di Comunità.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    La richiesta REST.
     * @return   WP_REST_Response   Risposta contenente la casa creata.
     */
    public function create_house($request) {
        global $wpdb;
        $table_prefix = $wpdb->prefix;
        
        $params = $request->get_params();
        
        // Prepara dati
        $data = array(
            'name' => $params['name'],
            'address' => $params['address'],
            'city' => $params['city'],
            'state' => $params['state'],
            'zip' => isset($params['zip']) ? $params['zip'] : '',
            'phone' => isset($params['phone']) ? $params['phone'] : '',
            'email' => isset($params['email']) ? $params['email'] : '',
            'status' => isset($params['status']) ? $params['status'] : 'active'
        );
        
        // Validazione
        if (empty($data['name']) || empty($data['address']) || empty($data['city']) || empty($data['state'])) {
            return new WP_Error(
                'farmacicase_missing_required',
                __('Campi obbligatori mancanti', 'farmacicase'),
                array('status' => 400)
            );
        }
        
        // Inserisci
        $result = $wpdb->insert(
            "{$table_prefix}fc_houses",
            $data
        );
        
        if (!$result) {
            return new WP_Error(
                'farmacicase_db_error',
                __('Errore nel creare la Casa di Comunità', 'farmacicase'),
                array('status' => 500)
            );
        }
        
        $house_id = $wpdb->insert_id;
        
        // Ottieni i dati inseriti
        $house = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_prefix}fc_houses WHERE id = %d",
            $house_id
        ));
        
        return rest_ensure_response($house);
    }
    
    /**
     * Aggiorna una Casa di Comunità esistente.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    La richiesta REST.
     * @return   WP_REST_Response   Risposta contenente la casa aggiornata.
     */
    public function update_house($request) {
        global $wpdb;
        $table_prefix = $wpdb->prefix;
        
        $house_id = $request->get_param('id');
        $params = $request->get_params();
        
        // Verifica esistenza
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table_prefix}fc_houses WHERE id = %d",
            $house_id
        ));
        
        if (!$existing) {
            return new WP_Error(
                'farmacicase_no_house',
                __('Casa di Comunità non trovata', 'farmacicase'),
                array('status' => 404)
            );
        }
        
        // Prepara dati
        $data = array();
        
        if (isset($params['name'])) {
            $data['name'] = $params['name'];
        }
        
        if (isset($params['address'])) {
            $data['address'] = $params['address'];
        }
        
        if (isset($params['city'])) {
            $data['city'] = $params['city'];
        }
        
        if (isset($params['state'])) {
            $data['state'] = $params['state'];
        }
        
        if (isset($params['zip'])) {
            $data['zip'] = $params['zip'];
        }
        
        if (isset($params['phone'])) {
            $data['phone'] = $params['phone'];
        }
        
        if (isset($params['email'])) {
            $data['email'] = $params['email'];
        }
        
        if (isset($params['status'])) {
            $data['status'] = $params['status'];
        }
        
        // Aggiorna
        $result = $wpdb->update(
            "{$table_prefix}fc_houses",
            $data,
            array('id' => $house_id)
        );
        
        if ($result === false) {
            return new WP_Error(
                'farmacicase_db_error',
                __('Errore nell\'aggiornare la Casa di Comunità', 'farmacicase'),
                array('status' => 500)
            );
        }
        
        // Ottieni i dati aggiornati
        $house = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_prefix}fc_houses WHERE id = %d",
            $house_id
        ));
        
        return rest_ensure_response($house);
    }
    
    /**
     * Elimina una Casa di Comunità.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    La richiesta REST.
     * @return   WP_REST_Response   Risposta di successo.
     */
    public function delete_house($request) {
        global $wpdb;
        $table_prefix = $wpdb->prefix;
        
        $house_id = $request->get_param('id');
        
        // Verifica esistenza
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table_prefix}fc_houses WHERE id = %d",
            $house_id
        ));
        
        if (!$existing) {
            return new WP_Error(
                'farmacicase_no_house',
                __('Casa di Comunità non trovata', 'farmacicase'),
                array('status' => 404)
            );
        }
        
        // Elimina
        $result = $wpdb->delete(
            "{$table_prefix}fc_houses",
            array('id' => $house_id)
        );
        
        if (!$result) {
            return new WP_Error(
                'farmacicase_db_error',
                __('Errore nell\'eliminare la Casa di Comunità', 'farmacicase'),
                array('status' => 500)
            );
        }
        
        return rest_ensure_response(array(
            'deleted' => true,
            'message' => __('Casa di Comunità eliminata con successo', 'farmacicase')
        ));
    }
    
    /**
     * Ottiene tutti i farmaci.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    La richiesta REST.
     * @return   WP_REST_Response   Risposta contenente i farmaci.
     */
    public function get_medications($request) {
        global $wpdb;
        $table_prefix = $wpdb->prefix;
        
        // Parametri di filtro
        $house_id = $request->get_param('house_id');
        $search = $request->get_param('search');
        $active_ingredient = $request->get_param('active_ingredient');
        $expiring_soon = $request->get_param('expiring_soon');
        $low_quantity = $request->get_param('low_quantity');
        
        // Carica la classe di autenticazione
        require_once FARMACICASE_PLUGIN_DIR . 'includes/class-farmacicase-auth.php';
        $auth = new FarmaciCase_Auth();
        
        $user_id = get_current_user_id();
        
        // Query base
        $query = "SELECT m.* FROM {$table_prefix}fc_medications m";
        $where_clauses = array();
        $params = array();
        
        // Filtro per casa
        if (!empty($house_id)) {
            $where_clauses[] = "m.house_id = %d";
            $params[] = $house_id;
        } else if (!$auth->is_admin($user_id)) {
            // Se non è admin, deve vedere solo le sue case
            $user_houses = $auth->get_user_houses($user_id);
            if (empty($user_houses)) {
                return rest_ensure_response(array());
            }
            
            $house_ids = array_column($user_houses, 'id');
            $placeholders = implode(',', array_fill(0, count($house_ids), '%d'));
            $where_clauses[] = "m.house_id IN ($placeholders)";
            $params = array_merge($params, $house_ids);
        }
        
        // Filtro per ricerca testuale
        if (!empty($search)) {
            $where_clauses[] = "(m.commercial_name LIKE %s OR m.active_ingredient LIKE %s OR m.description LIKE %s)";
            $search_param = '%' . $wpdb->esc_like($search) . '%';
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
        }
        
        // Filtro per principio attivo
        if (!empty($active_ingredient)) {
            $where_clauses[] = "m.active_ingredient LIKE %s";
            $params[] = '%' . $wpdb->esc_like($active_ingredient) . '%';
        }
        
        // Filtro per farmaci in scadenza
        if ($expiring_soon) {
            $days = get_option('farmacicase_expiration_days', 60);
            $where_clauses[] = "m.expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL %d DAY)";
            $params[] = $days;
        }
        
        // Filtro per farmaci sotto soglia
        if ($low_quantity) {
            $where_clauses[] = "m.total_quantity <= m.min_quantity_alert";
        }
        
        // Combina le condizioni WHERE
        if (!empty($where_clauses)) {
            $query .= " WHERE " . implode(' AND ', $where_clauses);
        }
        
        // Ordina per nome
        $query .= " ORDER BY m.commercial_name ASC";
        
        // Prepara la query se ci sono parametri
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }
        
        $medications = $wpdb->get_results($query);
        
        return rest_ensure_response($medications);
    }
    
    /**
     * Ottiene un singolo farmaco.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    La richiesta REST.
     * @return   WP_REST_Response   Risposta contenente il farmaco.
     */
    public function get_medication($request) {
        global $wpdb;
        $table_prefix = $wpdb->prefix;
        
        $medication_id = $request->get_param('id');
        
        $medication = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_prefix}fc_medications WHERE id = %d",
            $medication_id
        ));
        
        if (empty($medication)) {
            return new WP_Error(
                'farmacicase_no_medication',
                __('Farmaco non trovato', 'farmacicase'),
                array('status' => 404)
            );
        }
        
        return rest_ensure_response($medication);
    }
    
    /**
     * Crea un nuovo farmaco.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    La richiesta REST.
     * @return   WP_REST_Response   Risposta contenente il farmaco creato.
     */
    public function create_medication($request) {
        global $wpdb;
        $table_prefix = $wpdb->prefix;
        
        $params = $request->get_params();
        
        // Prepara dati
        $data = array(
            'house_id' => $params['house_id'],
            'commercial_name' => $params['commercial_name'],
            'active_ingredient' => $params['active_ingredient'],
            'description' => isset($params['description']) ? $params['description'] : '',
            'leaflet_url' => isset($params['leaflet_url']) ? $params['leaflet_url'] : '',
            'package_count' => $params['package_count'],
            'total_quantity' => $params['total_quantity'],
            'expiration_date' => $params['expiration_date'],
            'min_quantity_alert' => $params['min_quantity_alert'],
            'created_by' => get_current_user_id()
        );
        
        // Validazione
        if (empty($data['commercial_name']) || empty($data['active_ingredient'])) {
            return new WP_Error(
                'farmacicase_missing_required',
                __('Nome commerciale e principio attivo sono obbligatori', 'farmacicase'),
                array('status' => 400)
            );
        }
        
        // Verifica casa esistente
        $house_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table_prefix}fc_houses WHERE id = %d",
            $data['house_id']
        ));
        
        if (!$house_exists) {
            return new WP_Error(
                'farmacicase_no_house',
                __('Casa di Comunità non trovata', 'farmacicase'),
                array('status' => 404)
            );
        }
        
        // Inserisci
        $result = $wpdb->insert(
            "{$table_prefix}fc_medications",
            $data
        );
        
        if (!$result) {
            return new WP_Error(
                'farmacicase_db_error',
                __('Errore nel creare il farmaco', 'farmacicase'),
                array('status' => 500)
            );
        }
        
        $medication_id = $wpdb->insert_id;
        
        // Registra storico
        $this->record_medication_history($medication_id, 'create', get_current_user_id(), $data);
        
        // Ottieni i dati inseriti
        $medication = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_prefix}fc_medications WHERE id = %d",
            $medication_id
        ));
        
        return rest_ensure_response($medication);
    }
    
    /**
     * Aggiorna un farmaco esistente.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    La richiesta REST.
     * @return   WP_REST_Response   Risposta contenente il farmaco aggiornato.
     */
    public function update_medication($request) {
        global $wpdb;
        $table_prefix = $wpdb->prefix;
        
        $medication_id = $request->get_param('id');
        $params = $request->get_params();
        
        // Verifica esistenza
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_prefix}fc_medications WHERE id = %d",
            $medication_id
        ));
        
        if (!$existing) {
            return new WP_Error(
                'farmacicase_no_medication',
                __('Farmaco non trovato', 'farmacicase'),
                array('status' => 404)
            );
        }
        
        // Prepara dati
        $data = array();
        
        if (isset($params['house_id'])) {
            // Verifica casa esistente
            $house_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$table_prefix}fc_houses WHERE id = %d",
                $params['house_id']
            ));
            
            if (!$house_exists) {
                return new WP_Error(
                    'farmacicase_no_house',
                    __('Casa di Comunità non trovata', 'farmacicase'),
                    array('status' => 404)
                );
            }
            
            $data['house_id'] = $params['house_id'];
        }
        
        if (isset($params['commercial_name'])) {
            $data['commercial_name'] = $params['commercial_name'];
        }
        
        if (isset($params['active_ingredient'])) {
            $data['active_ingredient'] = $params['active_ingredient'];
        }
        
        if (isset($params['description'])) {
            $data['description'] = $params['description'];
        }
        
        if (isset($params['leaflet_url'])) {
            $data['leaflet_url'] = $params['leaflet_url'];
        }
        
        if (isset($params['package_count'])) {
            $data['package_count'] = $params['package_count'];
        }
        
        if (isset($params['total_quantity'])) {
            $data['total_quantity'] = $params['total_quantity'];
        }
        
        if (isset($params['expiration_date'])) {
            $data['expiration_date'] = $params['expiration_date'];
        }
        
        if (isset($params['min_quantity_alert'])) {
            $data['min_quantity_alert'] = $params['min_quantity_alert'];
        }
        
        $data['updated_by'] = get_current_user_id();
        
        // Aggiorna
        $result = $wpdb->update(
            "{$table_prefix}fc_medications",
            $data,
            array('id' => $medication_id)
        );
        
        if ($result === false) {
            return new WP_Error(
                'farmacicase_db_error',
                __('Errore nell\'aggiornare il farmaco', 'farmacicase'),
                array('status' => 500)
            );
        }
        
        // Registra storico
        $this->record_medication_history($medication_id, 'update', get_current_user_id(), $data);
        
        // Ottieni i dati aggiornati
        $medication = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_prefix}fc_medications WHERE id = %d",
            $medication_id
        ));
        
        return rest_ensure_response($medication);
    }
    
    /**
     * Elimina un farmaco.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    La richiesta REST.
     * @return   WP_REST_Response   Risposta di successo.
     */
    public function delete_medication($request) {
        global $wpdb;
        $table_prefix = $wpdb->prefix;
        
        $medication_id = $request->get_param('id');
        
        // Verifica esistenza
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_prefix}fc_medications WHERE id = %d",
            $medication_id
        ));
        
        if (!$existing) {
            return new WP_Error(
                'farmacicase_no_medication',
                __('Farmaco non trovato', 'farmacicase'),
                array('status' => 404)
            );
        }
        
        // Registra storico prima di eliminare
        $this->record_medication_history($medication_id, 'delete', get_current_user_id(), $existing);
        
        // Elimina
        $result = $wpdb->delete(
            "{$table_prefix}fc_medications",
            array('id' => $medication_id)
        );
        
        if (!$result) {
            return new WP_Error(
                'farmacicase_db_error',
                __('Errore nell\'eliminare il farmaco', 'farmacicase'),
                array('status' => 500)
            );
        }
        
        return rest_ensure_response(array(
            'deleted' => true,
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
     * @param    array     $data             Dati del farmaco.
     */
    private function record_medication_history($medication_id, $action, $user_id, $data = null) {
        global $wpdb;
        $table_prefix = $wpdb->prefix;
        
        // Se non abbiamo i dati, recuperali dal database
        if ($data === null) {
            $data = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table_prefix}fc_medications WHERE id = %d",
                $medication_id
            ), ARRAY_A);
        }
        
        // Prepara dettagli
        $details = json_encode($data);
        
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
    
    /**
     * Ottiene tutti gli utenti FarmaciCase.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    La richiesta REST.
     * @return   WP_REST_Response   Risposta contenente gli utenti.
     */
    public function get_users($request) {
        global $wpdb;
        $table_prefix = $wpdb->prefix;
        
        // Parametri di filtro
        $role = $request->get_param('role');
        $house_id = $request->get_param('house_id');
        
        // Query base
        $query = "SELECT u.*, wu.user_email, wu.display_name 
                 FROM {$table_prefix}fc_users u
                 JOIN {$wpdb->users} wu ON u.wp_user_id = wu.ID";
        
        $where_clauses = array();
        $params = array();
        

        // Filtro per ruolo
        if (!empty($role)) {
            $where_clauses[] = "u.role = %s";
            $params[] = $role;
        }
        
        // Filtro per casa
        if (!empty($house_id)) {
            $query .= " JOIN {$table_prefix}fc_user_houses uh ON u.id = uh.user_id";
            $where_clauses[] = "uh.house_id = %d";
            $params[] = $house_id;
        }
        
        // Combina le condizioni WHERE
        if (!empty($where_clauses)) {
            $query .= " WHERE " . implode(' AND ', $where_clauses);
        }
        
        // Ordina per nome
        $query .= " ORDER BY wu.display_name ASC";
        
        // Prepara la query se ci sono parametri
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }
        
        $users = $wpdb->get_results($query);
        
        // Arricchisci con case associate
        foreach ($users as &$user) {
            $user->houses = $this->get_user_houses($user->id);
        }
        
        return rest_ensure_response($users);
    }
    
    /**
     * Ottiene un singolo utente FarmaciCase.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    La richiesta REST.
     * @return   WP_REST_Response   Risposta contenente l'utente.
     */
    public function get_user($request) {
        global $wpdb;
        $table_prefix = $wpdb->prefix;
        
        $user_id = $request->get_param('id');
        
        $user = $wpdb->get_row($wpdb->prepare(
            "SELECT u.*, wu.user_email, wu.display_name 
             FROM {$table_prefix}fc_users u
             JOIN {$wpdb->users} wu ON u.wp_user_id = wu.ID
             WHERE u.id = %d",
            $user_id
        ));
        
        if (empty($user)) {
            return new WP_Error(
                'farmacicase_no_user',
                __('Utente non trovato', 'farmacicase'),
                array('status' => 404)
            );
        }
        
        // Aggiungi case associate
        $user->houses = $this->get_user_houses($user_id);
        
        return rest_ensure_response($user);
    }
    
    /**
     * Crea un nuovo utente FarmaciCase.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    La richiesta REST.
     * @return   WP_REST_Response   Risposta contenente l'utente creato.
     */
    public function create_user($request) {
        global $wpdb;
        $table_prefix = $wpdb->prefix;
        
        $params = $request->get_params();
        
        // Crea l'utente WordPress se non esiste
        $email = $params['email'];
        $wp_user = get_user_by('email', $email);
        
        if (!$wp_user) {
            // Genera username dal nome
            $username = sanitize_user(
                strtolower($params['first_name'] . '.' . $params['last_name']),
                true
            );
            
            // Assicura username unico
            if (username_exists($username)) {
                $counter = 1;
                while (username_exists($username . $counter)) {
                    $counter++;
                }
                $username = $username . $counter;
            }
            
            // Password
            $password = isset($params['password']) ? $params['password'] : wp_generate_password(12);
            
            // Crea utente WordPress
            $wp_user_id = wp_insert_user(array(
                'user_login' => $username,
                'user_pass' => $password,
                'user_email' => $email,
                'first_name' => $params['first_name'],
                'last_name' => $params['last_name'],
                'display_name' => $params['first_name'] . ' ' . $params['last_name']
            ));
            
            if (is_wp_error($wp_user_id)) {
                return new WP_Error(
                    'farmacicase_wp_user_error',
                    $wp_user_id->get_error_message(),
                    array('status' => 400)
                );
            }
            
            // Aggiungi ruolo WordPress corrispondente
            $user = new WP_User($wp_user_id);
            
            switch ($params['role']) {
                case 'admin':
                    $user->add_role('fc_admin');
                    break;
                case 'responsabile':
                    $user->add_role('fc_responsabile');
                    break;
                case 'medico':
                    $user->add_role('fc_medico');
                    break;
            }
        } else {
            $wp_user_id = $wp_user->ID;
        }
        
        // Verifica se l'utente già esiste nel sistema FarmaciCase
        $existing_fc_user = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table_prefix}fc_users WHERE wp_user_id = %d",
            $wp_user_id
        ));
        
        if ($existing_fc_user) {
            return new WP_Error(
                'farmacicase_user_exists',
                __('Utente già registrato nel sistema FarmaciCase', 'farmacicase'),
                array('status' => 400)
            );
        }
        
        // Crea utente FarmaciCase
        $result = $wpdb->insert(
            "{$table_prefix}fc_users",
            array(
                'wp_user_id' => $wp_user_id,
                'role' => $params['role'],
                'phone' => isset($params['phone']) ? $params['phone'] : ''
            )
        );
        
        if (!$result) {
            return new WP_Error(
                'farmacicase_db_error',
                __('Errore nel creare l\'utente FarmaciCase', 'farmacicase'),
                array('status' => 500)
            );
        }
        
        $fc_user_id = $wpdb->insert_id;
        
        // Assegna Case
        if (!empty($params['house_ids']) && is_array($params['house_ids'])) {
            foreach ($params['house_ids'] as $house_id) {
                $wpdb->insert(
                    "{$table_prefix}fc_user_houses",
                    array(
                        'user_id' => $fc_user_id,
                        'house_id' => $house_id
                    )
                );
            }
        }
        
        // Ottieni i dati completi
        $user = $wpdb->get_row($wpdb->prepare(
            "SELECT u.*, wu.user_email, wu.display_name 
             FROM {$table_prefix}fc_users u
             JOIN {$wpdb->users} wu ON u.wp_user_id = wu.ID
             WHERE u.id = %d",
            $fc_user_id
        ));
        
        // Aggiungi case associate
        $user->houses = $this->get_user_houses($fc_user_id);
        
        // Aggiungi password se è stata creata automaticamente
        if (!isset($params['password']) && isset($password)) {
            $user->generated_password = $password;
        }
        
        return rest_ensure_response($user);
    }
    
    /**
     * Aggiorna un utente FarmaciCase esistente.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    La richiesta REST.
     * @return   WP_REST_Response   Risposta contenente l'utente aggiornato.
     */
    public function update_user($request) {
        global $wpdb;
        $table_prefix = $wpdb->prefix;
        
        $fc_user_id = $request->get_param('id');
        $params = $request->get_params();
        
        // Verifica esistenza
        $user = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_prefix}fc_users WHERE id = %d",
            $fc_user_id
        ));
        
        if (!$user) {
            return new WP_Error(
                'farmacicase_no_user',
                __('Utente FarmaciCase non trovato', 'farmacicase'),
                array('status' => 404)
            );
        }
        
        $wp_user_id = $user->wp_user_id;
        
        // Aggiorna dati WordPress
        $wp_user_data = array();
        
        if (isset($params['email'])) {
            $wp_user_data['user_email'] = $params['email'];
        }
        
        if (isset($params['first_name'])) {
            $wp_user_data['first_name'] = $params['first_name'];
        }
        
        if (isset($params['last_name'])) {
            $wp_user_data['last_name'] = $params['last_name'];
        }
        
        if (!empty($wp_user_data)) {
            $wp_user_data['ID'] = $wp_user_id;
            
            // Aggiorna display name se nome è cambiato
            if (isset($params['first_name']) || isset($params['last_name'])) {
                $wp_user = get_userdata($wp_user_id);
                $first_name = isset($params['first_name']) ? $params['first_name'] : $wp_user->first_name;
                $last_name = isset($params['last_name']) ? $params['last_name'] : $wp_user->last_name;
                $wp_user_data['display_name'] = $first_name . ' ' . $last_name;
            }
            
            // Aggiorna password se fornita
            if (isset($params['password'])) {
                $wp_user_data['user_pass'] = $params['password'];
            }
            
            $wp_user_result = wp_update_user($wp_user_data);
            
            if (is_wp_error($wp_user_result)) {
                return new WP_Error(
                    'farmacicase_wp_user_error',
                    $wp_user_result->get_error_message(),
                    array('status' => 400)
                );
            }
        }
        
        // Aggiorna dati FarmaciCase
        $fc_user_data = array();
        
        if (isset($params['role'])) {
            $fc_user_data['role'] = $params['role'];
            
            // Aggiorna anche ruolo WordPress
            $user = new WP_User($wp_user_id);
            
            // Rimuovi ruoli precedenti
            $user->remove_role('fc_admin');
            $user->remove_role('fc_responsabile');
            $user->remove_role('fc_medico');
            
            // Assegna nuovo ruolo
            switch ($params['role']) {
                case 'admin':
                    $user->add_role('fc_admin');
                    break;
                case 'responsabile':
                    $user->add_role('fc_responsabile');
                    break;
                case 'medico':
                    $user->add_role('fc_medico');
                    break;
            }
        }
        
        if (isset($params['phone'])) {
            $fc_user_data['phone'] = $params['phone'];
        }
        
        if (!empty($fc_user_data)) {
            $wpdb->update(
                "{$table_prefix}fc_users",
                $fc_user_data,
                array('id' => $fc_user_id)
            );
        }
        
        // Aggiorna assegnazioni Case
        if (isset($params['house_ids']) && is_array($params['house_ids'])) {
            // Rimuovi assegnazioni esistenti
            $wpdb->delete(
                "{$table_prefix}fc_user_houses",
                array('user_id' => $fc_user_id)
            );
            
            // Inserisci nuove assegnazioni
            foreach ($params['house_ids'] as $house_id) {
                $wpdb->insert(
                    "{$table_prefix}fc_user_houses",
                    array(
                        'user_id' => $fc_user_id,
                        'house_id' => $house_id
                    )
                );
            }
        }
        
        // Ottieni dati aggiornati
        $updated_user = $wpdb->get_row($wpdb->prepare(
            "SELECT u.*, wu.user_email, wu.display_name 
             FROM {$table_prefix}fc_users u
             JOIN {$wpdb->users} wu ON u.wp_user_id = wu.ID
             WHERE u.id = %d",
            $fc_user_id
        ));
        
        // Aggiungi case associate
        $updated_user->houses = $this->get_user_houses($fc_user_id);
        
        return rest_ensure_response($updated_user);
    }
    
    /**
     * Elimina un utente FarmaciCase.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    La richiesta REST.
     * @return   WP_REST_Response   Risposta di successo.
     */
    public function delete_user($request) {
        global $wpdb;
        $table_prefix = $wpdb->prefix;
        
        $fc_user_id = $request->get_param('id');
        
        // Verifica esistenza
        $user = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_prefix}fc_users WHERE id = %d",
            $fc_user_id
        ));
        
        if (!$user) {
            return new WP_Error(
                'farmacicase_no_user',
                __('Utente FarmaciCase non trovato', 'farmacicase'),
                array('status' => 404)
            );
        }
        
        // Elimina solo i dati FarmaciCase, non l'utente WordPress
        // Rimuovi assegnazioni case
        $wpdb->delete(
            "{$table_prefix}fc_user_houses",
            array('user_id' => $fc_user_id)
        );
        
        // Rimuovi utente FarmaciCase
        $result = $wpdb->delete(
            "{$table_prefix}fc_users",
            array('id' => $fc_user_id)
        );
        
        if (!$result) {
            return new WP_Error(
                'farmacicase_db_error',
                __('Errore nell\'eliminare l\'utente FarmaciCase', 'farmacicase'),
                array('status' => 500)
            );
        }
        
        return rest_ensure_response(array(
            'deleted' => true,
            'message' => __('Utente FarmaciCase eliminato con successo', 'farmacicase')
        ));
    }
    
    /**
     * Ottiene le Case assegnate a un utente.
     *
     * @since    1.0.0
     * @param    int    $user_id    ID dell'utente FarmaciCase.
     * @return   array  Array di Case di Comunità.
     */
    private function get_user_houses($user_id) {
        global $wpdb;
        $table_prefix = $wpdb->prefix;
        
        $houses = $wpdb->get_results($wpdb->prepare(
            "SELECT h.* FROM {$table_prefix}fc_houses h
             JOIN {$table_prefix}fc_user_houses uh ON h.id = uh.house_id
             WHERE uh.user_id = %d
             ORDER BY h.name ASC",
            $user_id
        ));
        
        return $houses;
    }
}