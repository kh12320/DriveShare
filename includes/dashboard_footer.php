</main><!-- dashboard-main -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('dashSidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const main = document.getElementById('dashMain');
        sidebar.classList.toggle('sidebar-open');
        overlay.classList.toggle('active');
    }

    async function logout() {
        try {
            const res = await fetch('/api/auth.php?action=logout', { method: 'POST' });
            const data = await res.json();
            window.location.href = data.redirect || '/index.php';
        } catch (e) {
            window.location.href = '/index.php';
        }
    }
</script>
<?= $extraScript ?? '' ?>
</body>

</html>