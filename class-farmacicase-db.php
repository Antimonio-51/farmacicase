<?php
/**
 * Gestione del database per FarmaciCase
 *
 * Questa classe gestisce tutte le operazioni relative al database,
 * inclusa la creazione iniziale delle tabelle necessarie.
 *
 * @since      1.0.0
 * @package    FarmaciCase
 */

// Se questo file viene chiamato direttamente, interrompi
if (!defined('ABSPATH')) {
    exit;
}

class FarmaciCase_DB {

    /**
     * Crea le tabelle necessarie per il plugin.
     *
     * @since    1.0.0
     * @return   bool    True se la creazione è avvenuta con successo, false altrimenti.
     */
    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_prefix = $wpdb->prefix;
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Disabilita temporaneamente i vincoli di chiave esterna
        $this->safe_query('SET FOREIGN_KEY_CHECKS = 0');
        
        // Definisci l'ordine corretto di creazione delle tabelle
        $tables = array(
            'fc_houses' => "CREATE TABLE IF NOT EXISTS {$table_prefix}fc_houses (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                address VARCHAR(255) NOT NULL,
                city VARCHAR(100) NOT NULL,
                state VARCHAR(50) NOT NULL,
                zip VARCHAR(20),
                phone VARCHAR(30),
                email VARCHAR(100),
                status ENUM('active', 'inactive') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
            ) $charset_collate;",
            
            'fc_users' => "CREATE TABLE IF NOT EXISTS {$table_prefix}fc_users (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                wp_user_id BIGINT UNSIGNED NOT NULL,
                role ENUM('admin', 'responsabile', 'medico') NOT NULL,
                phone VARCHAR(30),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY wp_user_id (wp_user_id),
                FOREIGN KEY (wp_user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE
            ) $charset_collate;",
            
