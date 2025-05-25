<?php
/**
 * Modulo di login per FarmaciCase
 *
 * @since      1.0.0
 * @package    FarmaciCase
 */

// Se questo file viene chiamato direttamente, interrompi
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="fc-login-container">
    <div class="fc-login-header">
        <h1><?php _e('FarmaciCase', 'farmacicase'); ?></h1>
        <p><?php _e('Sistema per il monitoraggio dei farmaci nelle Case di Comunità', 'farmacicase'); ?></p>
    </div>
    
    <?php if (isset($_GET['login']) && $_GET['login'] === 'failed'): ?>
        <div class="fc-login-error">
            <?php _e('Credenziali non corrette. Riprova.', 'farmacicase'); ?>
        </div>
    <?php endif; ?>
    
    <div class="fc-login-form">
        <form method="post" action="<?php echo esc_url(wp_login_url()); ?>" id="fc-login-form">
            <div class="fc-form-group">
                <label for="user_login"><?php _e('Email', 'farmacicase'); ?></label>
                <input type="text" name="log" id="user_login" class="fc-form-control" required>
            </div>
            
            <div class="fc-form-group">
                <label for="user_pass"><?php _e('Password', 'farmacicase'); ?></label>
                <input type="password" name="pwd" id="user_pass" class="fc-form-control" required>
            </div>
            
            <div class="fc-form-group fc-form-checkbox">
                <input type="checkbox" name="rememberme" id="rememberme" value="forever">
                <label for="rememberme"><?php _e('Ricordami', 'farmacicase'); ?></label>
            </div>
            
            <input type="hidden" name="redirect_to" value="<?php echo esc_url(home_url('/farmacicase/')); ?>">
            
            <div class="fc-form-actions">
                <button type="submit" class="fc-btn fc-btn-primary"><?php _e('Accedi', 'farmacicase'); ?></button>
            </div>
        </form>
        
        <div class="fc-login-footer">
            <a href="<?php echo esc_url(wp_lostpassword_url()); ?>" class="fc-forgot-password">
                <?php _e('Password dimenticata?', 'farmacicase'); ?>
            </a>
        </div>
    </div>
    
    <div class="fc-language-switcher">
        <select id="fc-language-select" onchange="changeFCLanguage(this.value)">
            <option value="it_IT" <?php selected(get_locale(), 'it_IT'); ?>><?php _e('Italiano', 'farmacicase'); ?></option>
            <option value="en_GB" <?php selected(get_locale(), 'en_GB'); ?>><?php _e('English', 'farmacicase'); ?></option>
            <option value="es_ES" <?php selected(get_locale(), 'es_ES'); ?>><?php _e('Español', 'farmacicase'); ?></option>
        </select>
    </div>
    
    <script>
        function changeFCLanguage(locale) {
            // Imposta cookie per la lingua
            document.cookie = "wp_lang=" + locale + "; path=/; max-age=31536000";
            // Ricarica la pagina
            window.location.reload();
        }
    </script>
</div>
