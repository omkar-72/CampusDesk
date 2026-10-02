<?php

session_start();

require_once "../config/database.php";
require_once "../includes/functions.php";
require_once "../includes/audit.php";
require_once "../admin/admin_auth.php";

requireAdmin();


/* =========================================================
   HELPER FUNCTIONS
========================================================= */

function redirectToCategories($type)
{
    $allowedTypes = [
        "GRIEVANCE",
        "SUGGESTION",
        "APPLICATION"
    ];

    if (!in_array($type, $allowedTypes, true)) {
        $type = "GRIEVANCE";
    }

    header(
        "Location: ../admin/categories.php?tab=" .
            urlencode($type)
    );

    exit();
}


function setCategoryMessage($type, $message)
{
    $_SESSION["admin_categories_message"] = [
        "type" => $type,
        "message" => $message
    ];
}


/* =========================================================
   REQUEST METHOD
========================================================= */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    redirectToCategories("GRIEVANCE");
}


/* =========================================================
   GET BASIC INPUT
========================================================= */

$action = isset($_POST["action"])
    ? strtoupper(trim($_POST["action"]))
    : "";

$type = isset($_POST["type"])
    ? strtoupper(trim($_POST["type"]))
    : "";

$id = isset($_POST["id"])
    ? trim($_POST["id"])
    : "";

$name = isset($_POST["name"])
    ? trim($_POST["name"])
    : "";

$status = isset($_POST["status"])
    ? trim($_POST["status"])
    : "";


/* =========================================================
   VALIDATE TYPE
========================================================= */

$allowedTypes = [
    "GRIEVANCE",
    "SUGGESTION",
    "APPLICATION"
];

if (!in_array($type, $allowedTypes, true)) {

    setCategoryMessage(
        "error",
        "Invalid category type."
    );

    redirectToCategories("GRIEVANCE");
}


/* =========================================================
   VALIDATE ACTION
========================================================= */

$allowedActions = [
    "ADD",
    "EDIT",
    "ACTIVATE",
    "DEACTIVATE"
];

if (!in_array($action, $allowedActions, true)) {

    setCategoryMessage(
        "error",
        "Invalid category action."
    );

    redirectToCategories($type);
}


/* =========================================================
   GET TABLE INFORMATION
========================================================= */

$tableName = "";
$idColumn = "";
$nameColumn = "";
$moduleType = "";
$recordLabel = "";

switch ($type) {

    case "GRIEVANCE":

        $tableName = "grievance_categories";
        $idColumn = "category_id";
        $nameColumn = "category_name";
        $moduleType = "GRIEVANCE_CATEGORY";
        $recordLabel = "grievance category";

        break;


    case "SUGGESTION":

        $tableName = "suggestion_categories";
        $idColumn = "category_id";
        $nameColumn = "category_name";
        $moduleType = "SUGGESTION_CATEGORY";
        $recordLabel = "suggestion category";

        break;


    case "APPLICATION":

        $tableName = "application_types";
        $idColumn = "application_type_id";
        $nameColumn = "type_name";
        $moduleType = "APPLICATION_TYPE";
        $recordLabel = "application type";

        break;
}


/* =========================================================
   LOGGED-IN ADMIN
========================================================= */

$adminUserId = getAdminUserId();

if (!$adminUserId) {

    setCategoryMessage(
        "error",
        "Admin session could not be verified."
    );

    redirectToCategories($type);
}


/* =========================================================
   ADD
========================================================= */