            'fc_user_houses' => "CREATE TABLE IF NOT EXISTS {$table_prefix}fc_user_houses (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                house_id INT UNSIGNED NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY user_house_unique (user_id, house_id),
                FOREIGN KEY (user_id) REFERENCES {$table_prefix}fc_users(id) ON DELETE CASCADE,
                FOREIGN KEY (house_id) REFERENCES {$table_prefix}fc_houses(id) ON DELETE CASCADE
            ) $charset_collate;",
            
            'fc_medications' => "CREATE TABLE IF NOT EXISTS {$table_prefix}fc_medications (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                house_id INT UNSIGNED NOT NULL,
                commercial_name VARCHAR(255) NOT NULL,
                active_ingredient VARCHAR(255) NOT NULL,
                description TEXT,
                leaflet_url VARCHAR(255),
                package_count INT UNSIGNED NOT NULL DEFAULT 0,
                total_quantity INT UNSIGNED NOT NULL DEFAULT 0,
                expiration_date DATE NOT NULL,
                min_quantity_alert INT UNSIGNED NOT NULL DEFAULT 0,
                created_by BIGINT UNSIGNED NOT NULL,
                updated_by BIGINT UNSIGNED,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (house_id) REFERENCES {$table_prefix}fc_houses(id) ON DELETE CASCADE,
                FOREIGN KEY (created_by) REFERENCES {$wpdb->users}(ID) ON DELETE NO ACTION
            ) $charset_collate;",
            
            'fc_medication_history' => "CREATE TABLE IF NOT EXISTS {$table_prefix}fc_medication_history (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                medication_id INT UNSIGNED NOT NULL,
                action ENUM('create', 'update', 'delete') NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                details TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (medication_id) REFERENCES {$table_prefix}fc_medications(id) ON DELETE CASCADE
            ) $charset_collate;",
            
            'fc_notifications' => "CREATE TABLE IF NOT EXISTS {$table_prefix}fc_notifications (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                type ENUM('expiration', 'low_quantity') NOT NULL,
                medication_id INT UNSIGNED NOT NULL,
                sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                read_status ENUM('read', 'unread') DEFAULT 'unread',
                FOREIGN KEY (medication_id) REFERENCES {$table_prefix}fc_medications(id) ON DELETE CASCADE
            ) $charset_collate;",
            
            'fc_notification_recipients' => "CREATE TABLE IF NOT EXISTS {$table_prefix}fc_notification_recipients (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                notification_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                FOREIGN KEY (notification_id) REFERENCES {$table_prefix}fc_notifications(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES {$table_prefix}fc_users(id) ON DELETE CASCADE
            ) $charset_collate;"
        );
        
        $success = true;
        
        foreach ($tables as $table_name => $sql) {
            // Forza la creazione se la tabella non esiste
            if (!$this->table_exists($table_name)) {
                dbDelta($sql);
                
                if ($wpdb->last_error) {
                    $this->log_error("Errore creazione $table_name: " . $wpdb->last_error);
                    $success = false;
                }
            }
        }
        
        // Riabilita i vincoli di chiave esterna
        $this->safe_query('SET FOREIGN_KEY_CHECKS = 1');
        
        return $success && $this->verify_tables();
    }
    
    /**
     * Esegue query con meccanismo di ritentativo
     */
    private function safe_query($query, $params = array(), $max_retries = 3) {
        global $wpdb;
        
        $retry_count = 0;
        $result = false;
        
        while ($retry_count < $max_retries) {
            try {
                if (!empty($params)) {
                    $prepared = $wpdb->prepare($query, $params);
                    $result = $wpdb->query($prepared);
                } else {
                    $result = $wpdb->query($query);
                }
                
                if ($result !== false) break;
                
            } catch (Exception $e) {
                $this->log_error("DB Error (try $retry_count): " . $e->getMessage());
            }
            
            $retry_count++;
            usleep(100000 * $retry_count); // Backoff progressivo
        }
        
        return $result;
    }
    
    /**
     * Verifica l'esistenza di una tabella
     */
    private function table_exists($table_name) {
        global $wpdb;
        return $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}$table_name'") === $wpdb->prefix.$table_name;
    }
    
    /**
     * Verifica integrità tabelle
     */
    private function verify_tables() {
        $required_tables = array(
            'fc_houses',
            'fc_users',
            'fc_user_houses',
            'fc_medications',
            'fc_medication_history',
            'fc_notifications',
            'fc_notification_recipients'
        );
        
        $missing = array();
        
        foreach ($required_tables as $table) {
            if (!$this->table_exists($table)) {
                $missing[] = $table;
            }
        }
        
        if (!empty($missing)) {
            $this->log_error("Tabelle mancanti: " . implode(', ', $missing));
            return false;
        }
        
        return true;
    }
    
    /**
     * Registra errori nel log
     */
    private function log_error($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[FarmaciCase DB] ' . $message);
        }
    }
    
    /**
     * Verifica la presenza delle tabelle nel database.
     * Utile per diagnostica e controllo dell'installazione.
     *
     * @since    1.0.0
     * @return   array    Array associativo con nomi tabelle e stato (true/false)
     */
    public function check_tables() {
        global $wpdb;
        $table_prefix = $wpdb->prefix;
        
        $tables = array(
            'fc_houses',
            'fc_users',
            'fc_user_houses',
            'fc_medications',
            'fc_medication_history',
            'fc_notifications',
            'fc_notification_recipients'
        );
        
        $status = array();
        
        foreach ($tables as $table) {
            $full_name = $table_prefix . $table;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_name'") === $full_name;
            $status[$table] = $exists;
        }
        
        return $status;
    }
    
    /**
     * Inserisce dati di esempio nel database.
     * Utile per test e sviluppo.
     *
     * @since    1.0.0
     */
    public function insert_sample_data() {
        global $wpdb;
        $table_prefix = $wpdb->prefix;
        
        // Inserisci Case di esempio
        $houses = array(
            array(
                'name' => 'Casa di Comunità Roma Centro',
                'address' => 'Via del Corso 123',
                'city' => 'Roma',
                'state' => 'Italia',
                'zip' => '00186',
                'phone' => '+39 06 1234567',
                'email' => 'roma.centro@farmacicase.com',
                'status' => 'active'
            ),
            array(
                'name' => 'Casa di Comunità Milano Navigli',
                'address' => 'Via Naviglio Grande 45',
                'city' => 'Milano',
                'state' => 'Italia',
                'zip' => '20144',
                'phone' => '+39 02 9876543',
                'email' => 'milano.navigli@farmacicase.com',
                'status' => 'active'
            ),
            array(
                'name' => 'Casa di Comunità Madrid',
                'address' => 'Calle Gran Vía 56',
                'city' => 'Madrid',
                'state' => 'Spagna',
                'zip' => '28013',
                'phone' => '+34 91 1234567',
                'email' => 'madrid@farmacicase.com',
                'status' => 'active'
            )
        );
        
        foreach ($houses as $house) {
            $wpdb->insert("{$table_prefix}fc_houses", $house);
        }
        
        // Crea utenti di esempio se non esistono già
        $admin_user_id = username_exists('fc_admin');
        if (!$admin_user_id) {
            $admin_user_id = wp_create_user('fc_admin', wp_generate_password(), 'admin@farmacicase.com');
            $admin_user = new WP_User($admin_user_id);
            $admin_user->set_role('fc_admin');
        }
        
        $resp_user_id = username_exists('fc_responsabile');
        if (!$resp_user_id) {
            $resp_user_id = wp_create_user('fc_responsabile', wp_generate_password(), 'responsabile@farmacicase.com');
            $resp_user = new WP_User($resp_user_id);
            $resp_user->set_role('fc_responsabile');
        }
        
        $med_user_id = username_exists('fc_medico');
        if (!$med_user_id) {
            $med_user_id = wp_create_user('fc_medico', wp_generate_password(), 'medico@farmacicase.com');
            $med_user = new WP_User($med_user_id);
            $med_user->set_role('fc_medico');
        }
        
        // Inserisci relazioni utenti-FarmaciCase
        $wpdb->insert(
            "{$table_prefix}fc_users",
            array(
                'wp_user_id' => $admin_user_id,
                'role' => 'admin',
                'phone' => '+39 333 1234567'
            )
        );
        $admin_fc_id = $wpdb->insert_id;
        
        $wpdb->insert(
            "{$table_prefix}fc_users",
            array(
                'wp_user_id' => $resp_user_id,
                'role' => 'responsabile',
                'phone' => '+39 333 7654321'
            )
        );
        $resp_fc_id = $wpdb->insert_id;
        
        $wpdb->insert(
            "{$table_prefix}fc_users",
            array(
                'wp_user_id' => $med_user_id,
                'role' => 'medico',
                'phone' => '+39 333 9876543'
            )
        );
        $med_fc_id = $wpdb->insert_id;
        
        // Assegna utenti alle case
        // Responsabile alla prima casa
        $wpdb->insert(
            "{$table_prefix}fc_user_houses",
            array(
                'user_id' => $resp_fc_id,
                'house_id' => 1
            )
        );
        
        // Medico alla prima casa
        $wpdb->insert(
            "{$table_prefix}fc_user_houses",
            array(
                'user_id' => $med_fc_id,
                'house_id' => 1
            )
        );
        
        // Inserisci farmaci di esempio
        $medications = array(
            array(
                'house_id' => 1,
                'commercial_name' => 'Tachipirina 1000mg',
                'active_ingredient' => 'Paracetamolo',
                'description' => 'Antipiretico e analgesico indicato per febbre e dolori di varia natura.',
                'leaflet_url' => 'https://www.tachipirina.it/foglio_illustrativo.pdf',
                'package_count' => 3,
                'total_quantity' => 60,
                'expiration_date' => date('Y-m-d', strtotime('+1 year')),
                'min_quantity_alert' => 20,
                'created_by' => $admin_user_id
            ),
            array(
                'house_id' => 1,
                'commercial_name' => 'Augmentin 875/125mg',
                'active_ingredient' => 'Amoxicillina/Acido clavulanico',
                'description' => 'Antibiotico ad ampio spettro per infezioni batteriche.',
                'leaflet_url' => 'https://www.augmentin.it/foglio_illustrativo.pdf',
                'package_count' => 2,
                'total_quantity' => 24,
                'expiration_date' => date('Y-m-d', strtotime('+2 months')),
                'min_quantity_alert' => 12,
                'created_by' => $resp_user_id
            ),
            array(
                'house_id' => 2,
                'commercial_name' => 'Moment 400mg',
                'active_ingredient' => 'Ibuprofene',
                'description' => 'Antinfiammatorio non steroideo, analgesico e antipiretico.',
                'leaflet_url' => 'https://www.moment.it/foglio_illustrativo.pdf',
                'package_count' => 5,
                'total_quantity' => 100,
                'expiration_date' => date('Y-m-d', strtotime('+6 months')),
                'min_quantity_alert' => 30,
                'created_by' => $admin_user_id
            )
        );
        
        foreach ($medications as $med) {
            $wpdb->insert("{$table_prefix}fc_medications", $med);
            $med_id = $wpdb->insert_id;
            
            // Inserisci anche nello storico
            $wpdb->insert(
                "{$table_prefix}fc_medication_history",
                array(
                    'medication_id' => $med_id,
                    'action' => 'create',
                    'user_id' => $med['created_by'],
                    'details' => json_encode($med)
                )
            );
        }
    }
    
    /**
     * Aggiorna lo schema del database se necessario.
     * Da utilizzare per upgrade futuri del plugin.
     *
     * @since    1.0.0
     */
    public function update_schema() {
        $current_version = get_option('farmacicase_db_version', '1.0.0');
        
        // Esempio di aggiornamento schema per future versioni
        if (version_compare($current_version, '1.1.0', '<')) {
            // Qui il codice per aggiornare lo schema alla versione 1.1.0
            // Ad esempio aggiungere campi, indici o tabelle nuove
        }
        
        update_option('farmacicase_db_version', FARMACICASE_VERSION);
    }
}