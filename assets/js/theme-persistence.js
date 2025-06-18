// Initialize theme settings when DOM is ready
$(document).ready(function() {
  // Load saved settings
  loadThemeSettings();
  
  // Initialize checkmarks
  getCheckmark();
  
  // Set up window resize handler
  $(window).resize(function() {
    $(window).width();
  });
  
  // Dark mode toggle handler
  $('#darkModeSwitch').change(function() {
    if ($(this).is(':checked')) {
      $('html').attr('data-theme', 'dark');
    } else {
      $('html').removeAttr('data-theme');
    }
    saveThemeSettings();
  });
});

// Save theme settings to localStorage
function saveThemeSettings() {
  const settings = {
    logoHeaderColor: $('.logo-header').attr('data-background-color') || 'dark',
    topBarColor: $('.main-header .navbar-header').attr('data-background-color') || 'white',
    sideBarColor: $('.sidebar').attr('data-background-color') || 'dark',
    bodyBackgroundFull: $('body').attr('data-background-full') || 'default',
    backgroundColor: $('body').attr('data-background-color') || 'default',
    darkMode: $('html').attr('data-theme') === 'dark'
  };
  localStorage.setItem('themeSettings', JSON.stringify(settings));
}

// Load theme settings from localStorage
function loadThemeSettings() {
  const savedSettings = localStorage.getItem('themeSettings');
  if (savedSettings) {
    const settings = JSON.parse(savedSettings);
    
    // Apply dark mode if enabled
    if (settings.darkMode) {
      $('html').attr('data-theme', 'dark');
      $('#darkModeSwitch').prop('checked', true);
    }
    
    // Apply logo header color
    $('.logo-header').attr('data-background-color', settings.logoHeaderColor);
    $(`.changeLogoHeaderColor[data-color="${settings.logoHeaderColor}"]`).addClass('selected');
    
    // Apply top bar color
    $('.main-header .navbar-header').attr('data-background-color', settings.topBarColor);
    $(`.changeTopBarColor[data-color="${settings.topBarColor}"]`).addClass('selected');
    
    // Apply sidebar color
    $('.sidebar').attr('data-background-color', settings.sideBarColor);
    $(`.changeSideBarColor[data-color="${settings.sideBarColor}"]`).addClass('selected');
    
    // Apply body background full
    if (settings.bodyBackgroundFull !== 'default') {
      $('body').attr('data-background-full', settings.bodyBackgroundFull);
      $(`.changeBodyBackgroundFullColor[data-color="${settings.bodyBackgroundFull}"]`).addClass('selected');
    }
    
    // Apply background color
    if (settings.backgroundColor !== 'default') {
      $('body').attr('data-background-color', settings.backgroundColor);
      $(`.changeBackgroundColor[data-color="${settings.backgroundColor}"]`).addClass('selected');
    }
    
    // Update logo and UI
    customCheckColor();
    layoutsColors();
    getCheckmark();
  }
}

// Update logo based on header color
function customCheckColor() {
  // Force logo to always be invpos.gif (ignore theme-based changes)
  $(".logo-header .navbar-brand").attr("src", "<?= BASE_URL ?>assets/img/invpos.gif");
}

// Update checkmarks for selected buttons
function getCheckmark() {
  var checkmark = `<i class="gg-check"></i>`;
  $(".btnSwitch").find("button").empty();
  $(".btnSwitch").find("button.selected").append(checkmark);
}

// Logo header color change
$(".changeLogoHeaderColor").on("click", function() {
  $(".logo-header").attr("data-background-color", $(this).attr("data-color"));
  $(this).parent().find(".changeLogoHeaderColor").removeClass("selected");
  $(this).addClass("selected");
  customCheckColor();
  layoutsColors();
  getCheckmark();
  saveThemeSettings();
});

// Top bar color change
$(".changeTopBarColor").on("click", function() {
  $(".main-header .navbar-header").attr("data-background-color", $(this).attr("data-color"));
  $(this).parent().find(".changeTopBarColor").removeClass("selected");
  $(this).addClass("selected");
  layoutsColors();
  getCheckmark();
  saveThemeSettings();
});

// Sidebar color change
$(".changeSideBarColor").on("click", function() {
  $(".sidebar").attr("data-background-color", $(this).attr("data-color"));
  $(this).parent().find(".changeSideBarColor").removeClass("selected");
  $(this).addClass("selected");
  layoutsColors();
  getCheckmark();
  saveThemeSettings();
});

// Body background full color change
$(".changeBodyBackgroundFullColor").on("click", function() {
  if ($(this).attr("data-color") == "default") {
    $("body").removeAttr("data-background-full");
  } else {
    $("body").attr("data-background-full", $(this).attr("data-color"));
  }
  $(this).parent().find(".changeBodyBackgroundFullColor").removeClass("selected");
  $(this).addClass("selected");
  layoutsColors();
  getCheckmark();
  saveThemeSettings();
});

// Background color change
$(".changeBackgroundColor").on("click", function() {
  if ($(this).attr("data-color") == "default") {
    $("body").removeAttr("data-background-color");
  } else {
    $("body").attr("data-background-color", $(this).attr("data-color"));
  }
  $(this).parent().find(".changeBackgroundColor").removeClass("selected");
  $(this).addClass("selected");
  getCheckmark();
  saveThemeSettings();
});

// Custom sidebar toggle functionality
var toggle_customSidebar = false,
  custom_open = 0;

if (!toggle_customSidebar) {
  var toggle = $(".custom-template .custom-toggle");

  toggle.on("click", function() {
    if (custom_open == 1) {
      $(".custom-template").removeClass("open");
      toggle.removeClass("toggled");
      custom_open = 0;
    } else {
      $(".custom-template").addClass("open");
      toggle.addClass("toggled");
      custom_open = 1;
    }
  });
  toggle_customSidebar = true;
}