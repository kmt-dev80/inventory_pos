(function($) {
    // Initialize variables
    var toggle_sidebar = false,
        toggle_quick_sidebar = false,
        toggle_topbar = false,
        minimize_sidebar = false,
        first_toggle_sidebar = false,
        toggle_page_sidebar = false,
        toggle_overlay_sidebar = false,
        nav_open = 0,
        quick_sidebar_open = 0,
        topbar_open = 0,
        mini_sidebar = 0,
        page_sidebar_open = 0,
        overlay_sidebar_open = 0;

    // Copy logo header content
    var logoHeaderContent = $('.sidebar .logo-header').html();
    $('.main-header .logo-header').html(logoHeaderContent);

    // Search input focus effect
    $(".nav-search .input-group > input").on({
        focus: function() {
            $(this).parents().eq(2).addClass("focus");
        },
        blur: function() {
            $(this).parents().eq(2).removeClass("focus");
        }
    });

    // Initialize tooltips and popovers
    $(function() {
        // Tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Popovers
        const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.map(function(popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });

        layoutsColors();
        customBackgroundColor();
    });

    // Layout colors function
    function layoutsColors() {
        if ($('.sidebar').is('[data-background-color]')) {
            $('html').addClass('sidebar-color');
        } else {
            $('html').removeClass('sidebar-color');
        }
    }

    // Custom background function
    function customBackgroundColor() {
        $('*[data-background-color="custom"]').each(function() {
            if ($(this).is('[custom-color]')) {
                $(this).css('background', $(this).attr('custom-color'));
            } else if ($(this).is('[custom-background]')) {
                $(this).css('background-image', 'url(' + $(this).attr('custom-background') + ')');
            }
        });
    }

    // Chart legend click callback
    function legendClickCallback(event) {
        event = event || window.event;
        var target = event.target || event.srcElement;
        
        while (target.nodeName !== 'LI') {
            target = target.parentElement;
        }
        
        var parent = target.parentElement;
        var chartId = parseInt(parent.classList[0].split("-")[0], 10);
        var chart = Chart.instances[chartId];
        var index = [].slice.call(parent.children).indexOf(target);

        chart.legend.options.onClick.call(chart, event, chart.legend.legendItems[index]);
        if (chart.isDatasetVisible(index)) {
            target.classList.remove('hidden');
        } else {
            target.classList.add('hidden');
        }
    }

    // Document ready handler
    $(document).ready(function() {
        // Refresh card button
        $('.btn-refresh-card').on('click', function() {
            var card = $(this).parents(".card");
            if (card.length) {
                card.addClass("is-loading");
                setTimeout(function() {
                    card.removeClass("is-loading");
                }, 3000);
            }
        });

        // Initialize scrollbars
        initializeScrollbars();

        // Sidebar toggle functionality
        setupSidebarToggle();

        // Quick sidebar toggle functionality
        setupQuickSidebarToggle();

        // Topbar toggle functionality
        setupTopbarToggle();

        // Minimize sidebar functionality
        setupMinimizeSidebar();

        // Page sidebar toggle functionality
        setupPageSidebarToggle();

        // Overlay sidebar toggle functionality
        setupOverlaySidebarToggle();

        // Sidebar hover effects
        setupSidebarHover();

        // Nav item click handling
        $(".nav-item a").on('click', function() {
            $(this).parent().toggleClass('submenu', $(this).parent().find('.collapse').hasClass("show"));
        });

        // Chat functionality
        setupChat();

        // Checkbox select all functionality
        $('[data-select="checkbox"]').change(function() {
            var target = $(this).attr('data-target');
            $(target).prop('checked', $(this).prop("checked"));
        });

        // Form group focus effects
        $(".form-group-default .form-control").on({
            focus: function() {
                $(this).parent().addClass("active");
            },
            blur: function() {
                $(this).parent().removeClass("active");
            }
        });

        // Input file image preview
        setupFileInputPreview();

        // Show password toggle
        $('.show-password').on('click', function() {
            var inputPassword = $(this).parent().find('input');
            inputPassword.attr('type', inputPassword.attr('type') === "password" ? 'text' : 'password');
        });

        // Sign in/up container switching
        setupAuthContainers();

        // Floating label effect
        $('.form-floating-label .form-control').keyup(function() {
            $(this).toggleClass('filled', $(this).val() !== '');
        });
    });

    // Helper functions
    function initializeScrollbars() {
        var scrollbars = [
            '.sidebar .scrollbar',
            '.main-panel .content-scroll',
            '.messages-scroll',
            '.tasks-scroll',
            '.quick-scroll',
            '.message-notif-scroll',
            '.notif-scroll',
            '.quick-actions-scroll',
            '.dropdown-user-scroll'
        ];

        scrollbars.forEach(function(selector) {
            if ($(selector).length > 0) {
                $(selector).scrollbar();
            }
        });
    }

    function setupSidebarToggle() {
        if (!toggle_sidebar) {
            var toggle = $('.sidenav-toggler');
            toggle.on('click', function() {
                $('html').toggleClass('nav_open', nav_open !== 1);
                toggle.toggleClass('toggled', nav_open !== 1);
                nav_open = nav_open === 1 ? 0 : 1;
            });
            toggle_sidebar = true;
        }
    }

    function setupQuickSidebarToggle() {
        if (!quick_sidebar_open) {
            var toggle = $('.quick-sidebar-toggler');
            toggle.on('click', function() {
                $('html').toggleClass('quick_sidebar_open', quick_sidebar_open !== 1);
                toggle.toggleClass('toggled', quick_sidebar_open !== 1);
                
                if (quick_sidebar_open !== 1) {
                    $('<div class="quick-sidebar-overlay"></div>').insertAfter('.quick-sidebar');
                } else {
                    $('.quick-sidebar-overlay').remove();
                }
                
                quick_sidebar_open = quick_sidebar_open === 1 ? 0 : 1;
            });

            $('.wrapper').on('mouseup', function(e) {
                var subject = $('.quick-sidebar');
                if (!subject.is(e.target) && subject.has(e.target).length === 0) {
                    $('html').removeClass('quick_sidebar_open');
                    toggle.removeClass('toggled');
                    $('.quick-sidebar-overlay').remove();
                    quick_sidebar_open = 0;
                }
            });

            $(".close-quick-sidebar").on('click', function() {
                $('html').removeClass('quick_sidebar_open');
                toggle.removeClass('toggled');
                $('.quick-sidebar-overlay').remove();
                quick_sidebar_open = 0;
            });
        }
    }

    function setupTopbarToggle() {
        if (!toggle_topbar) {
            var topbar = $('.topbar-toggler');
            topbar.on('click', function() {
                $('html').toggleClass('topbar_open', topbar_open !== 1);
                topbar.toggleClass('toggled', topbar_open !== 1);
                topbar_open = topbar_open === 1 ? 0 : 1;
            });
            toggle_topbar = true;
        }
    }

    function setupMinimizeSidebar() {
        if (!minimize_sidebar) {
            var minibutton = $('.toggle-sidebar');
            
            if ($('.wrapper').hasClass('sidebar_minimize')) {
                minibutton.addClass('toggled');
                minibutton.html('<i class="gg-more-vertical-alt"></i>');
                mini_sidebar = 1;
            }

            minibutton.on('click', function() {
                $('.wrapper').toggleClass('sidebar_minimize', mini_sidebar !== 1);
                minibutton.toggleClass('toggled', mini_sidebar !== 1);
                minibutton.html(mini_sidebar === 1 ? '<i class="gg-menu-right"></i>' : '<i class="gg-more-vertical-alt"></i>');
                mini_sidebar = mini_sidebar === 1 ? 0 : 1;
                $(window).trigger('resize');
            });
            
            minimize_sidebar = true;
            first_toggle_sidebar = true;
        }
    }

    function setupPageSidebarToggle() {
        if (!toggle_page_sidebar) {
            var pageSidebarToggler = $('.page-sidebar-toggler');
            pageSidebarToggler.on('click', function() {
                $('html').toggleClass('pagesidebar_open', page_sidebar_open !== 1);
                pageSidebarToggler.toggleClass('toggled', page_sidebar_open !== 1);
                page_sidebar_open = page_sidebar_open === 1 ? 0 : 1;
            });

            $('.page-sidebar .back').on('click', function() {
                $('html').removeClass('pagesidebar_open');
                pageSidebarToggler.removeClass('toggled');
                page_sidebar_open = 0;
            });
            
            toggle_page_sidebar = true;
        }
    }

    function setupOverlaySidebarToggle() {
        if (!toggle_overlay_sidebar) {
            var overlaybutton = $('.sidenav-overlay-toggler');
            
            if ($('.wrapper').hasClass('is-show')) {
                overlay_sidebar_open = 1;
                overlaybutton.addClass('toggled');
                overlaybutton.html('<i class="icon-options-vertical"></i>');
            }

            overlaybutton.on('click', function() {
                $('.wrapper').toggleClass('is-show', overlay_sidebar_open !== 1);
                overlaybutton.toggleClass('toggled', overlay_sidebar_open !== 1);
                overlaybutton.html(overlay_sidebar_open === 1 ? '<i class="icon-menu"></i>' : '<i class="icon-options-vertical"></i>');
                overlay_sidebar_open = overlay_sidebar_open === 1 ? 0 : 1;
                $(window).trigger('resize');
            });
        }
    }

    function setupSidebarHover() {
        $('.sidebar').on({
            mouseenter: function() {
                if (mini_sidebar === 1 && !first_toggle_sidebar) {
                    $('.wrapper').addClass('sidebar_minimize_hover');
                    first_toggle_sidebar = true;
                }
            },
            mouseleave: function() {
                if (mini_sidebar === 1 && first_toggle_sidebar) {
                    $('.wrapper').removeClass('sidebar_minimize_hover');
                    first_toggle_sidebar = false;
                }
            }
        });
    }

    function setupChat() {
        $('.messages-contact .user a').on('click', function() {
            $('.tab-chat').addClass('show-chat');
        });

        $('.messages-wrapper .return').on('click', function() {
            $('.tab-chat').removeClass('show-chat');
        });
    }

    function setupFileInputPreview() {
        function readURL(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $(input).parent('.input-file-image').find('.img-upload-preview').attr('src', e.target.result);
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        $('.input-file-image input[type="file"]').change(function() {
            readURL(this);
        });
    }

    function setupAuthContainers() {
        var containerSignIn = $('.container-login'),
            containerSignUp = $('.container-signup'),
            showSignIn = true,
            showSignUp = false;

        function changeContainer() {
            containerSignIn.toggle(showSignIn);
            containerSignUp.toggle(showSignUp);
        }

        $('#show-signup').on('click', function() {
            showSignUp = true;
            showSignIn = false;
            changeContainer();
        });

        $('#show-signin').on('click', function() {
            showSignUp = false;
            showSignIn = true;
            changeContainer();
        });

        changeContainer();
    }
})(jQuery);