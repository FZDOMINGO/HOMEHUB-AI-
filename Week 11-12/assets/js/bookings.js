document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const hamburger = document.getElementById('hamburger');
    const mobileMenu = document.getElementById('nav-menu-mobile');
    
    if (hamburger && mobileMenu) {
        hamburger.addEventListener('click', function() {
            this.classList.toggle('active');
            mobileMenu.classList.toggle('active');
        });
    }
    
    // Modal functionality
    const modals = {
        reserve: document.getElementById('reserveModal'),
        visit: document.getElementById('visitModal'),
        status: document.getElementById('statusModal'),
        manageReservations: document.getElementById('manageReservationsModal'),
        manageVisits: document.getElementById('manageVisitsModal')
    };
    
    const modalButtons = {
        reserve: document.getElementById('reserveBtn'),
        visit: document.getElementById('scheduleBtn'),
        status: document.getElementById('statusBtn'),
        myReservations: document.getElementById('myReservationsBtn'),
        myVisits: document.getElementById('myVisitsBtn'),
        manageReservations: document.getElementById('manageReservationsBtn'),
        manageVisits: document.getElementById('manageVisitsBtn')
    };
    
    // Open modal functions
    function openModal(modal) {
        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    }
    
    // Close modal function
    function closeModal(modal) {
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = 'auto';
        }
    }
    
    // Add click events for all modal buttons
    Object.keys(modalButtons).forEach(key => {
        const button = modalButtons[key];
        const modal = modals[key];
        
        if (button) {
            button.addEventListener('click', function() {
                // Handle tenant "My Reservations" and "My Visits" buttons
                if (key === 'myReservations') {
                    openModal(modals.status);
                    loadBookingStatus();
                    // Auto-filter to show only reservations
                    setTimeout(() => {
                        const reservationFilterBtn = document.querySelector('[data-type="reservation"]');
                        if (reservationFilterBtn) {
                            reservationFilterBtn.click();
                        }
                    }, 100);
                } else if (key === 'myVisits') {
                    openModal(modals.status);
                    loadBookingStatus();
                    // Auto-filter to show only visits
                    setTimeout(() => {
                        const visitFilterBtn = document.querySelector('[data-type="visit"]');
                        if (visitFilterBtn) {
                            visitFilterBtn.click();
                        }
                    }, 100);
                } else if (modal) {
                    openModal(modal);
                    
                    // Load additional data if needed
                    if (key === 'reserve' || key === 'visit') {
                        loadAvailableProperties(key);
                    } else if (key === 'status') {
                        loadBookingStatus();
                    } else if (key === 'manageReservations') {
                        loadReservationRequests();
                    } else if (key === 'manageVisits') {
                        loadVisitRequests();
                    }
                }
            });
        }
    });
    
    // Close modal when clicking on X
    const closeButtons = document.querySelectorAll('.close-modal');
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            closeModal(modal);
        });
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        Object.values(modals).forEach(modal => {
            if (event.target === modal) {
                closeModal(modal);
            }
        });
    });
    
    // Load available properties for reserve and visit forms
    function loadAvailableProperties(formType) {
        const selectElement = formType === 'reserve' ? 
            document.getElementById('property_id') : 
            document.getElementById('visit_property_id');
            
        if (!selectElement) return;
        
        // Clear existing options except the first one
        while (selectElement.options.length > 1) {
            selectElement.remove(1);
        }
        
        fetch('api/get-available-properties.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    data.properties.forEach(property => {
                        const option = document.createElement('option');
                        option.value = property.id;
                        option.textContent = property.title + ' - ' + property.address + ', ' + property.city;
                        selectElement.appendChild(option);
                    });
                } else {
                    console.error('Failed to load properties:', data.message);
                }
            })
            .catch(error => {
                console.error('Error loading properties:', error);
            });
    }
    
    // Load booking status
    function loadBookingStatus() {
        const statusContainer = document.getElementById('status-container');
        if (!statusContainer) return;
        
        statusContainer.innerHTML = '<div class="loading">Loading your bookings...</div>';
        
        fetch('api/get-booking-status.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayBookingStatus(data.bookings);
                } else {
                    statusContainer.innerHTML = `<div class="error-message">${data.message}</div>`;
                }
            })
            .catch(error => {
                console.error('Error loading booking status:', error);
                statusContainer.innerHTML = '<div class="error-message">Failed to load bookings. Please try again.</div>';
            });
    }
    
    // Display booking status
    function displayBookingStatus(bookings) {
        const statusContainer = document.getElementById('status-container');
        if (!statusContainer) return;
        
        if (bookings.length === 0) {
            statusContainer.innerHTML = '<div class="no-bookings">You don\'t have any bookings yet.</div>';
            return;
        }
        
        // Group bookings by status
        const pendingBookings = bookings.filter(b => b.status === 'pending');
        const approvedBookings = bookings.filter(b => b.status === 'approved');
        const rejectedBookings = bookings.filter(b => b.status === 'rejected');
        const conflictBookings = bookings.filter(b => b.status === 'conflict');
        
        // Group bookings by type
        const visitBookings = bookings.filter(b => b.type === 'visit');
        const reservationBookings = bookings.filter(b => b.type === 'reservation');
        
        let html = '<div class="booking-filter-section">';
        
        // Type filter buttons
        html += '<div class="booking-type-filters">';
        html += '<button class="type-filter-btn active" data-type="all">All (' + bookings.length + ')</button>';
        html += '<button class="type-filter-btn" data-type="visit">📅 Visits (' + visitBookings.length + ')</button>';
        html += '<button class="type-filter-btn" data-type="reservation">🏠 Reservations (' + reservationBookings.length + ')</button>';
        html += '</div>';
        
        html += '<div class="booking-status-tabs">';
        html += '<button class="status-tab active" data-status="all">All</button>';
        html += '<button class="status-tab" data-status="pending">Pending (' + pendingBookings.length + ')</button>';
        html += '<button class="status-tab" data-status="approved">Approved (' + approvedBookings.length + ')</button>';
        html += '<button class="status-tab" data-status="rejected">Rejected (' + rejectedBookings.length + ')</button>';
        if (conflictBookings.length > 0) {
            html += '<button class="status-tab" data-status="conflict">Conflicts (' + conflictBookings.length + ')</button>';
        }
        html += '</div>';
        html += '</div>';
        
        html += '<div class="booking-status-content">';
        
        // All bookings
        html += '<div class="status-panel active" data-panel="all" data-booking-type="all">';
        html += generateBookingsList(bookings);
        html += '</div>';
        
        // Visits only
        html += '<div class="status-panel" data-panel="all" data-booking-type="visit">';
        if (visitBookings.length === 0) {
            html += '<div class="no-bookings-panel">No visit requests.</div>';
        } else {
            html += generateBookingsList(visitBookings);
        }
        html += '</div>';
        
        // Reservations only
        html += '<div class="status-panel" data-panel="all" data-booking-type="reservation">';
        if (reservationBookings.length === 0) {
            html += '<div class="no-bookings-panel">No reservation requests.</div>';
        } else {
            html += generateBookingsList(reservationBookings);
        }
        html += '</div>';
        
        // Pending bookings
        html += '<div class="status-panel" data-panel="pending" data-booking-type="all">';
        if (pendingBookings.length === 0) {
            html += '<div class="no-bookings-panel">No pending bookings.</div>';
        } else {
            html += generateBookingsList(pendingBookings);
        }
        html += '</div>';
        
        html += '<div class="status-panel" data-panel="pending" data-booking-type="visit">';
        const pendingVisits = pendingBookings.filter(b => b.type === 'visit');
        if (pendingVisits.length === 0) {
            html += '<div class="no-bookings-panel">No pending visits.</div>';
        } else {
            html += generateBookingsList(pendingVisits);
        }
        html += '</div>';
        
        html += '<div class="status-panel" data-panel="pending" data-booking-type="reservation">';
        const pendingReservations = pendingBookings.filter(b => b.type === 'reservation');
        if (pendingReservations.length === 0) {
            html += '<div class="no-bookings-panel">No pending reservations.</div>';
        } else {
            html += generateBookingsList(pendingReservations);
        }
        html += '</div>';
        
        // Approved bookings
        html += '<div class="status-panel" data-panel="approved" data-booking-type="all">';
        if (approvedBookings.length === 0) {
            html += '<div class="no-bookings-panel">No approved bookings.</div>';
        } else {
            html += generateBookingsList(approvedBookings);
        }
        html += '</div>';
        
        html += '<div class="status-panel" data-panel="approved" data-booking-type="visit">';
        const approvedVisits = approvedBookings.filter(b => b.type === 'visit');
        if (approvedVisits.length === 0) {
            html += '<div class="no-bookings-panel">No approved visits.</div>';
        } else {
            html += generateBookingsList(approvedVisits);
        }
        html += '</div>';
        
        html += '<div class="status-panel" data-panel="approved" data-booking-type="reservation">';
        const approvedReservations = approvedBookings.filter(b => b.type === 'reservation');
        if (approvedReservations.length === 0) {
            html += '<div class="no-bookings-panel">No approved reservations.</div>';
        } else {
            html += generateBookingsList(approvedReservations);
        }
        html += '</div>';
        
        // Rejected bookings
        html += '<div class="status-panel" data-panel="rejected" data-booking-type="all">';
        if (rejectedBookings.length === 0) {
            html += '<div class="no-bookings-panel">No rejected bookings.</div>';
        } else {
            html += generateBookingsList(rejectedBookings);
        }
        html += '</div>';
        
        html += '<div class="status-panel" data-panel="rejected" data-booking-type="visit">';
        const rejectedVisits = rejectedBookings.filter(b => b.type === 'visit');
        if (rejectedVisits.length === 0) {
            html += '<div class="no-bookings-panel">No rejected visits.</div>';
        } else {
            html += generateBookingsList(rejectedVisits);
        }
        html += '</div>';
        
        html += '<div class="status-panel" data-panel="rejected" data-booking-type="reservation">';
        const rejectedReservations = rejectedBookings.filter(b => b.type === 'reservation');
        if (rejectedReservations.length === 0) {
            html += '<div class="no-bookings-panel">No rejected reservations.</div>';
        } else {
            html += generateBookingsList(rejectedReservations);
        }
        html += '</div>';
        
        // Conflict bookings
        if (conflictBookings.length > 0) {
            html += '<div class="status-panel" data-panel="conflict" data-booking-type="all">';
            html += generateBookingsList(conflictBookings);
            html += '</div>';
            
            html += '<div class="status-panel" data-panel="conflict" data-booking-type="visit">';
            const conflictVisits = conflictBookings.filter(b => b.type === 'visit');
            if (conflictVisits.length === 0) {
                html += '<div class="no-bookings-panel">No conflict visits.</div>';
            } else {
                html += generateBookingsList(conflictVisits);
            }
            html += '</div>';
            
            html += '<div class="status-panel" data-panel="conflict" data-booking-type="reservation">';
            const conflictReservations = conflictBookings.filter(b => b.type === 'reservation');
            if (conflictReservations.length === 0) {
                html += '<div class="no-bookings-panel">No conflict reservations.</div>';
            } else {
                html += generateBookingsList(conflictReservations);
            }
            html += '</div>';
        }
        
        html += '</div>';
        
        statusContainer.innerHTML = html;
        
        // Store current filter states
        let currentType = 'all';
        let currentStatus = 'all';
        
        // Add type filter functionality
        document.querySelectorAll('.type-filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.type-filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                currentType = this.getAttribute('data-type');
                updateVisiblePanel(currentStatus, currentType);
            });
        });
        
        // Add status tab switching functionality
        document.querySelectorAll('.status-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.status-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                currentStatus = this.getAttribute('data-status');
                updateVisiblePanel(currentStatus, currentType);
            });
        });
        
        // Function to update visible panel based on filters
        function updateVisiblePanel(status, type) {
            document.querySelectorAll('.status-panel').forEach(p => p.classList.remove('active'));
            const targetPanel = document.querySelector(`[data-panel="${status}"][data-booking-type="${type}"]`);
            if (targetPanel) {
                targetPanel.classList.add('active');
            }
        }
        
        // Add event listeners for action buttons
        document.querySelectorAll('.status-actions .action-btn').forEach(button => {
            button.addEventListener('click', function() {
                const bookingId = this.getAttribute('data-booking-id');
                const bookingType = this.getAttribute('data-booking-type');
                const action = this.classList.contains('btn-reschedule') ? 'reschedule' : 'cancel';
                
                handleBookingAction(bookingId, bookingType, action);
            });
        });
    }
    
    // Generate bookings list HTML
    function generateBookingsList(bookings) {
        let html = '';
        
        bookings.forEach(booking => {
            let statusClass = '';
            let statusIcon = '';
            switch(booking.status) {
                case 'pending': 
                    statusClass = 'status-pending'; 
                    statusIcon = '⏳';
                    break;
                case 'approved': 
                    statusClass = 'status-approved'; 
                    statusIcon = '✅';
                    break;
                case 'rejected': 
                    statusClass = 'status-rejected'; 
                    statusIcon = '❌';
                    break;
                case 'conflict': 
                    statusClass = 'status-conflict'; 
                    statusIcon = '⚠️';
                    break;
                default: statusClass = '';
            }
            
            html += `
                <div class="status-item">
                    <div class="status-icon-badge ${statusClass}">${statusIcon}</div>
                    <div class="status-item-content">
                        <div class="status-item-header">
                            <div class="status-property">${booking.property_title}</div>
                            <div class="status-indicator ${statusClass}">${booking.status.charAt(0).toUpperCase() + booking.status.slice(1)}</div>
                        </div>
                        <div class="status-type-badge">${booking.type === 'visit' ? '📅 Visit Request' : '🏠 Reservation'}</div>
                        <div class="status-details">
                            ${booking.type === 'visit' ? 
                                `<strong>Visit Date:</strong> ${formatDate(booking.date)} at ${formatTime(booking.time)}` : 
                                `<strong>Move-in Date:</strong> ${formatDate(booking.date)}<br><strong>Submitted:</strong> ${formatDate(booking.created_at)}`}
                        </div>`;
                        
            if (booking.status === 'conflict') {
                html += `
                    <div class="conflict-alert">
                        <strong>⚠️ Double Booking Conflict</strong><br>
                        This property has overlapping reservations for ${formatDate(booking.date)}. 
                        Another booking (ID: #${booking.conflict_id}) was confirmed for the same time. 
                        Please reschedule or cancel to resolve this conflict.
                    </div>
                    <div class="status-actions">
                        <button class="action-btn btn-reschedule" data-booking-id="${booking.id}" data-booking-type="${booking.type}">
                            <i class="fas fa-calendar-alt"></i> Reschedule
                        </button>
                        <button class="action-btn btn-cancel" data-booking-id="${booking.id}" data-booking-type="${booking.type}">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>`;
            } else if (booking.status === 'pending') {
                html += `
                    <div class="status-actions">
                        <button class="action-btn btn-cancel" data-booking-id="${booking.id}" data-booking-type="${booking.type}">
                            <i class="fas fa-times"></i> Cancel Request
                        </button>
                    </div>`;
            }
            
            html += `</div></div>`;
        });
        
        return html;
    }
    
    // Load landlord's reservation requests
    function loadReservationRequests() {
        const container = document.getElementById('reservations-container');
        if (!container) return;
        
        container.innerHTML = '<div class="loading">Loading reservation requests...</div>';
        
        fetch('api/get-landlord-reservations.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayReservationRequests(data.reservations);
                } else {
                    container.innerHTML = `<div class="error-message">${data.message}</div>`;
                }
            })
            .catch(error => {
                console.error('Error loading reservations:', error);
                container.innerHTML = '<div class="error-message">Failed to load reservation requests. Please try again.</div>';
            });
    }
    
    // Display reservation requests for landlord
    function displayReservationRequests(reservations) {
        const container = document.getElementById('reservations-container');
        if (!container) return;
        
        if (reservations.length === 0) {
            container.innerHTML = '<div class="no-bookings">You don\'t have any reservation requests.</div>';
            return;
        }
        
        // Group reservations by status
        const pendingReservations = reservations.filter(r => r.status === 'pending');
        const approvedReservations = reservations.filter(r => r.status === 'approved');
        const rejectedReservations = reservations.filter(r => r.status === 'rejected');
        
        // Create tab header
        let html = '<div class="reservation-status-tabs">';
        html += '<button class="reservation-tab active" data-rstatus="all">All Reservations (' + reservations.length + ')</button>';
        html += '<button class="reservation-tab" data-rstatus="pending">Pending (' + pendingReservations.length + ')</button>';
        html += '<button class="reservation-tab" data-rstatus="approved">Approved (' + approvedReservations.length + ')</button>';
        html += '<button class="reservation-tab" data-rstatus="rejected">Rejected (' + rejectedReservations.length + ')</button>';
        html += '</div>';
        
        html += '<div class="reservation-status-content">';
        
        // All reservations panel
        html += '<div class="reservation-panel active" data-rpanel="all">';
        html += generateReservationsList(reservations);
        html += '</div>';
        
        // Pending reservations panel
        html += '<div class="reservation-panel" data-rpanel="pending">';
        if (pendingReservations.length === 0) {
            html += '<div class="no-bookings-panel">No pending reservation requests.</div>';
        } else {
            html += generateReservationsList(pendingReservations);
        }
        html += '</div>';
        
        // Approved reservations panel
        html += '<div class="reservation-panel" data-rpanel="approved">';
        if (approvedReservations.length === 0) {
            html += '<div class="no-bookings-panel">No approved reservations.</div>';
        } else {
            html += generateReservationsList(approvedReservations);
        }
        html += '</div>';
        
        // Rejected reservations panel
        html += '<div class="reservation-panel" data-rpanel="rejected">';
        if (rejectedReservations.length === 0) {
            html += '<div class="no-bookings-panel">No rejected reservations.</div>';
        } else {
            html += generateReservationsList(rejectedReservations);
        }
        html += '</div>';
        
        html += '</div>';
        
        container.innerHTML = html;
        
        console.log('Reservation requests displayed. Total buttons:', document.querySelectorAll('.reservation-card-footer .action-btn').length);
        
        // Add tab switching functionality
        document.querySelectorAll('.reservation-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.reservation-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.reservation-panel').forEach(p => p.classList.remove('active'));
                
                this.classList.add('active');
                const rstatus = this.getAttribute('data-rstatus');
                document.querySelector(`[data-rpanel="${rstatus}"]`).classList.add('active');
            });
        });
        
        // Add event listeners for landlord action buttons
        document.querySelectorAll('.reservation-card-footer .action-btn').forEach(button => {
            button.addEventListener('click', function(event) {
                const reservationId = this.getAttribute('data-reservation-id');
                const action = this.classList.contains('btn-approve') ? 'approve' : 'reject';
                
                handleLandlordReservationAction(reservationId, action, event);
            });
        });
    }
    
    // Generate reservations list HTML
    function generateReservationsList(reservations) {
        let html = '<div class="reservation-list-grid">';
        
        reservations.forEach(reservation => {
            let statusClass = '';
            let statusIcon = '';
            switch(reservation.status) {
                case 'pending': 
                    statusClass = 'status-pending'; 
                    statusIcon = '⏳';
                    break;
                case 'approved': 
                    statusClass = 'status-approved'; 
                    statusIcon = '✅';
                    break;
                case 'rejected': 
                    statusClass = 'status-rejected'; 
                    statusIcon = '❌';
                    break;
                case 'expired':
                    statusClass = 'status-expired';
                    statusIcon = '⏰';
                    break;
                case 'completed':
                    statusClass = 'status-completed';
                    statusIcon = '🎉';
                    break;
                case 'cancelled':
                    statusClass = 'status-cancelled';
                    statusIcon = '🚫';
                    break;
                default: statusClass = '';
            }
            
            html += `
                <div class="reservation-card">
                    <div class="reservation-card-header">
                        <div class="reservation-property-name">
                            <i class="fas fa-building"></i> ${reservation.property_title}
                        </div>
                        <div class="status-indicator ${statusClass}">
                            <span class="status-icon">${statusIcon}</span>
                            ${reservation.status.charAt(0).toUpperCase() + reservation.status.slice(1)}
                        </div>
                    </div>
                    
                    <div class="reservation-card-body">
                        <div class="reservation-tenant-info">
                            <div class="tenant-avatar">
                                <i class="fas fa-user-circle"></i>
                            </div>
                            <div class="tenant-details">
                                <div class="tenant-name">${reservation.tenant_name}</div>
                                <div class="tenant-contact">
                                    <i class="fas fa-envelope"></i> ${reservation.tenant_email}
                                </div>
                                ${reservation.tenant_phone ? 
                                    `<div class="tenant-contact">
                                        <i class="fas fa-phone"></i> ${reservation.tenant_phone}
                                    </div>` : ''}
                            </div>
                        </div>
                        
                        <div class="reservation-financial-info">
                            <div class="financial-item highlight">
                                <i class="fas fa-shield-alt"></i>
                                <div class="financial-content">
                                    <div class="financial-label">Reservation Fee</div>
                                    <div class="financial-value">₱${parseFloat(reservation.reservation_fee || 0).toLocaleString()}</div>
                                </div>
                            </div>
                            <div class="financial-item">
                                <i class="fas fa-wallet"></i>
                                <div class="financial-content">
                                    <div class="financial-label">Payment Method</div>
                                    <div class="financial-value">${formatPaymentMethod(reservation.payment_method)}</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="reservation-info-grid">
                            <div class="info-item">
                                <i class="fas fa-calendar-check"></i>
                                <div class="info-content">
                                    <div class="info-label">Move-in Date</div>
                                    <div class="info-value">${formatDate(reservation.move_in_date)}</div>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <i class="fas fa-hourglass-half"></i>
                                <div class="info-content">
                                    <div class="info-label">Lease Duration</div>
                                    <div class="info-value">${reservation.lease_duration} months</div>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <i class="fas fa-briefcase"></i>
                                <div class="info-content">
                                    <div class="info-label">Employment</div>
                                    <div class="info-value">${formatEmploymentStatus(reservation.employment_status)}</div>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <i class="fas fa-money-bill-wave"></i>
                                <div class="info-content">
                                    <div class="info-label">Monthly Income</div>
                                    <div class="info-value">₱${parseFloat(reservation.monthly_income || 0).toLocaleString()}/mo</div>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <i class="fas fa-home"></i>
                                <div class="info-content">
                                    <div class="info-label">Property Rent</div>
                                    <div class="info-value">₱${parseFloat(reservation.monthly_rent).toLocaleString()}/mo</div>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <i class="fas fa-clock"></i>
                                <div class="info-content">
                                    <div class="info-label">Requested On</div>
                                    <div class="info-value">${formatDate(reservation.created_at)}</div>
                                </div>
                            </div>
                        </div>`;
                        
            // Show expiration date for approved reservations
            if (reservation.status === 'approved' && reservation.expiration_date) {
                const daysLeft = calculateDaysLeft(reservation.expiration_date);
                const isUrgent = daysLeft <= 3;
                html += `
                    <div class="reservation-deadline ${isUrgent ? 'urgent' : ''}">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div class="deadline-content">
                            <div class="deadline-label">Requirements Deadline:</div>
                            <div class="deadline-value">${formatDate(reservation.expiration_date)} 
                                <span class="days-left">(${daysLeft} days remaining)</span>
                            </div>
                        </div>
                    </div>`;
            }
            
            // Show approval date
            if (reservation.approval_date) {
                html += `
                    <div class="reservation-timeline-item">
                        <i class="fas fa-check-circle"></i> Approved on ${formatDate(reservation.approval_date)}
                    </div>`;
            }
                        
            html += `${reservation.requirements ? 
                `<div class="reservation-requirements">
                    <div class="requirements-label"><i class="fas fa-list-ul"></i> Special Requirements:</div>
                    <div class="requirements-text">${reservation.requirements}</div>
                </div>` : ''}
                    </div>`;
                    
            if (reservation.status === 'pending') {
                html += `
                    <div class="reservation-card-footer">
                        <button class="action-btn btn-approve" data-reservation-id="${reservation.id}">
                            <i class="fas fa-check-circle"></i> Approve Reservation
                        </button>
                        <button class="action-btn btn-reject" data-reservation-id="${reservation.id}">
                            <i class="fas fa-times-circle"></i> Reject
                        </button>
                    </div>`;
            }
            
            html += `</div>`;
        });
        
        html += '</div>';
        return html;
    }
    
    // Helper function to format payment method
    function formatPaymentMethod(method) {
        const methods = {
            'bank_transfer': 'Bank Transfer',
            'gcash': 'GCash',
            'paymaya': 'PayMaya',
            'cash': 'Cash',
            'check': 'Check'
        };
        return methods[method] || method;
    }
    
    // Helper function to format employment status
    function formatEmploymentStatus(status) {
        const statuses = {
            'employed': 'Employed',
            'self_employed': 'Self-Employed',
            'student': 'Student',
            'retired': 'Retired',
            'unemployed': 'Unemployed'
        };
        return statuses[status] || status;
    }
    
    // Helper function to calculate days left
    function calculateDaysLeft(expirationDate) {
        const today = new Date();
        const expDate = new Date(expirationDate);
        const diffTime = expDate - today;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        return diffDays > 0 ? diffDays : 0;
    }
    
    // Load landlord's visit requests
