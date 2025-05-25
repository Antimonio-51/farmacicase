<?php
/**
 * Gestione dell'autenticazione e dei permessi per FarmaciCase.
 *
 * Questa classe gestisce l'autenticazione degli utenti, i permessi di accesso
 * e i ruoli nell'applicazione FarmaciCase.
 *
 * @since      1.0.0
 * @package    FarmaciCase
 */

// Se questo file viene chiamato direttamente, interrompi
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe per la gestione dell'autenticazione e dei permessi.
 */
class FarmaciCase_Auth {

    /**
     * Verifica se l'utente ha il ruolo di amministratore in FarmaciCase.
     *
     * @since    1.0.0
     * @param    int       $user_id    ID dell'utente WordPress.
     * @return   boolean   True se l'utente è admin, false altrimenti.
     */
    public function is_admin($user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        if ($user_id === 0) {
            return false;
        }
        
        // Controlla prima i ruoli WordPress
        $user = new WP_User($user_id);
        if ($user->has_cap('administrator') || $user->has_cap('fc_manage_all')) {
            return true;
        }
        
        // Controlla il ruolo nel database FarmaciCase
        global $wpdb;
        $table_prefix = $wpdb->prefix;
        
        $role = $wpdb->get_var($wpdb->prepare(
            "SELECT role FROM {$table_prefix}fc_users 
             WHERE wp_user_id = %d",
            $user_id
        ));
        
        return $role === 'admin';
    }
    
    /**
     * Ottiene il ruolo dell'utente nel sistema FarmaciCase.
     *
     * @since    1.0.0
     * @param    int       $user_id    ID dell'utente WordPress.
     * @return   string   Il ruolo dell'utente (admin, responsabile, medico) o vuoto se non trovato.
     */
    public function get_user_role($user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        if ($user_id === 0) {
            return '';
        }
        
        global $wpdb;
        $table_prefix = $wpdb->prefix;
        
        $role = $wpdb->get_var($wpdb->prepare(
            "SELECT role FROM {$table_prefix}fc_users 
             WHERE wp_user_id = %d",
            $user_id
        ));
        
        // Fallback ai ruoli WordPress se non trovato nel database FarmaciCase
        if (!$role) {
            $user = new WP_User($user_id);
            if ($user->has_cap('administrator') || $user->has_cap('fc_manage_all')) {
                return 'admin';
            }
            if ($user->has_cap('fc_manage_medications')) {
                return 'responsabile';
            }
            if ($user->has_cap('fc_view_medications')) {
                return 'medico';
            }
        }
        
        return $role;
    }
    
