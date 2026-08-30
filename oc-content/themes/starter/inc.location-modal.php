<?php
  // Query all active countries and regions
  $all_countries = Country::newInstance()->listAll();
  $regions = Region::newInstance()->findByCountry('LK');
  
  // Comprehensive static mapping of cities/districts for major regions
  $static_cities = array(
    'Western Province' => array('Colombo', 'Gampaha', 'Kalutara', 'Negombo', 'Dehiwala-Mount Lavinia', 'Moratuwa', 'Kotte', 'Kaduwela', 'Maharagama', 'Kesbewa', 'Battaramulla', 'Homagama', 'Malabe', 'Avissawella'),
    'Central Province' => array('Kandy', 'Matale', 'Nuwara Eliya', 'Gampola', 'Dambulla', 'Hatton'),
    'Southern Province' => array('Galle', 'Matara', 'Hambantota', 'Beliatta', 'Ambalangoda', 'Hikkaduwa', 'Weligama', 'Tangalle'),
    'Northern Province' => array('Jaffna', 'Vavuniya', 'Mannar', 'Point Pedro', 'Kilinochchi'),
    'Eastern Province' => array('Trincomalee', 'Batticaloa', 'Ampara', 'Kalmunai'),
    'North Western Province' => array('Kurunegala', 'Puttalam', 'Chilaw', 'Kuliyapitiya'),
    'North Central Province' => array('Anuradhapura', 'Polonnaruwa'),
    'Province of Uva' => array('Badulla', 'Bandarawela', 'Monaragala'),
    'Sabaragamuwa Province' => array('Ratnapura', 'Kegalle', 'Mawanella', 'Balangoda')
  );

  $global_countries_static = array(
    array('code' => 'US', 'name' => __('United States', 'starter')),
    array('code' => 'GB', 'name' => __('United Kingdom', 'starter')),
    array('code' => 'LK', 'name' => __('Sri Lanka', 'starter')),
    array('code' => 'AE', 'name' => __('United Arab Emirates', 'starter')),
    array('code' => 'AU', 'name' => __('Australia', 'starter')),
    array('code' => 'CA', 'name' => __('Canada', 'starter')),
    array('code' => 'DE', 'name' => __('Germany', 'starter')),
    array('code' => 'FR', 'name' => __('France', 'starter')),
    array('code' => 'IN', 'name' => __('India', 'starter')),
    array('code' => 'JP', 'name' => __('Japan', 'starter')),
    array('code' => 'SG', 'name' => __('Singapore', 'starter')),
    array('code' => 'CH', 'name' => __('Switzerland', 'starter')),
  );

  $location_hierarchy = array();
  if (is_array($regions)) {
    foreach($regions as $r) {
      $r_name = osc_location_native_name_selector($r, 's_name');
      $city_list = array();
      
      if (isset($static_cities[$r_name])) {
        foreach($static_cities[$r_name] as $index => $c_name) {
          $city_list[] = array(
            'id' => $r['pk_i_id'] . '_' . $index,
            'name' => $c_name
          );
        }
      }
      
      $location_hierarchy[] = array(
        'id' => $r['pk_i_id'],
        'name' => $r_name,
        'cities' => $city_list
      );
    }
  }
?>

