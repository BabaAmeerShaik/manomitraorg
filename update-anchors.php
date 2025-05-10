<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Update Emotional Anchors - ManoMitra</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .image-option img {
      border: 4px solid transparent;
      transition: all 0.3s ease;
      height: 220px;
      width: 220px;
      object-fit: cover;
      margin: auto;
    }

    /* Enlarge on hover */
    .image-option img:hover {
      transform: scale(1.15);
      cursor: pointer;
      z-index: 10;
      position: relative;
    }

    /* Green border on selection */
    .image-option img.selected {
      border-color: #10b981;
      box-shadow: 0 0 0 4px #d1fae5;
      transform: scale(1.15);
      z-index: 5;
      position: relative;
    }
    
    .alert {
      opacity: 0;
      transition: opacity 0.5s ease;
      display: none;
    }
    
    .alert.show {
      opacity: 1;
      display: block;
    }
    
    .alert.success {
      background-color: #d1fae5;
      border-color: #10b981;
      color: #065f46;
    }
    
    .alert.error {
      background-color: #fee2e2;
      border-color: #ef4444;
      color: #b91c1c;
    }
    
    .alert.warning {
      background-color: #fff7ed;
      border-color: #f97316;
      color: #c2410c;
    }
    
    /* Make grid responsive for larger images */
    @media (max-width: 1024px) {
      .grid-cols-3 {
        grid-template-columns: repeat(2, 1fr);
      }
      
      .image-option img {
        height: 200px;
        width: 200px;
      }
    }
    
    @media (max-width: 640px) {
      .grid-cols-3 {
        grid-template-columns: repeat(1, 1fr);
      }
    }
  </style>
