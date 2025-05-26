<script>
  // Initialize sidebar state on page load
  document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    
    if (isCollapsed) {
      sidebar.classList.add('collapsed');
      document.getElementById('logo-text').innerText = 'P';
    }
  });

  function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const isCollapsed = !sidebar.classList.contains('collapsed');
    sidebar.classList.toggle('collapsed');
    document.getElementById('logo-text').innerText = isCollapsed ? 'P' : 'POS';
    
    // Store the state in localStorage
    localStorage.setItem('sidebarCollapsed', isCollapsed);
    
    if (isCollapsed) {
      document.querySelectorAll('.submenu').forEach(menu => menu.classList.remove('show'));
      document.querySelectorAll('.has-submenu').forEach(item => item.classList.remove('expanded'));
    }
  }

  document.querySelectorAll('.has-submenu').forEach(item => {
    item.addEventListener('click', function(e) {
      const sidebar = document.getElementById('sidebar');
      
      if (sidebar.classList.contains('collapsed')) {
        e.preventDefault();
        const menu = this.nextElementSibling;
        
        document.querySelectorAll('.submenu').forEach(m => m !== menu && m.classList.remove('show'));
        document.querySelectorAll('.has-submenu').forEach(i => i !== this && i.classList.remove('expanded'));
        
        menu.classList.toggle('show');
        this.classList.toggle('expanded');
        
        setTimeout(() => {
          const clickHandler = (event) => {
            if (!menu.contains(event.target) && event.target !== this) {
              menu.classList.remove('show');
              this.classList.remove('expanded');
              document.removeEventListener('click', clickHandler);
            }
          };
          document.addEventListener('click', clickHandler);
        }, 10);
      } else {
        // Default behavior for expanded sidebar
        this.classList.toggle("expanded");
        this.nextElementSibling.classList.toggle("show");
      }
    });
  });

  document.querySelectorAll('.nav-link:not(.has-submenu)').forEach(link => {
    link.addEventListener('click', function() {
      const sidebar = document.getElementById('sidebar');
      if (sidebar.classList.contains('collapsed')) {
        document.querySelectorAll('.submenu').forEach(menu => menu.classList.remove('show'));
        document.querySelectorAll('.has-submenu').forEach(item => item.classList.remove('expanded'));
      }
      // Allow normal navigation to proceed
    });
  });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>