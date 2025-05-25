<?php
/**
 * Interfaccia Admin per FarmaciCase
 *
 * @since      1.0.0
 * @package    FarmaciCase
 */

// Se questo file viene chiamato direttamente, interrompi
if (!defined('ABSPATH')) {
    exit;
}

// Ottieni istanza Auth
require_once FARMACICASE_PLUGIN_DIR . 'includes/class-farmacicase-auth.php';
$auth = new FarmaciCase_Auth();
$current_user_id = get_current_user_id();

// Carica la funzione per ottenere dati statistici
function get_medication_stats() {
    global $wpdb;
    $table_prefix = $wpdb->prefix;
    
    // Conteggio Case di Comunità
    $houses_count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_prefix}fc_houses WHERE status = 'active'");
    
    // Conteggio farmaci
    $medications_count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_prefix}fc_medications");
    
    // Farmaci in scadenza
    $days = get_option('farmacicase_expiration_days', 60);
    $expiring_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table_prefix}fc_medications 
        WHERE expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL %d DAY)",
        $days
    ));
    
    // Farmaci sotto soglia
    $below_threshold_count = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$table_prefix}fc_medications 
        WHERE total_quantity <= min_quantity_alert"
    );
    
    return array(
        'houses_count' => $houses_count,
        'medications_count' => $medications_count,
        'expiring_count' => $expiring_count,
        'below_threshold_count' => $below_threshold_count
    );
}

$stats = get_medication_stats();
?>

