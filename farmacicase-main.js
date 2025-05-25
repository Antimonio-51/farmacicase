/**
 * Script principale FarmaciCase
 *
 * @since      1.0.0
 * @package    FarmaciCase
 */

(function($) {
    'use strict';

    // Variabili globali
    var FC = {
        // Dati dell'applicazione
        data: {
            houses: [],
            medications: [],
            notifications: [],
            states: []
        },
        
        // Elementi DOM frequentemente utilizzati
        elements: {},
        
        // Utente corrente
        currentUser: {
            role: farmacicase_data.user_role || 'anonymous'
        },
        
        // Flag per caricare i dati solo una volta
        dataLoaded: {
            houses: false,
            medications: false,
            notifications: false
        },
        
        // Filtri correnti
        filters: {
            house: '',
            state: '',
            status: '',
            search: '',
            role: ''
        },
        
        // Costanti per la visualizzazione
        EXPIRATION_DAYS_THRESHOLD: 60, // Giorni prima della scadenza per segnalare un farmaco
        
        // Modal corrente
        currentModal: null,
        
        // ID elemento corrente per operazioni
        currentId: null
    };

    // Inizializzazione
    $(document).ready(function() {
        // Definizione elementi DOM principali
        FC.elements = {
            // Navigazione
            navItems: $('.fc-nav-item'),
            sections: $('.fc-section'),
            
            // Liste
            housesList: $('#fc-houses-list'),
            medicationsList: $('#fc-medications-list'),
            usersList: $('#fc-users-list'),
            notificationsList: $('#fc-notifications-list'),
            expiringList: $('#fc-expiring-medications-list'),
            lowQuantityList: $('#fc-low-quantity-medications-list'),
            
            // Filtri
            stateFilter: $('#fc-state-filter'),
            statusFilter: $('#fc-status-filter'),
            statusMedicationFilter: $('#fc-status-medication-filter'),
            houseFilter: $('#fc-house-medication-filter'),
            houseUserFilter: $('#fc-house-user-filter'),
            roleFilter: $('#fc-role-filter'),
            searchMedications: $('#fc-search-medications'),
            searchHouses: $('#fc-search-houses'),
            searchUsers: $('#fc-search-users'),
            
            // Statistiche
            houseCount: $('#fc-house-count'),
            medicationCount: $('#fc-medication-count'),
            expiringCount: $('#fc-expiring-count'),
            belowThresholdCount: $('#fc-below-threshold-count'),
            
            // Modali
            houseModal: $('#fc-house-modal'),
            userModal: $('#fc-user-modal'),
            medicationModal: $('#fc-medication-modal'),
            medicationDetailsModal: $('#fc-medication-details-modal'),
            confirmModal: $('#fc-confirm-modal'),
            
            // Pulsanti
            newHouseBtn: $('#fc-new-house-btn'),
            saveHouseBtn: $('#fc-save-house-btn'),
            newUserBtn: $('#fc-new-user-btn'),
            saveUserBtn: $('#fc-save-user-btn'),
            newMedicationBtn: $('#fc-new-medication-btn'),
            saveMedicationBtn: $('#fc-save-medication-btn'),
            exportMedicationsBtn: $('#fc-export-medications-btn'),
            confirmModalBtn: $('#fc-confirm-modal-btn'),
            
            // Form
            houseForm: $('#fc-house-form'),
            userForm: $('#fc-user-form'),
            medicationForm: $('#fc-medication-form')
        };

        // Inizializza gli eventi
        initEvents();
        
        // Carica i dati iniziali in base alla sezione attiva
        loadInitialData();
    });

    /**
     * Inizializza gli eventi dell'interfaccia
     */
    function initEvents() {
        // Cambio sezione nella navigazione
        FC.elements.navItems.on('click', function(e) {
            var section = $(this).data('section');
            if (section) {
                e.preventDefault();
                changeSection(section);
            }
        });
        
        // Filtraggio
        if (FC.elements.stateFilter.length) {
            FC.elements.stateFilter.on('change', function() {
                FC.filters.state = $(this).val();
                renderHouses();
            });
        }
        
        if (FC.elements.statusFilter.length) {
            FC.elements.statusFilter.on('change', function() {
                FC.filters.status = $(this).val();
                renderHouses();
            });
        }
        
        if (FC.elements.statusMedicationFilter.length) {
            FC.elements.statusMedicationFilter.on('change', function() {
                FC.filters.status = $(this).val();
                renderMedications();
            });
        }
        
        if (FC.elements.houseFilter.length) {
            FC.elements.houseFilter.on('change', function() {
                FC.filters.house = $(this).val();
                renderMedications();
            });
        }
        
        if (FC.elements.houseUserFilter.length) {
            FC.elements.houseUserFilter.on('change', function() {
                FC.filters.house = $(this).val();
                renderUsers();
            });
        }
        
        if (FC.elements.roleFilter.length) {
            FC.elements.roleFilter.on('change', function() {
                FC.filters.role = $(this).val();
                renderUsers();
            });
        }
        
        if (FC.elements.searchMedications.length) {
            FC.elements.searchMedications.on('input', function() {
                FC.filters.search = $(this).val();
                renderMedications();
            });
        }
        
        if (FC.elements.searchHouses.length) {
            FC.elements.searchHouses.on('input', function() {
                FC.filters.search = $(this).val();
                renderHouses();
            });
        }
        
        if (FC.elements.searchUsers.length) {
            FC.elements.searchUsers.on('input', function() {
                FC.filters.search = $(this).val();
                renderUsers();
            });
        }
        
        // Gestione modali
        $('.fc-modal-close, .fc-modal-close-btn').on('click', closeCurrentModal);
        
        // Pulsanti di azione
        if (FC.elements.newHouseBtn.length) {
            FC.elements.newHouseBtn.on('click', showAddHouseModal);
        }
        
        if (FC.elements.saveHouseBtn.length) {
            FC.elements.saveHouseBtn.on('click', saveHouse);
        }
        
        if (FC.elements.newUserBtn.length) {
            FC.elements.newUserBtn.on('click', showAddUserModal);
        }
        
        if (FC.elements.saveUserBtn.length) {
            FC.elements.saveUserBtn.on('click', saveUser);
        }
        
        if (FC.elements.newMedicationBtn.length) {
            FC.elements.newMedicationBtn.on('click', showAddMedicationModal);
        }
        
        if (FC.elements.saveMedicationBtn.length) {
            FC.elements.saveMedicationBtn.on('click', saveMedication);
        }
        
        if (FC.elements.exportMedicationsBtn.length) {
            FC.elements.exportMedicationsBtn.on('click', exportMedications);
        }
        
        // Chiudi modal con Escape
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                closeCurrentModal();
            }
        });
    }

    /**
     * Carica i dati iniziali in base alla sezione attiva
     */
    function loadInitialData() {
        // Determina quale sezione è attiva
        var activeSection = FC.elements.sections.filter('.fc-section-active').attr('id');
        activeSection = activeSection ? activeSection.replace('fc-section-', '') : '';
        
        // Carica i dati in base alla sezione attiva
        switch (activeSection) {
            case 'dashboard':
                loadDashboardData();
                break;
            case 'houses':
                loadHousesData();
                break;
            case 'medications':
                loadMedicationsData();
                break;
            case 'users':
                loadUsersData();
                break;
            case 'notifications':
                loadNotificationsData();
                break;
        }
        
        // Carica comunque i dati degli stati che servono ovunque
        loadStatesData();
    }

    /**
     * Cambia sezione attiva
     * 
     * @param {string} section Identificatore della sezione
     */
    function changeSection(section) {
        // Aggiorna classe attiva sulla navigazione
        FC.elements.navItems.removeClass('active');
        FC.elements.navItems.filter('[data-section="' + section + '"]').addClass('active');
        
        // Cambia la sezione visibile
        FC.elements.sections.removeClass('fc-section-active');
        $('#fc-section-' + section).addClass('fc-section-active');
        
        // Carica i dati necessari se non sono già stati caricati
        switch (section) {
            case 'dashboard':
                loadDashboardData();
                break;
            case 'houses':
                loadHousesData();
                break;
            case 'medications':
                loadMedicationsData();
                break;
            case 'users':
                loadUsersData();
                break;
            case 'notifications':
                loadNotificationsData();
                break;
        }
        
        // Aggiorna URL con un hash
        window.location.hash = section;
    }

    /**
     * Carica i dati degli stati europei
     */
    function loadStatesData() {
        // Dati statici degli stati europei
        FC.data.states = [
            'Albania', 'Andorra', 'Austria', 'Belgio', 'Bielorussia', 'Bosnia ed Erzegovina', 
            'Bulgaria', 'Cipro', 'Città del Vaticano', 'Croazia', 'Danimarca', 'Estonia', 
            'Finlandia', 'Francia', 'Germania', 'Grecia', 'Irlanda', 'Islanda', 'Italia', 
            'Lettonia', 'Liechtenstein', 'Lituania', 'Lussemburgo', 'Macedonia del Nord', 
            'Malta', 'Moldavia', 'Monaco', 'Montenegro', 'Norvegia', 'Paesi Bassi', 
            'Polonia', 'Portogallo', 'Regno Unito', 'Repubblica Ceca', 'Romania', 'Russia', 
            'San Marino', 'Serbia', 'Slovacchia', 'Slovenia', 'Spagna', 'Svezia', 
            'Svizzera', 'Ucraina', 'Ungheria'
        ];
        
        // Popola i select degli stati
        var stateOptions = '<option value="">' + (FC.elements.stateFilter.find('option:first').text() || 'Tutti') + '</option>';
        FC.data.states.forEach(function(state) {
            stateOptions += '<option value="' + state + '">' + state + '</option>';
        });
        
        // Aggiungi opzioni ai selettori
        if (FC.elements.stateFilter.length) {
            FC.elements.stateFilter.html(stateOptions);
        }
        
        if ($('#fc-house-state').length) {
            $('#fc-house-state').html('<option value="">' + ($('#fc-house-state option:first').text() || 'Seleziona...') + '</option>' + 
                FC.data.states.map(function(state) {
                    return '<option value="' + state + '">' + state + '</option>';
                }).join('')
            );
        }
        
        if ($('#fc-state-medication-filter').length) {
            $('#fc-state-medication-filter').html(stateOptions);
        }
    }

    /**
     * Carica i dati della dashboard
     */
    function loadDashboardData() {
        // Carica i farmaci in scadenza e sotto soglia
        if (!FC.dataLoaded.medications) {
            FC.elements.expiringList.addClass('fc-loading');
            FC.elements.lowQuantityList.addClass('fc-loading');
            
            $.ajax({
                url: farmacicase_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'farmacicase_get_medications',
                    security: farmacicase_data.nonce
                },
                success: function(response) {
                    if (response.success) {
                        FC.data.medications = response.data.medications || [];
                        FC.dataLoaded.medications = true;
                        
                        // Aggiorna contatori
                        updateDashboardCounters();
                        
                        // Renderizza liste
                        renderExpiringMedications();
                        renderLowQuantityMedications();
                    } else {
                        // Gestione errori
                        FC.elements.expiringList.html('<p class="fc-error">' + (response.data.message || 'Errore nel caricamento dei dati') + '</p>');
                        FC.elements.lowQuantityList.html('<p class="fc-error">' + (response.data.message || 'Errore nel caricamento dei dati') + '</p>');
                    }
                    
                    FC.elements.expiringList.removeClass('fc-loading');
                    FC.elements.lowQuantityList.removeClass('fc-loading');
                },
                error: function() {
                    // Gestione errore di rete
                    FC.elements.expiringList.html('<p class="fc-error">Errore di connessione al server</p>');
                    FC.elements.lowQuantityList.html('<p class="fc-error">Errore di connessione al server</p>');
                    
                    FC.elements.expiringList.removeClass('fc-loading');
                    FC.elements.lowQuantityList.removeClass('fc-loading');
                }
            });
        } else {
            // Usa i dati già caricati
            updateDashboardCounters();
            renderExpiringMedications();
            renderLowQuantityMedications();
        }
        
        // Carica Case se necessario per i contatori
        if (!FC.dataLoaded.houses) {
            $.ajax({
                url: farmacicase_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'farmacicase_get_houses',
                    security: farmacicase_data.nonce
                },
                success: function(response) {
                    if (response.success) {
                        FC.data.houses = response.data.houses || [];
                        FC.dataLoaded.houses = true;
                        
                        // Aggiorna conteggio case
                        if (FC.elements.houseCount.length) {
                            FC.elements.houseCount.text(FC.data.houses.length);
                        }
                    }
                }
            });
        }
    }

    /**
     * Aggiorna i contatori della dashboard
     */
    function updateDashboardCounters() {
        if (!FC.elements.medicationCount.length) return;
        
        // Conteggio totale farmaci
        FC.elements.medicationCount.text(FC.data.medications.length);
        
        // Farmaci in scadenza
        var now = new Date();
        var thresholdDate = new Date();
        thresholdDate.setDate(now.getDate() + FC.EXPIRATION_DAYS_THRESHOLD);
        
        var expiringCount = FC.data.medications.filter(function(med) {
            var expirationDate = new Date(med.expiration_date);
            return expirationDate <= thresholdDate && expirationDate >= now;
        }).length;
        
        FC.elements.expiringCount.text(expiringCount);
        
        // Farmaci sotto soglia
        var lowQuantityCount = FC.data.medications.filter(function(med) {
            return parseInt(med.total_quantity) <= parseInt(med.min_quantity_alert);
        }).length;
        
        FC.elements.belowThresholdCount.text(lowQuantityCount);
    }

    /**
     * Renderizza la lista dei farmaci in scadenza
     */
    function renderExpiringMedications() {
        if (!FC.elements.expiringList.length) return;
        
        var now = new Date();
        var thresholdDate = new Date();
        thresholdDate.setDate(now.getDate() + FC.EXPIRATION_DAYS_THRESHOLD);
        
        var expiringMeds = FC.data.medications.filter(function(med) {
            var expirationDate = new Date(med.expiration_date);
            return expirationDate <= thresholdDate && expirationDate >= now;
        });
        
        if (expiringMeds.length === 0) {
            FC.elements.expiringList.html('<p class="fc-info-box fc-info-success">Nessun farmaco in scadenza nei prossimi ' + FC.EXPIRATION_DAYS_THRESHOLD + ' giorni.</p>');
            return;
        }
        
        var html = '<table class="fc-table">';
        html += '<thead><tr>';
        html += '<th>Nome</th>';
        html += '<th>Principio Attivo</th>';
        html += '<th>Scadenza</th>';
        html += '<th>Giorni Rimasti</th>';
        html += '<th>Casa</th>';
        html += '</tr></thead>';
        
        html += '<tbody>';
        
        expiringMeds.forEach(function(med) {
            var expirationDate = new Date(med.expiration_date);
            var daysLeft = Math.ceil((expirationDate - now) / (1000 * 60 * 60 * 24));
            var house = FC.data.houses.find(function(h) { return h.id === med.house_id; });
            
            html += '<tr>';
            html += '<td>' + med.commercial_name + '</td>';
            html += '<td>' + med.active_ingredient + '</td>';
            html += '<td>' + formatDate(med.expiration_date) + '</td>';
            html += '<td>' + daysLeft + '</td>';
            html += '<td>' + (house ? house.name : 'Casa #' + med.house_id) + '</td>';
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        
        FC.elements.expiringList.html(html);
    }

    /**
     * Renderizza la lista dei farmaci sotto soglia
     */
    function renderLowQuantityMedications() {
        if (!FC.elements.lowQuantityList.length) return;
        
        var lowQuantityMeds = FC.data.medications.filter(function(med) {
            return parseInt(med.total_quantity) <= parseInt(med.min_quantity_alert);
        });
        
        if (lowQuantityMeds.length === 0) {
            FC.elements.lowQuantityList.html('<p class="fc-info-box fc-info-success">Nessun farmaco sotto la soglia minima.</p>');
            return;
        }
        
        var html = '<table class="fc-table">';
        html += '<thead><tr>';
        html += '<th>Nome</th>';
        html += '<th>Principio Attivo</th>';
        html += '<th>Quantità</th>';
        html += '<th>Soglia Minima</th>';
        html += '<th>Casa</th>';
        html += '</tr></thead>';
        
        html += '<tbody>';
        
        lowQuantityMeds.forEach(function(med) {
            var house = FC.data.houses.find(function(h) { return h.id === med.house_id; });
            
            html += '<tr>';
            html += '<td>' + med.commercial_name + '</td>';
            html += '<td>' + med.active_ingredient + '</td>';
            html += '<td>' + med.total_quantity + '</td>';
            html += '<td>' + med.min_quantity_alert + '</td>';
            html += '<td>' + (house ? house.name : 'Casa #' + med.house_id) + '</td>';
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        
        FC.elements.lowQuantityList.html(html);
    }

    /**
     * Carica i dati delle case
     */
    function loadHousesData() {
        if (FC.dataLoaded.houses) {
            // Usa i dati già caricati
            renderHouses();
            return;
        }
        
        FC.elements.housesList.addClass('fc-loading');
        
        $.ajax({
            url: farmacicase_data.ajax_url,
            type: 'POST',
            data: {
                action: 'farmacicase_get_houses',
                security: farmacicase_data.nonce
            },
            success: function(response) {
                if (response.success) {
                    FC.data.houses = response.data.houses || [];
                    FC.dataLoaded.houses = true;
                    renderHouses();
                    
                    // Popola selettori case se esistono
                    populateHouseSelectors();
                } else {
                    // Gestione errori
                    FC.elements.housesList.html('<p class="fc-error">' + (response.data.message || 'Errore nel caricamento dei dati') + '</p>');
                }
                
                FC.elements.housesList.removeClass('fc-loading');
            },
            error: function() {
                // Gestione errore di rete
                FC.elements.housesList.html('<p class="fc-error">Errore di connessione al server</p>');
                FC.elements.housesList.removeClass('fc-loading');
            }
        });
    }

    /**
     * Popola selettori case
     */
    function populateHouseSelectors() {
        var options = '<option value="">' + (FC.elements.houseFilter ? FC.elements.houseFilter.find('option:first').text() : 'Tutte') + '</option>';
        
        FC.data.houses.forEach(function(house) {
            options += '<option value="' + house.id + '">' + house.name + '</option>';
        });
        
        // Popola selettori
        if (FC.elements.houseFilter && FC.elements.houseFilter.length) {
            FC.elements.houseFilter.html(options);
        }
        
        if (FC.elements.houseUserFilter && FC.elements.houseUserFilter.length) {
            FC.elements.houseUserFilter.html(options);
        }
        
        if ($('#fc-medication-house') && $('#fc-medication-house').is('select')) {
            $('#fc-medication-house').html('<option value="">' + ($('#fc-medication-house option:first').text() || 'Seleziona...') + '</option>' + 
                FC.data.houses.map(function(house) {
                    return '<option value="' + house.id + '">' + house.name + '</option>';
                }).join('')
            );
        }
    }

    /**
     * Renderizza le case
     */
    function renderHouses() {
        if (!FC.elements.housesList.length) return;
        
        // Filtra le case
        var houses = FC.data.houses.filter(function(house) {
            // Filtra per stato
            if (FC.filters.state && house.state !== FC.filters.state) {
                return false;
            }
            
            // Filtra per status
            if (FC.filters.status && house.status !== FC.filters.status) {
                return false;
            }
            
            // Filtra per testo di ricerca
            if (FC.filters.search) {
                var searchLower = FC.filters.search.toLowerCase();
                return house.name.toLowerCase().includes(searchLower) || 
                       house.city.toLowerCase().includes(searchLower) || 
                       house.address.toLowerCase().includes(searchLower);
            }
            
            return true;
        });
        
        if (houses.length === 0) {
            FC.elements.housesList.html('<p class="fc-info-box fc-info-warning">Nessuna casa trovata con i filtri selezionati.</p>');
            return;
        }
        
        var html = '<table class="fc-table">';
        html += '<thead><tr>';
        html += '<th>Nome</th>';
        html += '<th>Città</th>';
        html += '<th>Stato</th>';
        html += '<th>Indirizzo</th>';
        html += '<th>Status</th>';
        
        // Solo admin vede le azioni
        if (FC.currentUser.role === 'admin') {
            html += '<th>Azioni</th>';
        }
        
        html += '</tr></thead>';
        
        html += '<tbody>';
        
        houses.forEach(function(house) {
            html += '<tr data-id="' + house.id + '">';
            html += '<td>' + house.name + '</td>';
            html += '<td>' + house.city + '</td>';
            html += '<td>' + house.state + '</td>';
            html += '<td>' + house.address + '</td>';
            html += '<td>' + (house.status === 'active' ? 
                '<span class="fc-badge fc-badge-success">Attiva</span>' : 
                '<span class="fc-badge fc-badge-inactive">Inattiva</span>') + '</td>';
            
            // Solo admin vede le azioni
            if (FC.currentUser.role === 'admin') {
                html += '<td class="fc-table-actions">';
                html += '<button type="button" class="fc-action-btn fc-edit" data-id="' + house.id + '"><i class="dashicons dashicons-edit"></i></button>';
                html += '<button type="button" class="fc-action-btn fc-delete" data-id="' + house.id + '"><i class="dashicons dashicons-trash"></i></button>';
                html += '</td>';
            }
            
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        
        FC.elements.housesList.html(html);
        
        // Aggiungi eventi alle azioni
        if (FC.currentUser.role === 'admin') {
            $('.fc-edit', FC.elements.housesList).on('click', function() {
                var houseId = $(this).data('id');
                editHouse(houseId);
            });
            
            $('.fc-delete', FC.elements.housesList).on('click', function() {
                var houseId = $(this).data('id');
                var house = FC.data.houses.find(function(h) { return h.id == houseId; });
                confirmDelete('casa', house ? house.name : 'Sconosciuta', function() {
                    deleteHouse(houseId);
                });
            });
        }
    }

    /**
     * Mostra il modal per aggiungere una casa
     */
    function showAddHouseModal() {
        // Reset form
        $('#fc-house-id').val('');
        $('#fc-house-name').val('');
        $('#fc-house-address').val('');
        $('#fc-house-city').val('');
        $('#fc-house-zip').val('');
        $('#fc-house-state').val('');
        $('#fc-house-phone').val('');
        $('#fc-house-email').val('');
        $('#fc-house-status').val('active');
        
        // Aggiorna titolo
        $('#fc-house-modal-title').text('Aggiungi Casa di Comunità');
        
        // Mostra modal
        showModal(FC.elements.houseModal);
    }

    /**
     * Prepara modal per modificare una casa
     * 
     * @param {number} houseId ID della casa
     */
    function editHouse(houseId) {
        var house = FC.data.houses.find(function(h) { return h.id == houseId; });
        
        if (!house) {
            alert('Casa non trovata');
            return;
        }
        
        // Compila form
        $('#fc-house-id').val(house.id);
        $('#fc-house-name').val(house.name);
        $('#fc-house-address').val(house.address);
        $('#fc-house-city').val(house.city);
        $('#fc-house-zip').val(house.zip);
        $('#fc-house-state').val(house.state);
        $('#fc-house-phone').val(house.phone);
        $('#fc-house-email').val(house.email);
        $('#fc-house-status').val(house.status);
        
        // Aggiorna titolo
        $('#fc-house-modal-title').text('Modifica Casa di Comunità');
        
        // Mostra modal
        showModal(FC.elements.houseModal);
    }

    /**
     * Salva una casa (creazione o modifica)
     */
    function saveHouse() {
        // Validazione base
        var houseId = $('#fc-house-id').val();
        var name = $('#fc-house-name').val().trim();
        var address = $('#fc-house-address').val().trim();
        var city = $('#fc-house-city').val().trim();
        var state = $('#fc-house-state').val();
        
        if (!name || !address || !city || !state) {
            alert('Compila tutti i campi obbligatori');
            return;
        }
        
        // Dati per l'invio
        var data = {
            action: 'farmacicase_save_house',
            security: farmacicase_data.nonce,
            id: houseId,
            name: name,
            address: address,
            city: city,
            zip: $('#fc-house-zip').val().trim(),
            state: state,
            phone: $('#fc-house-phone').val().trim(),
            email: $('#fc-house-email').val().trim(),
            status: $('#fc-house-status').val()
        };
        
        // Disabilita pulsante durante invio
        FC.elements.saveHouseBtn.prop('disabled', true).text('Salvataggio...');
        
        // Invia richiesta
        $.ajax({
            url: farmacicase_data.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    // Aggiorna dati locali
                    var house = response.data.house;
                    
                    if (houseId) {
                        // Modifica
                        var index = FC.data.houses.findIndex(function(h) { return h.id == houseId; });
                        if (index !== -1) {
                            FC.data.houses[index] = house;
                        }
                    } else {
                        // Nuova casa
                        FC.data.houses.push(house);
                    }
                    
                    // Ricarica vista
                    renderHouses();
                    populateHouseSelectors();
                    
                    // Chiudi modal
                    closeCurrentModal();
                    
                    // Mostra conferma
                    alert(houseId ? 'Casa aggiornata con successo' : 'Casa creata con successo');
                } else {
                    // Mostra errore
                    alert(response.data.message || 'Errore nel salvare i dati');
                }
                
                // Riabilita pulsante
                FC.elements.saveHouseBtn.prop('disabled', false).text('Salva');
            },
            error: function() {
                alert('Errore di connessione al server');
                FC.elements.saveHouseBtn.prop('disabled', false).text('Salva');
            }
        });
    }

    /**
     * Elimina una casa
     * 
     * @param {number} houseId ID della casa
     */
    function deleteHouse(houseId) {
        $.ajax({
            url: farmacicase_data.ajax_url,
            type: 'POST',
            data: {
                action: 'farmacicase_delete_house',
                security: farmacicase_data.nonce,
                id: houseId
            },
            success: function(response) {
                if (response.success) {
                    // Rimuovi dai dati locali
                    FC.data.houses = FC.data.houses.filter(function(h) { return h.id != houseId; });
                    
                    // Aggiorna vista
                    renderHouses();
                    populateHouseSelectors();
                    
                    // Mostra conferma
                    alert('Casa eliminata con successo');
                } else {
                    alert(response.data.message || 'Errore nell\'eliminare la casa');
                }
            },
            error: function() {
                alert('Errore di connessione al server');
            }
        });
    }

    /**
     * Carica i dati dei farmaci
     */
    function loadMedicationsData() {
        if (FC.dataLoaded.medications) {
            // Usa i dati già caricati
            renderMedications();
            return;
        }
        
        FC.elements.medicationsList.addClass('fc-loading');
        
        $.ajax({
            url: farmacicase_data.ajax_url,
            type: 'POST',
            data: {
                action: 'farmacicase_get_medications',
                security: farmacicase_data.nonce
            },
            success: function(response) {
                if (response.success) {
                    FC.data.medications = response.data.medications || [];
                    FC.dataLoaded.medications = true;
                    renderMedications();
                } else {
                    // Gestione errori
                    FC.elements.medicationsList.html('<p class="fc-error">' + (response.data.message || 'Errore nel caricamento dei dati') + '</p>');
                }
                
                FC.elements.medicationsList.removeClass('fc-loading');
            },
            error: function() {
                // Gestione errore di rete
                FC.elements.medicationsList.html('<p class="fc-error">Errore di connessione al server</p>');
                FC.elements.medicationsList.removeClass('fc-loading');
            }
        });
        
        // Carica anche le Case se non sono già state caricate
        if (!FC.dataLoaded.houses) {
            loadHousesData();
        }
    }

    /**
     * Renderizza i farmaci
     */
    function renderMedications() {
        if (!FC.elements.medicationsList.length) return;
        
        // Filtra i farmaci
        var medications = FC.data.medications.filter(function(med) {
            // Filtra per casa
            if (FC.filters.house && med.house_id != FC.filters.house) {
                return false;
            }
            
            // Filtra per stato
            if (FC.filters.status) {
                var now = new Date();
                var expirationDate = new Date(med.expiration_date);
                var thresholdDate = new Date();
                thresholdDate.setDate(now.getDate() + FC.EXPIRATION_DAYS_THRESHOLD);
                
                if (FC.filters.status === 'expiring' && 
                    !(expirationDate <= thresholdDate && expirationDate >= now)) {
                    return false;
                }
                
                if (FC.filters.status === 'low' && 
                    !(parseInt(med.total_quantity) <= parseInt(med.min_quantity_alert))) {
                    return false;
                }
                
                if (FC.filters.status === 'ok' && 
                    (expirationDate <= thresholdDate || parseInt(med.total_quantity) <= parseInt(med.min_quantity_alert))) {
                    return false;
                }
            }
            
            // Filtra per testo di ricerca
            if (FC.filters.search) {
                var searchLower = FC.filters.search.toLowerCase();
                return med.commercial_name.toLowerCase().includes(searchLower) || 
                       med.active_ingredient.toLowerCase().includes(searchLower) || 
                       (med.description && med.description.toLowerCase().includes(searchLower));
            }
            
            return true;
        });
        
        // Ordina per nome commerciale
        medications.sort(function(a, b) {
            return a.commercial_name.localeCompare(b.commercial_name);
        });
        
        if (medications.length === 0) {
            FC.elements.medicationsList.html('<p class="fc-info-box fc-info-warning">Nessun farmaco trovato con i filtri selezionati.</p>');
            return;
        }
        
        var html = '<table class="fc-table">';
        html += '<thead><tr>';
        html += '<th>Nome</th>';
        html += '<th>Principio Attivo</th>';
        html += '<th>Quantità</th>';
        html += '<th>Min</th>';
        html += '<th>Scadenza</th>';
        html += '<th>Stato</th>';
        html += '<th>Casa</th>';
        
        // Azioni solo per admin e responsabili
        if (FC.currentUser.role === 'admin' || FC.currentUser.role === 'responsabile') {
            html += '<th>Azioni</th>';
        }
        
        html += '</tr></thead>';
        
        html += '<tbody>';
        
        var now = new Date();
        var thresholdDate = new Date();
        thresholdDate.setDate(now.getDate() + FC.EXPIRATION_DAYS_THRESHOLD);
        
        medications.forEach(function(med) {
            var expirationDate = new Date(med.expiration_date);
            var isExpiring = expirationDate <= thresholdDate && expirationDate >= now;
            var isExpired = expirationDate < now;
            var isLowQuantity = parseInt(med.total_quantity) <= parseInt(med.min_quantity_alert);
            
            var statusClass = 'success';
            var statusText = 'OK';
            
            if (isExpired) {
                statusClass = 'danger';
                statusText = 'Scaduto';
            } else if (isExpiring) {
                statusClass = 'warning';
                statusText = 'In Scadenza';
            } else if (isLowQuantity) {
                statusClass = 'warning';
                statusText = 'Sotto Soglia';
            }
            
            var house = FC.data.houses.find(function(h) { return h.id == med.house_id; });
            
            html += '<tr data-id="' + med.id + '">';
            html += '<td>' + med.commercial_name + '</td>';
            html += '<td>' + med.active_ingredient + '</td>';
            html += '<td>' + med.total_quantity + '</td>';
            html += '<td>' + med.min_quantity_alert + '</td>';
            html += '<td>' + formatDate(med.expiration_date) + '</td>';
            html += '<td><span class="fc-indicator fc-indicator-' + statusClass + '"></span>' + statusText + '</td>';
            html += '<td>' + (house ? house.name : 'Casa #' + med.house_id) + '</td>';
            
            // Azioni per admin e responsabili
            if (FC.currentUser.role === 'admin' || FC.currentUser.role === 'responsabile') {
                html += '<td class="fc-table-actions">';
                html += '<button type="button" class="fc-action-btn fc-edit" data-id="' + med.id + '"><i class="dashicons dashicons-edit"></i></button>';
                html += '<button type="button" class="fc-action-btn fc-delete" data-id="' + med.id + '"><i class="dashicons dashicons-trash"></i></button>';
                html += '</td>';
            }
            
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        
        FC.elements.medicationsList.html(html);
        
        // Aggiungi eventi alle azioni
        if (FC.currentUser.role === 'admin' || FC.currentUser.role === 'responsabile') {
            $('.fc-edit', FC.elements.medicationsList).on('click', function() {
                var medicationId = $(this).data('id');
                editMedication(medicationId);
            });
            
            $('.fc-delete', FC.elements.medicationsList).on('click', function() {
                var medicationId = $(this).data('id');
                var medication = FC.data.medications.find(function(m) { return m.id == medicationId; });
                confirmDelete('farmaco', medication ? medication.commercial_name : 'Sconosciuto', function() {
                    deleteMedication(medicationId);
                });
            });
        }
    }

    /**
     * Mostra il modal per aggiungere un farmaco
     */
    function showAddMedicationModal() {
        // Reset form
        $('#fc-medication-id').val('');
        $('#fc-medication-commercial-name').val('');
        $('#fc-medication-active-ingredient').val('');
        $('#fc-medication-description').val('');
        $('#fc-medication-leaflet-url').val('');
        $('#fc-medication-package-count').val('');
        $('#fc-medication-total-quantity').val('');
        $('#fc-medication-expiration').val('');
        $('#fc-medication-min-quantity').val('');
        
        // Se è un select, imposta il valore default
        if ($('#fc-medication-house').is('select')) {
            $('#fc-medication-house').val('');
        }
        
        // Aggiorna titolo
        $('#fc-medication-modal-title').text('Aggiungi Farmaco');
        
        // Mostra modal
        showModal(FC.elements.medicationModal);
    }

    /**
     * Prepara modal per modificare un farmaco
     * 
     * @param {number} medicationId ID del farmaco
     */
    function editMedication(medicationId) {
        var medication = FC.data.medications.find(function(m) { return m.id == medicationId; });
        
        if (!medication) {
            alert('Farmaco non trovato');
            return;
        }
        
        // Compila form
        $('#fc-medication-id').val(medication.id);
        $('#fc-medication-commercial-name').val(medication.commercial_name);
        $('#fc-medication-active-ingredient').val(medication.active_ingredient);
        $('#fc-medication-description').val(medication.description);
        $('#fc-medication-leaflet-url').val(medication.leaflet_url);
        $('#fc-medication-package-count').val(medication.package_count);
        $('#fc-medication-total-quantity').val(medication.total_quantity);
        
        // Formatta data per input date
        if (medication.expiration_date) {
            var parts = medication.expiration_date.split('-');
            if (parts.length === 3) {
                $('#fc-medication-expiration').val(parts[0] + '-' + parts[1] + '-' + parts[2].split(' ')[0]);
            }
        }
        
        $('#fc-medication-min-quantity').val(medication.min_quantity_alert);
        
        // Se è un select, imposta il valore della casa
        if ($('#fc-medication-house').is('select')) {
            $('#fc-medication-house').val(medication.house_id);
        }
        
        // Aggiorna titolo
        $('#fc-medication-modal-title').text('Modifica Farmaco');
        
        // Mostra modal
        showModal(FC.elements.medicationModal);
    }

    /**
     * Salva un farmaco (creazione o modifica)
     */
    function saveMedication() {
        // Validazione base
        var medicationId = $('#fc-medication-id').val();
        var commercialName = $('#fc-medication-commercial-name').val().trim();
        var activeIngredient = $('#fc-medication-active-ingredient').val().trim();
        var houseId = $('#fc-medication-house').is('select') ? $('#fc-medication-house').val() : $('#fc-medication-house').val();
        var packageCount = $('#fc-medication-package-count').val();
        var totalQuantity = $('#fc-medication-total-quantity').val();
        var expiration = $('#fc-medication-expiration').val();
        var minQuantity = $('#fc-medication-min-quantity').val();
        
        if (!commercialName || !activeIngredient || !houseId || !packageCount || !totalQuantity || !expiration || !minQuantity) {
            alert('Compila tutti i campi obbligatori');
            return;
        }
        
        // Dati per l'invio
        var data = {
            action: 'farmacicase_save_medication',
            security: farmacicase_data.nonce,
            id: medicationId,
            house_id: houseId,
            commercial_name: commercialName,
            active_ingredient: activeIngredient,
            description: $('#fc-medication-description').val().trim(),
            leaflet_url: $('#fc-medication-leaflet-url').val().trim(),
            package_count: packageCount,
            total_quantity: totalQuantity,
            expiration_date: expiration,
            min_quantity_alert: minQuantity
        };
        
        // Disabilita pulsante durante invio
        FC.elements.saveMedicationBtn.prop('disabled', true).text('Salvataggio...');
        
        // Invia richiesta
        $.ajax({
            url: farmacicase_data.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    // Aggiorna dati locali
                    var medication = response.data.medication;
                    
                    if (medicationId) {
                        // Modifica
                        var index = FC.data.medications.findIndex(function(m) { return m.id == medicationId; });
                        if (index !== -1) {
                            FC.data.medications[index] = medication;
                        }
                    } else {
                        // Nuovo farmaco
                        FC.data.medications.push(medication);
                    }
                    
                    // Ricarica vista
                    renderMedications();
                    
                    // Aggiorna anche dashboard se visibile
                    if ($('#fc-section-dashboard').hasClass('fc-section-active')) {
                        updateDashboardCounters();
                        renderExpiringMedications();
                        renderLowQuantityMedications();
                    }
                    
                    // Chiudi modal
                    closeCurrentModal();
                    
                    // Mostra conferma
                    alert(medicationId ? 'Farmaco aggiornato con successo' : 'Farmaco creato con successo');
                } else {
                    // Mostra errore
                    alert(response.data.message || 'Errore nel salvare i dati');
                }
                
                // Riabilita pulsante
                FC.elements.saveMedicationBtn.prop('disabled', false).text('Salva');
            },
            error: function() {
                alert('Errore di connessione al server');
                FC.elements.saveMedicationBtn.prop('disabled', false).text('Salva');
            }
        });
    }

    /**
     * Elimina un farmaco
     * 
     * @param {number} medicationId ID del farmaco
     */
    function deleteMedication(medicationId) {
        $.ajax({
            url: farmacicase_data.ajax_url,
            type: 'POST',
            data: {
                action: 'farmacicase_delete_medication',
                security: farmacicase_data.nonce,
                id: medicationId
            },
            success: function(response) {
                if (response.success) {
                    // Rimuovi dai dati locali
                    FC.data.medications = FC.data.medications.filter(function(m) { return m.id != medicationId; });
                    
                    // Ricarica vista
                    renderMedications();
                    
                    // Aggiorna anche dashboard se visibile
                    if ($('#fc-section-dashboard').hasClass('fc-section-active')) {
                        updateDashboardCounters();
                        renderExpiringMedications();
                        renderLowQuantityMedications();
                    }
                    
                    // Mostra conferma
                    alert('Farmaco eliminato con successo');
                } else {
                    alert(response.data.message || 'Errore nell\'eliminare il farmaco');
                }
            },
            error: function() {
                alert('Errore di connessione al server');
            }
        });
    }

    /**
     * Carica i dati degli utenti (solo per admin)
     */
    function loadUsersData() {
        if (FC.currentUser.role !== 'admin') return;
        
        if (FC.dataLoaded.users) {
            // Usa i dati già caricati
            renderUsers();
            return;
        }
        
        FC.elements.usersList.addClass('fc-loading');
        
        $.ajax({
            url: farmacicase_data.ajax_url,
            type: 'POST',
            data: {
                action: 'farmacicase_get_users',
                security: farmacicase_data.nonce
            },
            success: function(response) {
                if (response.success) {
                    FC.data.users = response.data.users || [];
                    FC.dataLoaded.users = true;
                    renderUsers();
                } else {
                    // Gestione errori
                    FC.elements.usersList.html('<p class="fc-error">' + (response.data.message || 'Errore nel caricamento dei dati') + '</p>');
                }
                
                FC.elements.usersList.removeClass('fc-loading');
            },
            error: function() {
                // Gestione errore di rete
                FC.elements.usersList.html('<p class="fc-error">Errore di connessione al server</p>');
                FC.elements.usersList.removeClass('fc-loading');
            }
        });
        
        // Carica anche le Case se non sono già state caricate
        if (!FC.dataLoaded.houses) {
            loadHousesData();
        }
    }

    /**
     * Renderizza gli utenti (solo per admin)
     */
    function renderUsers() {
        if (!FC.elements.usersList.length || FC.currentUser.role !== 'admin') return;
        
        // Filtra gli utenti
        var users = FC.data.users.filter(function(user) {
            // Filtra per ruolo
            if (FC.filters.role && user.role !== FC.filters.role) {
                return false;
            }
            
            // Filtra per casa
            if (FC.filters.house && (!user.houses || !user.houses.some(function(h) { return h.id == FC.filters.house; }))) {
                return false;
            }
            
            // Filtra per testo di ricerca
            if (FC.filters.search) {
                var searchLower = FC.filters.search.toLowerCase();
                return (user.display_name && user.display_name.toLowerCase().includes(searchLower)) || 
                       (user.user_email && user.user_email.toLowerCase().includes(searchLower));
            }
            
            return true;
        });
        
        // Ordina per nome
        users.sort(function(a, b) {
            return (a.display_name || '').localeCompare(b.display_name || '');
        });
        
        if (users.length === 0) {
            FC.elements.usersList.html('<p class="fc-info-box fc-info-warning">Nessun utente trovato con i filtri selezionati.</p>');
            return;
        }
        
        var html = '<table class="fc-table">';
        html += '<thead><tr>';
        html += '<th>Nome</th>';
        html += '<th>Email</th>';
        html += '<th>Ruolo</th>';
        html += '<th>Case Associate</th>';
        html += '<th>Azioni</th>';
        html += '</tr></thead>';
        
        html += '<tbody>';
        
        users.forEach(function(user) {
            var roleLabel = '';
            var roleClass = '';
            
            switch(user.role) {
                case 'admin':
                    roleLabel = 'Admin';
                    roleClass = 'admin';
                    break;
                case 'responsabile':
                    roleLabel = 'Responsabile';
                    roleClass = 'responsabile';
                    break;
                case 'medico':
                    roleLabel = 'Medico';
                    roleClass = 'medico';
                    break;
            }
            
            var houseNames = '';
            if (user.houses && user.houses.length > 0) {
                houseNames = user.houses.map(function(h) { return h.name; }).join(', ');
            }
            
            html += '<tr data-id="' + user.id + '">';
            html += '<td>' + (user.display_name || '') + '</td>';
            html += '<td>' + (user.user_email || '') + '</td>';
            html += '<td><span class="fc-role fc-role-' + roleClass + '">' + roleLabel + '</span></td>';
            html += '<td>' + houseNames + '</td>';
            html += '<td class="fc-table-actions">';
            html += '<button type="button" class="fc-action-btn fc-edit" data-id="' + user.id + '"><i class="dashicons dashicons-edit"></i></button>';
            html += '<button type="button" class="fc-action-btn fc-delete" data-id="' + user.id + '"><i class="dashicons dashicons-trash"></i></button>';
            html += '</td>';
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        
        FC.elements.usersList.html(html);
        
        // Aggiungi eventi alle azioni
        $('.fc-edit', FC.elements.usersList).on('click', function() {
            var userId = $(this).data('id');
            editUser(userId);
        });
        
        $('.fc-delete', FC.elements.usersList).on('click', function() {
            var userId = $(this).data('id');
            var user = FC.data.users.find(function(u) { return u.id == userId; });
            confirmDelete('utente', user ? user.display_name : 'Sconosciuto', function() {
                deleteUser(userId);
            });
        });
    }

    /**
     * Mostra il modal per aggiungere un utente
     */
    function showAddUserModal() {
        // Reset form
        $('#fc-user-id').val('');
        $('#fc-user-email').val('');
        $('#fc-user-first-name').val('');
        $('#fc-user-last-name').val('');
        $('#fc-user-phone').val('');
        $('#fc-user-role').val('');
        $('#fc-user-password').val('');
        
        // Pulisci checkbox case
        var checkboxContainer = $('#fc-user-houses-checkboxes');
        checkboxContainer.html('');
        
        // Popola checkbox con le case disponibili
        if (FC.data.houses.length > 0) {
            FC.data.houses.forEach(function(house) {
                var checkboxId = 'house-' + house.id;
                var checkbox = $('<div class="fc-checkbox-group">' +
                                '<input type="checkbox" id="' + checkboxId + '" name="houses[]" value="' + house.id + '">' +
                                '<label for="' + checkboxId + '">' + house.name + '</label>' +
                                '</div>');
                checkboxContainer.append(checkbox);
            });
        } else {
            checkboxContainer.html('<p>Nessuna casa disponibile. Creane prima una.</p>');
        }
        
        // Aggiorna titolo
        $('#fc-user-modal-title').text('Aggiungi Utente');
        
        // Mostra modal
        showModal(FC.elements.userModal);
    }

    /**
     * Prepara modal per modificare un utente
     * 
     * @param {number} userId ID dell'utente FarmaciCase
     */
    function editUser(userId) {
        var user = FC.data.users.find(function(u) { return u.id == userId; });
        
        if (!user) {
            alert('Utente non trovato');
            return;
        }
        
        // Compila form
        $('#fc-user-id').val(user.id);
        $('#fc-user-email').val(user.user_email || '');
        
        // Estrai nome e cognome dal display_name
        var nameParts = (user.display_name || '').split(' ');
        $('#fc-user-first-name').val(nameParts[0] || '');
        $('#fc-user-last-name').val(nameParts.slice(1).join(' ') || '');
        
        $('#fc-user-phone').val(user.phone || '');
        $('#fc-user-role').val(user.role || '');
        $('#fc-user-password').val(''); // Sempre vuoto per sicurezza
        
        // Pulisci checkbox case
        var checkboxContainer = $('#fc-user-houses-checkboxes');
        checkboxContainer.html('');
        
        // Popola checkbox con le case disponibili
        if (FC.data.houses.length > 0) {
            FC.data.houses.forEach(function(house) {
                var checkboxId = 'house-' + house.id;
                var isChecked = user.houses && user.houses.some(function(h) { return h.id == house.id; });
                
                var checkbox = $('<div class="fc-checkbox-group">' +
                                '<input type="checkbox" id="' + checkboxId + '" name="houses[]" value="' + house.id + '" ' + (isChecked ? 'checked' : '') + '>' +
                                '<label for="' + checkboxId + '">' + house.name + '</label>' +
                                '</div>');
                checkboxContainer.append(checkbox);
            });
        } else {
            checkboxContainer.html('<p>Nessuna casa disponibile. Creane prima una.</p>');
        }
        
        // Aggiorna titolo
        $('#fc-user-modal-title').text('Modifica Utente');
        
        // Mostra modal
        showModal(FC.elements.userModal);
    }

    /**
     * Salva un utente (creazione o modifica)
     */
    function saveUser() {
        // Validazione base
        var userId = $('#fc-user-id').val();
        var email = $('#fc-user-email').val().trim();
        var firstName = $('#fc-user-first-name').val().trim();
        var lastName = $('#fc-user-last-name').val().trim();
        var role = $('#fc-user-role').val();
        
        if (!email || !firstName || !lastName || !role) {
            alert('Compila tutti i campi obbligatori');
            return;
        }
        
        // Raccogli case selezionate
        var selectedHouses = [];
        $('input[name="houses[]"]:checked').each(function() {
            selectedHouses.push($(this).val());
        });
        
        if (selectedHouses.length === 0) {
            alert('Seleziona almeno una casa');
            return;
        }
        
        // Dati per l'invio
        var data = {
            action: 'farmacicase_save_user',
            security: farmacicase_data.nonce,
            id: userId,
            email: email,
            first_name: firstName,
            last_name: lastName,
            phone: $('#fc-user-phone').val().trim(),
            role: role,
            house_ids: selectedHouses
        };
        
        // Aggiungi password solo se specificata
        var password = $('#fc-user-password').val();
        if (password) {
            data.password = password;
        }
        
        // Disabilita pulsante durante invio
        FC.elements.saveUserBtn.prop('disabled', true).text('Salvataggio...');
        
        // Invia richiesta
        $.ajax({
            url: farmacicase_data.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    // Aggiorna dati locali
                    var user = response.data.user;
                    
                    if (userId) {
                        // Modifica
                        var index = FC.data.users.findIndex(function(u) { return u.id == userId; });
                        if (index !== -1) {
                            FC.data.users[index] = user;
                        }
                    } else {
                        // Nuovo utente
                        FC.data.users.push(user);
                    }
                    
                    // Ricarica vista
                    renderUsers();
                    
                    // Chiudi modal
                    closeCurrentModal();
                    
                    // Mostra conferma e password se generata
                    var message = userId ? 'Utente aggiornato con successo' : 'Utente creato con successo';
                    if (response.data.generated_password) {
                        message += '\n\nPassword generata: ' + response.data.generated_password + '\n\nCopia questa password, non sarà più visibile in seguito!';
                    }
                    alert(message);
                } else {
                    // Mostra errore
                    alert(response.data.message || 'Errore nel salvare i dati');
                }
                
                // Riabilita pulsante
                FC.elements.saveUserBtn.prop('disabled', false).text('Salva');
            },
            error: function() {
                alert('Errore di connessione al server');
                FC.elements.saveUserBtn.prop('disabled', false).text('Salva');
            }
        });
    }

    /**
     * Elimina un utente
     * 
     * @param {number} userId ID dell'utente FarmaciCase
     */
    function deleteUser(userId) {
        $.ajax({
            url: farmacicase_data.ajax_url,
            type: 'POST',
            data: {
                action: 'farmacicase_delete_user',
                security: farmacicase_data.nonce,
                id: userId
            },
            success: function(response) {
                if (response.success) {
                    // Rimuovi dai dati locali
                    FC.data.users = FC.data.users.filter(function(u) { return u.id != userId; });
                    
                    // Ricarica vista
                    renderUsers();
                    
                    // Mostra conferma
                    alert('Utente eliminato con successo');
                } else {
                    alert(response.data.message || 'Errore nell\'eliminare l\'utente');
                }
            },
            error: function() {
                alert('Errore di connessione al server');
            }
        });
    }

    /**
     * Carica i dati delle notifiche
     */
    function loadNotificationsData() {
        if (FC.dataLoaded.notifications) {
            // Usa i dati già caricati
            renderNotifications();
            return;
        }
        
        FC.elements.notificationsList.addClass('fc-loading');
        
        $.ajax({
            url: farmacicase_data.ajax_url,
            type: 'POST',
            data: {
                action: 'farmacicase_get_notifications',
                security: farmacicase_data.nonce
            },
            success: function(response) {
                if (response.success) {
                    FC.data.notifications = response.data.notifications || [];
                    FC.dataLoaded.notifications = true;
                    renderNotifications();
                } else {
                    // Gestione errori
                    FC.elements.notificationsList.html('<p class="fc-error">' + (response.data.message || 'Errore nel caricamento dei dati') + '</p>');
                }
                
                FC.elements.notificationsList.removeClass('fc-loading');
            },
            error: function() {
                // Gestione errore di rete
                FC.elements.notificationsList.html('<p class="fc-error">Errore di connessione al server</p>');
                FC.elements.notificationsList.removeClass('fc-loading');
            }
        });
    }

    /**
     * Renderizza le notifiche
     */
    function renderNotifications() {
        if (!FC.elements.notificationsList.length) return;
        
        if (FC.data.notifications.length === 0) {
            FC.elements.notificationsList.html('<p class="fc-info-box fc-info-neutral">Nessuna notifica recente.</p>');
            return;
        }
        
        var html = '<table class="fc-table">';
        html += '<thead><tr>';
        html += '<th>Data</th>';
        html += '<th>Tipo</th>';
        html += '<th>Farmaco</th>';
        html += '<th>Dettagli</th>';
        html += '<th>Casa</th>';
        html += '<th>Stato</th>';
        html += '</tr></thead>';
        
        html += '<tbody>';
        
        FC.data.notifications.forEach(function(notification) {
            var typeLabel = '';
            var typeClass = '';
            
            switch(notification.type) {
                case 'expiration':
                    typeLabel = 'Scadenza';
                    typeClass = 'warning';
                    break;
                case 'low_quantity':
                    typeLabel = 'Sotto Soglia';
                    typeClass = 'warning';
                    break;
            }
            
            html += '<tr data-id="' + notification.id + '">';
            html += '<td>' + formatDateTime(notification.sent_at) + '</td>';
            html += '<td><span class="fc-indicator fc-indicator-' + typeClass + '"></span>' + typeLabel + '</td>';
            html += '<td>' + notification.commercial_name + '</td>';
            html += '<td>' + (notification.type === 'expiration' ? 'Scadenza: ' + formatDate(notification.expiration_date) : 'Quantità: ' + notification.total_quantity) + '</td>';
            html += '<td>' + notification.house_name + '</td>';
            html += '<td>' + (notification.read_status === 'read' ? 'Letta' : '<strong>Non letta</strong>') + '</td>';
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        
        FC.elements.notificationsList.html(html);
        
        // Aggiungi evento click per marcare come letta
        $('.fc-table tbody tr', FC.elements.notificationsList).on('click', function() {
            var notificationId = $(this).data('id');
            markNotificationAsRead(notificationId, $(this));
        });
    }

    /**
     * Marca una notifica come letta
     * 
     * @param {number} notificationId ID della notifica
     * @param {jQuery} rowElement Elemento DOM della riga della notifica
     */
    function markNotificationAsRead(notificationId, rowElement) {
        if (!notificationId) return;
        
        var notification = FC.data.notifications.find(function(n) { return n.id == notificationId; });
        if (!notification || notification.read_status === 'read') return;
        
        $.ajax({
            url: farmacicase_data.ajax_url,
            type: 'POST',
            data: {
                action: 'farmacicase_mark_notification_read',
                security: farmacicase_data.nonce,
                id: notificationId
            },
            success: function(response) {
                if (response.success) {
                    // Aggiorna dati locali
                    notification.read_status = 'read';
                    
                    // Aggiorna UI
                    rowElement.find('td:last-child').html('Letta');
                }
            }
        });
    }

    /**
     * Esporta i farmaci in formato CSV
     */
    function exportMedications() {
        // Filtra i farmaci con gli stessi filtri della visualizzazione
        var medications = FC.data.medications.filter(function(med) {
            // Filtra per casa
            if (FC.filters.house && med.house_id != FC.filters.house) {
                return false;
            }
            
            // Filtra per stato
            if (FC.filters.status) {
                var now = new Date();
                var expirationDate = new Date(med.expiration_date);
                var thresholdDate = new Date();
                thresholdDate.setDate(now.getDate() + FC.EXPIRATION_DAYS_THRESHOLD);
                
                if (FC.filters.status === 'expiring' && 
                    !(expirationDate <= thresholdDate && expirationDate >= now)) {
                    return false;
                }
                
                if (FC.filters.status === 'low' && 
                    !(parseInt(med.total_quantity) <= parseInt(med.min_quantity_alert))) {
                    return false;
                }
                
                if (FC.filters.status === 'ok' && 
                    (expirationDate <= thresholdDate || parseInt(med.total_quantity) <= parseInt(med.min_quantity_alert))) {
                    return false;
                }
            }
            
            // Filtra per testo di ricerca
            if (FC.filters.search) {
                var searchLower = FC.filters.search.toLowerCase();
                return med.commercial_name.toLowerCase().includes(searchLower) || 
                       med.active_ingredient.toLowerCase().includes(searchLower) || 
                       (med.description && med.description.toLowerCase().includes(searchLower));
            }
            
            return true;
        });
        
        if (medications.length === 0) {
            alert('Nessun farmaco da esportare con i filtri selezionati.');
            return;
        }
        
        // Prepara CSV header
        var csvContent = 'Nome Commerciale,Principio Attivo,Descrizione,Confezioni,Quantità Totale,Soglia Minima,Data Scadenza,Casa\n';
        
        // Aggiungi righe
        medications.forEach(function(med) {
            var house = FC.data.houses.find(function(h) { return h.id == med.house_id; });
            var houseName = house ? house.name : 'Casa #' + med.house_id;
            
            // Pulisci dati per CSV
            var description = med.description ? med.description.replace(/,/g, ' ').replace(/\n/g, ' ') : '';
            
            csvContent += [
                escapeCSV(med.commercial_name),
                escapeCSV(med.active_ingredient),
                escapeCSV(description),
                med.package_count,
                med.total_quantity,
                med.min_quantity_alert,
                formatDate(med.expiration_date),
                escapeCSV(houseName)
            ].join(',') + '\n';
        });
        
        // Crea file e scarica
        var encodedUri = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csvContent);
        var link = document.createElement('a');
        link.setAttribute('href', encodedUri);
        link.setAttribute('download', 'farmaci_export_' + formatDateFileName(new Date()) + '.csv');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    /**
     * Funzione utility per escaping di valori CSV
     * 
     * @param {string} value Valore da escapare per CSV
     * @return {string} Valore escapato
     */
    function escapeCSV(value) {
        if (!value) return '';
        value = value.toString();
        if (value.includes(',') || value.includes('"') || value.includes('\n')) {
            value = '"' + value.replace(/"/g, '""') + '"';
        }
        return value;
    }

    /**
     * Mostra un modal di conferma per eliminazione
     * 
     * @param {string} itemType Tipo di elemento (es. 'casa', 'farmaco', 'utente')
     * @param {string} itemName Nome dell'elemento
     * @param {function} confirmCallback Funzione da eseguire alla conferma
     */
    function confirmDelete(itemType, itemName, confirmCallback) {
        $('#fc-confirm-modal-title').text('Conferma Eliminazione');
        $('#fc-confirm-modal-message').text('Sei sicuro di voler eliminare ' + itemType + ' "' + itemName + '"? Questa operazione non può essere annullata.');
        
        // Salva callback
        FC.confirmCallback = confirmCallback;
        
        // Aggiorna pulsante
        $('#fc-confirm-modal-btn').text('Elimina').off('click').on('click', function() {
            // Esegui callback
            if (typeof FC.confirmCallback === 'function') {
                FC.confirmCallback();
            }
            
            // Chiudi modal
            closeCurrentModal();
        });
        
        // Mostra modal
        showModal(FC.elements.confirmModal);
    }

    /**
     * Mostra un modal
     * 
     * @param {jQuery} modal Elemento modal da mostrare
     */
    function showModal(modal) {
        // Chiudi eventuali modal aperti
        closeCurrentModal();
        
        // Salva riferimento al modal corrente
        FC.currentModal = modal;
        
        // Mostra
        modal.css('display', 'block');
        
        // Impedisci scroll body
        $('body').addClass('fc-modal-open');
    }

    /**
     * Chiude il modal corrente se aperto
     */
    function closeCurrentModal() {
        if (FC.currentModal) {
            FC.currentModal.css('display', 'none');
            FC.currentModal = null;
            
            // Ripristina scroll body
            $('body').removeClass('fc-modal-open');
        }
    }

    /**
     * Formatta una data in formato locale
     * 
     * @param {string} dateString Data in formato ISO
     * @return {string} Data formattata
     */
    function formatDate(dateString) {
        if (!dateString) return '';
        
        var date = new Date(dateString);
        if (isNaN(date.getTime())) return dateString;
        
        return date.toLocaleDateString();
    }

    /**
     * Formatta una data con ora in formato locale
     * 
     * @param {string} dateTimeString Data e ora in formato ISO
     * @return {string} Data e ora formattata
     */
    function formatDateTime(dateTimeString) {
        if (!dateTimeString) return '';
        
        var date = new Date(dateTimeString);
        if (isNaN(date.getTime())) return dateTimeString;
        
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
    }

    /**
     * Formatta una data per nome file
     * 
     * @param {Date} date Oggetto data
     * @return {string} Data formattata per nome file (YYYY-MM-DD)
     */
    function formatDateFileName(date) {
        var year = date.getFullYear();
        var month = (date.getMonth() + 1).toString().padStart(2, '0');
        var day = date.getDate().toString().padStart(2, '0');
        
        return year + '-' + month + '-' + day;
    }
})(jQuery);