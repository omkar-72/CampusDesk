<?php
/*
|--------------------------------------------------------------------------
| CampusDesk - Department Actions
|--------------------------------------------------------------------------
*/

require_once "../admin/admin_auth.php";
requireAdmin();

require_once "../config/database.php";
require_once "../includes/functions.php";
require_once "../includes/audit.php";


/* =========================================================
   ONLY POST REQUESTS
========================================================= */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: ../admin/departments.php");
    exit();
}


/* =========================================================
   ADMIN USER
========================================================= */

$adminUserId = getAdminUserId();

if (!$adminUserId) {

    header("Location: ../admin/admin_login.php");
    exit();
}


/* =========================================================
   ACTION
========================================================= */

$action = trim($_POST["action"] ?? "");


/* =========================================================
   MESSAGE HELPER
========================================================= */

function departmentActionMessage($type, $text)
{
    $_SESSION["admin_departments_message"] = [
        "type" => $type,
        "text" => $text
    ];

    header("Location: ../admin/departments.php");
    exit();
}


/* =========================================================
   CREATE DEPARTMENT
========================================================= */

if ($action === "create") {

    $departmentName =
        trim($_POST["department_name"] ?? "");

    $status =
        ($_POST["status"] ?? "1") === "1";


    /* Validation */

    if ($departmentName === "") {

        departmentActionMessage(
            "error",
            "Department name is required."
        );
    }

    if (strlen($departmentName) > 100) {

        departmentActionMessage(
            "error",
            "Department name cannot exceed 100 characters."
        );
    }


    /* Duplicate check */

    $duplicateResult = pg_query_params(
        $conn,
        "SELECT department_id
         FROM departments
         WHERE LOWER(department_name) = LOWER($1)
         LIMIT 1",
        [$departmentName]
    );

    if ($duplicateResult === false) {

        departmentActionMessage(
            "error",
            "Unable to validate the department name."
        );
    }

    if (pg_num_rows($duplicateResult) > 0) {

        departmentActionMessage(
            "error",
            "A department with this name already exists."
        );
    }


    /* Transaction */

    pg_query($conn, "BEGIN");


    $insertResult = pg_query_params(
        $conn,
        "INSERT INTO departments
        (
            department_name,
            status
        )
        VALUES
        (
            $1,
            $2
        )
        RETURNING department_id",
        [
            $departmentName,
            $status ? "true" : "false"
        ]
    );


    if ($insertResult === false) {

        pg_query($conn, "ROLLBACK");

        departmentActionMessage(
            "error",
            "Failed to create the department."
        );
    }


    $row =
        pg_fetch_assoc($insertResult);

    $departmentId =
        (int)$row["department_id"];


    /* Audit */

    if (!addAuditLog(
        $conn,
        $adminUserId,
        "DEPARTMENT",
        $departmentId,
        "CREATE_DEPARTMENT"
    )) {

        pg_query($conn, "ROLLBACK");

        departmentActionMessage(
            "error",
            "Department was not created because the audit log could not be recorded."
        );
    }


    if (!pg_query($conn, "COMMIT")) {

        pg_query($conn, "ROLLBACK");

        departmentActionMessage(
            "error",
            "Failed to save the department."
        );
    }


    departmentActionMessage(
        "success",
        "Department created successfully."
    );
}


/* =========================================================
   UPDATE DEPARTMENT
========================================================= */

