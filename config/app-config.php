<?php
// app-config.php - Global configuration constants for Bus Pass System

define('MAX_ROUTE_DISTANCE', 50.0); // Maximum travel distance in km for standard routing validation
define('ALLOWED_DOC_EXTENSIONS', ['png', 'jpg', 'jpeg', 'pdf']); // Allowed document formats
define('MAX_DOC_SIZE_BYTES', 2097152); // Max document size (2MB)
define('SESSION_TIMEOUT_SECONDS', 900); // Session timeout limit (15 minutes)
define('ROUTING_DEPARTMENTS', ['Transport Dept', 'Academic HOD', 'Admin Exception']); // Available routing queues
define('PASS_DEFAULT_VALIDITY_MONTHS', 6); // Default pass duration
?>