if ($action === "ADD") {

    /* -----------------------------------------------------
       Validate name
    ----------------------------------------------------- */

    if ($name === "") {

        setCategoryMessage(
            "error",
            "Please enter a name."
        );

        redirectToCategories($type);
    }


    if (strlen($name) > 100) {

        setCategoryMessage(
            "error",
            "Name must not exceed 100 characters."
        );

        redirectToCategories($type);
    }


    /* -----------------------------------------------------
       Validate status
    ----------------------------------------------------- */

    if ($status !== "0" && $status !== "1") {
        $status = "1";
    }


    /* -----------------------------------------------------
       Check duplicate
    ----------------------------------------------------- */

    $duplicateQuery = "
        SELECT {$idColumn}
        FROM {$tableName}
        WHERE LOWER(TRIM({$nameColumn})) =
              LOWER(TRIM($1))
        LIMIT 1
    ";

    $duplicateResult = pg_query_params(
        $conn,
        $duplicateQuery,
        [$name]
    );


    if (!$duplicateResult) {

        setCategoryMessage(
            "error",
            "Unable to check existing records."
        );

        redirectToCategories($type);
    }


    if (pg_num_rows($duplicateResult) > 0) {

        setCategoryMessage(
            "error",
            "A {$recordLabel} with this name already exists."
        );

        redirectToCategories($type);
    }


    /* -----------------------------------------------------
       Begin transaction
    ----------------------------------------------------- */

    pg_query($conn, "BEGIN");

    try {

        $insertQuery = "
            INSERT INTO {$tableName}
            (
                {$nameColumn},
                status
            )
            VALUES
            (
                $1,
                $2
            )
            RETURNING {$idColumn}
        ";

        $insertResult = pg_query_params(
            $conn,
            $insertQuery,
            [
                $name,
                $status === "1"
            ]
        );


        /* -------------------------------------------------
           Corrected: check INSERT result
        ------------------------------------------------- */

        if (!$insertResult) {

            throw new Exception(
                "Failed to create the record: " . pg_last_error($conn)
            );
        }


        $newRecord = pg_fetch_assoc($insertResult);

        $newId = (int) $newRecord[$idColumn];


        /* -------------------------------------------------
           Audit Log
        ------------------------------------------------- */

        $auditResult = addAuditLog(
            $conn,
            $adminUserId,
            $moduleType,
            $newId,
            "CREATE_" . $type
        );


        if (!$auditResult) {

            throw new Exception(
                "Failed to create audit log: " . pg_last_error($conn)
            );
        }


        pg_query($conn, "COMMIT");


        setCategoryMessage(
            "success",
            ucfirst($recordLabel) .
                " added successfully."
        );
    } catch (Exception $e) {

        pg_query($conn, "ROLLBACK");

        setCategoryMessage(
            "error",
            $e->getMessage()
        );
    }


    redirectToCategories($type);
}


/* =========================================================
   EDIT
========================================================= */

if ($action === "EDIT") {

    /* -----------------------------------------------------
       Validate ID
    ----------------------------------------------------- */

    if (!isValidId($id)) {

        setCategoryMessage(
            "error",
            "Invalid record ID."
        );

        redirectToCategories($type);
    }

    $recordId = (int) $id;


    /* -----------------------------------------------------
       Validate name
    ----------------------------------------------------- */

    if ($name === "") {

        setCategoryMessage(
            "error",
            "Please enter a name."
        );

        redirectToCategories($type);
    }


    if (strlen($name) > 100) {

        setCategoryMessage(
            "error",
            "Name must not exceed 100 characters."
        );

        redirectToCategories($type);
    }


    /* -----------------------------------------------------
       Validate status
    ----------------------------------------------------- */

    if ($status !== "0" && $status !== "1") {

        setCategoryMessage(
            "error",
            "Invalid status value."
        );

        redirectToCategories($type);
    }


    /* -----------------------------------------------------
       Check record exists
    ----------------------------------------------------- */

    $existingQuery = "
        SELECT
            {$idColumn},
            {$nameColumn},
            status
        FROM {$tableName}
        WHERE {$idColumn} = $1
        LIMIT 1
    ";

    $existingResult = pg_query_params(
        $conn,
        $existingQuery,
        [$recordId]
    );


    if (!$existingResult) {

        setCategoryMessage(
            "error",
            "Unable to find the selected record."
        );

        redirectToCategories($type);
    }


    if (pg_num_rows($existingResult) === 0) {

        setCategoryMessage(
            "error",
            "The selected record does not exist."
        );

        redirectToCategories($type);
    }


    /* -----------------------------------------------------
       Check duplicate name
    ----------------------------------------------------- */

    $duplicateQuery = "
        SELECT {$idColumn}
        FROM {$tableName}
        WHERE LOWER(TRIM({$nameColumn})) =
              LOWER(TRIM($1))
          AND {$idColumn} <> $2
        LIMIT 1
    ";

    $duplicateResult = pg_query_params(
        $conn,
        $duplicateQuery,
        [
            $name,
            $recordId
        ]
    );


    if (!$duplicateResult) {

        setCategoryMessage(
            "error",
            "Unable to check duplicate records."
        );

        redirectToCategories($type);
    }


    if (pg_num_rows($duplicateResult) > 0) {

        setCategoryMessage(
            "error",
            "Another {$recordLabel} with this name already exists."
        );

        redirectToCategories($type);
    }


    /* -----------------------------------------------------
       Begin transaction
    ----------------------------------------------------- */

    pg_query($conn, "BEGIN");

    try {

        $updateQuery = "
            UPDATE {$tableName}
            SET
                {$nameColumn} = $1,
                status = $2
            WHERE {$idColumn} = $3
        ";

        $updateResult = pg_query_params(
            $conn,
            $updateQuery,
            [
                $name,
                ($status === "1") ? "t" : "f",
                $recordId
            ]
        );


        if (!$updateResult) {

            throw new Exception(
                "Failed to update the record: " . pg_last_error($conn)
            );
        }


        /* -------------------------------------------------
           Audit Log
        ------------------------------------------------- */

        $auditResult = addAuditLog(
            $conn,
            $adminUserId,
            $moduleType,
            $recordId,
            "UPDATE_" . $type
        );


        if (!$auditResult) {

            throw new Exception(
                "Failed to create audit log: " . pg_last_error($conn)
            );
        }


        pg_query($conn, "COMMIT");


        setCategoryMessage(
            "success",
            ucfirst($recordLabel) .
                " updated successfully."
        );
    } catch (Exception $e) {

        pg_query($conn, "ROLLBACK");

        setCategoryMessage(
            "error",
            $e->getMessage()
        );
    }


    redirectToCategories($type);
}


