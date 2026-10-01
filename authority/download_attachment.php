<?php

session_start();

if (
    !isset($_SESSION["role_name"]) ||
    $_SESSION["role_name"] !== "AUTHORITY"
) {
    http_response_code(403);
    exit("Access denied.");
}

require_once "../config/database.php";


/* =========================
   ATTACHMENT ID
========================= */

$id = $_GET["id"] ?? "";

if (!is_numeric($id) || (int)$id <= 0) {
    http_response_code(400);
    exit("Invalid attachment.");
}

$id = (int)$id;


/* =========================
   GET ATTACHMENT
========================= */

$result = pg_query_params(
    $conn,
    "SELECT
        attachment_id,
        module_type,
        reference_id,
        file_name,
        file_type,
        file_size,
        file_data
     FROM attachments
     WHERE attachment_id = $1
     AND module_type = 'APPLICATION'
     LIMIT 1",
    [$id]
);

if (
    !$result ||
    pg_num_rows($result) === 0
) {
    http_response_code(404);
    exit("Attachment not found.");
}


$file = pg_fetch_assoc($result);


/* =========================
   CHECK FILE DATA
========================= */

if (
    !isset($file["file_data"]) ||
    $file["file_data"] === ""
) {
    http_response_code(404);
    exit("Attachment data not found.");
}


/* =========================
   PREPARE FILE
========================= */

$file_data = pg_unescape_bytea(
    $file["file_data"]
);


/* =========================
   HEADERS
========================= */

$file_type = $file["file_type"] ?: "application/octet-stream";

$file_name = basename(
    $file["file_name"]
);

header(
    "Content-Type: " . $file_type
);

header(
    "Content-Length: " . strlen($file_data)
);

header(
    'Content-Disposition: inline; filename="' .
        str_replace('"', '', $file_name) .
        '"'
);

header(
    "X-Content-Type-Options: nosniff"
);


/* =========================
   OUTPUT FILE
========================= */

echo $file_data;

exit;
