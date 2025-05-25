<?php
/**
 * Gestione delle notifiche per FarmaciCase.
 *
 * Questa classe gestisce l'invio delle notifiche settimanali sui farmaci
 * in scadenza e sotto soglia minima.
 *
 * @since      1.0.0
 * @package    FarmaciCase
 */

// Se questo file viene chiamato direttamente, interrompi
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe per la gestione delle notifiche.
 */
class FarmaciCase_Notifications {

    /**
     * Invia le notifiche settimanali per farmaci in scadenza e sotto soglia.
     *
     * @since    1.0.0
     * @return   boolean   True se le notifiche sono state inviate con successo, false altrimenti.
     */
    public function send_weekly_notifications() {
        global $wpdb;
        $table_prefix = $wpdb->prefix;
        
        // Ottieni tutte le Case attive
        $houses = $wpdb->get_results(
            "SELECT * FROM {$table_prefix}fc_houses WHERE status = 'active'"
        );
        
        if (empty($houses)) {
            $this->log_notification_event('no_houses', 'Nessuna Casa di Comunità attiva trovata');
            return false;
        }
        
        // Ottieni il numero di giorni di anticipo per le scadenze
        $expiration_days = get_option('farmacicase_expiration_days', 60);
        
        // Data limite per le scadenze
        $expiration_limit = date('Y-m-d', strtotime('+' . $expiration_days . ' days'));
        
        $notifications_sent = 0;
        
        // Loop su tutte le Case
        foreach ($houses as $house) {
            // Ottieni farmaci in scadenza per questa Casa
            $expiring_medications = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$table_prefix}fc_medications 
                 WHERE house_id = %d 
                 AND expiration_date <= %s 
                 AND expiration_date >= CURDATE()
                 ORDER BY expiration_date ASC",
                $house->id,
                $expiration_limit
            ));
            
            // Ottieni farmaci sotto soglia per questa Casa
            $low_quantity_medications = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$table_prefix}fc_medications 
                 WHERE house_id = %d 
                 AND total_quantity <= min_quantity_alert
                 ORDER BY (total_quantity - min_quantity_alert) ASC",
                $house->id
            ));
            
            // Se non ci sono farmaci da segnalare, passa alla Casa successiva
            if (empty($expiring_medications) && empty($low_quantity_medications)) {
                continue;
            }
            
            // Ottieni gli utenti associati a questa Casa
            $users = $wpdb->get_results($wpdb->prepare(
                "SELECT u.*, wu.user_email, wu.display_name 
                 FROM {$table_prefix}fc_users u
                 JOIN {$table_prefix}fc_user_houses uh ON u.id = uh.user_id
                 JOIN {$wpdb->users} wu ON u.wp_user_id = wu.ID
                 WHERE uh.house_id = %d",
                $house->id
            ));
            
            if (empty($users)) {
                $this->log_notification_event('no_users', 'Nessun utente associato alla Casa: ' . $house->name);
                continue;
            }
            
            // Invia notifiche agli utenti
            foreach ($users as $user) {
                $sent = $this->send_medication_alert($user, $house, $expiring_medications, $low_quantity_medications, $expiration_days);
                
                if ($sent) {
                    $notifications_sent++;
                    
                    // Registra le notifiche nel database
                    $this->record_notifications($user->id, $expiring_medications, $low_quantity_medications);
                }
            }
        }
        
        // Notifica anche tutti gli admin
        $this->notify_all_admins($expiration_days);
        
        // Log dell'evento
        $this->log_notification_event('weekly_sent', 'Inviate ' . $notifications_sent . ' notifiche settimanali');
        
        return $notifications_sent > 0;
    }
    
    /**
     * Invia una notifica di avviso sui farmaci ad un utente specifico.
     *
     * @since    1.0.0
     * @param    object    $user                   Oggetto utente con dati FarmaciCase e WordPress.
     * @param    object    $house                  Oggetto Casa di Comunità.
     * @param    array     $expiring_medications   Array di farmaci in scadenza.
     * @param    array     $low_quantity_medications Array di farmaci sotto soglia.
     * @param    int       $expiration_days        Giorni di anticipo per le scadenze.
     * @return   boolean   True se la notifica è stata inviata, false altrimenti.
     */
    public function send_medication_alert($user, $house, $expiring_medications, $low_quantity_medications, $expiration_days = 60) {
        // Controlla se l'utente ha una email valida
        if (!isset($user->user_email) || !is_email($user->user_email)) {
            return false;
        }
        
        // Prepara l'oggetto dell'email
        $subject = sprintf(
            /* translators: %s: nome della casa */
            __('[FarmaciCase] Notifica Settimanale Farmaci - %s', 'farmacicase'),
            $house->name
        );
        
        // Inizia a comporre il corpo dell'email
        $message = sprintf(
            /* translators: %s: nome utente */
            __('Gentile %s,', 'farmacicase'),
            isset($user->display_name) ? $user->display_name : __('Utente', 'farmacicase')
        ) . "\n\n";
        
        $message .= sprintf(
            /* translators: %s: nome della casa */
            __('Di seguito il rapporto settimanale dei farmaci che richiedono attenzione presso %s:', 'farmacicase'),
            $house->name
        ) . "\n\n";
        
        // Aggiungi farmaci sotto soglia alla notifica
        if (!empty($low_quantity_medications)) {
            $message .= __('FARMACI SOTTO SOGLIA MINIMA:', 'farmacicase') . "\n";
            
            foreach ($low_quantity_medications as $med) {
                $message .= sprintf(
                    "- %s (%s): %d %s (soglia minima: %d)\n",
                    $med->commercial_name,
                    $med->active_ingredient,
                    $med->total_quantity,
                    __('rimanenti', 'farmacicase'),
                    $med->min_quantity_alert
                );
            }
            
            $message .= "\n";
        }
        
        // Aggiungi farmaci in scadenza alla notifica
        if (!empty($expiring_medications)) {
            $message .= __('FARMACI IN SCADENZA:', 'farmacicase') . "\n";
            
            foreach ($expiring_medications as $med) {
                $days_until = (strtotime($med->expiration_date) - time()) / (60 * 60 * 24);
                
                $message .= sprintf(
                    "- %s (%s): %s %s (%s %d %s)\n",
                    $med->commercial_name,
                    $med->active_ingredient,
                    __('Scadenza', 'farmacicase'),
                    $med->expiration_date,
                    __('entro', 'farmacicase'),
                    round($days_until),
                    __('giorni', 'farmacicase')
                );
            }
            
            $message .= "\n";
        }
        
        // Aggiungi link all'applicazione e firma
        $message .= __('Per visualizzare i dettagli e prendere provvedimenti, acceda al sistema:', 'farmacicase') . "\n";
        $message .= home_url('/farmacicase/') . "\n\n";
        $message .= __('Grazie per la collaborazione,', 'farmacicase') . "\n";
        $message .= __('Sistema FarmaciCase', 'farmacicase');
        
        // Configura parametri email
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        // Usa l'email mittente configurata nelle impostazioni o quella predefinita
        $from_email = get_option('farmacicase_email_sender', get_option('admin_email'));
        if (is_email($from_email)) {
            $headers[] = 'From: FarmaciCase <' . $from_email . '>';
        }
        
        // Invia l'email
        $sent = wp_mail($user->user_email, $subject, $message, $headers);
        
        return $sent;
    }
    
    /**
     * Notifica tutti gli utenti admin di tutte le Case con farmaci problematici.
     *
     * @since    1.0.0
     * @param    int       $expiration_days   Giorni di anticipo per le scadenze.
     * @return   boolean   True se le notifiche sono state inviate, false altrimenti.
     */
    public function notify_all_admins($expiration_days = 60) {
        global $wpdb;
        $table_prefix = $wpdb->prefix;
        
        // Ottieni tutti gli admin
        $admins = $wpdb->get_results(
            "SELECT u.*, wu.user_email, wu.display_name 
             FROM {$table_prefix}fc_users u
             JOIN {$wpdb->users} wu ON u.wp_user_id = wu.ID
             WHERE u.role = 'admin'"
        );
        
        if (empty($admins)) {
            return false;
        }
        
        // Data limite per le scadenze
        $expiration_limit = date('Y-m-d', strtotime('+' . $expiration_days . ' days'));
        
        // Ottieni farmaci in scadenza da tutte le Case
        $expiring_medications = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, h.name as house_name
             FROM {$table_prefix}fc_medications m
             JOIN {$table_prefix}fc_houses h ON m.house_id = h.id
             WHERE m.expiration_date <= %s 
             AND m.expiration_date >= CURDATE()
             ORDER BY m.expiration_date ASC",
            $expiration_limit
        ));
        
        // Ottieni farmaci sotto soglia da tutte le Case
        $low_quantity_medications = $wpdb->get_results(
            "SELECT m.*, h.name as house_name
             FROM {$table_prefix}fc_medications m
             JOIN {$table_prefix}fc_houses h ON m.house_id = h.id
             WHERE m.total_quantity <= m.min_quantity_alert
             ORDER BY (m.total_quantity - m.min_quantity_alert) ASC"
        );
        
        // Se non ci sono farmaci da segnalare, termina
        if (empty($expiring_medications) && empty($low_quantity_medications)) {
            return false;
        }
        
        $notifications_sent = 0;
        
        // Prepara l'oggetto dell'email
        $subject = __('[FarmaciCase] Rapporto Amministrativo Settimanale - Farmaci', 'farmacicase');
        
        foreach ($admins as $admin) {
            // Controlla se l'admin ha una email valida
            if (!isset($admin->user_email) || !is_email($admin->user_email)) {
                continue;
            }
            
            // Inizia a comporre il corpo dell'email
            $message = sprintf(
                /* translators: %s: nome admin */
                __('Gentile %s,', 'farmacicase'),
                isset($admin->display_name) ? $admin->display_name : __('Amministratore', 'farmacicase')
            ) . "\n\n";
            
            $message .= __('Di seguito il rapporto settimanale completo dei farmaci che richiedono attenzione in tutte le Case di Comunità:', 'farmacicase') . "\n\n";
            
            // Aggiungi farmaci sotto soglia alla notifica
            if (!empty($low_quantity_medications)) {
                $message .= __('FARMACI SOTTO SOGLIA MINIMA:', 'farmacicase') . "\n";
                
                foreach ($low_quantity_medications as $med) {
                    $message .= sprintf(
                        "- %s (%s): %d %s (soglia minima: %d) - %s\n",
                        $med->commercial_name,
                        $med->active_ingredient,
                        $med->total_quantity,
                        __('rimanenti', 'farmacicase'),
                        $med->min_quantity_alert,
                        $med->house_name
                    );
                }
                
                $message .= "\n";
            }
            
            // Aggiungi farmaci in scadenza alla notifica
            if (!empty($expiring_medications)) {
                $message .= __('FARMACI IN SCADENZA:', 'farmacicase') . "\n";
                
                foreach ($expiring_medications as $med) {
                    $days_until = (strtotime($med->expiration_date) - time()) / (60 * 60 * 24);
                    
                    $message .= sprintf(
                        "- %s (%s): %s %s (%s %d %s) - %s\n",
                        $med->commercial_name,
                        $med->active_ingredient,
                        __('Scadenza', 'farmacicase'),
                        $med->expiration_date,
                        __('entro', 'farmacicase'),
                        round($days_until),
                        __('giorni', 'farmacicase'),
                        $med->house_name
                    );
                }
                
                $message .= "\n";
            }
            
            // Aggiungi riepilogo per Casa
            $message .= __('RIEPILOGO PER CASA DI COMUNITÀ:', 'farmacicase') . "\n";
            
            // Crea un conteggio per Casa
            $house_counts = array();
            
            foreach ($expiring_medications as $med) {
                if (!isset($house_counts[$med->house_id])) {
                    $house_counts[$med->house_id] = array(
                        'name' => $med->house_name,
                        'expiring' => 0,
                        'low_quantity' => 0
                    );
                }
                $house_counts[$med->house_id]['expiring']++;
            }
            
            foreach ($low_quantity_medications as $med) {
                if (!isset($house_counts[$med->house_id])) {
                    $house_counts[$med->house_id] = array(
                        'name' => $med->house_name,
                        'expiring' => 0,
                        'low_quantity' => 0
                    );
                }
                $house_counts[$med->house_id]['low_quantity']++;
            }
            
            foreach ($house_counts as $house_data) {
                $message .= sprintf(
                    "- %s: %d %s, %d %s\n",
                    $house_data['name'],
                    $house_data['expiring'],
                    __('in scadenza', 'farmacicase'),
                    $house_data['low_quantity'],
                    __('sotto soglia', 'farmacicase')
                );
            }
            
            // Aggiungi link all'applicazione e firma
            $message .= "\n" . __('Per visualizzare i dettagli e prendere provvedimenti, acceda al sistema:', 'farmacicase') . "\n";
            $message .= home_url('/farmacicase/') . "\n\n";
            $message .= __('Grazie per la collaborazione,', 'farmacicase') . "\n";
            $message .= __('Sistema FarmaciCase', 'farmacicase');
            
            // Configura parametri email
            $headers = array('Content-Type: text/plain; charset=UTF-8');
            
            // Usa l'email mittente configurata nelle impostazioni o quella predefinita
            $from_email = get_option('farmacicase_email_sender', get_option('admin_email'));
            if (is_email($from_email)) {
                $headers[] = 'From: FarmaciCase <' . $from_email . '>';
            }
            
            // Invia l'email
            $sent = wp_mail($admin->user_email, $subject, $message, $headers);
            
            if ($sent) {
                $notifications_sent++;
            }
        }
        
        return $notifications_sent > 0;
    }
    
    /**
     * Registra le notifiche inviate nel database.
     *
     * @since    1.0.0
     * @param    int       $user_id               ID dell'utente FarmaciCase.
     * @param    array     $expiring_medications   Array di farmaci in scadenza.
     * @param    array     $low_quantity_medications Array di farmaci sotto soglia.
     */
    public function record_notifications($user_id, $expiring_medications, $low_quantity_medications) {
        global $wpdb;
        $table_prefix = $wpdb->prefix;
        
        // Registra notifiche per farmaci in scadenza
        foreach ($expiring_medications as $med) {
            // Inserisci notifica
            $wpdb->insert(
                "{$table_prefix}fc_notifications",
                array(
                    'type' => 'expiration',
                    'medication_id' => $med->id,
                    'sent_at' => current_time('mysql')
                )
            );
            
            $notification_id = $wpdb->insert_id;
            
            // Associa al destinatario
            $wpdb->insert(
                "{$table_prefix}fc_notification_recipients",
                array(
                    'notification_id' => $notification_id,
                    'user_id' => $user_id
                )
            );
        }
        
        // Registra notifiche per farmaci sotto soglia
        foreach ($low_quantity_medications as $med) {
            // Inserisci notifica
            $wpdb->insert(
                "{$table_prefix}fc_notifications",
                array(
                    'type' => 'low_quantity',
                    'medication_id' => $med->id,
                    'sent_at' => current_time('mysql')
                )
            );
            
            $notification_id = $wpdb->insert_id;
            
            // Associa al destinatario
            $wpdb->insert(
                "{$table_prefix}fc_notification_recipients",
                array(
                    'notification_id' => $notification_id,
                    'user_id' => $user_id
                )
            );
        }
    }
    
    /**
     * Invia una notifica di test agli amministratori.
     *
     * @since    1.0.0
     * @return   boolean   True se la notifica è stata inviata con successo, false altrimenti.
     */
    public function send_test_notification() {
        // Ottieni l'utente corrente
        $current_user = wp_get_current_user();
        
        if (!$current_user || $current_user->ID === 0) {
            return false;
        }
        
        // Prepara l'oggetto dell'email
        $subject = __('[FarmaciCase] Notifica di Test', 'farmacicase');
        
        // Corpo dell'email
        $message = sprintf(
            __('Gentile %s,', 'farmacicase'),
            $current_user->display_name
        ) . "\n\n";
        
        $message .= __('Questa è una notifica di test dal sistema FarmaciCase.', 'farmacicase') . "\n\n";
        $message .= __('Se stai ricevendo questa email, il sistema di notifiche è configurato correttamente.', 'farmacicase') . "\n\n";
        $message .= __('Le notifiche automatiche per i farmaci in scadenza e sotto soglia minima verranno inviate ogni lunedì mattina.', 'farmacicase') . "\n\n";
        $message .= __('Grazie,', 'farmacicase') . "\n";
        $message .= __('Sistema FarmaciCase', 'farmacicase');
        
        // Configura parametri email
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        // Usa l'email mittente configurata nelle impostazioni o quella predefinita
        $from_email = get_option('farmacicase_email_sender', get_option('admin_email'));
        if (is_email($from_email)) {
            $headers[] = 'From: FarmaciCase <' . $from_email . '>';
        }
        
        // Invia l'email
        $sent = wp_mail($current_user->user_email, $subject, $message, $headers);
        
        // Log dell'evento
        if ($sent) {
            $this->log_notification_event('test_sent', 'Notifica di test inviata a ' . $current_user->user_email);
        } else {
            $this->log_notification_event('test_failed', 'Invio notifica di test fallito a ' . $current_user->user_email);
        }
        
        return $sent;
    }
    
    /**
     * Ottieni le notifiche per un utente specifico.
     *
     * @since    1.0.0
     * @param    int       $user_id    ID dell'utente FarmaciCase.
     * @param    int       $limit      Numero massimo di notifiche da recuperare.
     * @return   array     Array di oggetti notifica.
     */
    public function get_user_notifications($user_id, $limit = 20) {
        global $wpdb;
        $table_prefix = $wpdb->prefix;
        
        // Ottieni le notifiche per questo utente
        $notifications = $wpdb->get_results($wpdb->prepare(
            "SELECT n.*, m.commercial_name, m.active_ingredient, h.name as house_name 
             FROM {$table_prefix}fc_notifications n
             JOIN {$table_prefix}fc_notification_recipients nr ON n.id = nr.notification_id
             JOIN {$table_prefix}fc_medications m ON n.medication_id = m.id
             JOIN {$table_prefix}fc_houses h ON m.house_id = h.id
             WHERE nr.user_id = %d
             ORDER BY n.sent_at DESC
             LIMIT %d",
            $user_id,
            $limit
        ));
        
        return $notifications;
    }
    
    /**
     * Marca una notifica come letta.
     *
     * @since    1.0.0
     * @param    int       $notification_id    ID della notifica.
     * @param    int       $user_id           ID dell'utente FarmaciCase.
     * @return   boolean   True se la notifica è stata marcata come letta, false altrimenti.
     */
    public function mark_notification_as_read($notification_id, $user_id) {
        global $wpdb;
        $table_prefix = $wpdb->prefix;
        
        // Verifica che l'utente sia un destinatario di questa notifica
        $is_recipient = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_prefix}fc_notification_recipients 
             WHERE notification_id = %d AND user_id = %d",
            $notification_id,
            $user_id
        ));
        
        if ($is_recipient == 0) {
            return false;
        }
        
        // Aggiorna stato notifica
        $result = $wpdb->update(
            "{$table_prefix}fc_notifications",
            array('read_status' => 'read'),
            array('id' => $notification_id)
        );
        
        return $result !== false;
    }
    
    /**
     * Log degli eventi delle notifiche.
     *
     * @since    1.0.0
     * @param    string    $type       Tipo di evento.
     * @param    string    $message    Messaggio di log.
     */
    public function log_notification_event($type, $message) {
        // Se WordPress ha la funzionalità di debug log attiva
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('[FarmaciCase] ' . $type . ': ' . $message);
        }
        
        // Salva anche in opzioni WordPress per debugging
        $logs = get_option('farmacicase_notification_logs', array());
        
        // Limita a 100 log
        if (count($logs) >= 100) {
            array_shift($logs); // Rimuovi il log più vecchio
        }
        
        // Aggiungi nuovo log
        $logs[] = array(
            'timestamp' => current_time('mysql'),
            'type' => $type,
            'message' => $message
        );
        
        update_option('farmacicase_notification_logs', $logs);
    }
    
    /**
     * Ottiene i log delle notifiche.
     *
     * @since    1.0.0
     * @param    int       $limit    Numero massimo di log da recuperare.
     * @return   array     Array di log notifiche.
     */
    public function get_notification_logs($limit = 50) {
        $logs = get_option('farmacicase_notification_logs', array());
        
        // Ordina per timestamp decrescente (più recenti prima)
        usort($logs, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        // Limita il numero di log
        return array_slice($logs, 0, $limit);
    }
    
    /**
     * Controlla se ci sono notifiche non lette per un utente.
     *
     * @since    1.0.0
     * @param    int       $user_id    ID dell'utente FarmaciCase.
     * @return   int       Numero di notifiche non lette.
     */
    public function count_unread_notifications($user_id) {
        global $wpdb;
        $table_prefix = $wpdb->prefix;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_prefix}fc_notifications n
             JOIN {$table_prefix}fc_notification_recipients nr ON n.id = nr.notification_id
             WHERE nr.user_id = %d AND n.read_status = 'unread'",
            $user_id
        ));
    }
}