/* =========================================================
   ACTIVATE / DEACTIVATE
========================================================= */

if (
    $action === "ACTIVATE" ||
    $action === "DEACTIVATE"
) {

    /* -----------------------------------------------------
       Validate ID
    ----------------------------------------------------- */

    if (!isValidId($id)) {

        setCategoryMessage(
            "error",
            "Invalid record ID."
        );

        redirectToCategories($type);
    }

    $recordId = (int) $id;


    /* -----------------------------------------------------
       Determine new status
    ----------------------------------------------------- */

    $newStatus =
        ($action === "ACTIVATE") ? "t" : "f";


    /* -----------------------------------------------------
       Check record exists
    ----------------------------------------------------- */

    $existingQuery = "
        SELECT
            {$idColumn},
            {$nameColumn},
            status
        FROM {$tableName}
        WHERE {$idColumn} = $1
        LIMIT 1
    ";

    $existingResult = pg_query_params(
        $conn,
        $existingQuery,
        [$recordId]
    );


    if (!$existingResult) {

        setCategoryMessage(
            "error",
            "Unable to find the selected record."
        );

        redirectToCategories($type);
    }


    if (pg_num_rows($existingResult) === 0) {

        setCategoryMessage(
            "error",
            "The selected record does not exist."
        );

        redirectToCategories($type);
    }


    $existingRecord =
        pg_fetch_assoc($existingResult);


    $currentStatus =
        (
            $existingRecord["status"] === "t" ||
            $existingRecord["status"] === "1"
        );


    /* -----------------------------------------------------
       Prevent unnecessary update
    ----------------------------------------------------- */

    if ($currentStatus === $newStatus) {

        setCategoryMessage(
            "success",
            "The {$recordLabel} is already " .
                ($newStatus ? "active." : "inactive.")
        );

        redirectToCategories($type);
    }


    /* -----------------------------------------------------
       Begin transaction
    ----------------------------------------------------- */

    pg_query($conn, "BEGIN");

    try {

        $updateQuery = "
            UPDATE {$tableName}
            SET status = $1
            WHERE {$idColumn} = $2
        ";

        $updateResult = pg_query_params(
            $conn,
            $updateQuery,
            [
                $newStatus,
                $recordId
            ]
        );


        /* -------------------------------------------------
           Corrected: show actual PostgreSQL error
        ------------------------------------------------- */

        if (!$updateResult) {

            throw new Exception(
                "Failed to update the status: " . pg_last_error($conn)
            );
        }


        /* -------------------------------------------------
           Audit Log
        ------------------------------------------------- */

        $auditAction =
            $newStatus
            ? "ACTIVATE_" . $type
            : "DEACTIVATE_" . $type;


        $auditResult = addAuditLog(
            $conn,
            $adminUserId,
            $moduleType,
            $recordId,
            $auditAction
        );


        if (!$auditResult) {

            throw new Exception(
                "Failed to create audit log: " . pg_last_error($conn)
            );
        }


        pg_query($conn, "COMMIT");


        setCategoryMessage(
            "success",
            ucfirst($recordLabel) .
                " " .
                ($newStatus ? "activated" : "deactivated") .
                " successfully."
        );
    } catch (Exception $e) {

        pg_query($conn, "ROLLBACK");

        setCategoryMessage(
            "error",
            $e->getMessage()
        );
    }


    redirectToCategories($type);
}


/* =========================================================
   FALLBACK
========================================================= */

setCategoryMessage(
    "error",
    "The requested action could not be completed."
);

redirectToCategories($type);
