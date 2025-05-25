<?php
/**
 * Pagina Impostazioni per FarmaciCase
 *
 * @since      1.0.0
 * @package    FarmaciCase
 */

// Se questo file viene chiamato direttamente, interrompi
if (!defined('ABSPATH')) {
    exit;
}

// Controlla se il form è stato inviato
$message = '';
$message_type = '';
if (isset($_POST['farmacicase_settings_submit'])) {
    if (check_admin_referer('farmacicase_settings_nonce', 'farmacicase_settings_nonce')) {
        // Salva impostazioni
        $email_sender = sanitize_email($_POST['farmacicase_email_sender']);
        update_option('farmacicase_email_sender', $email_sender);
        
        $notification_time = sanitize_text_field($_POST['farmacicase_notification_time']);
        update_option('farmacicase_notification_time', $notification_time);
        
        $expiration_days = absint($_POST['farmacicase_expiration_days']);
        update_option('farmacicase_expiration_days', $expiration_days);
        
        // Aggiorna pianificazione cron se cambiata
        if ($notification_time !== get_option('farmacicase_notification_time')) {
            wp_clear_scheduled_hook('farmacicase_weekly_notifications');
            
            // Calcola prossimo timestamp basato sul nuovo orario
            list($hour, $minute) = explode(':', $notification_time);
            $timestamp = strtotime("next Monday {$hour}:{$minute}");
            
            wp_schedule_event($timestamp, 'weekly', 'farmacicase_weekly_notifications');
        }
        
        $message = __('Impostazioni salvate con successo.', 'farmacicase');
        $message_type = 'updated';
    }
}

// Test notifica
if (isset($_POST['farmacicase_test_notification'])) {
    if (check_admin_referer('farmacicase_test_notification_nonce', 'farmacicase_test_notification_nonce')) {
        // Carica la classe notifiche e invia una notifica di test
        require_once FARMACICASE_PLUGIN_DIR . 'includes/class-farmacicase-notifications.php';
        $notifications = new FarmaciCase_Notifications();
        
        $result = $notifications->send_test_notification();
        
        if ($result) {
            $message = __('Notifica di test inviata con successo!', 'farmacicase');
            $message_type = 'updated';
        } else {
            $message = __('Errore nell\'invio della notifica di test.', 'farmacicase');
            $message_type = 'error';
        }
    }
}

// Opzioni attuali
$email_sender = get_option('farmacicase_email_sender', get_option('admin_email'));
$notification_time = get_option('farmacicase_notification_time', '07:00');
$expiration_days = get_option('farmacicase_expiration_days', 60);
?>

