</main>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Sidebar toggle functionality
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const adminContainer = document.querySelector('.admin-container');
        
        sidebarToggle.addEventListener('click', function() {
            adminContainer.classList.toggle('sidebar-collapsed');
        });

        // User dropdown functionality
        const adminUser = document.querySelector('.admin-user');
        adminUser.addEventListener('click', function() {
            this.classList.toggle('active');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            if (!adminUser.contains(event.target)) {
                adminUser.classList.remove('active');
            }
        });

        // Responsive sidebar behavior
        function handleResize() {
            if (window.innerWidth <= 768) {
                adminContainer.classList.add('sidebar-collapsed');
            } else {
                adminContainer.classList.remove('sidebar-collapsed');
            }
        }

        window.addEventListener('resize', handleResize);
        handleResize(); // Initial check
    });
    </script>
</body>
</html>