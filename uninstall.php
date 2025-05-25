<?php
/**
* Script di disinstallazione FarmaciCase
*
* Eseguito quando il plugin viene disinstallato.
* Rimuove tabelle e dati dal database.
*
* @package FarmaciCase
*/

// Se lo script di disinstallazione non viene chiamato da WordPress, esci
if (!defined('WP_UNINSTALL_PLUGIN')) {
   exit;
}

// Opzione di configurazione per determinare se eliminare dati alla disinstallazione
$delete_data = get_option('farmacicase_delete_data_on_uninstall', false);

if (!$delete_data) {
   // Non eliminare i dati se l'utente non ha attivato questa opzione
   return;
}

// Tabelle da eliminare
$tables = array(
   'fc_houses',
   'fc_users',
   'fc_user_houses',
   'fc_medications',
   'fc_medication_history',
   'fc_notifications',
   'fc_notification_recipients'
);

global $wpdb;

// Elimina ciascuna tabella
foreach ($tables as $table) {
   $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table}");
}

// Rimuovi i ruoli personalizzati
remove_role('fc_admin');
remove_role('fc_responsabile');
remove_role('fc_medico');

// Elimina le opzioni salvate
delete_option('farmacicase_version');
delete_option('farmacicase_email_sender');
delete_option('farmacicase_notification_time');
delete_option('farmacicase_expiration_days');
delete_option('farmacicase_delete_data_on_uninstall');

// Rimuovi gli utenti
// Nota: questo non elimina gli utenti WordPress, rimuove solo
// le associazioni con il plugin FarmaciCase