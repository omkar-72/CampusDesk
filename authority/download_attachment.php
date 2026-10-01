<?php
/*
|--------------------------------------------------------------------------
| CAMPUSDESK - VIEW / DOWNLOAD APPLICATION ATTACHMENT
|--------------------------------------------------------------------------
| This file:
| - Allows only authenticated AUTHORITY users.
| - Fetches an application attachment from PostgreSQL.
| - Reads the BYTEA file data safely.
| - Sends the file directly to the browser.
| - Uses inline disposition so supported files can be viewed.
|--------------------------------------------------------------------------
*/


/* =========================================================
   START SESSION
   ========================================================= */

session_start();


/* =========================================================
   AUTHORITY ACCESS CHECK
   ========================================================= */

if (
    !isset($_SESSION["role_name"]) ||
    $_SESSION["role_name"] !== "AUTHORITY"
) {
    http_response_code(403);
    exit("Access denied.");
}


/* =========================================================
   DATABASE CONNECTION
   ========================================================= */

require_once "../config/database.php";


/* =========================================================
   GET ATTACHMENT ID
   ========================================================= */

$id = $_GET["id"] ?? "";


/*
 * Make sure the ID is a valid positive integer.
 */

if (
    !is_numeric($id) ||
    (int)$id <= 0
) {
    http_response_code(400);
    exit("Invalid attachment.");
}

$id = (int)$id;


/* =========================================================
   FETCH ATTACHMENT
   ========================================================= */

/*
 * Only APPLICATION attachments are allowed here.
 *
 * This matches the structure used by view_application.php:
 *
 * module_type = APPLICATION
 * reference_id = application_id
 */

$result = pg_query_params(
    $conn,
    "
    SELECT
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
    LIMIT 1
    ",
    [$id]
);


/*
 * Stop if the database query failed.
 */

if (!$result) {
    http_response_code(500);
    exit("Unable to retrieve attachment.");
}


/*
 * Stop if the attachment does not exist.
 */

if (pg_num_rows($result) === 0) {
    http_response_code(404);
    exit("Attachment not found.");
}


/*
 * Get the attachment record.
 */

$file = pg_fetch_assoc($result);


/* =========================================================
   CHECK FILE DATA
   ========================================================= */

/*
 * PostgreSQL BYTEA data may be returned as an escaped
 * string depending on the PostgreSQL configuration.
 *
 * We first check that file_data exists.
 */

if (
    !isset($file["file_data"]) ||
    $file["file_data"] === null ||
    $file["file_data"] === ""
) {
    http_response_code(404);
    exit("Attachment data not found.");
}


/* =========================================================
   CONVERT POSTGRESQL BYTEA DATA
   ========================================================= */

/*
 * pg_unescape_bytea() converts PostgreSQL BYTEA escaped
 * data into the original binary file data.
 */

$file_data = pg_unescape_bytea(
    $file["file_data"]
);


/*
 * Make sure conversion actually produced file data.
 */

if (
    $file_data === false ||
    $file_data === ""
) {
    http_response_code(404);
    exit("Unable to read attachment data.");
}


/* =========================================================
   PREPARE FILE INFORMATION
   ========================================================= */

/*
 * Use the MIME type stored in the database.
 *
 * If it is missing, use a generic binary type.
 */

$file_type = trim(
    $file["file_type"] ?? ""
);

if ($file_type === "") {
    $file_type = "application/octet-stream";
}


/*
 * Use only the base filename.
 *
 * This prevents a stored path from being used in the
 * Content-Disposition header.
 */

$file_name = basename(
    $file["file_name"] ?? "attachment"
);


/*
 * Remove double quotes from the filename because the
 * filename is placed inside a quoted HTTP header value.
 */

$file_name = str_replace(
    '"',
    "",
    $file_name
);


/*
 * Make sure a usable filename exists.
 */

if ($file_name === "") {
    $file_name = "attachment";
}


/* =========================================================
   CLEAR OUTPUT BUFFER
   ========================================================= */

/*
 * Any unexpected whitespace or output generated before
 * the file headers can corrupt PDF/image output.
 *
 * Clear all active output buffers before sending the file.
 */

while (ob_get_level() > 0) {
    ob_end_clean();
}


/* =========================================================
   SEND FILE HEADERS
   ========================================================= */

/*
 * Tell the browser what type of file is being returned.
 */

header(
    "Content-Type: " . $file_type
);


/*
 * Tell the browser the exact size of the binary data.
 */

header(
    "Content-Length: " . strlen($file_data)
);


/*
 * "inline" tells the browser to try to display the file
 * instead of forcing an immediate download.
 *
 * PDFs and images can normally open directly in the browser.
 */

header(
    'Content-Disposition: inline; filename="' .
        $file_name .
        '"'
);


/*
 * Prevent browsers from MIME-sniffing the response.
 */

header(
    "X-Content-Type-Options: nosniff"
);


/*
 * Allow the browser to cache the attachment during this
 * viewing session.
 */

header(
    "Cache-Control: private, max-age=3600"
);


/* =========================================================
   OUTPUT FILE
   ========================================================= */

/*
 * Send the original binary file data to the browser.
 */

echo $file_data;


/*
 * Stop PHP execution immediately after the file.
 */

exit;
