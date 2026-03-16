<footer class="footer">
    <div class="container-fluid">
        <nav class="pull-left">
            <ul class="nav">
                <li class="nav-item">
                    <a class="nav-link" href="#">SYi - Tech Global Services</a>
                </li>
            </ul>
        </nav>
        <div class="copyright ml-auto">
            <?php echo date('Y'); ?>, made with <i class="fa fa-heart heart text-danger"></i> by <a href="#">SYi-Tech</a>
        </div>
    </div>
</footer>
<script src="assets/js/jquery.3.2.1.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/atlantis.min.js"></script>
<script>
    function get_badge_class(status) {
        switch (status.toLowerCase()) {
            case 'completed':
                return 'success';
            case 'processing':
                return 'info';
            case 'pending':
                return 'warning';
            case 'cancelled':
                return 'danger';
            default:
                return 'secondary';
        }
    }
</script>