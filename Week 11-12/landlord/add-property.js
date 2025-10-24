document.addEventListener('DOMContentLoaded', function() {
  console.log('Add property script loaded');
  
  // Image preview functionality
  const imageInput = document.getElementById('property_images');
  const imagePreview = document.getElementById('image-preview');
  
  if (imageInput) {
    console.log('Image input found');
    imageInput.addEventListener('change', function() {
      console.log('Images selected:', this.files.length);
      // Clear previous previews
      imagePreview.innerHTML = '';
      
      if (this.files) {
        // Limit to 10 files
        const maxFiles = 10;
        const filesToProcess = Math.min(this.files.length, maxFiles);
        
        if (this.files.length > maxFiles) {
          alert(`You can only upload a maximum of ${maxFiles} images. Only the first ${maxFiles} will be processed.`);
        }
        
        // Create preview for each file
        for (let i = 0; i < filesToProcess; i++) {
          const file = this.files[i];
          
          // Check if file is an image
          if (!file.type.match('image.*')) {
            continue;
          }
          
          const reader = new FileReader();
          
          reader.onload = function(e) {
            const previewItem = document.createElement('div');
            previewItem.className = 'preview-item';
            
            const img = document.createElement('img');
            img.src = e.target.result;
            
            const removeBtn = document.createElement('div');
            removeBtn.className = 'remove-btn';
            removeBtn.innerHTML = '×';
            removeBtn.addEventListener('click', function() {
              previewItem.remove();
            });
            
            previewItem.appendChild(img);
            previewItem.appendChild(removeBtn);
            imagePreview.appendChild(previewItem);
            
            // Mark the first image as primary
            if (i === 0) {
              const primaryMarker = document.createElement('div');
              primaryMarker.className = 'primary-marker';
              primaryMarker.textContent = 'Primary';
              primaryMarker.style.position = 'absolute';
              primaryMarker.style.bottom = '0';
              primaryMarker.style.left = '0';
              primaryMarker.style.right = '0';
              primaryMarker.style.background = 'rgba(139, 92, 246, 0.8)';
              primaryMarker.style.color = 'white';
              primaryMarker.style.textAlign = 'center';
              primaryMarker.style.fontSize = '12px';
              primaryMarker.style.padding = '3px';
              previewItem.appendChild(primaryMarker);
            }
          };
          
          reader.readAsDataURL(file);
        }
      }
    });
  } else {
    console.error('Property image input not found');
  }
  
  // Form submission
  const propertyForm = document.getElementById('propertyForm');
  const submitBtn = document.querySelector('.btn-submit');
  
  if (propertyForm) {
    console.log('Property form found');
    propertyForm.addEventListener('submit', function(e) {
      console.log('Form submit event triggered');
      
      // Ensure the form is valid
      if (!this.checkValidity()) {
        console.log('Form is invalid');
        return; // Browser will handle invalid form feedback
      }
      
      // Add loading state
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding Property...';
      }
      
      // Form will submit normally
    });
  } else {
    console.error('Property form not found');
  }
  
  // Add a direct click handler on the submit button as a backup
  if (submitBtn) {
    submitBtn.addEventListener('click', function() {
      console.log('Submit button clicked');
      // This is just for logging, the form submit handler should still work
    });
  }
});