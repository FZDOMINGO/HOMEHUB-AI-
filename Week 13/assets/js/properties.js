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
    
    // Tab switching functionality
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Get the tab to show
            const tabToShow = this.getAttribute('data-tab');
            
            // Special handling for Smart Recommendations - redirect to AI Features
            if (tabToShow === 'smart') {
                window.location.href = 'ai-features.php';
                return;
            }
            
            // Remove active class from all buttons
            tabBtns.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Hide all tab contents
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Show the selected tab
            document.getElementById(tabToShow + '-tab').classList.add('active');
        });
    });
    
    // Property card click to show detail modal
    const propertyCards = document.querySelectorAll('.property-card');
    const propertyModal = document.getElementById('property-modal');
    const closeModal = document.querySelector('.close-modal');
    const propertyDetailContent = document.getElementById('property-detail-content');
    
    propertyCards.forEach(card => {
        card.addEventListener('click', function() {
            const propertyId = this.getAttribute('data-property-id');
            fetchPropertyDetails(propertyId);
        });
    });
    
    if (closeModal) {
        closeModal.addEventListener('click', function() {
            propertyModal.classList.remove('show');
            document.body.style.overflow = 'auto';
        });
    }
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === propertyModal) {
            propertyModal.classList.remove('show');
            document.body.style.overflow = 'auto';
        }
    });
    
    // Search form submission
    const searchForm = document.getElementById('property-search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const searchParams = new URLSearchParams();
            
            for (const pair of formData.entries()) {
                if (pair[1]) {
                    searchParams.append(pair[0], pair[1]);
                }
            }
            
            // Redirect to search results page
            window.location.href = 'properties.php?' + searchParams.toString();
        });
    }
    
    // Function to fetch property details
    function fetchPropertyDetails(propertyId) {
        // Show loading state
        propertyDetailContent.innerHTML = '<div class="loading">Loading property details...</div>';
        
        // Show modal
        propertyModal.classList.add('show');
        document.body.style.overflow = 'hidden';
        
        // Fetch property details via AJAX
        fetch('property-detail-ajax.php?id=' + propertyId)
            .then(response => response.text())
            .then(html => {
                propertyDetailContent.innerHTML = html;
                
                // Initialize save/bookmark functionality
                const bookmarkBtn = document.querySelector('.bookmark-btn');
                if (bookmarkBtn) {
                    bookmarkBtn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        toggleSaveProperty(propertyId, this);
                    });
                }
            })
            .catch(error => {
                console.error('Error fetching property details:', error);
                propertyDetailContent.innerHTML = '<div class="error">Failed to load property details. Please try again.</div>';
            });
    }
    
    // Function to toggle save property
    function toggleSaveProperty(propertyId, button) {
        // Check if user is logged in
        const isLoggedIn = button.hasAttribute('data-logged-in');
        
        if (!isLoggedIn) {
            alert('Please log in to save properties.');
            return;
        }
        
        // Toggle saved state visually
        button.classList.toggle('saved');
        const isSaved = button.classList.contains('saved');
        
        // Update icon
        button.innerHTML = isSaved ? 
            '<i class="fas fa-heart"></i>' : 
            '<i class="far fa-heart"></i>';
        
        // Send save/unsave request to server
        fetch('save-property.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'property_id=' + propertyId + '&action=' + (isSaved ? 'save' : 'unsave')
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                // Revert UI if operation failed
                button.classList.toggle('saved');
                button.innerHTML = !isSaved ? 
                    '<i class="fas fa-heart"></i>' : 
                    '<i class="far fa-heart"></i>';
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Revert UI
            button.classList.toggle('saved');
            button.innerHTML = !isSaved ? 
                '<i class="fas fa-heart"></i>' : 
                '<i class="far fa-heart"></i>';
            alert('Failed to update saved status. Please try again.');
        });
    }
});