function loadVisitRequests() {
    const container = document.getElementById('visits-container');
    if (!container) return;
    
    container.innerHTML = '<div class="loading">Loading visit requests...</div>';
    
fetch('api/get-landlord-visits-flexible.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                displayVisitRequests(data.visits);
            } else {
                container.innerHTML = `<div class="error-message">${data.message || 'An unknown error occurred'}</div>`;
            }
        })
        .catch(error => {
            console.error('Error loading visits:', error);
            container.innerHTML = '<div class="error-message">Failed to load visit requests. Please try again.</div>';
        });
}
    
    // Display visit requests for landlord
    function displayVisitRequests(visits) {
        const container = document.getElementById('visits-container');
        if (!container) return;
        
        if (visits.length === 0) {
            container.innerHTML = '<div class="no-bookings">You don\'t have any visit requests.</div>';
            return;
        }
        
        // Group visits by status
        const pendingVisits = visits.filter(v => v.status === 'pending');
        const approvedVisits = visits.filter(v => v.status === 'approved');
        const rejectedVisits = visits.filter(v => v.status === 'rejected');
        const completedVisits = visits.filter(v => v.status === 'completed');
        
        // Create tab header
        let html = '<div class="visit-status-tabs">';
        html += '<button class="visit-tab active" data-vstatus="all">All Visits (' + visits.length + ')</button>';
        html += '<button class="visit-tab" data-vstatus="pending">Pending (' + pendingVisits.length + ')</button>';
        html += '<button class="visit-tab" data-vstatus="approved">Approved (' + approvedVisits.length + ')</button>';
        html += '<button class="visit-tab" data-vstatus="rejected">Rejected (' + rejectedVisits.length + ')</button>';
        if (completedVisits.length > 0) {
            html += '<button class="visit-tab" data-vstatus="completed">Completed (' + completedVisits.length + ')</button>';
        }
        html += '</div>';
        
        html += '<div class="visit-status-content">';
        
        // All visits panel
        html += '<div class="visit-panel active" data-vpanel="all">';
        html += generateVisitsList(visits);
        html += '</div>';
        
        // Pending visits panel
        html += '<div class="visit-panel" data-vpanel="pending">';
        if (pendingVisits.length === 0) {
            html += '<div class="no-bookings-panel">No pending visit requests.</div>';
        } else {
            html += generateVisitsList(pendingVisits);
        }
        html += '</div>';
        
        // Approved visits panel
        html += '<div class="visit-panel" data-vpanel="approved">';
        if (approvedVisits.length === 0) {
            html += '<div class="no-bookings-panel">No approved visits.</div>';
        } else {
            html += generateVisitsList(approvedVisits);
        }
        html += '</div>';
        
        // Rejected visits panel
        html += '<div class="visit-panel" data-vpanel="rejected">';
        if (rejectedVisits.length === 0) {
            html += '<div class="no-bookings-panel">No rejected visits.</div>';
        } else {
            html += generateVisitsList(rejectedVisits);
        }
        html += '</div>';
        
        // Completed visits panel
        if (completedVisits.length > 0) {
            html += '<div class="visit-panel" data-vpanel="completed">';
            html += generateVisitsList(completedVisits);
            html += '</div>';
        }
        
        html += '</div>';
        
        container.innerHTML = html;
        
        console.log('Visit requests displayed. Total buttons:', document.querySelectorAll('.visit-card-footer .action-btn').length);
        
        // Add tab switching functionality
        document.querySelectorAll('.visit-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.visit-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.visit-panel').forEach(p => p.classList.remove('active'));
                
                this.classList.add('active');
                const vstatus = this.getAttribute('data-vstatus');
                document.querySelector(`[data-vpanel="${vstatus}"]`).classList.add('active');
            });
        });
        
        // Add event listeners for landlord action buttons
        document.querySelectorAll('.visit-card-footer .action-btn').forEach(button => {
            button.addEventListener('click', function(event) {
                const visitId = this.getAttribute('data-visit-id');
                let action = '';
                
                if (this.classList.contains('btn-approve')) action = 'approve';
                else if (this.classList.contains('btn-reject')) action = 'reject';
                else if (this.classList.contains('btn-cancel')) action = 'cancel';
                
                handleLandlordVisitAction(visitId, action, event);
            });
        });
    }
    
    // Generate visits list HTML
    function generateVisitsList(visits) {
        let html = '<div class="visit-list-grid">';
        
        visits.forEach(visit => {
            let statusClass = '';
            let statusIcon = '';
            switch(visit.status) {
                case 'pending': 
                    statusClass = 'status-pending'; 
                    statusIcon = '⏳';
                    break;
                case 'approved': 
                    statusClass = 'status-approved'; 
                    statusIcon = '✅';
                    break;
                case 'rejected': 
                    statusClass = 'status-rejected'; 
                    statusIcon = '❌';
                    break;
                case 'completed': 
                    statusClass = 'status-completed'; 
                    statusIcon = '✔️';
                    break;
                default: statusClass = '';
            }
            
            html += `
                <div class="visit-card">
                    <div class="visit-card-header">
                        <div class="visit-property-name">
                            <i class="fas fa-home"></i> ${visit.property_title}
                        </div>
                        <div class="status-indicator ${statusClass}">
                            <span class="status-icon">${statusIcon}</span>
                            ${visit.status.charAt(0).toUpperCase() + visit.status.slice(1)}
                        </div>
                    </div>
                    
                    <div class="visit-card-body">
                        <div class="visit-tenant-info">
                            <div class="tenant-avatar">
                                <i class="fas fa-user-circle"></i>
                            </div>
                            <div class="tenant-details">
                                <div class="tenant-name">${visit.tenant_name}</div>
                                <div class="tenant-contact">
                                    <i class="fas fa-phone"></i> ${visit.phone_number}
                                </div>
                            </div>
                        </div>
                        
                        <div class="visit-info-grid">
                            <div class="info-item">
                                <i class="fas fa-calendar-day"></i>
                                <div class="info-content">
                                    <div class="info-label">Visit Date</div>
                                    <div class="info-value">${formatDate(visit.visit_date)}</div>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <i class="fas fa-clock"></i>
                                <div class="info-content">
                                    <div class="info-label">Visit Time</div>
                                    <div class="info-value">${formatTime(visit.visit_time)}</div>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <i class="fas fa-users"></i>
                                <div class="info-content">
                                    <div class="info-label">Visitors</div>
                                    <div class="info-value">${visit.number_of_visitors} ${visit.number_of_visitors > 1 ? 'people' : 'person'}</div>
                                </div>
                            </div>
                        </div>
                        
                        ${visit.message ? 
                            `<div class="visit-message">
                                <div class="message-label"><i class="fas fa-comment"></i> Message:</div>
                                <div class="message-text">${visit.message}</div>
                            </div>` : ''}
                    </div>`;
                    
            if (visit.status === 'pending') {
                html += `
                    <div class="visit-card-footer">
                        <button class="action-btn btn-approve" data-visit-id="${visit.id}">
                            <i class="fas fa-check"></i> Approve Visit
                        </button>
                        <button class="action-btn btn-reject" data-visit-id="${visit.id}">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    </div>`;
            } else if (visit.status === 'approved' && new Date(visit.visit_date + ' ' + visit.visit_time) > new Date()) {
                html += `
                    <div class="visit-card-footer">
                        <button class="action-btn btn-cancel" data-visit-id="${visit.id}">
                            <i class="fas fa-ban"></i> Cancel Visit
                        </button>
                    </div>`;
            }
            
            html += `</div>`;
        });
        
        html += '</div>';
        return html;
    }
    
    // Handle booking action (cancel or reschedule)
    function handleBookingAction(bookingId, bookingType, action) {
        if (action === 'cancel') {
            if (!confirm('Are you sure you want to cancel this booking?')) return;
            
            const endpoint = bookingType === 'visit' ? 
                'api/cancel-visit.php' : 'api/cancel-reservation.php';
                
            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${bookingId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Booking cancelled successfully.');
                    loadBookingStatus(); // Refresh the status view
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        } else if (action === 'reschedule') {
            // For simplicity, we'll close the current modal and open the appropriate booking form
            closeModal(modals.status);
            
            if (bookingType === 'visit') {
                openModal(modals.visit);
                loadAvailableProperties('visit');
                
                // We could pre-fill the form with the previous booking details
                // This would require additional API call to get those details
            } else {
                openModal(modals.reserve);
                loadAvailableProperties('reserve');
            }
        }
    }
    
    // Handle landlord's reservation actions (approve or reject)
    function handleLandlordReservationAction(reservationId, action, event) {
        if (!confirm(`Are you sure you want to ${action} this reservation request?`)) return;
        
        console.log(`Processing reservation ${reservationId} with action: ${action}`);
        
        // Show loading state on the button if possible
        const actionBtn = event ? event.target : null;
        const originalText = actionBtn ? actionBtn.textContent : '';
        if (actionBtn) {
            actionBtn.textContent = 'Processing...';
            actionBtn.disabled = true;
        }
        
        fetch('api/process-reservation-request.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${reservationId}&action=${action}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`Reservation ${action}ed successfully.`);
                loadReservationRequests(); // Refresh the view
            } else {
                alert('Error: ' + data.message);
                
                // Reset button state
                if (actionBtn) {
                    actionBtn.textContent = originalText;
                    actionBtn.disabled = false;
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
            
            // Reset button state
            if (actionBtn) {
                actionBtn.textContent = originalText;
                actionBtn.disabled = false;
            }
        });
    }
    
    // Handle landlord's visit actions (approve, reject, or cancel)
function handleLandlordVisitAction(visitId, action, event) {
    if (!confirm(`Are you sure you want to ${action} this visit?`)) return;
    
    console.log(`Processing visit ${visitId} with action: ${action}`);
    
    // Show loading state on the button if possible
    const actionBtn = event ? event.target : null;
    const originalText = actionBtn ? actionBtn.textContent : '';
    if (actionBtn) {
        actionBtn.textContent = 'Processing...';
        actionBtn.disabled = true;
    }
    
    fetch('api/process-visit-request.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `id=${visitId}&action=${action}`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert(`Visit ${action}ed successfully.`);
            loadVisitRequests(); // Refresh the view
        } else {
            console.error('Error:', data.message);
            alert('Error: ' + data.message);
            
            // Reset button state
            if (actionBtn) {
                actionBtn.textContent = originalText;
                actionBtn.disabled = false;
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while processing your request. Please try again.');
        
        // Reset button state
        if (actionBtn) {
            actionBtn.textContent = originalText;
            actionBtn.disabled = false;
        }
    });
}
    
    // Generate calendar view for visit scheduling
    function generateCalendarView(month, year, visitsByDate) {
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 
                           'August', 'September', 'October', 'November', 'December'];
                           
        const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        
        // Create the first day of the month
        const firstDay = new Date(year, month, 1);
        const startingDay = firstDay.getDay(); // 0 = Sunday, 1 = Monday, etc.
        
        // Get the number of days in the month
        const lastDay = new Date(year, month + 1, 0);
        const daysInMonth = lastDay.getDate();
        
        // Create calendar header
        let html = `
            <div class="calendar-header">
                <div class="calendar-title">${monthNames[month]} ${year}</div>
                <div class="calendar-nav">
                    <button class="calendar-btn" id="prev-month">&lt; Prev</button>
                    <button class="calendar-btn" id="next-month">Next &gt;</button>
                </div>
            </div>
            <div class="calendar-grid">`;
        
        // Add day names header
        dayNames.forEach(day => {
            html += `<div class="calendar-day-header">${day}</div>`;
        });
        
        // Add empty cells for days before the first day of the month
        for (let i = 0; i < startingDay; i++) {
            html += '<div class="calendar-day empty"></div>';
        }
        
        // Fill in the days of the month
        for (let i = 1; i <= daysInMonth; i++) {
            const dateString = `${year}-${(month + 1).toString().padStart(2, '0')}-${i.toString().padStart(2, '0')}`;
            const visits = visitsByDate[dateString] || [];
            
            // Count visits by status
            const pendingVisits = visits.filter(v => v.status === 'pending').length;
            const approvedVisits = visits.filter(v => v.status === 'approved').length;
            
            html += `
                <div class="calendar-day${visits.length > 0 ? ' has-events' : ''}">
                    <div class="calendar-date">${i}</div>`;
                    
            if (visits.length > 0) {
                if (pendingVisits > 0) {
                    html += `<div class="calendar-event event-pending">${pendingVisits} pending</div>`;
                }
                if (approvedVisits > 0) {
                    html += `<div class="calendar-event event-approved">${approvedVisits} approved</div>`;
                }
                html += `<div class="calendar-event-action" data-date="${dateString}">View all</div>`;
            }
            
            html += `</div>`;
        }
        
        html += '</div>';
        return html;
    }
    
    // Set up calendar navigation
    function setupCalendarNavigation(currentMonth, currentYear, visitsByDate) {
        const prevBtn = document.getElementById('prev-month');
        const nextBtn = document.getElementById('next-month');
        
        if (prevBtn && nextBtn) {
            prevBtn.addEventListener('click', function() {
                let newMonth = currentMonth - 1;
                let newYear = currentYear;
                
                if (newMonth < 0) {
                    newMonth = 11;
                    newYear--;
                }
                
                const container = document.getElementById('calendar-view');
                container.innerHTML = generateCalendarView(newMonth, newYear, visitsByDate);
                setupCalendarNavigation(newMonth, newYear, visitsByDate);
            });
            
            nextBtn.addEventListener('click', function() {
                let newMonth = currentMonth + 1;
                let newYear = currentYear;
                
                if (newMonth > 11) {
                    newMonth = 0;
                    newYear++;
                }
                
                const container = document.getElementById('calendar-view');
                container.innerHTML = generateCalendarView(newMonth, newYear, visitsByDate);
                setupCalendarNavigation(newMonth, newYear, visitsByDate);
            });
        }
        
        // Add event listeners to view all events for a specific date
        document.querySelectorAll('.calendar-event-action').forEach(element => {
            element.addEventListener('click', function() {
                const date = this.getAttribute('data-date');
                showDateVisitsModal(date, visitsByDate[date]);
            });
        });
    }
    
    // Show modal with all visits for a specific date
    function showDateVisitsModal(date, visits) {
        if (!visits || visits.length === 0) return;
        
        // Create a temporary modal to show the visits
        const modalHTML = `
            <div class="modal" id="dateVisitsModal">
                <div class="modal-content">
                    <span class="close-modal">&times;</span>
                    <h2>Visits on ${formatDate(date)}</h2>
                    <div class="date-visits-list">
                        ${visits.map(visit => {
                            let statusClass = '';
                            switch(visit.status) {
                                case 'pending': statusClass = 'status-pending'; break;
                                case 'approved': statusClass = 'status-approved'; break;
                                case 'rejected': statusClass = 'status-rejected'; break;
                                default: statusClass = '';
                            }
                            
                            return `
                                <div class="visit-item">
                                    <div class="visit-time">${formatTime(visit.visit_time)}</div>
                                    <div class="visit-info">
                                        <div>${visit.tenant_name}</div>
                                        <div class="status-indicator small ${statusClass}">
                                            ${visit.status.charAt(0).toUpperCase() + visit.status.slice(1)}
                                        </div>
                                    </div>
                                    <div class="visit-property">${visit.property_title}</div>
                                </div>
                            `;
                        }).join('')}
                    </div>
                </div>
            </div>
        `;
        
        // Append modal to body
        const modalElement = document.createElement('div');
        modalElement.innerHTML = modalHTML;
        document.body.appendChild(modalElement);
        
        const dateVisitsModal = document.getElementById('dateVisitsModal');
        dateVisitsModal.classList.add('show');
        document.body.style.overflow = 'hidden';
        
        // Close modal functionality
        const closeBtn = dateVisitsModal.querySelector('.close-modal');
        closeBtn.addEventListener('click', function() {
            dateVisitsModal.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(modalElement);
                document.body.style.overflow = 'auto';
            }, 300);
        });
        
        window.addEventListener('click', function(event) {
            if (event.target === dateVisitsModal) {
                dateVisitsModal.classList.remove('show');
                setTimeout(() => {
                    document.body.removeChild(modalElement);
                    document.body.style.overflow = 'auto';
                }, 300);
            }
        });
    }
    
    // Handle form submissions
    const reservationForm = document.getElementById('reservationForm');
    const visitForm = document.getElementById('visitForm');
    
    if (reservationForm) {
        reservationForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('process-reservation.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Reservation request submitted successfully!');
                    this.reset();
                    closeModal(modals.reserve);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        });
    }
    
    if (visitForm) {
        visitForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('process-visit.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Visit request submitted successfully!');
                    this.reset();
                    closeModal(modals.visit);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        });
    }
    
    // Utility functions
    function formatDate(dateString) {
        if (!dateString) return '';
        
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', options);
    }
    
    function formatTime(timeString) {
        if (!timeString) return '';
        
        const [hours, minutes] = timeString.split(':');
        const hour = parseInt(hours);
        
        if (hour === 0) {
            return `12:${minutes} AM`;
        } else if (hour < 12) {
            return `${hour}:${minutes} AM`;
        } else if (hour === 12) {
            return `12:${minutes} PM`;
        } else {
            return `${hour - 12}:${minutes} PM`;
        }
    }
});