    /**
     * Verifica se l'utente può visualizzare una Casa di Comunità.
     *
     * @since    1.0.0
     * @param    int       $user_id    ID dell'utente WordPress.
     * @param    int       $house_id   ID della Casa di Comunità.
     * @return   boolean   True se l'utente può visualizzare la Casa, false altrimenti.
     */
    public function can_view_house($user_id, $house_id) {
        // Admin può visualizzare tutte le Case
        if ($this->is_admin($user_id)) {
            return true;
        }
        
        // Per responsabili e medici, controlla l'assegnazione
        global $wpdb;
        $table_prefix = $wpdb->prefix;
        
        $fc_user_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table_prefix}fc_users WHERE wp_user_id = %d",
            $user_id
        ));
        
        if (!$fc_user_id) {
            return false;
        }
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_prefix}fc_user_houses 
             WHERE user_id = %d AND house_id = %d",
            $fc_user_id, $house_id
        ));
        
        return $count > 0;
    }
    
    /**
     * Verifica se l'utente può gestire una Casa di Comunità (modificare, eliminare).
     *
     * @since    1.0.0
     * @param    int       $user_id    ID dell'utente WordPress.
     * @param    int       $house_id   ID della Casa di Comunità.
     * @return   boolean   True se l'utente può gestire la Casa, false altrimenti.
     */
    public function can_manage_house($user_id, $house_id) {
        // Solo admin o responsabili possono gestire le Case
        
        // Admin può gestire tutte le Case
        if ($this->is_admin($user_id)) {
            return true;
        }
        
        // Controlla se l'utente è un responsabile assegnato a questa Casa
        global $wpdb;
        $table_prefix = $wpdb->prefix;
        
        $fc_user_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table_prefix}fc_users WHERE wp_user_id = %d AND role = 'responsabile'",
            $user_id
        ));
        
        if (!$fc_user_id) {
            return false;
        }
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_prefix}fc_user_houses 
             WHERE user_id = %d AND house_id = %d",
            $fc_user_id, $house_id
        ));
        
        return $count > 0;
    }
    
    /**
     * Verifica se l'utente può visualizzare i farmaci.
     *
     * @since    1.0.0
     * @param    int       $user_id    ID dell'utente WordPress (opzionale, default utente corrente).
     * @return   boolean   True se l'utente può visualizzare i farmaci, false altrimenti.
     */
    public function can_view_medications($user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        if ($user_id === 0) {
            return false;
        }
        
        // Tutti gli utenti FarmaciCase possono visualizzare i farmaci
        // ma limitati alle loro Case
        $role = $this->get_user_role($user_id);
        return in_array($role, ['admin', 'responsabile', 'medico']);
    }
    
    /**
     * Verifica se l'utente può gestire i farmaci (aggiungere, modificare, eliminare).
     *
     * @since    1.0.0
     * @param    int       $user_id    ID dell'utente WordPress (opzionale, default utente corrente).
     * @return   boolean   True se l'utente può gestire i farmaci, false altrimenti.
     */
    public function can_manage_medications($user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        if ($user_id === 0) {
            return false;
        }
        
        // Solo admin e responsabili possono gestire i farmaci
        $role = $this->get_user_role($user_id);
        return in_array($role, ['admin', 'responsabile']);
    }
    
    /**
     * Ottiene le Case di Comunità assegnate all'utente.
     *
     * @since    1.0.0
     * @param    int       $user_id    ID dell'utente WordPress (opzionale, default utente corrente).
     * @return   array    Array di oggetti Case di Comunità.
     */
    public function get_user_houses($user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        if ($user_id === 0) {
            return [];
        }
        
        global $wpdb;
        $table_prefix = $wpdb->prefix;
        
        // Admin vede tutte le Case
        if ($this->is_admin($user_id)) {
            return $wpdb->get_results(
                "SELECT * FROM {$table_prefix}fc_houses 
                 ORDER BY name ASC"
            );
        }
        
        // Altri utenti vedono solo Case assegnate
        $fc_user_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table_prefix}fc_users WHERE wp_user_id = %d",
            $user_id
        ));
        
        if (!$fc_user_id) {
            return [];
        }
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT h.* 
             FROM {$table_prefix}fc_houses h
             JOIN {$table_prefix}fc_user_houses uh ON h.id = uh.house_id
             WHERE uh.user_id = %d
             ORDER BY h.name ASC",
            $fc_user_id
        ));
    }
    
    /**
     * Verifica se l'utente può visualizzare le Case di Comunità.
     *
     * @since    1.0.0
     * @param    int       $user_id    ID dell'utente WordPress (opzionale, default utente corrente).
     * @return   boolean   True se l'utente può visualizzare le Case, false altrimenti.
     */
    public function can_view_houses($user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        if ($user_id === 0) {
            return false;
        }
        
        $role = $this->get_user_role($user_id);
        return in_array($role, ['admin', 'responsabile', 'medico']);
    }
    
    /**
     * Verifica se l'utente può gestire gli utenti.
     *
     * @since    1.0.0
     * @param    int       $user_id    ID dell'utente WordPress (opzionale, default utente corrente).
     * @return   boolean   True se l'utente può gestire gli utenti, false altrimenti.
     */
    public function can_manage_users($user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        if ($user_id === 0) {
            return false;
        }
        
        // Solo admin può gestire gli utenti
        return $this->is_admin($user_id);
    }
    
    /**
     * Ottiene l'ID FarmaciCase dell'utente a partire dall'ID WordPress.
     *
     * @since    1.0.0
     * @param    int       $wp_user_id    ID dell'utente WordPress.
     * @return   int|false   ID dell'utente FarmaciCase o false se non trovato.
     */
    public function get_fc_user_id($wp_user_id) {
        global $wpdb;
        $table_prefix = $wpdb->prefix;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table_prefix}fc_users WHERE wp_user_id = %d",
            $wp_user_id
        ));
    }
    
    /**
     * Ottiene l'ID WordPress dell'utente a partire dall'ID FarmaciCase.
     *
     * @since    1.0.0
     * @param    int       $fc_user_id    ID dell'utente FarmaciCase.
     * @return   int|false   ID dell'utente WordPress o false se non trovato.
     */
    public function get_wp_user_id($fc_user_id) {
        global $wpdb;
        $table_prefix = $wpdb->prefix;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT wp_user_id FROM {$table_prefix}fc_users WHERE id = %d",
            $fc_user_id
        ));
    }
    
    /**
     * Verifica se un utente esiste nel sistema FarmaciCase.
     *
     * @since    1.0.0
     * @param    int       $wp_user_id    ID dell'utente WordPress.
     * @return   boolean   True se l'utente esiste, false altrimenti.
     */
    public function fc_user_exists($wp_user_id) {
        global $wpdb;
        $table_prefix = $wpdb->prefix;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_prefix}fc_users WHERE wp_user_id = %d",
            $wp_user_id
        ));
        
        return $count > 0;
    }
    
    /**
     * Registra un utente WordPress esistente nel sistema FarmaciCase.
     *
     * @since    1.0.0
     * @param    int       $wp_user_id    ID dell'utente WordPress.
     * @param    string    $role          Ruolo FarmaciCase (admin, responsabile, medico).
     * @param    string    $phone         Numero di telefono (opzionale).
     * @return   int|false L'ID dell'utente FarmaciCase creato o false in caso di errore.
     */
    public function register_fc_user($wp_user_id, $role, $phone = '') {
        global $wpdb;
        $table_prefix = $wpdb->prefix;
        
        // Verifica se l'utente WordPress esiste
        $wp_user = get_userdata($wp_user_id);
        if (!$wp_user) {
            return false;
        }
        
        // Verifica se l'utente è già registrato in FarmaciCase
        if ($this->fc_user_exists($wp_user_id)) {
            return false;
        }
        
        // Verifica che il ruolo sia valido
        if (!in_array($role, ['admin', 'responsabile', 'medico'])) {
            return false;
        }
        
        // Inserisci l'utente nel database FarmaciCase
        $result = $wpdb->insert(
            "{$table_prefix}fc_users",
            array(
                'wp_user_id' => $wp_user_id,
                'role' => $role,
                'phone' => sanitize_text_field($phone)
            )
        );
        
        if (!$result) {
            return false;
        }
        
        // Aggiungi anche il ruolo WordPress corrispondente
        $wp_user->add_role('fc_' . $role);
        
        return $wpdb->insert_id;
    }
    
    /**
     * Assegna un utente a una Casa di Comunità.
     *
     * @since    1.0.0
     * @param    int       $fc_user_id    ID dell'utente FarmaciCase.
     * @param    int       $house_id      ID della Casa di Comunità.
     * @return   boolean   True se l'assegnazione è riuscita, false altrimenti.
     */
    public function assign_user_to_house($fc_user_id, $house_id) {
        global $wpdb;
        $table_prefix = $wpdb->prefix;
        
        // Verifica se l'assegnazione esiste già
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_prefix}fc_user_houses 
             WHERE user_id = %d AND house_id = %d",
            $fc_user_id, $house_id
        ));
        
        if ($existing > 0) {
            return true; // Già assegnato
        }
        
        // Inserisci la nuova assegnazione
        $result = $wpdb->insert(
            "{$table_prefix}fc_user_houses",
            array(
                'user_id' => $fc_user_id,
                'house_id' => $house_id
            )
        );
        
        return $result !== false;
    }
    
    /**
     * Rimuove l'assegnazione di un utente a una Casa di Comunità.
     *
     * @since    1.0.0
     * @param    int       $fc_user_id    ID dell'utente FarmaciCase.
     * @param    int       $house_id      ID della Casa di Comunità.
     * @return   boolean   True se la rimozione è riuscita, false altrimenti.
     */
    public function remove_user_from_house($fc_user_id, $house_id) {
        global $wpdb;
        $table_prefix = $wpdb->prefix;
        
        $result = $wpdb->delete(
            "{$table_prefix}fc_user_houses",
            array(
                'user_id' => $fc_user_id,
                'house_id' => $house_id
            )
        );
        
        return $result !== false;
    }
}