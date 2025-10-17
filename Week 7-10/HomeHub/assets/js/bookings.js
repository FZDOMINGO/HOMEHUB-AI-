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
        
        if (button && modal) {
            button.addEventListener('click', function() {
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
        
        let html = '';
        
        bookings.forEach(booking => {
            let statusClass = '';
            switch(booking.status) {
                case 'pending': statusClass = 'status-pending'; break;
                case 'approved': statusClass = 'status-approved'; break;
                case 'rejected': statusClass = 'status-rejected'; break;
                case 'conflict': statusClass = 'status-conflict'; break;
                default: statusClass = '';
            }
            
            html += `
                <div class="status-item">
                    <div class="status-item-header">
                        <div class="status-property">${booking.property_title}</div>
                        <div class="status-indicator ${statusClass}">${booking.status.charAt(0).toUpperCase() + booking.status.slice(1)}</div>
                    </div>
                    <div class="status-details">
                        ${booking.type === 'visit' ? 
                            `Visit scheduled for ${formatDate(booking.date)} at ${formatTime(booking.time)}` : 
                            `Reservation submitted on ${formatDate(booking.created_at)}`}
                    </div>`;
                    
            if (booking.status === 'conflict') {
                html += `
                    <div class="conflict-alert">
                        Double Booking Conflict: This property has overlapping reservations for 
                        ${formatDate(booking.date)}. Another booking (ID: #${booking.conflict_id}) was confirmed for the
                        same time. Please reschedule or cancel to resolve this conflict.
                    </div>
                    <div class="status-actions">
                        <button class="action-btn btn-reschedule" data-booking-id="${booking.id}" data-booking-type="${booking.type}">Reschedule</button>
                        <button class="action-btn btn-cancel" data-booking-id="${booking.id}" data-booking-type="${booking.type}">Cancel</button>
                    </div>`;
            } else if (booking.status === 'pending') {
                html += `
                    <div class="status-actions">
                        <button class="action-btn btn-cancel" data-booking-id="${booking.id}" data-booking-type="${booking.type}">Cancel Request</button>
                    </div>`;
            }
            
            html += `</div>`;
        });
        
        statusContainer.innerHTML = html;
        
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
        
        let html = '<div class="reservation-list">';
        
        reservations.forEach(reservation => {
            let statusClass = '';
            switch(reservation.status) {
                case 'pending': statusClass = 'status-pending'; break;
                case 'approved': statusClass = 'status-approved'; break;
                case 'rejected': statusClass = 'status-rejected'; break;
                default: statusClass = '';
            }
            
            html += `
                <div class="reservation-item">
                    <div class="reservation-header">
                        <div class="reservation-property">
                            <strong>${reservation.property_title}</strong>
                            <span class="status-indicator ${statusClass}">${reservation.status.charAt(0).toUpperCase() + reservation.status.slice(1)}</span>
                        </div>
                        <div class="reservation-tenant">
                            <i class="fas fa-user"></i> ${reservation.tenant_name}
                        </div>
                    </div>
                    
                    <div class="reservation-details">
                        <div class="detail-row">
                            <span class="detail-label">Move-in Date:</span>
                            <span class="detail-value">${formatDate(reservation.move_in_date)}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Lease Duration:</span>
                            <span class="detail-value">${reservation.lease_duration} months</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Request Date:</span>
                            <span class="detail-value">${formatDate(reservation.created_at)}</span>
                        </div>
                        ${reservation.requirements ? 
                            `<div class="detail-row">
                                <span class="detail-label">Requirements:</span>
                                <span class="detail-value">${reservation.requirements}</span>
                            </div>` : ''}
                    </div>`;
                    
            if (reservation.status === 'pending') {
                html += `
                    <div class="reservation-actions">
                        <button class="action-btn btn-approve" data-reservation-id="${reservation.id}">Approve</button>
                        <button class="action-btn btn-reject" data-reservation-id="${reservation.id}">Reject</button>
                    </div>`;
            }
            
            html += `</div>`;
        });
        
        html += '</div>';
        container.innerHTML = html;
        
        // Add event listeners for landlord action buttons
        document.querySelectorAll('.reservation-actions .action-btn').forEach(button => {
            button.addEventListener('click', function() {
                const reservationId = this.getAttribute('data-reservation-id');
                const action = this.classList.contains('btn-approve') ? 'approve' : 'reject';
                
                handleLandlordReservationAction(reservationId, action);
            });
        });
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

        // Add event listeners for landlord action buttons
document.querySelectorAll('.visit-actions .action-btn').forEach(button => {
    button.addEventListener('click', function(event) {
        event.preventDefault(); // Prevent any default action
        const visitId = this.getAttribute('data-visit-id');
        let action = '';
        
        if (this.classList.contains('btn-approve')) action = 'approve';
        else if (this.classList.contains('btn-reject')) action = 'reject';
        else if (this.classList.contains('btn-cancel')) action = 'cancel';
        
        handleLandlordVisitAction(visitId, action);
    });
});
        
        // Group visits by date for calendar view
        const visitsByDate = {};
        visits.forEach(visit => {
            const dateKey = visit.visit_date;
            if (!visitsByDate[dateKey]) {
                visitsByDate[dateKey] = [];
            }
            visitsByDate[dateKey].push(visit);
        });
        
        // Generate calendar view
        const currentDate = new Date();
        const currentMonth = currentDate.getMonth();
        const currentYear = currentDate.getFullYear();
        
        const calendarHTML = generateCalendarView(currentMonth, currentYear, visitsByDate);
        
        // Generate list view
        let listHTML = '<div class="visit-list">';
        
        visits.forEach(visit => {
            let statusClass = '';
            switch(visit.status) {
                case 'pending': statusClass = 'status-pending'; break;
                case 'approved': statusClass = 'status-approved'; break;
                case 'rejected': statusClass = 'status-rejected'; break;
                case 'completed': statusClass = 'status-completed'; break;
                default: statusClass = '';
            }
            
            listHTML += `
                <div class="visit-item">
                    <div class="visit-header">
                        <div class="visit-property">
                            <strong>${visit.property_title}</strong>
                            <span class="status-indicator ${statusClass}">${visit.status.charAt(0).toUpperCase() + visit.status.slice(1)}</span>
                        </div>
                        <div class="visit-tenant">
                            <i class="fas fa-user"></i> ${visit.tenant_name}
                        </div>
                    </div>
                    
                    <div class="visit-details">
                        <div class="detail-row">
                            <span class="detail-label">Visit Date:</span>
                            <span class="detail-value">${formatDate(visit.visit_date)}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Visit Time:</span>
                            <span class="detail-value">${formatTime(visit.visit_time)}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Visitors:</span>
                            <span class="detail-value">${visit.number_of_visitors}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Contact:</span>
                            <span class="detail-value">${visit.phone_number}</span>
                        </div>
                        ${visit.message ? 
                            `<div class="detail-row">
                                <span class="detail-label">Message:</span>
                                <span class="detail-value">${visit.message}</span>
                            </div>` : ''}
                    </div>`;
                    
            if (visit.status === 'pending') {
                listHTML += `
                    <div class="visit-actions">
                        <button class="action-btn btn-approve" data-visit-id="${visit.id}">Approve</button>
                        <button class="action-btn btn-reject" data-visit-id="${visit.id}">Reject</button>
                    </div>`;
            } else if (visit.status === 'approved' && new Date(visit.visit_date + ' ' + visit.visit_time) > new Date()) {
                listHTML += `
                    <div class="visit-actions">
                        <button class="action-btn btn-cancel" data-visit-id="${visit.id}">Cancel Visit</button>
                    </div>`;
            }
            
            listHTML += `</div>`;
        });
        
        listHTML += '</div>';
        
        // Create tab system for calendar and list views
        const html = `
            <div class="view-tabs">
                <button class="view-tab active" data-view="calendar">Calendar View</button>
                <button class="view-tab" data-view="list">List View</button>
            </div>
            <div class="view-content">
                <div id="calendar-view" class="view-panel active">${calendarHTML}</div>
                <div id="list-view" class="view-panel">${listHTML}</div>
            </div>
        `;
        
        container.innerHTML = html;
        
        // Add tab switching functionality
        document.querySelectorAll('.view-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.view-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.view-panel').forEach(p => p.classList.remove('active'));
                
                this.classList.add('active');
                const view = this.getAttribute('data-view');
                document.getElementById(`${view}-view`).classList.add('active');
            });
        });
        
        // Add event listeners for landlord action buttons
        document.querySelectorAll('.visit-actions .action-btn').forEach(button => {
            button.addEventListener('click', function() {
                const visitId = this.getAttribute('data-visit-id');
                let action = '';
                
                if (this.classList.contains('btn-approve')) action = 'approve';
                else if (this.classList.contains('btn-reject')) action = 'reject';
                else if (this.classList.contains('btn-cancel')) action = 'cancel';
                
                handleLandlordVisitAction(visitId, action);
            });
        });
        
        // Add calendar navigation
        setupCalendarNavigation(currentMonth, currentYear, visitsByDate);
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
    function handleLandlordReservationAction(reservationId, action) {
        if (!confirm(`Are you sure you want to ${action} this reservation request?`)) return;
        
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
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }
    
    // Handle landlord's visit actions (approve, reject, or cancel)
// Handle landlord's visit actions (approve, reject, or cancel)
function handleLandlordVisitAction(visitId, action) {
    if (!confirm(`Are you sure you want to ${action} this visit?`)) return;
    
    console.log(`Processing visit ${visitId} with action: ${action}`);
    
    // Show loading state on the button if possible
    const actionBtn = event.target;
    const originalText = actionBtn.textContent;
    actionBtn.textContent = 'Processing...';
    actionBtn.disabled = true;
    
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
    
    // Handle logout
    const logoutBtn = document.getElementById('logoutBtn');
    const logoutBtnMobile = document.getElementById('logoutBtnMobile');
    
    function handleLogout(e) {
        e.preventDefault();
        
        fetch('api/logout.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.redirect || 'index.php';
                } else {
                    alert('Logout failed. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred during logout. Please try again.');
            });
    }
    
    if (logoutBtn) logoutBtn.addEventListener('click', handleLogout);
    if (logoutBtnMobile) logoutBtnMobile.addEventListener('click', handleLogout);
    
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