<div class="modern-location-modal-overlay" id="modern-location-modal" style="display: none;">
  <div class="modern-location-modal-card">
    <div class="modal-header">
      <h3><?php _e('Select Location', 'starter'); ?></h3>
      <button class="modal-close-btn" onclick="closeLocationModal()">&times;</button>
    </div>
    
    <div class="modal-search-wrapper" style="padding: 16px 24px !important; position: relative !important; background: #ffffff !important; border-bottom: 1px solid #f1f5f9 !important;">
      <div class="modal-search-input-box" style="position: relative !important; width: 100% !important; height: 48px !important; min-height: 48px !important; display: flex !important; align-items: center !important; float: none !important; clear: both !important; margin: 0 !important; padding: 0 !important;">
        <i class="fa fa-search search-icon" style="position: absolute !important; left: 14px !important; top: 50% !important; transform: translateY(-50%) !important; -webkit-transform: translateY(-50%) !important; color: #64748b !important; font-size: 16px !important; height: 16px !important; line-height: 16px !important; z-index: 10 !important; pointer-events: none !important; margin: 0 !important; padding: 0 !important; float: none !important; display: inline-block !important;"></i>
        <input type="text" id="modal-location-search" placeholder="<?php _e('Search country, region or city...', 'starter'); ?>" oninput="filterLocations(this.value)" autocomplete="off" style="float: none !important; clear: both !important; width: 100% !important; height: 48px !important; min-height: 48px !important; padding-left: 44px !important; padding-right: 16px !important; border-radius: 12px !important; background: #f8fafc !important; border: 1px solid #cbd5e1 !important; box-sizing: border-box !important; font-size: 15px !important; color: #0f172a !important; outline: none !important; margin: 0 !important; position: relative !important;" />
      </div>
    </div>
    
    <div class="modal-breadcrumbs" id="modal-breadcrumbs" style="display: none;">
      <span class="breadcrumb-back" onclick="goBackToRegions()"><i class="fa fa-globe"></i> <?php _e('Worldwide', 'starter'); ?></span>
      <span class="breadcrumb-separator">/</span>
      <span class="breadcrumb-current" id="breadcrumb-current-region"></span>
    </div>
    
    <div class="modal-list-container">
      <ul class="location-list" id="location-list">
        <!-- populated dynamically via JS -->
      </ul>
    </div>
  </div>
</div>