<div class="wrap">
    <h1><?php _e('FarmaciCase - Impostazioni', 'farmacicase'); ?></h1>
    
    <?php if ($message): ?>
    <div class="notice notice-<?php echo $message_type === 'updated' ? 'success' : 'error'; ?> is-dismissible">
        <p><?php echo esc_html($message); ?></p>
    </div>
    <?php endif; ?>
    
    <div class="fc-admin-settings-container">
        <div class="fc-admin-settings-main">
            <form method="post" action="">
                <?php wp_nonce_field('farmacicase_settings_nonce', 'farmacicase_settings_nonce'); ?>
                
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="farmacicase_email_sender"><?php _e('Email Mittente Notifiche', 'farmacicase'); ?></label>
                            </th>
                            <td>
                                <input name="farmacicase_email_sender" type="email" id="farmacicase_email_sender" 
                                       value="<?php echo esc_attr($email_sender); ?>" class="regular-text">
                                <p class="description">
                                    <?php _e('Indirizzo email da cui verranno inviate le notifiche.', 'farmacicase'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="farmacicase_notification_time"><?php _e('Orario Invio Notifiche', 'farmacicase'); ?></label>
                            </th>
                            <td>
                                <input name="farmacicase_notification_time" type="time" id="farmacicase_notification_time" 
                                       value="<?php echo esc_attr($notification_time); ?>" class="regular-text">
                                <p class="description">
                                    <?php _e('Orario di invio delle notifiche settimanali (ogni lunedì).', 'farmacicase'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="farmacicase_expiration_days"><?php _e('Giorni Anticipo Scadenza', 'farmacicase'); ?></label>
                            </th>
                            <td>
                                <input name="farmacicase_expiration_days" type="number" id="farmacicase_expiration_days" 
                                       value="<?php echo esc_attr($expiration_days); ?>" class="small-text" min="1" max="365">
                                <p class="description">
                                    <?php _e('Numero di giorni prima della scadenza per cui inviare le notifiche.', 'farmacicase'); ?>
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <p class="submit">
                    <input type="submit" name="farmacicase_settings_submit" id="submit" class="button button-primary" 
                           value="<?php _e('Salva Impostazioni', 'farmacicase'); ?>">
                </p>
            </form>
        </div>
        
        <div class="fc-admin-settings-sidebar">
            <div class="fc-admin-settings-box">
                <h3><?php _e('Test Notifiche', 'farmacicase'); ?></h3>
                <p>
                    <?php _e('Invia una notifica di test per verificare che il sistema di notifiche funzioni correttamente.', 'farmacicase'); ?>
                </p>
                <form method="post" action="">
                    <?php wp_nonce_field('farmacicase_test_notification_nonce', 'farmacicase_test_notification_nonce'); ?>
                    <p>
                        <input type="submit" name="farmacicase_test_notification" class="button button-secondary" 
                               value="<?php _e('Invia Notifica Test', 'farmacicase'); ?>">
                    </p>
                </form>
            </div>
            
            <div class="fc-admin-settings-box">
                <h3><?php _e('Database', 'farmacicase'); ?></h3>
                <p>
                    <?php _e('Controlla lo stato delle tabelle del database.', 'farmacicase'); ?>
                </p>
                
                <?php
                // Verifica stato tabelle
                require_once FARMACICASE_PLUGIN_DIR . 'includes/class-farmacicase-db.php';
                $db = new FarmaciCase_DB();
                $tables_status = $db->check_tables();
                
                $all_tables_ok = !in_array(false, $tables_status);
                ?>
                
                <?php if ($all_tables_ok): ?>
                <p class="fc-db-status fc-db-status-ok">
                    <?php _e('Tutte le tabelle sono presenti nel database.', 'farmacicase'); ?>
                </p>
                <?php else: ?>
                <p class="fc-db-status fc-db-status-error">
                    <?php _e('Alcune tabelle sono mancanti. Prova a disattivare e riattivare il plugin.', 'farmacicase'); ?>
                </p>
                <ul class="fc-db-tables-list">
                    <?php foreach ($tables_status as $table => $exists): ?>
                    <li class="<?php echo $exists ? 'fc-db-table-ok' : 'fc-db-table-missing'; ?>">
                        <?php echo $table; ?> - <?php echo $exists ? __('OK', 'farmacicase') : __('Mancante', 'farmacicase'); ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
            
            <div class="fc-admin-settings-box">
                <h3><?php _e('Info', 'farmacicase'); ?></h3>
                <p>
                    <strong><?php _e('Versione Plugin:', 'farmacicase'); ?></strong> <?php echo FARMACICASE_VERSION; ?>
                </p>
                <p>
                    <strong><?php _e('Versione WordPress:', 'farmacicase'); ?></strong> <?php echo get_bloginfo('version'); ?>
                </p>
                <p>
                    <strong><?php _e('Versione PHP:', 'farmacicase'); ?></strong> <?php echo phpversion(); ?>
                </p>
                <p>
                    <strong><?php _e('Versione MySQL:', 'farmacicase'); ?></strong> <?php echo $wpdb->db_version(); ?>
                </p>
            </div>
        </div>
    </div>
</div>