if ($action === "update") {

    $departmentId =
        $_POST["department_id"] ?? "";

    $departmentName =
        trim($_POST["department_name"] ?? "");

    $status =
        ($_POST["status"] ?? "1") === "1";


    /* Validate ID */

    if (
        !is_numeric($departmentId) ||
        (int)$departmentId <= 0
    ) {

        departmentActionMessage(
            "error",
            "Invalid department."
        );
    }

    $departmentId =
        (int)$departmentId;


    /* Validate name */

    if ($departmentName === "") {

        departmentActionMessage(
            "error",
            "Department name is required."
        );
    }

    if (strlen($departmentName) > 100) {

        departmentActionMessage(
            "error",
            "Department name cannot exceed 100 characters."
        );
    }


    /* Check department exists */

    $existingResult = pg_query_params(
        $conn,
        "SELECT department_id
         FROM departments
         WHERE department_id = $1",
        [$departmentId]
    );

    if (
        $existingResult === false ||
        pg_num_rows($existingResult) === 0
    ) {

        departmentActionMessage(
            "error",
            "Department not found."
        );
    }


    /* Duplicate name */

    $duplicateResult = pg_query_params(
        $conn,
        "SELECT department_id
         FROM departments
         WHERE LOWER(department_name) = LOWER($1)
           AND department_id <> $2
         LIMIT 1",
        [
            $departmentName,
            $departmentId
        ]
    );

    if ($duplicateResult === false) {

        departmentActionMessage(
            "error",
            "Unable to validate the department name."
        );
    }

    if (pg_num_rows($duplicateResult) > 0) {

        departmentActionMessage(
            "error",
            "Another department with this name already exists."
        );
    }


    /* Transaction */

    pg_query($conn, "BEGIN");


    $updateResult = pg_query_params(
        $conn,
        "UPDATE departments
         SET
            department_name = $1,
            status = $2
         WHERE department_id = $3",
        [
            $departmentName,
            $status ? "true" : "false",
            $departmentId
        ]
    );


    if ($updateResult === false) {

        pg_query($conn, "ROLLBACK");

        departmentActionMessage(
            "error",
            "Failed to update the department."
        );
    }


    /* Audit */

    if (!addAuditLog(
        $conn,
        $adminUserId,
        "DEPARTMENT",
        $departmentId,
        "UPDATE_DEPARTMENT"
    )) {

        pg_query($conn, "ROLLBACK");

        departmentActionMessage(
            "error",
            "Department was not updated because the audit log could not be recorded."
        );
    }


    if (!pg_query($conn, "COMMIT")) {

        pg_query($conn, "ROLLBACK");

        departmentActionMessage(
            "error",
            "Failed to save department changes."
        );
    }


    departmentActionMessage(
        "success",
        "Department updated successfully."
    );
}


/* =========================================================
   ACTIVATE / DEACTIVATE
========================================================= */

if (
    $action === "activate" ||
    $action === "deactivate"
) {

    $departmentId =
        $_POST["department_id"] ?? "";


    /* Validate ID */

    if (
        !is_numeric($departmentId) ||
        (int)$departmentId <= 0
    ) {

        departmentActionMessage(
            "error",
            "Invalid department."
        );
    }

    $departmentId =
        (int)$departmentId;


    $newStatus =
        $action === "activate";


    /* Check exists */

    $existingResult = pg_query_params(
        $conn,
        "SELECT
            department_id,
            department_name,
            status
         FROM departments
         WHERE department_id = $1",
        [$departmentId]
    );


    if (
        $existingResult === false ||
        pg_num_rows($existingResult) === 0
    ) {

        departmentActionMessage(
            "error",
            "Department not found."
        );
    }


    $department =
        pg_fetch_assoc($existingResult);


    /* Avoid unnecessary action */

    $currentStatus =
        (
            $department["status"] === true ||
            $department["status"] === "t" ||
            $department["status"] === "1" ||
            $department["status"] === 1
        );


    if ($currentStatus === $newStatus) {

        departmentActionMessage(
            "error",
            "Department is already " .
                ($newStatus ? "active." : "inactive.")
        );
    }


    /* Transaction */

    pg_query($conn, "BEGIN");


    $updateResult = pg_query_params(
        $conn,
        "UPDATE departments
         SET status = $1
         WHERE department_id = $2",
        [
            $newStatus ? "true" : "false",
            $departmentId
        ]
    );


    if ($updateResult === false) {

        pg_query($conn, "ROLLBACK");

        departmentActionMessage(
            "error",
            "Failed to update department status."
        );
    }


    /* Audit */

    $auditAction =
        $newStatus
        ? "ACTIVATE_DEPARTMENT"
        : "DEACTIVATE_DEPARTMENT";


    if (!addAuditLog(
        $conn,
        $adminUserId,
        "DEPARTMENT",
        $departmentId,
        $auditAction
    )) {

        pg_query($conn, "ROLLBACK");

        departmentActionMessage(
            "error",
            "Department status was not changed because the audit log could not be recorded."
        );
    }


    if (!pg_query($conn, "COMMIT")) {

        pg_query($conn, "ROLLBACK");

        departmentActionMessage(
            "error",
            "Failed to save department status."
        );
    }


    departmentActionMessage(
        "success",
        $newStatus
            ? "Department activated successfully."
            : "Department deactivated successfully."
    );
}


/* =========================================================
   UNKNOWN ACTION
========================================================= */

departmentActionMessage(
    "error",
    "Invalid department action."
);