<script type="text/javascript">
  var sriLankaLocations = <?php echo json_encode($location_hierarchy); ?>;
  var globalCountries = <?php echo json_encode($global_countries_static); ?>;
  var currentRegion = null;
  var locationSearchQuery = '';

  function openLocationModal() {
    document.getElementById('modern-location-modal').style.display = 'flex';
    document.body.classList.add('no-scroll');
    document.getElementById('modal-location-search').value = '';
    document.getElementById('modal-location-search').focus();
    locationSearchQuery = '';
    renderRegions();
  }

  function closeLocationModal() {
    document.getElementById('modern-location-modal').style.display = 'none';
    document.body.classList.remove('no-scroll');
  }

  // Close modal when clicking outside card
  window.addEventListener('click', function(e) {
    var modal = document.getElementById('modern-location-modal');
    if (e.target === modal) {
      closeLocationModal();
    }
  });

  function renderRegions() {
    currentRegion = null;
    document.getElementById('modal-breadcrumbs').style.display = 'none';
    
    var list = document.getElementById('location-list');
    list.innerHTML = '';
    
    // Add "Worldwide" option
    var allLi = document.createElement('li');
    allLi.className = 'location-item all-srilanka-item';
    allLi.innerHTML = '<i class="fa fa-globe"></i> <span><?php echo osc_esc_js(__('Worldwide', 'starter')); ?></span>';
    allLi.onclick = function() {
      selectLocation('', '', 'Worldwide');
    };
    list.appendChild(allLi);
    
    // Add Regions & Countries
    sriLankaLocations.forEach(function(r) {
      if (locationSearchQuery === '' || r.name.toLowerCase().includes(locationSearchQuery.toLowerCase())) {
        var li = document.createElement('li');
        li.className = 'location-item region-item';
        li.innerHTML = '<span>' + r.name + '</span><i class="fa fa-angle-right"></i>';
        li.onclick = function() {
          selectRegion(r);
        };
        list.appendChild(li);
      }
    });

    globalCountries.forEach(function(c) {
      if (c.code !== 'LK' && (locationSearchQuery === '' || c.name.toLowerCase().includes(locationSearchQuery.toLowerCase()))) {
        var li = document.createElement('li');
        li.className = 'location-item region-item';
        li.innerHTML = '<span><i class="fa fa-flag" style="margin-right:8px;color:#94a3b8;"></i>' + c.name + '</span>';
        li.onclick = function() {
          selectLocation(c.code, '', c.name);
        };
        list.appendChild(li);
      }
    });
  }

  function selectRegion(region) {
    currentRegion = region;
    document.getElementById('breadcrumb-current-region').innerText = region.name;
    document.getElementById('modal-breadcrumbs').style.display = 'flex';
    
    var list = document.getElementById('location-list');
    list.innerHTML = '';
    
    // Add "All in Province" option
    var allLi = document.createElement('li');
    allLi.className = 'location-item all-region-item';
    allLi.innerHTML = '<i class="fa fa-map-marker"></i> <span>All of ' + region.name + '</span>';
    allLi.onclick = function() {
      selectLocation(region.name, '', region.name);
    };
    list.appendChild(allLi);
    
    // Add Cities
    region.cities.forEach(function(c) {
      if (locationSearchQuery === '' || c.name.toLowerCase().includes(locationSearchQuery.toLowerCase())) {
        var li = document.createElement('li');
        li.className = 'location-item city-item';
        li.innerHTML = '<span>' + c.name + '</span>';
        li.onclick = function() {
          selectLocation(region.name, c.name, c.name + ', ' + region.name);
        };
        list.appendChild(li);
      }
    });
  }

  function goBackToRegions() {
    currentRegion = null;
    renderRegions();
  }

  function filterLocations(query) {
    locationSearchQuery = query;
    if (query !== '') {
      var list = document.getElementById('location-list');
      list.innerHTML = '';
      
      var matchesCount = 0;
      
      // Filter regions
      sriLankaLocations.forEach(function(r) {
        if (r.name.toLowerCase().includes(query.toLowerCase())) {
          var li = document.createElement('li');
          li.className = 'location-item region-item search-match';
          li.innerHTML = '<span>' + r.name + ' <small class="type-label">(Province)</small></span><i class="fa fa-angle-right"></i>';
          li.onclick = function() {
            selectRegion(r);
          };
          list.appendChild(li);
          matchesCount++;
        }
        
        // Filter cities
        r.cities.forEach(function(c) {
          if (c.name.toLowerCase().includes(query.toLowerCase())) {
            var li = document.createElement('li');
            li.className = 'location-item city-item search-match';
            li.innerHTML = '<span>' + c.name + ' <small class="region-parent">in ' + r.name + '</small></span>';
            li.onclick = function() {
              selectLocation(r.name, c.name, c.name + ', ' + r.name);
            };
            list.appendChild(li);
            matchesCount++;
          }
        });
      });
      
      if (matchesCount === 0) {
        var li = document.createElement('li');
        li.className = 'location-item no-matches';
        li.innerText = 'No locations match your search';
        list.appendChild(li);
      }
    } else {
      if (currentRegion) {
        selectRegion(currentRegion);
      } else {
        renderRegions();
      }
    }
  }

  function selectLocation(regionName, cityName, displayName) {
    // 1. Update form fields on page if they exist
    var regionInput = document.getElementById('sRegion');
    var cityInput = document.getElementById('sCity');
    var termInput = document.getElementById('term');
    
    if (regionInput) regionInput.value = (cityName !== '') ? '' : regionName;
    if (cityInput) cityInput.value = cityName;
    if (termInput) termInput.value = displayName;
    
    // Update visual triggers
    var labels = document.querySelectorAll('.selected-location-text');
    labels.forEach(function(lbl) {
      lbl.innerText = displayName;
    });
    
    closeLocationModal();
    
    // 2. Submit form or redirect
    var heroForm = document.querySelector('.hero-search-panel form');
    var sidebarForm = document.querySelector('form.search-side-form');
    
    if (heroForm) {
      heroForm.submit();
    } else if (sidebarForm) {
      var regionSidebar = sidebarForm.querySelector('input[name="sRegion"]');
      var citySidebar = sidebarForm.querySelector('input[name="sCity"]');
      var termSidebar = sidebarForm.querySelector('input[name="term"]');
      
      if (regionSidebar) regionSidebar.value = (cityName !== '') ? '' : regionName;
      if (citySidebar) citySidebar.value = cityName;
      if (termSidebar) termSidebar.value = displayName;
      
      if (citySidebar) {
        $(citySidebar).change();
      } else if (regionSidebar) {
        $(regionSidebar).change();
      } else {
        sidebarForm.submit();
      }
    } else {
      var searchUrl = baseDir + 'index.php?page=search';
      if (cityName !== '') {
        searchUrl += '&sCity=' + encodeURIComponent(cityName);
      } else if (regionName !== '') {
        searchUrl += '&sRegion=' + encodeURIComponent(regionName);
      }
      
      var queryField = document.getElementById('query');
      if (queryField && queryField.value !== '') {
        searchUrl += '&sPattern=' + encodeURIComponent(queryField.value);
      }
      
      window.location.href = searchUrl;
    }
  }
</script>
