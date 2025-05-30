            </div> <!-- /.container-fluid -->
        </div> <!-- /#content -->
    </div> <!-- /.wrapper -->

    <!-- Custom JavaScript -->
    <script src="assets/js/script.js"></script>
    
    <script>
    // Toggle sidebar
    $(document).ready(function () {
        $('#sidebarCollapse').on('click', function () {
            $('.sidebar').toggleClass('active');
            $('#content').toggleClass('active');
        });
    });
    </script>
    <?php
    // Clean and flush the output buffer
    ob_end_flush();
    ?>
</body>
</html>
