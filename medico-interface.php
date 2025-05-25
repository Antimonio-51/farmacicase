<?php
/**
 * Interfaccia Medico per FarmaciCase
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

// Ottieni le case assegnate al medico
$user_houses = $auth->get_user_houses($current_user_id);
?>

<div class="fc-container">
    <div class="fc-header">
        <h1><?php _e('FarmaciCase - Dashboard Medico', 'farmacicase'); ?></h1>
        <div class="fc-user-info">
            <?php
            $current_user = wp_get_current_user();
            echo esc_html($current_user->display_name);
            ?>
            <span class="fc-role fc-role-medico"><?php _e('Medico', 'farmacicase'); ?></span>
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
                <li class="fc-nav-item" data-section="medications">
                    <a href="#medications" class="fc-nav-link">
                        <i class="dashicons dashicons-pills"></i> <?php _e('Farmaci', 'farmacicase'); ?>
                    </a>
                </li>
                <li class="fc-nav-item" data-section="houses">
                    <a href="#houses" class="fc-nav-link">
                        <i class="dashicons dashicons-admin-home"></i> <?php _e('Le Mie Case', 'farmacicase'); ?>
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
                
                <?php if (empty($user_houses)): ?>
                <div class="fc-info-box fc-info-warning">
                    <p><?php _e('Non hai Case di Comunità assegnate. Contatta l\'amministratore.', 'farmacicase'); ?></p>
                </div>
                <?php else: ?>
                <div class="fc-dashboard-stats">
                    <div class="fc-stat-card">
                        <div class="fc-stat-icon">
                            <i class="dashicons dashicons-admin-home"></i>
                        </div>
                        <div class="fc-stat-content">
                            <div class="fc-stat-value" id="fc-house-count">0</div>
                            <div class="fc-stat-label"><?php _e('Case di Comunità', 'farmacicase'); ?></div>
                        </div>
                    </div>
                    
                    <div class="fc-stat-card">
                        <div class="fc-stat-icon">
                            <i class="dashicons dashicons-pills"></i>
                        </div>
                        <div class="fc-stat-content">
                            <div class="fc-stat-value" id="fc-medication-count">0</div>
                            <div class="fc-stat-label"><?php _e('Farmaci', 'farmacicase'); ?></div>
                        </div>
                    </div>
                    
                    <div class="fc-stat-card fc-stat-warning">
                        <div class="fc-stat-icon">
                            <i class="dashicons dashicons-calendar-alt"></i>
                        </div>
                        <div class="fc-stat-content">
                            <div class="fc-stat-value" id="fc-expiring-count">0</div>
                            <div class="fc-stat-label"><?php _e('Farmaci in Scadenza', 'farmacicase'); ?></div>
                        </div>
                    </div>
                    
                    <div class="fc-stat-card fc-stat-danger">
                        <div class="fc-stat-icon">
                            <i class="dashicons dashicons-warning"></i>
                        </div>
                        <div class="fc-stat-content">
                            <div class="fc-stat-value" id="fc-below-threshold-count">0</div>
                            <div class="fc-stat-label"><?php _e('Sotto Soglia', 'farmacicase'); ?></div>
                        </div>
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
                <?php endif; ?>
            </div>
            
            <!-- Medications Section -->
            <div class="fc-section" id="fc-section-medications">
                <div class="fc-section-header">
                    <h2><?php _e('Farmaci', 'farmacicase'); ?></h2>
                    <div class="fc-section-actions">
                        <button id="fc-export-medications-btn" class="fc-btn fc-btn-secondary">
                            <i class="dashicons dashicons-download"></i> <?php _e('Esporta', 'farmacicase'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="fc-info-box fc-info-neutral">
                    <p><?php _e('In questa sezione puoi visualizzare tutti i farmaci delle Case di Comunità a cui sei assegnato. Non puoi modificare i dati, ma puoi esportarli.', 'farmacicase'); ?></p>
                </div>
                
                <div class="fc-filters">
                    <?php if (count($user_houses) > 1): ?>
                    <div class="fc-filter-group">
                        <label for="fc-house-medication-filter"><?php _e('Casa:', 'farmacicase'); ?></label>
                        <select id="fc-house-medication-filter" class="fc-form-control">
                            <option value=""><?php _e('Tutte', 'farmacicase'); ?></option>
                            <?php foreach ($user_houses as $house): ?>
                            <option value="<?php echo esc_attr($house->id); ?>">
                                <?php echo esc_html($house->name); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
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
            
            <!-- Houses Section -->
            <div class="fc-section" id="fc-section-houses">
                <h2><?php _e('Le Mie Case di Comunità', 'farmacicase'); ?></h2>
                
                <div id="fc-houses-list" class="fc-loading">
                    <p><?php _e('Caricamento...', 'farmacicase'); ?></p>
                </div>
            </div>
            
            <!-- Notifications Section -->
            <div class="fc-section" id="fc-section-notifications">
                <h2><?php _e('Notifiche', 'farmacicase'); ?></h2>
                
                <div class="fc-panel">
                    <div class="fc-panel-header">
                        <h3><?php _e('Notifiche Recenti', 'farmacicase'); ?></h3>
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
<div id="fc-medication-details-modal" class="fc-modal">
    <div class="fc-modal-content">
        <div class="fc-modal-header">
            <h3 id="fc-medication-details-modal-title"><?php _e('Dettagli Farmaco', 'farmacicase'); ?></h3>
            <span class="fc-modal-close">&times;</span>
        </div>
        <div class="fc-modal-body">
            <div id="fc-medication-details-content" class="fc-loading">
                <p><?php _e('Caricamento...', 'farmacicase'); ?></p>
            </div>
        </div>
        <div class="fc-modal-footer">
            <button type="button" class="fc-btn fc-btn-primary fc-modal-close-btn"><?php _e('Chiudi', 'farmacicase'); ?></button>
        </div>
    </div>
</div>