<div class="fc-container">
    <div class="fc-header">
        <h1><?php _e('FarmaciCase - Dashboard Admin', 'farmacicase'); ?></h1>
        <div class="fc-user-info">
            <?php
            $current_user = wp_get_current_user();
            echo esc_html($current_user->display_name);
            ?>
            <span class="fc-role fc-role-admin"><?php _e('Admin', 'farmacicase'); ?></span>
        </div>
    </div>
    
    <div class="fc-layout">
        <div class="fc-sidebar">
            <ul class="fc-nav">
                <li class="fc-nav-item active" data-section="dashboard">
                    <a href="#dashboard" class="fc-nav-link">
                        <i class="dashicons dashicons-dashboard"></i> <?php _e('Dashboard', 'farmacicase'); ?>
                    </a>
                </li>
                <li class="fc-nav-item" data-section="houses">
                    <a href="#houses" class="fc-nav-link">
                        <i class="dashicons dashicons-admin-home"></i> <?php _e('Gestione Case', 'farmacicase'); ?>
                    </a>
                </li>
                <li class="fc-nav-item" data-section="users">
                    <a href="#users" class="fc-nav-link">
                        <i class="dashicons dashicons-admin-users"></i> <?php _e('Gestione Utenti', 'farmacicase'); ?>
                    </a>
                </li>
                <li class="fc-nav-item" data-section="medications">
                    <a href="#medications" class="fc-nav-link">
                        <i class="dashicons dashicons-pills"></i> <?php _e('Gestione Farmaci', 'farmacicase'); ?>
                    </a>
                </li>
                <li class="fc-nav-item" data-section="notifications">
                    <a href="#notifications" class="fc-nav-link">
                        <i class="dashicons dashicons-email-alt"></i> <?php _e('Notifiche', 'farmacicase'); ?>
                    </a>
                </li>
                <li class="fc-nav-item">
                    <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="fc-nav-link">
                        <i class="dashicons dashicons-exit"></i> <?php _e('Esci', 'farmacicase'); ?>
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="fc-content">
            <!-- Dashboard Section -->
            <div class="fc-section fc-section-active" id="fc-section-dashboard">
                <h2><?php _e('Dashboard', 'farmacicase'); ?></h2>
                
                <div class="fc-dashboard-stats">
                    <div class="fc-stat-card">
                        <div class="fc-stat-icon">
                            <i class="dashicons dashicons-admin-home"></i>
                        </div>
                        <div class="fc-stat-content">
                            <div class="fc-stat-value"><?php echo esc_html($stats['houses_count']); ?></div>
                            <div class="fc-stat-label"><?php _e('Case di Comunità', 'farmacicase'); ?></div>
                        </div>
                    </div>
                    
                    <div class="fc-stat-card">
                        <div class="fc-stat-icon">
                            <i class="dashicons dashicons-pills"></i>
                        </div>
                        <div class="fc-stat-content">
                            <div class="fc-stat-value"><?php echo esc_html($stats['medications_count']); ?></div>
                            <div class="fc-stat-label"><?php _e('Farmaci', 'farmacicase'); ?></div>
                        </div>
                    </div>
                    
                    <div class="fc-stat-card fc-stat-warning">
                        <div class="fc-stat-icon">
                            <i class="dashicons dashicons-calendar-alt"></i>
                        </div>
                        <div class="fc-stat-content">
                            <div class="fc-stat-value"><?php echo esc_html($stats['expiring_count']); ?></div>
                            <div class="fc-stat-label"><?php _e('Farmaci in Scadenza', 'farmacicase'); ?></div>
                        </div>
                    </div>
                    
                    <div class="fc-stat-card fc-stat-danger">
                        <div class="fc-stat-icon">
                            <i class="dashicons dashicons-warning"></i>
                        </div>
                        <div class="fc-stat-content">
                            <div class="fc-stat-value"><?php echo esc_html($stats['below_threshold_count']); ?></div>
                            <div class="fc-stat-label"><?php _e('Sotto Soglia', 'farmacicase'); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="fc-dashboard-charts">
                    <div class="fc-chart-container">
                        <h3><?php _e('Distribuzione Case per Stato', 'farmacicase'); ?></h3>
                        <div class="fc-chart" id="fc-houses-chart"></div>
                    </div>
                    
                    <div class="fc-chart-container">
                        <h3><?php _e('Farmaci Critici', 'farmacicase'); ?></h3>
                        <div class="fc-chart" id="fc-critical-meds-chart"></div>
                    </div>
                </div>
                
                <div class="fc-recent-section">
                    <h3><?php _e('Farmaci in Scadenza', 'farmacicase'); ?></h3>
                    <div id="fc-expiring-medications-list" class="fc-loading">
                        <p><?php _e('Caricamento...', 'farmacicase'); ?></p>
                    </div>
                    
                    <h3><?php _e('Farmaci Sotto Soglia', 'farmacicase'); ?></h3>
                    <div id="fc-low-quantity-medications-list" class="fc-loading">
                        <p><?php _e('Caricamento...', 'farmacicase'); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Houses Section -->
            <div class="fc-section" id="fc-section-houses">
                <div class="fc-section-header">
                    <h2><?php _e('Gestione Case di Comunità', 'farmacicase'); ?></h2>
                    <div class="fc-section-actions">
                        <button id="fc-new-house-btn" class="fc-btn fc-btn-primary">
                            <i class="dashicons dashicons-plus-alt"></i> <?php _e('Aggiungi Casa', 'farmacicase'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="fc-filters">
                    <div class="fc-filter-group">
                        <label for="fc-state-filter"><?php _e('Stato:', 'farmacicase'); ?></label>
                        <select id="fc-state-filter" class="fc-form-control">
                            <option value=""><?php _e('Tutti', 'farmacicase'); ?></option>
                            <!-- Gli stati verranno popolati dinamicamente -->
                        </select>
                    </div>
                    
                    <div class="fc-filter-group">
                        <label for="fc-status-filter"><?php _e('Status:', 'farmacicase'); ?></label>
                        <select id="fc-status-filter" class="fc-form-control">
                            <option value=""><?php _e('Tutti', 'farmacicase'); ?></option>
                            <option value="active"><?php _e('Attivo', 'farmacicase'); ?></option>
                            <option value="inactive"><?php _e('Inattivo', 'farmacicase'); ?></option>
                        </select>
                    </div>
                    
                    <div class="fc-filter-group">
                        <label for="fc-search-houses"><?php _e('Cerca:', 'farmacicase'); ?></label>
                        <input type="text" id="fc-search-houses" class="fc-form-control" placeholder="<?php esc_attr_e('Nome, città...', 'farmacicase'); ?>">
                    </div>
                </div>

                <div id="fc-houses-list" class="fc-loading">
                    <p><?php _e('Caricamento...', 'farmacicase'); ?></p>
                </div>
            </div>
            
            <!-- Users Section -->
            <div class="fc-section" id="fc-section-users">
                <div class="fc-section-header">
                    <h2><?php _e('Gestione Utenti', 'farmacicase'); ?></h2>
                    <div class="fc-section-actions">
                        <button id="fc-new-user-btn" class="fc-btn fc-btn-primary">
                            <i class="dashicons dashicons-plus-alt"></i> <?php _e('Aggiungi Utente', 'farmacicase'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="fc-filters">
                    <div class="fc-filter-group">
                        <label for="fc-role-filter"><?php _e('Ruolo:', 'farmacicase'); ?></label>
                        <select id="fc-role-filter" class="fc-form-control">
                            <option value=""><?php _e('Tutti', 'farmacicase'); ?></option>
                            <option value="admin"><?php _e('Admin', 'farmacicase'); ?></option>
                            <option value="responsabile"><?php _e('Responsabile', 'farmacicase'); ?></option>
                            <option value="medico"><?php _e('Medico', 'farmacicase'); ?></option>
                        </select>
                    </div>
                    
                    <div class="fc-filter-group">
                        <label for="fc-house-user-filter"><?php _e('Casa:', 'farmacicase'); ?></label>
                        <select id="fc-house-user-filter" class="fc-form-control">
                            <option value=""><?php _e('Tutte', 'farmacicase'); ?></option>
                            <!-- Le case verranno popolate dinamicamente -->
                        </select>
                    </div>
                    
                    <div class="fc-filter-group">
                        <label for="fc-search-users"><?php _e('Cerca:', 'farmacicase'); ?></label>
                        <input type="text" id="fc-search-users" class="fc-form-control" placeholder="<?php esc_attr_e('Nome, email...', 'farmacicase'); ?>">
                    </div>
                </div>
                
                <div id="fc-users-list" class="fc-loading">
                    <p><?php _e('Caricamento...', 'farmacicase'); ?></p>
                </div>
            </div>
            
            <!-- Medications Section -->
            <div class="fc-section" id="fc-section-medications">
                <div class="fc-section-header">
                    <h2><?php _e('Gestione Farmaci', 'farmacicase'); ?></h2>
                    <div class="fc-section-actions">
                        <button id="fc-new-medication-btn" class="fc-btn fc-btn-primary">
                            <i class="dashicons dashicons-plus-alt"></i> <?php _e('Aggiungi Farmaco', 'farmacicase'); ?>
                        </button>
                        <button id="fc-export-medications-btn" class="fc-btn fc-btn-secondary">
                            <i class="dashicons dashicons-download"></i> <?php _e('Esporta', 'farmacicase'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="fc-filters">
                    <div class="fc-filter-group">
                        <label for="fc-house-medication-filter"><?php _e('Casa:', 'farmacicase'); ?></label>
                        <select id="fc-house-medication-filter" class="fc-form-control">
                            <option value=""><?php _e('Tutte', 'farmacicase'); ?></option>
                            <!-- Le case verranno popolate dinamicamente -->
                        </select>
                    </div>
                    
                    <div class="fc-filter-group">
                        <label for="fc-state-medication-filter"><?php _e('Stato:', 'farmacicase'); ?></label>
                        <select id="fc-state-medication-filter" class="fc-form-control">
                            <option value=""><?php _e('Tutti', 'farmacicase'); ?></option>
                            <!-- Gli stati verranno popolati dinamicamente -->
                        </select>
                    </div>
                    
                    <div class="fc-filter-group">
                        <label for="fc-status-medication-filter"><?php _e('Status:', 'farmacicase'); ?></label>
                        <select id="fc-status-medication-filter" class="fc-form-control">
                            <option value=""><?php _e('Tutti', 'farmacicase'); ?></option>
                            <option value="ok"><?php _e('OK', 'farmacicase'); ?></option>
                            <option value="expiring"><?php _e('In Scadenza', 'farmacicase'); ?></option>
                            <option value="low"><?php _e('Sotto Soglia', 'farmacicase'); ?></option>
                        </select>
                    </div>
                    
                    <div class="fc-filter-group">
                        <label for="fc-search-medications"><?php _e('Cerca:', 'farmacicase'); ?></label>
                        <input type="text" id="fc-search-medications" class="fc-form-control" placeholder="<?php esc_attr_e('Nome, principio attivo...', 'farmacicase'); ?>">
                    </div>
                </div>
                
                <div id="fc-medications-list" class="fc-loading">
                    <p><?php _e('Caricamento...', 'farmacicase'); ?></p>
                </div>
            </div>
            
            <!-- Notifications Section -->
            <div class="fc-section" id="fc-section-notifications">
                <h2><?php _e('Notifiche', 'farmacicase'); ?></h2>
                
                <div class="fc-panel">
                    <div class="fc-panel-header">
                        <h3><?php _e('Configurazione Notifiche', 'farmacicase'); ?></h3>
                    </div>
                    <div class="fc-panel-body">
                        <form id="fc-notification-settings-form">
                            <div class="fc-form-group">
                                <label for="fc-notification-days"><?php _e('Giorni anticipo per scadenza:', 'farmacicase'); ?></label>
                                <input type="number" id="fc-notification-days" class="fc-form-control" value="<?php echo esc_attr(get_option('farmacicase_expiration_days', 60)); ?>" min="1" max="365">
                            </div>
                            
                            <div class="fc-form-actions">
                                <button type="submit" class="fc-btn fc-btn-primary"><?php _e('Salva', 'farmacicase'); ?></button>
                                <button type="button" id="fc-test-notification-btn" class="fc-btn fc-btn-secondary">
                                    <?php _e('Invia Notifica Test', 'farmacicase'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="fc-panel">
                    <div class="fc-panel-header">
                        <h3><?php _e('Storico Notifiche', 'farmacicase'); ?></h3>
                    </div>
                    <div class="fc-panel-body">
                        <div id="fc-notifications-list" class="fc-loading">
                            <p><?php _e('Caricamento...', 'farmacicase'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
<div id="fc-house-modal" class="fc-modal">
    <div class="fc-modal-content">
        <div class="fc-modal-header">
            <h3 id="fc-house-modal-title"><?php _e('Aggiungi Casa di Comunità', 'farmacicase'); ?></h3>
            <span class="fc-modal-close">&times;</span>
        </div>
        <div class="fc-modal-body">
            <form id="fc-house-form">
                <input type="hidden" id="fc-house-id" value="">
                
                <div class="fc-form-group">
                    <label for="fc-house-name"><?php _e('Nome', 'farmacicase'); ?> <span class="required">*</span></label>
                    <input type="text" id="fc-house-name" class="fc-form-control" required>
                </div>
                
                <div class="fc-form-group">
                    <label for="fc-house-address"><?php _e('Indirizzo', 'farmacicase'); ?> <span class="required">*</span></label>
                    <input type="text" id="fc-house-address" class="fc-form-control" required>
                </div>
                
                <div class="fc-form-row">
                    <div class="fc-form-group fc-form-col">
                        <label for="fc-house-city"><?php _e('Città', 'farmacicase'); ?> <span class="required">*</span></label>
                        <input type="text" id="fc-house-city" class="fc-form-control" required>
                    </div>
                    
                    <div class="fc-form-group fc-form-col">
                        <label for="fc-house-zip"><?php _e('CAP', 'farmacicase'); ?></label>
                        <input type="text" id="fc-house-zip" class="fc-form-control">
                    </div>
                </div>
                
                <div class="fc-form-group">
                    <label for="fc-house-state"><?php _e('Stato', 'farmacicase'); ?> <span class="required">*</span></label>
                    <select id="fc-house-state" class="fc-form-control" required>
                        <option value=""><?php _e('Seleziona...', 'farmacicase'); ?></option>
                        <!-- Gli stati verranno popolati dinamicamente -->
                    </select>
                </div>
                
                <div class="fc-form-row">
                    <div class="fc-form-group fc-form-col">
                        <label for="fc-house-phone"><?php _e('Telefono', 'farmacicase'); ?></label>
                        <input type="tel" id="fc-house-phone" class="fc-form-control">
                    </div>
                    
                    <div class="fc-form-group fc-form-col">
                        <label for="fc-house-email"><?php _e('Email', 'farmacicase'); ?></label>
                        <input type="email" id="fc-house-email" class="fc-form-control">
                    </div>
                </div>
                
                <div class="fc-form-group">
                    <label for="fc-house-status"><?php _e('Status', 'farmacicase'); ?></label>
                    <select id="fc-house-status" class="fc-form-control">
                        <option value="active"><?php _e('Attiva', 'farmacicase'); ?></option>
                        <option value="inactive"><?php _e('Inattiva', 'farmacicase'); ?></option>
                    </select>
                </div>
            </form>
        </div>
        <div class="fc-modal-footer">
            <button type="button" class="fc-btn fc-btn-secondary fc-modal-close-btn"><?php _e('Annulla', 'farmacicase'); ?></button>
            <button type="button" id="fc-save-house-btn" class="fc-btn fc-btn-primary"><?php _e('Salva', 'farmacicase'); ?></button>
        </div>
    </div>
</div>

<div id="fc-user-modal" class="fc-modal">
    <div class="fc-modal-content">
        <div class="fc-modal-header">
            <h3 id="fc-user-modal-title"><?php _e('Aggiungi Utente', 'farmacicase'); ?></h3>
            <span class="fc-modal-close">&times;</span>
        </div>
        <div class="fc-modal-body">
            <form id="fc-user-form">
                <input type="hidden" id="fc-user-id" value="">
                
                <div class="fc-form-group">
                    <label for="fc-user-email"><?php _e('Email', 'farmacicase'); ?> <span class="required">*</span></label>
                    <input type="email" id="fc-user-email" class="fc-form-control" required>
                </div>
                
                <div class="fc-form-row">
                    <div class="fc-form-group fc-form-col">
                        <label for="fc-user-first-name"><?php _e('Nome', 'farmacicase'); ?> <span class="required">*</span></label>
                        <input type="text" id="fc-user-first-name" class="fc-form-control" required>
                    </div>
                    
                    <div class="fc-form-group fc-form-col">
                        <label for="fc-user-last-name"><?php _e('Cognome', 'farmacicase'); ?> <span class="required">*</span></label>
                        <input type="text" id="fc-user-last-name" class="fc-form-control" required>
                    </div>
                </div>
                
                <div class="fc-form-group">
                    <label for="fc-user-phone"><?php _e('Telefono', 'farmacicase'); ?></label>
                    <input type="tel" id="fc-user-phone" class="fc-form-control">
                </div>
                
                <div class="fc-form-group">
                    <label for="fc-user-role"><?php _e('Ruolo', 'farmacicase'); ?> <span class="required">*</span></label>
                    <select id="fc-user-role" class="fc-form-control" required>
                        <option value=""><?php _e('Seleziona...', 'farmacicase'); ?></option>
                        <option value="admin"><?php _e('Admin', 'farmacicase'); ?></option>
                        <option value="responsabile"><?php _e('Responsabile', 'farmacicase'); ?></option>
                        <option value="medico"><?php _e('Medico', 'farmacicase'); ?></option>
                    </select>
                </div>
                
                <div class="fc-form-group">
                    <label><?php _e('Case Assegnate', 'farmacicase'); ?></label>
                    <div id="fc-user-houses-checkboxes" class="fc-checkbox-list">
                        <!-- Le case verranno popolate dinamicamente -->
                        <p class="fc-loading"><?php _e('Caricamento...', 'farmacicase'); ?></p>
                    </div>
                </div>
                
                <div class="fc-form-group fc-password-group">
                    <label for="fc-user-password"><?php _e('Password', 'farmacicase'); ?></label>
                    <div class="fc-password-field">
                        <input type="password" id="fc-user-password" class="fc-form-control">
                        <button type="button" class="fc-btn fc-btn-secondary fc-generate-password-btn">
                            <?php _e('Genera', 'farmacicase'); ?>
                        </button>
                    </div>
                    <p class="fc-help-text">
                        <?php _e('Lascia vuoto per mantenere la password attuale (modifica) o inviare un\'email di impostazione all\'utente (nuovo utente).', 'farmacicase'); ?>
                    </p>
                </div>
            </form>
        </div>
        <div class="fc-modal-footer">
            <button type="button" class="fc-btn fc-btn-secondary fc-modal-close-btn"><?php _e('Annulla', 'farmacicase'); ?></button>
            <button type="button" id="fc-save-user-btn" class="fc-btn fc-btn-primary"><?php _e('Salva', 'farmacicase'); ?></button>
        </div>
    </div>
</div>

<div id="fc-medication-modal" class="fc-modal">
    <div class="fc-modal-content">
        <div class="fc-modal-header">
            <h3 id="fc-medication-modal-title"><?php _e('Aggiungi Farmaco', 'farmacicase'); ?></h3>
            <span class="fc-modal-close">&times;</span>
        </div>
        <div class="fc-modal-body">
            <form id="fc-medication-form">
                <input type="hidden" id="fc-medication-id" value="">
                
                <div class="fc-form-group">
                    <label for="fc-medication-house"><?php _e('Casa di Comunità', 'farmacicase'); ?> <span class="required">*</span></label>
                    <select id="fc-medication-house" class="fc-form-control" required>
                        <option value=""><?php _e('Seleziona...', 'farmacicase'); ?></option>
                        <!-- Le case verranno popolate dinamicamente -->
                    </select>
                </div>
                
                <div class="fc-form-group">
                    <label for="fc-medication-commercial-name"><?php _e('Nome Commerciale', 'farmacicase'); ?> <span class="required">*</span></label>
                    <input type="text" id="fc-medication-commercial-name" class="fc-form-control" required>
                </div>
                
                <div class="fc-form-group">
                    <label for="fc-medication-active-ingredient"><?php _e('Principio Attivo', 'farmacicase'); ?> <span class="required">*</span></label>
                    <input type="text" id="fc-medication-active-ingredient" class="fc-form-control" required>
                </div>
                
                <div class="fc-form-group">
                    <label for="fc-medication-description"><?php _e('Descrizione', 'farmacicase'); ?></label>
                    <textarea id="fc-medication-description" class="fc-form-control" rows="3"></textarea>
                </div>
                
                <div class="fc-form-group">
                    <label for="fc-medication-leaflet-url"><?php _e('Link Foglietto Illustrativo', 'farmacicase'); ?></label>
                    <input type="url" id="fc-medication-leaflet-url" class="fc-form-control">
                </div>
                
                <div class="fc-form-row">
                    <div class="fc-form-group fc-form-col">
                        <label for="fc-medication-package-count"><?php _e('Numero Confezioni', 'farmacicase'); ?> <span class="required">*</span></label>
                        <input type="number" id="fc-medication-package-count" class="fc-form-control" min="0" required>
                    </div>
                    
                    <div class="fc-form-group fc-form-col">
                        <label for="fc-medication-total-quantity"><?php _e('Quantità Totale', 'farmacicase'); ?> <span class="required">*</span></label>
                        <input type="number" id="fc-medication-total-quantity" class="fc-form-control" min="0" required>
                    </div>
                </div>
                
                <div class="fc-form-row">
                    <div class="fc-form-group fc-form-col">
                        <label for="fc-medication-expiration"><?php _e('Data Scadenza', 'farmacicase'); ?> <span class="required">*</span></label>
                        <input type="date" id="fc-medication-expiration" class="fc-form-control" required>
                    </div>
                    
                    <div class="fc-form-group fc-form-col">
                        <label for="fc-medication-min-quantity"><?php _e('Quantità Minima', 'farmacicase'); ?> <span class="required">*</span></label>
                        <input type="number" id="fc-medication-min-quantity" class="fc-form-control" min="0" required>
                    </div>
                </div>
            </form>
        </div>
        <div class="fc-modal-footer">
            <button type="button" class="fc-btn fc-btn-secondary fc-modal-close-btn"><?php _e('Annulla', 'farmacicase'); ?></button>
            <button type="button" id="fc-save-medication-btn" class="fc-btn fc-btn-primary"><?php _e('Salva', 'farmacicase'); ?></button>
        </div>
    </div>
</div>

<div id="fc-confirm-modal" class="fc-modal">
    <div class="fc-modal-content">
        <div class="fc-modal-header">
            <h3 id="fc-confirm-modal-title"><?php _e('Conferma', 'farmacicase'); ?></h3>
            <span class="fc-modal-close">&times;</span>
        </div>
        <div class="fc-modal-body">
            <p id="fc-confirm-modal-message"></p>
        </div>
        <div class="fc-modal-footer">
            <button type="button" class="fc-btn fc-btn-secondary fc-modal-close-btn"><?php _e('Annulla', 'farmacicase'); ?></button>
            <button type="button" id="fc-confirm-modal-btn" class="fc-btn fc-btn-danger"><?php _e('Conferma', 'farmacicase'); ?></button>
        </div>
    </div>
</div>
