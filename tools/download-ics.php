<?php

// Kept only so old cached forms fail safely. New forms use admin-post.php.
http_response_code(410);
header('Content-Type: text/plain; charset=utf-8');
header('X-Content-Type-Options: nosniff');
echo 'This endpoint has moved. Reload the page and try again.';
exit;
