/**
 * Bus Pass Management System - Main JavaScript
 */

$(document).ready(function() {
    
    // Sidebar Toggle for Mobile
    $('#sidebarToggle').on('click', function() {
        $('#sidebarNav').toggleClass('open');
        $('#sidebarOverlay').toggleClass('active');
    });

    $('#sidebarOverlay').on('click', function() {
        $('#sidebarNav').removeClass('open');
        $('#sidebarOverlay').removeClass('active');
    });

    // Close sidebar on nav link click (mobile)
    $('.sidebar .nav-link').on('click', function() {
        if ($(window).width() < 992) {
            $('#sidebarNav').removeClass('open');
            $('#sidebarOverlay').removeClass('active');
        }
    });

    // Initialize DataTables for tables with class 'datatable'
    if ($.fn.DataTable) {
        $('.datatable').DataTable({
            "pageLength": 10,
            "ordering": true,
            "responsive": true,
            "language": {
                "search": "Search:",
                "lengthMenu": "Show _MENU_ entries",
                "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                "infoEmpty": "No entries found",
                "infoFiltered": "(filtered from _MAX_ total entries)"
            }
        });
    }

    // Auto-hide flash messages after 5 seconds
    setTimeout(function() {
        $('.alert-dismissible').fadeOut('slow');
    }, 5000);

    // File upload preview
    $('input[type="file"]').on('change', function() {
        var file = this.files[0];
        if (file) {
            var reader = new FileReader();
            var previewId = $(this).data('preview');
            var fileNameSpan = $(this).closest('.upload-area').find('.file-name');
            
            reader.onload = function(e) {
                if (previewId && $('#' + previewId).length) {
                    $('#' + previewId).attr('src', e.target.result).show();
                }
            };
            
            reader.readAsDataURL(file);
            
            if (fileNameSpan.length) {
                fileNameSpan.text(file.name);
                fileNameSpan.removeClass('text-muted').addClass('text-success fw-bold');
            }
        }
    });

    // Upload area drag and drop
    $('.upload-area').on('dragover dragenter', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('dragover');
    });

    $('.upload-area').on('dragleave drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragover');
    });

    // Confirm delete actions with SweetAlert
    $(document).on('click', '.confirm-delete', function(e) {
        e.preventDefault();
        var link = $(this).attr('href');
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = link;
            }
        });
    });

    // Cancel application confirmation
    $(document).on('click', '.cancel-application', function(e) {
        e.preventDefault();
        var link = $(this).attr('href');
        Swal.fire({
            title: 'Cancel Application?',
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, cancel it'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = link;
            }
        });
    });

    // Form validation enhancement
    $('.needs-validation').on('submit', function(e) {
        if (!this.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        $(this).addClass('was-validated');
    });

    // Toggle password visibility
    $('.toggle-password').on('click', function() {
        var input = $($(this).data('target'));
        var icon = $(this).find('i');
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('bi-eye').addClass('bi-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('bi-eye-slash').addClass('bi-eye');
        }
    });

    // Notification mark as read
    $(document).on('click', '.notification-item', function() {
        var notifId = $(this).data('id');
        if (notifId && $(this).hasClass('unread')) {
            $.ajax({
                url: BASE_URL + '/ajax/mark_notification_read.php',
                type: 'POST',
                data: { id: notifId },
                success: function(response) {
                    // Update UI
                }
            });
            $(this).removeClass('unread');
        }
    });

    // Print pass
    $('.print-pass').on('click', function() {
        window.print();
    });

});

// Base URL for AJAX calls (set from PHP)
var BASE_URL = window.location.origin + '/Bus-pass-managemnet';

