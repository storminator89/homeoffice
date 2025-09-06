</div>
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize sidenav
            var elems = document.querySelectorAll('.sidenav');
            M.Sidenav.init(elems, {
                edge: 'left',
                draggable: true
            });

            // Initialize collapsibles
            var collapsibles = document.querySelectorAll('.collapsible');
            M.Collapsible.init(collapsibles);

            // Initialize tooltips if they exist
            var tooltips = document.querySelectorAll('.tooltipped');
            if (tooltips.length > 0) {
                M.Tooltip.init(tooltips);
            }

            // Initialize tabs if they exist
            var tabs = document.querySelectorAll('.tabs');
            if (tabs.length > 0) {
                M.Tabs.init(tabs);
            }
        });

        // Theme toggling functionality
        function toggleTheme() {
            const body = document.body;
            const currentTheme = body.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            body.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
        }

        // Apply saved theme on load
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.body.setAttribute('data-theme', savedTheme);

       
    </script>
</body>
</html>