</head>
<body class="bg-green-50 min-h-screen flex items-center justify-center p-6">
  <div class="bg-white shadow-2xl rounded-3xl p-8 w-full max-w-4xl">
    <div class="text-center mb-6">
      <img src="manomitra.jpeg" alt="ManoMitra Logo" class="h-20 mx-auto rounded-xl mb-4">
      <h1 class="text-3xl font-bold text-emerald-800">Update Your Emotional Anchors</h1>
      <p class="text-emerald-700">Choose new images that resonate with your current emotional state</p>
    </div>

    <!-- Notification area -->
    <div id="notification" class="mb-4 p-4 rounded-xl text-center font-medium alert">
      <!-- Notification text will appear here -->
    </div>

    <form id="update-anchors-form">
      <!-- Peace Selection -->
      <div class="mb-8">
        <p class="text-lg font-semibold text-emerald-700 mb-3">When you're at peace, choose an image:</p>
        <div class="grid grid-cols-3 gap-6 image-option">
          <button type="button" onclick="select('calm','boat_lake')" id="calm-boat_lake"><img src="images/boat_lake.jpg" class="rounded-xl"></button>
          <button type="button" onclick="select('calm','rocks')" id="calm-rocks"><img src="images/rocks.jpg" class="rounded-xl"></button>
          <button type="button" onclick="select('calm','still_water')" id="calm-still_water"><img src="images/still_water.jpg" class="rounded-xl"></button>
          <button type="button" onclick="select('calm','tree')" id="calm-tree"><img src="images/tree.jpg" class="rounded-xl"></button>
          <button type="button" onclick="select('calm','swan')" id="calm-swan"><img src="images/swan.jpg" class="rounded-xl"></button>
          <button type="button" onclick="select('calm','cliff')" id="calm-cliff"><img src="images/cliff.jpg" class="rounded-xl"></button>
        </div>
      </div>
  
      <!-- Strength Selection -->
      <div class="mb-8">
        <p class="text-lg font-semibold text-emerald-700 mb-3">When you're at your strongest, pick an image:</p>
        <div class="grid grid-cols-3 gap-6 image-option">
          <button type="button" onclick="select('strong','chain')" id="strong-chain"><img src="images/chain.jpg" class="rounded-xl"></button>
          <button type="button" onclick="select('strong','fist')" id="strong-fist"><img src="images/fist.jpg" class="rounded-xl"></button>
          <button type="button" onclick="select('strong','elephant')" id="strong-elephant"><img src="images/catherine-zaidova_elephant.jpg" class="rounded-xl"></button>
          <button type="button" onclick="select('strong','storm')" id="strong-storm"><img src="images/marcus-woodbridge_storm.jpg" class="rounded-xl"></button>
          <button type="button" onclick="select('strong','mother')" id="strong-mother"><img src="images/sippakorn-yamkasikorn_mother_child.jpg" class="rounded-xl"></button>
          <button type="button" onclick="select('strong','mountain')" id="strong-mountain"><img src="images/x-mountain_climb.jpg" class="rounded-xl"></button>
        </div>
      </div>
  
      <!-- Focused Selection -->
      <div class="mb-8">
        <p class="text-lg font-semibold text-emerald-700 mb-3">When you're deeply focused, choose one:</p>
        <div class="grid grid-cols-3 gap-6 image-option">
          <button type="button" onclick="select('focused','camera')" id="focused-camera"><img src="images/devin-avery-camera.jpg" class="rounded-xl"></button>
          <button type="button" onclick="select('focused','leopard')" id="focused-leopard"><img src="images/harshit-suryawanshi-leopard.jpg" class="rounded-xl"></button>
          <button type="button" onclick="select('focused','tightrope')" id="focused-tightrope"><img src="images/loic-leray-tight_rope.jpg" class="rounded-xl"></button>
          <button type="button" onclick="select('focused','chess')" id="focused-chess"><img src="images/michal-vrba-chess.jpg" class="rounded-xl"></button>
          <button type="button" onclick="select('focused','microscope')" id="focused-microscope"><img src="images/microscope.jpg" class="rounded-xl"></button>
          <button type="button" onclick="select('focused','archer')" id="focused-archer"><img src="images/archer.jpg" class="rounded-xl"></button>
        </div>
      </div>
      
      <!-- Joy Selection -->
      <div class="mb-8">
        <p class="text-lg font-semibold text-emerald-700 mb-3">When you're feeling joy, select an image:</p>
        <div class="grid grid-cols-3 gap-6 image-option">
          <button type="button" onclick="select('joy','children')" id="joy-children"><img src="images/children_playing.jpg" class="rounded-xl"></button>
          <button type="button" onclick="select('joy','sunset')" id="joy-sunset"><img src="images/sunset.jpg" class="rounded-xl"></button>
          <button type="button" onclick="select('joy','dog')" id="joy-dog"><img src="images/dog_running.jpg" class="rounded-xl"></button>
          <button type="button" onclick="select('joy','friends')" id="joy-friends"><img src="images/friends_laughing.jpg" class="rounded-xl"></button>
          <button type="button" onclick="select('joy','balloon')" id="joy-balloon"><img src="images/hot_air_balloon.jpg" class="rounded-xl"></button>
          <button type="button" onclick="select('joy','family')" id="joy-family"><img src="images/family_embrace.jpg" class="rounded-xl"></button>
        </div>
      </div>
      
      <div class="flex justify-center mt-10">
        <button type="submit" id="submit-button" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-8 rounded-xl transition-all duration-300 transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed">
          Save My Emotional Anchors
        </button>
      </div>
    </form>
  </div>

  <script>
    // Variables to track selections
    const selections = {
      calm: null,
      strong: null,
      focused: null,
      joy: null
    };
    
    // Handle image selection
    function select(category, image) {
      // Remove selected class from all images in category
      document.querySelectorAll(`[id^="${category}-"] img`).forEach(img => {
        img.classList.remove('selected');
      });
      
      // Add selected class to chosen image
      const selectedImg = document.querySelector(`#${category}-${image} img`);
      selectedImg.classList.add('selected');
      
      // Store selection
      selections[category] = image;
      
      // Check if all categories have selections to enable submit button
      checkSubmitStatus();
      
      // Show temporary notification
      showNotification('success', `Image selected for ${category.charAt(0).toUpperCase() + category.slice(1)}`);
    }
    
    // Check if all required categories have selections
    function checkSubmitStatus() {
      const allSelected = Object.values(selections).every(selection => selection !== null);
      document.getElementById('submit-button').disabled = !allSelected;
    }
    
    // Show notification
    function showNotification(type, message) {
      const notification = document.getElementById('notification');
      
      // Remove any existing classes
      notification.classList.remove('success', 'error', 'warning');
      
      // Add new class and message
      notification.classList.add(type, 'show');
      notification.textContent = message;
      
      // Hide after 3 seconds
      setTimeout(() => {
        notification.classList.remove('show');
      }, 3000);
    }
    
    // Form submission
    document.getElementById('update-anchors-form').addEventListener('submit', function(event) {
      event.preventDefault();
      
      // Simulate API call
      setTimeout(() => {
        showNotification('success', 'Your emotional anchors have been updated successfully!');
        
        // Reset form after successful submission (optional)
        // resetForm();
      }, 1500);
      
      // Show loading notification
      showNotification('warning', 'Updating your emotional anchors...');
    });
    
    // Reset form (optional function)
    function resetForm() {
      // Remove all selections
      document.querySelectorAll('.image-option img').forEach(img => {
        img.classList.remove('selected');
      });
      
      // Reset selections object
      Object.keys(selections).forEach(key => {
        selections[key] = null;
      });
      
      // Disable submit button
      checkSubmitStatus();
    }
    
    // Initialize form
    document.addEventListener('DOMContentLoaded', function() {
      checkSubmitStatus();
    });
  </script>
</body>
</html>