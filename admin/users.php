<?php

/*
 * CampusDesk
 * Admin - User Management
 */

require_once "admin_auth.php";
requireAdmin();

require_once "../config/database.php";
require_once "../includes/functions.php";


/* =========================================================
   SESSION MESSAGE
========================================================= */

$admin_message = $_SESSION["admin_users_message"] ?? null;

unset(
    $_SESSION["admin_users_message"]
);


/* =========================================================
   FILTERS
========================================================= */

$search = trim(
    $_GET["search"] ?? ""
);

$role_filter = strtoupper(
    trim(
        $_GET["role"] ?? "ALL"
    )
);

$status_filter = strtoupper(
    trim(
        $_GET["status"] ?? "ALL"
    )
);


/* =========================================================
   VALID FILTER VALUES
========================================================= */

$allowed_roles = [
    "ALL",
    "STUDENT",
    "AUTHORITY",
    "ADMIN"
];

$allowed_statuses = [
    "ALL",
    "ACTIVE",
    "INACTIVE"
];


if (
    !in_array(
        $role_filter,
        $allowed_roles,
        true
    )
) {
    $role_filter = "ALL";
}


if (
    !in_array(
        $status_filter,
        $allowed_statuses,
        true
    )
) {
    $status_filter = "ALL";
}


/* =========================================================
   DEPARTMENTS
========================================================= */

$departments = [];

$department_result = pg_query(
    $conn,
    "
    SELECT
        department_id,
        department_name,
        status
    FROM departments
    ORDER BY department_name ASC
    "
);

if ($department_result) {

    while (
        $department = pg_fetch_assoc(
            $department_result
        )
    ) {

        $departments[] = $department;
    }
}


/* =========================================================
   USER QUERY
========================================================= */

$user_conditions = [];

$user_params = [];

$parameter_number = 1;


/* ---------------------------------------------------------
   SEARCH
--------------------------------------------------------- */

if ($search !== "") {

    $user_conditions[] = "
        (
            LOWER(u.email)
                LIKE LOWER($" . $parameter_number . ")

            OR LOWER(
                COALESCE(s.full_name, a.name, '')
            )
                LIKE LOWER($" . $parameter_number . ")

            OR LOWER(
                COALESCE(s.prn, '')
            )
                LIKE LOWER($" . $parameter_number . ")

            OR COALESCE(
                u.mobile_number,
                ''
            )
                LIKE $" . $parameter_number . "
        )
    ";

    $user_params[] =
        "%" . $search . "%";

    $parameter_number++;
}


/* ---------------------------------------------------------
   ROLE FILTER
--------------------------------------------------------- */

if ($role_filter !== "ALL") {

    $user_conditions[] =
        "r.role_name = $" .
        $parameter_number;

    $user_params[] =
        $role_filter;

    $parameter_number++;
}


/* ---------------------------------------------------------
   STATUS FILTER
--------------------------------------------------------- */

if ($status_filter === "ACTIVE") {

    $user_conditions[] =
        "u.account_status = TRUE";
} elseif ($status_filter === "INACTIVE") {

    $user_conditions[] =
        "u.account_status = FALSE";
}


/* ---------------------------------------------------------
   WHERE CLAUSE
--------------------------------------------------------- */

$where_clause = "";

if (!empty($user_conditions)) {

    $where_clause =
        " WHERE " .
        implode(
            " AND ",
            $user_conditions
        );
}


/* =========================================================
   GET USERS
========================================================= */

$user_query = "
    SELECT

        u.user_id,
        u.role_id,
        u.email,
        u.mobile_number,
        u.account_status,
        u.last_login,
        u.created_at,
        u.updated_at,
        u.profile_photo,

        r.role_name,

        s.student_id,
        s.department_id AS student_department_id,
        s.full_name AS student_name,
        s.prn,
        s.roll_no,
        s.course,
        s.year,
        s.semester,
        s.division,
        s.address,

        a.authority_id,
        a.department_id AS authority_department_id,
        a.name AS authority_name,
        a.designation,
        a.status AS authority_status,

        COALESCE(
            s.department_id,
            a.department_id
        ) AS department_id,

        d.department_name

    FROM users u

    INNER JOIN roles r
        ON u.role_id = r.role_id

    LEFT JOIN students s
        ON u.user_id = s.user_id

    LEFT JOIN authorities a
        ON u.user_id = a.user_id

    LEFT JOIN departments d
        ON d.department_id =
            COALESCE(
                s.department_id,
                a.department_id
            )

    " . $where_clause . "

    ORDER BY
        u.user_id DESC
";


$user_result = pg_query_params(
    $conn,
    $user_query,
    $user_params
);


$users = [];

if ($user_result) {

    while (
        $user = pg_fetch_assoc(
            $user_result
        )
    ) {

        $users[] = $user;
    }
}


/* =========================================================
   STATISTICS
========================================================= */

$total_users = 0;
$total_students = 0;
$total_authorities = 0;
$total_admins = 0;
$active_users = 0;
$inactive_users = 0;


/* Total users */

$result = pg_query(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM users
    "
);

if ($result) {

    $row = pg_fetch_assoc(
        $result
    );

    $total_users =
        (int) $row["total"];
}


/* Students */

$result = pg_query(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM users u
    INNER JOIN roles r
        ON u.role_id = r.role_id
    WHERE r.role_name = 'STUDENT'
    "
);

if ($result) {

    $row = pg_fetch_assoc(
        $result
    );

    $total_students =
        (int) $row["total"];
}


/* Authorities */

$result = pg_query(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM users u
    INNER JOIN roles r
        ON u.role_id = r.role_id
    WHERE r.role_name = 'AUTHORITY'
    "
);

if ($result) {

    $row = pg_fetch_assoc(
        $result
    );

    $total_authorities =
        (int) $row["total"];
}


/* Admins */

$result = pg_query(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM users u
    INNER JOIN roles r
        ON u.role_id = r.role_id
    WHERE r.role_name = 'ADMIN'
    "
);

if ($result) {

    $row = pg_fetch_assoc(
        $result
    );

    $total_admins =
        (int) $row["total"];
}


/* Active */

$result = pg_query(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM users
    WHERE account_status = TRUE
    "
);

if ($result) {

    $row = pg_fetch_assoc(
        $result
    );

    $active_users =
        (int) $row["total"];
}


/* Inactive */

$result = pg_query(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM users
    WHERE account_status = FALSE
    "
);

if ($result) {

    $row = pg_fetch_assoc(
        $result
    );

    $inactive_users =
        (int) $row["total"];
}


/* =========================================================
   HELPER FUNCTIONS
========================================================= */

function formatAdminUserId($user_id)
{
    return "USR-" .
        str_pad(
            (int) $user_id,
            4,
            "0",
            STR_PAD_LEFT
        );
}


function getUserDisplayName($user)
{
    if (
        !empty($user["student_name"])
    ) {
        return $user["student_name"];
    }

    if (
        !empty($user["authority_name"])
    ) {
        return $user["authority_name"];
    }

    return "Administrator";
}


function getUserDepartmentId($user)
{
    if (
        !empty($user["student_department_id"])
    ) {
        return (int)
        $user["student_department_id"];
    }

    if (
        !empty($user["authority_department_id"])
    ) {
        return (int)
        $user["authority_department_id"];
    }

    return "";
}


function getRoleBadgeClass($role)
{
    switch ($role) {

        case "STUDENT":
            return "role-student";

        case "AUTHORITY":
            return "role-authority";

        case "ADMIN":
            return "role-admin";

        default:
            return "";
    }
}


function getStatusBadgeClass($status)
{
    return $status === "t"
        ? "status-active"
        : "status-inactive";
}


function getProfilePhotoDataUrl($user)
{
    if (
        empty($user["profile_photo"])
    ) {
        return "";
    }

    $binary_data =
        pg_unescape_bytea(
            $user["profile_photo"]
        );

    if ($binary_data === false) {
        return "";
    }

    $image_info =
        @getimagesizefromstring(
            $binary_data
        );

    if (
        !$image_info ||
        empty($image_info["mime"])
    ) {
        return "";
    }

    return "data:" .
        $image_info["mime"] .
        ";base64," .
        base64_encode(
            $binary_data
        );
}


function getDepartmentName(
    $departments,
    $department_id
) {
    foreach (
        $departments as $department
    ) {

        if (
            (int)
            $department["department_id"]
            ===
            (int) $department_id
        ) {
            return $department["department_name"];
        }
    }

    return "Not assigned";
}


/* =========================================================
   CURRENT PAGE
========================================================= */

$current_page =
    basename(
        $_SERVER["PHP_SELF"]
    );

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Users - Admin - CampusDesk
    </title>


    <!-- Font Awesome -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">


    <!-- Admin Common -->

    <link
        rel="stylesheet"
        href="css/admin-common.css">


    <!-- Admin Navbar -->

    <link
        rel="stylesheet"
        href="css/admin-navbar.css">


    <!-- Users Page -->

    <link
        rel="stylesheet"
        href="css/admin-users.css">

</head>


<body>


    <!-- ======================================================
     ADMIN SIDEBAR
======================================================= -->

    <?php

    require_once "includes/navbar.php";

    ?>


    <!-- ======================================================
     MAIN CONTENT
======================================================= -->

    <main class="admin-main">


        <!-- ==================================================
         PAGE HEADER
    =================================================== -->

        <div class="admin-page-header">

            <div>

                <h1>
                    User Management
                </h1>

                <p>
                    Manage CampusDesk student, authority and administrator accounts.
                </p>

            </div>


            <div class="admin-page-header-actions">

                <button
                    type="button"
                    class="admin-btn admin-btn-primary"
                    id="openAddUserButton">
                    <i class="fa-solid fa-user-plus"></i>

                    Add User

                </button>

            </div>

        </div>


        <!-- ==================================================
         MESSAGE
    =================================================== -->

        <?php if ($admin_message): ?>

            <div
                class="admin-alert
            <?php
            echo
            $admin_message["type"] === "success"
                ? "admin-alert-success"
                : "admin-alert-error";
            ?>">

                <div class="admin-alert-icon">

                    <?php if (
                        $admin_message["type"] === "success"
                    ): ?>

                        <i class="fa-solid fa-circle-check"></i>

                    <?php else: ?>

                        <i class="fa-solid fa-circle-exclamation"></i>

                    <?php endif; ?>

                </div>


                <div class="admin-alert-content">

                    <?php
                    echo escape(
                        $admin_message["message"]
                    );
                    ?>

                </div>


                <button
                    type="button"
                    class="admin-alert-close"
                    onclick="this.parentElement.remove();"
                    aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>

            </div>

        <?php endif; ?>


        <!-- ==================================================
         SUMMARY CARDS
    =================================================== -->

        <section class="admin-stats-grid">


            <!-- Total Users -->

            <div class="admin-stat-card">

                <div class="admin-stat-icon">

                    <i class="fa-solid fa-users"></i>

                </div>

                <div class="admin-stat-content">

                    <span>
                        Total Users
                    </span>

                    <strong>
                        <?php
                        echo $total_users;
                        ?>
                    </strong>

                </div>

            </div>


            <!-- Students -->

            <div class="admin-stat-card">

                <div class="admin-stat-icon">

                    <i class="fa-solid fa-graduation-cap"></i>

                </div>

                <div class="admin-stat-content">

                    <span>
                        Students
                    </span>

                    <strong>
                        <?php
                        echo $total_students;
                        ?>
                    </strong>

                </div>

            </div>


            <!-- Authorities -->

            <div class="admin-stat-card">

                <div class="admin-stat-icon">

                    <i class="fa-solid fa-user-tie"></i>

                </div>

                <div class="admin-stat-content">

                    <span>
                        Authorities
                    </span>

                    <strong>
                        <?php
                        echo $total_authorities;
                        ?>
                    </strong>

                </div>

            </div>


            <!-- Admins -->

            <div class="admin-stat-card">

                <div class="admin-stat-icon">

                    <i class="fa-solid fa-user-shield"></i>

                </div>

                <div class="admin-stat-content">

                    <span>
                        Administrators
                    </span>

                    <strong>
                        <?php
                        echo $total_admins;
                        ?>
                    </strong>

                </div>

            </div>


            <!-- Active -->

            <div class="admin-stat-card">

                <div class="admin-stat-icon">

                    <i class="fa-solid fa-circle-check"></i>

                </div>

                <div class="admin-stat-content">

                    <span>
                        Active Accounts
                    </span>

                    <strong>
                        <?php
                        echo $active_users;
                        ?>
                    </strong>

                </div>

            </div>


            <!-- Inactive -->

            <div class="admin-stat-card">

                <div class="admin-stat-icon">

                    <i class="fa-solid fa-circle-xmark"></i>

                </div>

                <div class="admin-stat-content">

                    <span>
                        Inactive Accounts
                    </span>

                    <strong>
                        <?php
                        echo $inactive_users;
                        ?>
                    </strong>

                </div>

            </div>

        </section>


        <!-- ==================================================
         FILTERS
    =================================================== -->

        <section class="admin-content-card">


            <div class="admin-content-card-header">

                <div>

                    <h2>
                        Search & Filter Users
                    </h2>

                    <p>
                        Find users by name, email, PRN or mobile number.
                    </p>

                </div>

            </div>


            <form
                method="GET"
                action="users.php"
                class="admin-filter-form">


                <!-- SEARCH -->

                <div class="admin-filter-group">

                    <label for="search">
                        Search
                    </label>

                    <div class="admin-input-with-icon">

                        <i class="fa-solid fa-magnifying-glass"></i>

                        <input
                            type="text"
                            id="search"
                            name="search"
                            value="<?php
                                    echo escape($search);
                                    ?>"
                            placeholder="Name, email, PRN or mobile">

                    </div>

                </div>


                <!-- ROLE -->

                <div class="admin-filter-group">

                    <label for="role">
                        Role
                    </label>

                    <select
                        id="role"
                        name="role">

                        <option
                            value="ALL"
                            <?php
                            echo
                            $role_filter === "ALL"
                                ? "selected"
                                : "";
                            ?>>
                            All Roles
                        </option>


                        <option
                            value="STUDENT"
                            <?php
                            echo
                            $role_filter === "STUDENT"
                                ? "selected"
                                : "";
                            ?>>
                            Student
                        </option>


                        <option
                            value="AUTHORITY"
                            <?php
                            echo
                            $role_filter === "AUTHORITY"
                                ? "selected"
                                : "";
                            ?>>
                            Authority
                        </option>


                        <option
                            value="ADMIN"
                            <?php
                            echo
                            $role_filter === "ADMIN"
                                ? "selected"
                                : "";
                            ?>>
                            Administrator
                        </option>

                    </select>

                </div>


                <!-- STATUS -->

                <div class="admin-filter-group">

                    <label for="status">
                        Account Status
                    </label>

                    <select
                        id="status"
                        name="status">

                        <option
                            value="ALL"
                            <?php
                            echo
                            $status_filter === "ALL"
                                ? "selected"
                                : "";
                            ?>>
                            All Status
                        </option>


                        <option
                            value="ACTIVE"
                            <?php
                            echo
                            $status_filter === "ACTIVE"
                                ? "selected"
                                : "";
                            ?>>
                            Active
                        </option>


                        <option
                            value="INACTIVE"
                            <?php
                            echo
                            $status_filter === "INACTIVE"
                                ? "selected"
                                : "";
                            ?>>
                            Inactive
                        </option>

                    </select>

                </div>


                <!-- ACTIONS -->

                <div class="admin-filter-actions">

                    <button
                        type="submit"
                        class="admin-btn admin-btn-primary">

                        <i class="fa-solid fa-filter"></i>

                        Apply

                    </button>


                    <a
                        href="users.php"
                        class="admin-btn admin-btn-secondary">

                        <i class="fa-solid fa-rotate-left"></i>

                        Reset

                    </a>

                </div>

            </form>

        </section>


        <!-- ==================================================
         USER TABLE
    =================================================== -->

        <section class="admin-content-card">


            <div class="admin-content-card-header">

                <div>

                    <h2>
                        Users
                    </h2>

                    <p>
                        <?php
                        echo count($users);
                        ?>
                        user(s) found.
                    </p>

                </div>

            </div>


            <?php if (!empty($users)): ?>


                <div class="admin-table-wrapper">

                    <table class="admin-table">

                        <thead>

                            <tr>

                                <th>
                                    ID
                                </th>

                                <th>
                                    User
                                </th>

                                <th>
                                    Role
                                </th>

                                <th>
                                    Department
                                </th>

                                <th>
                                    Details
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Created
                                </th>

                                <th>
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach (
                                $users as $user
                            ): ?>


                                <?php

                                $display_name =
                                    getUserDisplayName(
                                        $user
                                    );

                                $department_id =
                                    getUserDepartmentId(
                                        $user
                                    );

                                $department_name =
                                    getDepartmentName(
                                        $departments,
                                        $department_id
                                    );

                                $role_name =
                                    strtoupper(
                                        $user["role_name"]
                                    );

                                $photo_url =
                                    getProfilePhotoDataUrl(
                                        $user
                                    );

                                ?>


                                <tr>


                                    <!-- ID -->

                                    <td>

                                        <span class="admin-record-id">

                                            <?php
                                            echo escape(
                                                formatAdminUserId(
                                                    $user["user_id"]
                                                )
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <!-- USER -->

                                    <td>

                                        <div class="admin-user-cell">


                                            <div class="admin-user-avatar">

                                                <?php if (
                                                    $photo_url !== ""
                                                ): ?>

                                                    <img
                                                        src="<?php
                                                                echo
                                                                escape(
                                                                    $photo_url
                                                                );
                                                                ?>"
                                                        alt="Profile Photo">

                                                <?php else: ?>

                                                    <span>

                                                        <?php
                                                        echo
                                                        strtoupper(
                                                            substr(
                                                                $display_name,
                                                                0,
                                                                1
                                                            )
                                                        );
                                                        ?>

                                                    </span>

                                                <?php endif; ?>

                                            </div>


                                            <div class="admin-user-info">

                                                <strong>

                                                    <?php
                                                    echo escape(
                                                        $display_name
                                                    );
                                                    ?>

                                                </strong>

                                                <span>

                                                    <?php
                                                    echo escape(
                                                        $user["email"]
                                                    );
                                                    ?>

                                                </span>

                                            </div>

                                        </div>

                                    </td>


                                    <!-- ROLE -->

                                    <td>

                                        <span
                                            class="admin-role-badge
                                    <?php
                                    echo
                                    getRoleBadgeClass(
                                        $role_name
                                    );
                                    ?>">

                                            <?php
                                            echo escape(
                                                $role_name
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <!-- DEPARTMENT -->

                                    <td>

                                        <span class="admin-table-secondary">

                                            <?php
                                            echo escape(
                                                $department_name
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <!-- DETAILS -->

                                    <td>

                                        <div class="admin-user-details">


                                            <?php if (
                                                $role_name ===
                                                "STUDENT"
                                            ): ?>

                                                <?php if (
                                                    !empty($user["prn"])
                                                ): ?>

                                                    <span>

                                                        PRN:
                                                        <?php
                                                        echo escape(
                                                            $user["prn"]
                                                        );
                                                        ?>

                                                    </span>

                                                <?php endif; ?>


                                                <?php if (
                                                    !empty($user["course"])
                                                ): ?>

                                                    <span>

                                                        <?php
                                                        echo escape(
                                                            $user["course"]
                                                        );
                                                        ?>

                                                    </span>

                                                <?php endif; ?>


                                                <?php if (
                                                    !empty($user["year"])
                                                ): ?>

                                                    <span>

                                                        Year:
                                                        <?php
                                                        echo escape(
                                                            $user["year"]
                                                        );
                                                        ?>

                                                    </span>

                                                <?php endif; ?>


                                            <?php elseif (
                                                $role_name ===
                                                "AUTHORITY"
                                            ): ?>


                                                <?php if (
                                                    !empty($user["designation"])
                                                ): ?>

                                                    <span>

                                                        <?php
                                                        echo escape(
                                                            $user["designation"]
                                                        );
                                                        ?>

                                                    </span>

                                                <?php endif; ?>


                                            <?php else: ?>

                                                <span>
                                                    Administrator
                                                </span>

                                            <?php endif; ?>

                                        </div>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <span
                                            class="admin-status-badge
                                    <?php
                                    echo
                                    getStatusBadgeClass(
                                        $user["account_status"]
                                    );
                                    ?>">

                                            <?php
                                            echo
                                            $user["account_status"] === "t"
                                                ? "Active"
                                                : "Inactive";
                                            ?>

                                        </span>

                                    </td>


                                    <!-- CREATED -->

                                    <td>

                                        <span class="admin-table-secondary">

                                            <?php
                                            echo
                                            !empty($user["created_at"])
                                                ? escape(
                                                    formatDateTime(
                                                        $user["created_at"]
                                                    )
                                                )
                                                : "—";
                                            ?>

                                        </span>

                                    </td>


                                    <!-- ACTIONS -->

                                    <td>

                                        <div class="admin-action-group">


                                            <!-- VIEW -->

                                            <button
                                                type="button"
                                                class="admin-icon-btn"
                                                title="View User"
                                                onclick="openViewUserModal(
                                            <?php
                                            echo
                                            (int)
                                            $user["user_id"];
                                            ?>
                                        )">

                                                <i class="fa-solid fa-eye"></i>

                                            </button>


                                            <!-- EDIT -->

                                            <button
                                                type="button"
                                                class="admin-icon-btn"
                                                title="Edit User"
                                                onclick="openEditUserModal(
                                            <?php
                                            echo
                                            (int)
                                            $user["user_id"];
                                            ?>
                                        )">

                                                <i class="fa-solid fa-pen"></i>

                                            </button>


                                            <!-- ACTIVATE / DEACTIVATE -->

                                            <?php if (
                                                $user["account_status"] === "t"
                                            ): ?>

                                                <?php if (
                                                    (int)
                                                    $user["user_id"] !==
                                                    (int)
                                                    getAdminUserId()
                                                ): ?>

                                                    <form
                                                        method="POST"
                                                        action="../admin_actions/users.php"
                                                        class="admin-inline-form"
                                                        onsubmit="
                                                    return confirmAction(
                                                        'Are you sure you want to deactivate this account?'
                                                    );
                                                ">

                                                        <input
                                                            type="hidden"
                                                            name="action"
                                                            value="deactivate">

                                                        <input
                                                            type="hidden"
                                                            name="user_id"
                                                            value="<?php
                                                                    echo
                                                                    (int)
                                                                    $user["user_id"];
                                                                    ?>">

                                                        <button
                                                            type="submit"
                                                            class="admin-icon-btn admin-icon-danger"
                                                            title="Deactivate User">

                                                            <i class="fa-solid fa-user-slash"></i>

                                                        </button>

                                                    </form>

                                                <?php endif; ?>

                                            <?php else: ?>

                                                <form
                                                    method="POST"
                                                    action="../admin_actions/users.php"
                                                    class="admin-inline-form"
                                                    onsubmit="
                                                return confirmAction(
                                                    'Are you sure you want to activate this account?'
                                                );
                                            ">

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="activate">

                                                    <input
                                                        type="hidden"
                                                        name="user_id"
                                                        value="<?php
                                                                echo
                                                                (int)
                                                                $user["user_id"];
                                                                ?>">

                                                    <button
                                                        type="submit"
                                                        class="admin-icon-btn admin-icon-success"
                                                        title="Activate User">

                                                        <i class="fa-solid fa-user-check"></i>

                                                    </button>

                                                </form>

                                            <?php endif; ?>


                                        </div>

                                    </td>

                                </tr>


                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>


            <?php else: ?>


                <div class="admin-empty-state">

                    <div class="admin-empty-icon">

                        <i class="fa-solid fa-users"></i>

                    </div>

                    <h3>
                        No Users Found
                    </h3>

                    <p>
                        No users match the selected search or filters.
                    </p>

                    <a
                        href="users.php"
                        class="admin-btn admin-btn-secondary">
                        Reset Filters
                    </a>

                </div>


            <?php endif; ?>


        </section>


    </main>


    <!-- ======================================================
     VIEW USER MODAL
======================================================= -->

    <div
        class="admin-modal"
        id="viewUserModal"
        aria-hidden="true">

        <div class="admin-modal-overlay"></div>


        <div class="admin-modal-dialog admin-modal-large">


            <div class="admin-modal-header">

                <div>

                    <h2>
                        User Details
                    </h2>

                    <p>
                        View complete account information.
                    </p>

                </div>


                <button
                    type="button"
                    class="admin-modal-close"
                    onclick="closeViewUserModal();"
                    aria-label="Close">

                    <i class="fa-solid fa-xmark"></i>

                </button>

            </div>


            <div
                class="admin-modal-body"
                id="viewUserContent">

                <div class="admin-user-detail-header">


                    <div
                        class="admin-user-detail-avatar"
                        id="viewUserAvatar">
                        <i class="fa-solid fa-user"></i>
                    </div>


                    <div>

                        <h3 id="viewUserName">
                            —
                        </h3>

                        <p id="viewUserEmail">
                            —
                        </p>

                        <div id="viewUserRole">
                            —
                        </div>

                    </div>

                </div>


                <div class="admin-details-grid">


                    <div class="admin-detail-item">

                        <span>
                            User ID
                        </span>

                        <strong id="viewUserId">
                            —
                        </strong>

                    </div>


                    <div class="admin-detail-item">

                        <span>
                            Mobile
                        </span>

                        <strong id="viewUserMobile">
                            —
                        </strong>

                    </div>


                    <div class="admin-detail-item">

                        <span>
                            Department
                        </span>

                        <strong id="viewUserDepartment">
                            —
                        </strong>

                    </div>


                    <div class="admin-detail-item">

                        <span>
                            Account Status
                        </span>

                        <strong id="viewUserStatus">
                            —
                        </strong>

                    </div>


                    <div class="admin-detail-item">

                        <span>
                            Created
                        </span>

                        <strong id="viewUserCreated">
                            —
                        </strong>

                    </div>


                    <div class="admin-detail-item">

                        <span>
                            Last Login
                        </span>

                        <strong id="viewUserLastLogin">
                            —
                        </strong>

                    </div>

                </div>


                <div
                    class="admin-user-extra-details"
                    id="viewUserExtra">

                </div>


            </div>


            <div class="admin-modal-footer">

                <button
                    type="button"
                    class="admin-btn admin-btn-secondary"
                    onclick="closeViewUserModal();">
                    Close
                </button>

            </div>

        </div>

    </div>


    <!-- ======================================================
     ADD / EDIT USER MODAL
======================================================= -->

    <div
        class="admin-modal"
        id="userModal"
        aria-hidden="true">

        <div class="admin-modal-overlay"></div>


        <div class="admin-modal-dialog admin-modal-extra-large">


            <div class="admin-modal-header">

                <div>

                    <h2 id="userModalTitle">
                        Add User
                    </h2>

                    <p id="userModalDescription">
                        Create a new CampusDesk user account.
                    </p>

                </div>


                <button
                    type="button"
                    class="admin-modal-close"
                    onclick="closeUserModal();"
                    aria-label="Close">

                    <i class="fa-solid fa-xmark"></i>

                </button>

            </div>


            <form
                id="userForm"
                method="POST"
                action="../admin_actions/users.php"
                enctype="multipart/form-data">

                <input
                    type="hidden"
                    name="action"
                    id="userAction"
                    value="create">


                <input
                    type="hidden"
                    name="user_id"
                    id="userId"
                    value="">


                <!-- Role is sent for create.
                 It is ignored by the server during edit. -->

                <input
                    type="hidden"
                    name="role_name"
                    id="hiddenRoleName"
                    value="">


                <div class="admin-modal-body">


                    <!-- =================================================
                     ACCOUNT INFORMATION
                ================================================== -->

                    <div class="admin-form-section">

                        <div class="admin-form-section-header">

                            <div>

                                <h3>
                                    Account Information
                                </h3>

                                <p>
                                    Basic login and account details.
                                </p>

                            </div>

                        </div>


                        <div class="admin-form-grid">


                            <!-- EMAIL -->

                            <div class="admin-form-group">

                                <label for="userEmail">

                                    Email Address

                                    <span class="required">
                                        *
                                    </span>

                                </label>

                                <input
                                    type="email"
                                    id="userEmail"
                                    name="email"
                                    required
                                    maxlength="150"
                                    placeholder="user@example.com">

                            </div>


                            <!-- MOBILE -->

                            <div class="admin-form-group">

                                <label for="userMobile">

                                    Mobile Number

                                </label>

                                <input
                                    type="text"
                                    id="userMobile"
                                    name="mobile"
                                    maxlength="15"
                                    placeholder="Mobile number">

                            </div>


                            <!-- ROLE -->

                            <div
                                class="admin-form-group"
                                id="roleField">

                                <label for="userRole">

                                    Role

                                    <span class="required">
                                        *
                                    </span>

                                </label>

                                <select
                                    id="userRole"
                                    name="role_name"
                                    required>

                                    <option value="">
                                        Select Role
                                    </option>

                                    <option value="STUDENT">
                                        Student
                                    </option>

                                    <option value="AUTHORITY">
                                        Authority
                                    </option>

                                    <option value="ADMIN">
                                        Administrator
                                    </option>

                                </select>

                            </div>


                            <!-- PASSWORD -->

                            <div class="admin-form-group">

                                <label for="userPassword">

                                    Password

                                    <span
                                        class="required"
                                        id="passwordRequired">
                                        *
                                    </span>

                                </label>

                                <div class="admin-password-field">

                                    <input
                                        type="password"
                                        id="userPassword"
                                        name="password"
                                        minlength="8"
                                        autocomplete="new-password"
                                        placeholder="Minimum 8 characters">

                                    <button
                                        type="button"
                                        class="admin-password-toggle"
                                        onclick="togglePasswordField(
                                        'userPassword',
                                        this
                                    );"
                                        tabindex="-1">

                                        <i class="fa-solid fa-eye"></i>

                                    </button>

                                </div>

                                <small id="passwordHelp">
                                    Required when creating a user.
                                </small>

                            </div>


                            <!-- PHOTO -->

                            <div class="admin-form-group">

                                <label for="userProfilePhoto">

                                    Profile Photo

                                </label>

                                <input
                                    type="file"
                                    id="userProfilePhoto"
                                    name="profile_photo"
                                    accept=".jpg,.jpeg,.png,image/jpeg,image/png">

                                <small>
                                    JPG or PNG. Maximum 5 MB.
                                </small>

                            </div>


                            <!-- ACCOUNT STATUS -->

                            <div class="admin-form-group">

                                <label for="userAccountStatus">

                                    Account Status

                                </label>

                                <select
                                    id="userAccountStatus"
                                    name="account_status">

                                    <option value="1">
                                        Active
                                    </option>

                                    <option value="0">
                                        Inactive
                                    </option>

                                </select>

                            </div>

                        </div>

                    </div>


                    <!-- =================================================
                     STUDENT INFORMATION
                ================================================== -->

                    <div
                        class="admin-form-section"
                        id="studentFields">

                        <div class="admin-form-section-header">

                            <div>

                                <h3>
                                    Student Information
                                </h3>

                                <p>
                                    Academic information associated with the student account.
                                </p>

                            </div>

                        </div>


                        <div class="admin-form-grid">


                            <!-- FULL NAME -->

                            <div class="admin-form-group">

                                <label for="studentFullName">

                                    Full Name

                                    <span class="required">
                                        *
                                    </span>

                                </label>

                                <input
                                    type="text"
                                    id="studentFullName"
                                    name="full_name"
                                    maxlength="150"
                                    placeholder="Student full name">

                            </div>


                            <!-- PRN -->

                            <div class="admin-form-group">

                                <label for="studentPrn">

                                    PRN

                                </label>

                                <input
                                    type="text"
                                    id="studentPrn"
                                    name="prn"
                                    maxlength="30"
                                    placeholder="PRN">

                            </div>


                            <!-- ROLL NO -->

                            <div class="admin-form-group">

                                <label for="studentRollNo">

                                    Roll Number

                                </label>

                                <input
                                    type="text"
                                    id="studentRollNo"
                                    name="roll_no"
                                    maxlength="20"
                                    placeholder="Roll number">

                            </div>


                            <!-- DEPARTMENT -->

                            <div class="admin-form-group">

                                <label for="studentDepartment">

                                    Department

                                    <span class="required">
                                        *
                                    </span>

                                </label>

                                <select
                                    id="studentDepartment"
                                    name="department_id">

                                    <option value="">
                                        Select Department
                                    </option>

                                    <?php foreach (
                                        $departments
                                        as $department
                                    ): ?>

                                        <option
                                            value="<?php
                                                    echo
                                                    (int)
                                                    $department["department_id"];
                                                    ?>">

                                            <?php
                                            echo escape(
                                                $department["department_name"]
                                            );
                                            ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>


                            <!-- COURSE -->

                            <div class="admin-form-group">

                                <label for="studentCourse">

                                    Course

                                </label>

                                <input
                                    type="text"
                                    id="studentCourse"
                                    name="course"
                                    maxlength="100"
                                    placeholder="Course">

                            </div>


                            <!-- YEAR -->

                            <div class="admin-form-group">

                                <label for="studentYear">

                                    Year

                                </label>

                                <input
                                    type="number"
                                    id="studentYear"
                                    name="year"
                                    min="1"
                                    max="10"
                                    placeholder="Year">

                            </div>


                            <!-- SEMESTER -->

                            <div class="admin-form-group">

                                <label for="studentSemester">

                                    Semester

                                </label>

                                <input
                                    type="number"
                                    id="studentSemester"
                                    name="semester"
                                    min="1"
                                    max="20"
                                    placeholder="Semester">

                            </div>


                            <!-- DIVISION -->

                            <div class="admin-form-group">

                                <label for="studentDivision">

                                    Division

                                </label>

                                <input
                                    type="text"
                                    id="studentDivision"
                                    name="division"
                                    maxlength="20"
                                    placeholder="Division">

                            </div>


                            <!-- ADDRESS -->

                            <div
                                class="admin-form-group admin-form-group-full">

                                <label for="studentAddress">

                                    Address

                                </label>

                                <textarea
                                    id="studentAddress"
                                    name="address"
                                    rows="3"
                                    placeholder="Student address"></textarea>

                            </div>

                        </div>

                    </div>


                    <!-- =================================================
                     AUTHORITY / ADMIN INFORMATION
                ================================================== -->

                    <div
                        class="admin-form-section"
                        id="authorityFields">

                        <div class="admin-form-section-header">

                            <div>

                                <h3>
                                    Professional Information
                                </h3>

                                <p>
                                    Department and designation information.
                                </p>

                            </div>

                        </div>


                        <div class="admin-form-grid">


                            <!-- NAME -->

                            <div class="admin-form-group">

                                <label for="authorityName">

                                    Name

                                    <span class="required">
                                        *
                                    </span>

                                </label>

                                <input
                                    type="text"
                                    id="authorityName"
                                    name="name"
                                    maxlength="150"
                                    placeholder="Full name">

                            </div>


                            <!-- DEPARTMENT -->

                            <div class="admin-form-group">

                                <label for="authorityDepartment">

                                    Department

                                    <span class="required">
                                        *
                                    </span>

                                </label>

                                <select
                                    id="authorityDepartment"
                                    name="department_id">

                                    <option value="">
                                        Select Department
                                    </option>

                                    <?php foreach (
                                        $departments
                                        as $department
                                    ): ?>

                                        <option
                                            value="<?php
                                                    echo
                                                    (int)
                                                    $department["department_id"];
                                                    ?>">

                                            <?php
                                            echo escape(
                                                $department["department_name"]
                                            );
                                            ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>


                            <!-- DESIGNATION -->

                            <div
                                class="admin-form-group">

                                <label for="authorityDesignation">

                                    Designation

                                </label>

                                <input
                                    type="text"
                                    id="authorityDesignation"
                                    name="designation"
                                    maxlength="100"
                                    placeholder="Designation">

                            </div>

                        </div>

                    </div>


                    <!-- =================================================
                     EDIT INFORMATION
                ================================================== -->

                    <div
                        class="admin-readonly-notice"
                        id="editRoleNotice">

                        <i class="fa-solid fa-circle-info"></i>

                        <span>
                            User role cannot be changed after account creation.
                        </span>

                    </div>


                </div>


                <!-- =================================================
                 FOOTER
            ================================================== -->

                <div class="admin-modal-footer">

                    <button
                        type="button"
                        class="admin-btn admin-btn-danger"
                        id="resetUserPasswordButton"
                        onclick="openResetPasswordModal();"
                        style="display: none;">
                        <i class="fa-solid fa-key"></i>
                        Reset Password
                    </button>

                    <button
                        type="button"
                        class="admin-btn admin-btn-secondary"
                        onclick="closeUserModal();">
                        Cancel
                    </button>


                    <button
                        type="submit"
                        class="admin-btn admin-btn-primary"
                        id="userSubmitButton">

                        <i class="fa-solid fa-user-plus"></i>

                        <span id="userSubmitText">
                            Create User
                        </span>

                    </button>

                </div>

            </form>

        </div>

    </div>


    <!-- ======================================================
     RESET PASSWORD MODAL
======================================================= -->

    <div
        class="admin-modal"
        id="resetPasswordModal"
        aria-hidden="true">

        <div class="admin-modal-overlay"></div>

        <div class="admin-modal-dialog">

            <div class="admin-modal-header">

                <div>
                    <h2>Reset User Password</h2>
                    <p>Set a new password for the selected user account.</p>
                </div>

                <button
                    type="button"
                    class="admin-modal-close"
                    onclick="closeResetPasswordModal();"
                    aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>

            </div>

            <form
                id="resetPasswordForm"
                method="POST"
                action="../admin_actions/users.php">

                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" id="resetPasswordUserId" value="">

                <div class="admin-modal-body">

                    <div class="admin-form-group">
                        <label for="resetPasswordUserName">User</label>
                        <input
                            type="text"
                            id="resetPasswordUserName"
                            readonly>
                    </div>

                    <div class="admin-form-group">
                        <label for="resetPasswordEmail">Email</label>
                        <input
                            type="text"
                            id="resetPasswordEmail"
                            readonly>
                    </div>

                    <div class="admin-form-group">
                        <label for="resetNewPassword">New Password <span class="required">*</span></label>
                        <div class="admin-password-field">
                            <input
                                type="password"
                                id="resetNewPassword"
                                name="new_password"
                                minlength="8"
                                required
                                autocomplete="new-password"
                                placeholder="Minimum 8 characters">
                            <button
                                type="button"
                                class="admin-password-toggle"
                                onclick="togglePasswordField('resetNewPassword', this);"
                                tabindex="-1">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="admin-form-group">
                        <label for="resetConfirmPassword">Confirm New Password <span class="required">*</span></label>
                        <div class="admin-password-field">
                            <input
                                type="password"
                                id="resetConfirmPassword"
                                name="confirm_password"
                                minlength="8"
                                required
                                autocomplete="new-password"
                                placeholder="Re-enter new password">
                            <button
                                type="button"
                                class="admin-password-toggle"
                                onclick="togglePasswordField('resetConfirmPassword', this);"
                                tabindex="-1">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        <small>Minimum 8 characters. The current password is not required.</small>
                    </div>

                </div>

                <div class="admin-modal-footer">
                    <button
                        type="button"
                        class="admin-btn admin-btn-secondary"
                        onclick="closeResetPasswordModal();">
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="admin-btn admin-btn-primary"
                        id="resetPasswordSubmitButton">
                        <i class="fa-solid fa-key"></i>
                        Reset Password
                    </button>
                </div>

            </form>

        </div>

    </div>


    <!-- ======================================================
     USER DATA FOR JAVASCRIPT
======================================================= -->

    <script>
        const adminUsers = <?php

                            $javascript_users = [];

                            foreach (
                                $users as $user
                            ) {

                                $role_name =
                                    strtoupper(
                                        $user["role_name"]
                                    );

                                $department_id =
                                    getUserDepartmentId(
                                        $user
                                    );

                                $department_name =
                                    getDepartmentName(
                                        $departments,
                                        $department_id
                                    );

                                $display_name =
                                    getUserDisplayName(
                                        $user
                                    );

                                $javascript_users[] = [

                                    "user_id" =>
                                    (int) $user["user_id"],

                                    "role_name" =>
                                    $role_name,

                                    "email" =>
                                    $user["email"] ?? "",

                                    "mobile" =>
                                    $user["mobile_number"] ?? "",

                                    "account_status" =>
                                    $user["account_status"] === "t",

                                    "created_at" =>
                                    $user["created_at"] ?? "",

                                    "last_login" =>
                                    $user["last_login"] ?? "",

                                    "display_name" =>
                                    $display_name,

                                    "department_id" =>
                                    $department_id,

                                    "department_name" =>
                                    $department_name,

                                    "student_name" =>
                                    $user["student_name"] ?? "",

                                    "student_id" =>
                                    !empty($user["student_id"])
                                        ? (int)
                                        $user["student_id"]
                                        : null,

                                    "prn" =>
                                    $user["prn"] ?? "",

                                    "roll_no" =>
                                    $user["roll_no"] ?? "",

                                    "course" =>
                                    $user["course"] ?? "",

                                    "year" =>
                                    $user["year"] ?? "",

                                    "semester" =>
                                    $user["semester"] ?? "",

                                    "division" =>
                                    $user["division"] ?? "",

                                    "address" =>
                                    $user["address"] ?? "",

                                    "authority_id" =>
                                    !empty($user["authority_id"])
                                        ? (int)
                                        $user["authority_id"]
                                        : null,

                                    "authority_name" =>
                                    $user["authority_name"] ?? "",

                                    "designation" =>
                                    $user["designation"] ?? "",

                                    "authority_status" =>
                                    $user["authority_status"] === "t",

                                    "photo_url" =>
                                    getProfilePhotoDataUrl(
                                        $user
                                    )

                                ];
                            }

                            echo json_encode(
                                $javascript_users,
                                JSON_HEX_TAG |
                                    JSON_HEX_AMP |
                                    JSON_HEX_APOS |
                                    JSON_HEX_QUOT
                            );

                            ?>;


        /* =========================================================
           ELEMENTS
        ========================================================= */

        const userModal =
            document.getElementById(
                "userModal"
            );

        const viewUserModal =
            document.getElementById(
                "viewUserModal"
            );

        const userForm =
            document.getElementById(
                "userForm"
            );

        const userAction =
            document.getElementById(
                "userAction"
            );

        const userId =
            document.getElementById(
                "userId"
            );

        const userModalTitle =
            document.getElementById(
                "userModalTitle"
            );

        const userModalDescription =
            document.getElementById(
                "userModalDescription"
            );

        const userSubmitText =
            document.getElementById(
                "userSubmitText"
            );

        const userSubmitButton =
            document.getElementById(
                "userSubmitButton"
            );

        const userRole =
            document.getElementById(
                "userRole"
            );

        const hiddenRoleName =
            document.getElementById(
                "hiddenRoleName"
            );

        const studentFields =
            document.getElementById(
                "studentFields"
            );

        const authorityFields =
            document.getElementById(
                "authorityFields"
            );

        const editRoleNotice =
            document.getElementById(
                "editRoleNotice"
            );

        const passwordRequired =
            document.getElementById(
                "passwordRequired"
            );

        const passwordHelp =
            document.getElementById(
                "passwordHelp"
            );

        const resetPasswordModal =
            document.getElementById(
                "resetPasswordModal"
            );

        const resetPasswordButton =
            document.getElementById(
                "resetUserPasswordButton"
            );

        const resetPasswordForm =
            document.getElementById(
                "resetPasswordForm"
            );

        const resetPasswordUserId =
            document.getElementById(
                "resetPasswordUserId"
            );

        const resetPasswordUserName =
            document.getElementById(
                "resetPasswordUserName"
            );

        const resetPasswordEmail =
            document.getElementById(
                "resetPasswordEmail"
            );

        const resetNewPassword =
            document.getElementById(
                "resetNewPassword"
            );

        const resetConfirmPassword =
            document.getElementById(
                "resetConfirmPassword"
            );

        const resetPasswordSubmitButton =
            document.getElementById(
                "resetPasswordSubmitButton"
            );


        /* =========================================================
           GET USER
        ========================================================= */

        function findUser(userIdValue) {
            return adminUsers.find(
                function(user) {

                    return Number(
                        user.user_id
                    ) === Number(
                        userIdValue
                    );

                }
            );
        }


        /* =========================================================
           OPEN ADD USER
        ========================================================= */

        function openAddUserModal() {
            if (!userModal) {
                return;
            }


            userForm.reset();


            userAction.value =
                "create";


            userId.value =
                "";


            userModalTitle.textContent =
                "Add User";


            userModalDescription.textContent =
                "Create a new CampusDesk user account.";


            userSubmitText.textContent =
                "Create User";


            userSubmitButton
                .querySelector("i")
                .className =
                "fa-solid fa-user-plus";


            userRole.disabled =
                false;


            userRole.required =
                true;


            hiddenRoleName.value =
                "";


            document.getElementById(
                "userPassword"
            ).required = true;


            passwordRequired.style.display =
                "inline";


            passwordHelp.textContent =
                "Required when creating a user.";


            editRoleNotice.style.display =
                "none";

            resetPasswordButton.style.display =
                "none";


            studentFields.style.display =
                "none";


            authorityFields.style.display =
                "none";


            clearUserModalFields();


            userModal.classList.add(
                "show"
            );

            userModal.setAttribute(
                "aria-hidden",
                "false"
            );


            updateRoleFields();

        }


        /* =========================================================
           OPEN EDIT USER
        ========================================================= */

        function openEditUserModal(
            selectedUserId
        ) {
            const user =
                findUser(
                    selectedUserId
                );


            if (!user) {

                alert(
                    "User information could not be loaded."
                );

                return;
            }


            userForm.reset();


            userAction.value =
                "edit";


            userId.value =
                user.user_id;


            userModalTitle.textContent =
                "Edit User";


            userModalDescription.textContent =
                "Update account and profile information.";


            userSubmitText.textContent =
                "Save Changes";


            userSubmitButton
                .querySelector("i")
                .className =
                "fa-solid fa-floppy-disk";


            userRole.value =
                user.role_name;


            userRole.disabled =
                true;


            userRole.required =
                false;


            hiddenRoleName.value =
                user.role_name;


            document.getElementById(
                    "userEmail"
                ).value =
                user.email || "";


            document.getElementById(
                    "userMobile"
                ).value =
                user.mobile || "";


            document.getElementById(
                    "userAccountStatus"
                ).value =
                user.account_status ?
                "1" :
                "0";


            document.getElementById(
                    "userPassword"
                ).value =
                "";


            document.getElementById(
                    "userPassword"
                ).required =
                false;


            passwordRequired.style.display =
                "none";


            passwordHelp.textContent =
                "Leave blank to keep the current password.";


            editRoleNotice.style.display =
                "flex";

            resetPasswordButton.style.display =
                "inline-flex";

            resetPasswordButton.dataset.userId =
                user.user_id;

            resetPasswordButton.dataset.userName =
                user.display_name || "";

            resetPasswordButton.dataset.userEmail =
                user.email || "";


            /* -----------------------------------------------------
               STUDENT
            ----------------------------------------------------- */

            document.getElementById(
                    "studentFullName"
                ).value =
                user.student_name || "";


            document.getElementById(
                    "studentPrn"
                ).value =
                user.prn || "";


            document.getElementById(
                    "studentRollNo"
                ).value =
                user.roll_no || "";


            document.getElementById(
                    "studentCourse"
                ).value =
                user.course || "";


            document.getElementById(
                    "studentYear"
                ).value =
                user.year || "";


            document.getElementById(
                    "studentSemester"
                ).value =
                user.semester || "";


            document.getElementById(
                    "studentDivision"
                ).value =
                user.division || "";


            document.getElementById(
                    "studentAddress"
                ).value =
                user.address || "";


            document.getElementById(
                    "studentDepartment"
                ).value =
                user.department_id || "";


            /* -----------------------------------------------------
               AUTHORITY / ADMIN
            ----------------------------------------------------- */

            document.getElementById(
                    "authorityName"
                ).value =
                user.authority_name || "";


            document.getElementById(
                    "authorityDesignation"
                ).value =
                user.designation || "";


            document.getElementById(
                    "authorityDepartment"
                ).value =
                user.department_id || "";


            userModal.classList.add(
                "show"
            );

            userModal.setAttribute(
                "aria-hidden",
                "false"
            );


            updateRoleFields();
        }


        /* =========================================================
           UPDATE ROLE FIELDS
        ========================================================= */

        function updateRoleFields() {
            const role =
                userRole.value;


            if (role === "STUDENT") {

                studentFields.style.display =
                    "block";

                authorityFields.style.display =
                    "none";


                document.getElementById(
                        "studentDepartment"
                    ).disabled =
                    false;


                document.getElementById(
                        "authorityDepartment"
                    ).disabled =
                    true;


                document.getElementById(
                        "studentFullName"
                    ).required =
                    true;


                document.getElementById(
                        "authorityName"
                    ).required =
                    false;

            } else if (
                role === "AUTHORITY" ||
                role === "ADMIN"
            ) {

                studentFields.style.display =
                    "none";

                authorityFields.style.display =
                    "block";


                document.getElementById(
                        "studentDepartment"
                    ).disabled =
                    true;


                document.getElementById(
                        "authorityDepartment"
                    ).disabled =
                    false;


                document.getElementById(
                        "studentFullName"
                    ).required =
                    false;


                document.getElementById(
                        "authorityName"
                    ).required =
                    true;

            } else {

                studentFields.style.display =
                    "none";

                authorityFields.style.display =
                    "none";


                document.getElementById(
                        "studentDepartment"
                    ).disabled =
                    true;


                document.getElementById(
                        "authorityDepartment"
                    ).disabled =
                    true;


                document.getElementById(
                        "studentFullName"
                    ).required =
                    false;


                document.getElementById(
                        "authorityName"
                    ).required =
                    false;
            }
        }


        /* =========================================================
           CLEAR FIELDS
        ========================================================= */

        function clearUserModalFields() {
            document.getElementById(
                "userEmail"
            ).value = "";


            document.getElementById(
                "userMobile"
            ).value = "";


            document.getElementById(
                "userPassword"
            ).value = "";


            document.getElementById(
                "userAccountStatus"
            ).value = "1";


            document.getElementById(
                "studentFullName"
            ).value = "";


            document.getElementById(
                "studentPrn"
            ).value = "";


            document.getElementById(
                "studentRollNo"
            ).value = "";


            document.getElementById(
                "studentCourse"
            ).value = "";


            document.getElementById(
                "studentYear"
            ).value = "";


            document.getElementById(
                "studentSemester"
            ).value = "";


            document.getElementById(
                "studentDivision"
            ).value = "";


            document.getElementById(
                "studentAddress"
            ).value = "";


            document.getElementById(
                "studentDepartment"
            ).value = "";


            document.getElementById(
                "authorityName"
            ).value = "";


            document.getElementById(
                "authorityDesignation"
            ).value = "";


            document.getElementById(
                "authorityDepartment"
            ).value = "";
        }


        /* =========================================================
           RESET PASSWORD MODAL
        ========================================================= */

        function openResetPasswordModal() {
            if (!resetPasswordModal || !resetPasswordButton) {
                return;
            }

            const selectedId =
                resetPasswordButton.dataset.userId ||
                userId.value;

            const selectedUser =
                findUser(selectedId);

            if (!selectedUser) {
                alert("User information could not be loaded.");
                return;
            }

            resetPasswordUserId.value =
                selectedUser.user_id;

            resetPasswordUserName.value =
                selectedUser.display_name ||
                "";

            resetPasswordEmail.value =
                selectedUser.email ||
                "";

            resetNewPassword.value =
                "";

            resetConfirmPassword.value =
                "";

            resetPasswordModal.classList.add("show");
            resetPasswordModal.setAttribute(
                "aria-hidden",
                "false"
            );

            resetNewPassword.focus();
        }


        function closeResetPasswordModal() {
            if (!resetPasswordModal) {
                return;
            }

            resetPasswordModal.classList.remove("show");
            resetPasswordModal.setAttribute(
                "aria-hidden",
                "true"
            );

            if (resetPasswordForm) {
                resetPasswordForm.reset();
            }
        }


        /* =========================================================
           CLOSE USER MODAL
        ========================================================= */

        function closeUserModal() {
            if (!userModal) {
                return;
            }


            userModal.classList.remove(
                "show"
            );

            userModal.setAttribute(
                "aria-hidden",
                "true"
            );
        }


        /* =========================================================
           VIEW USER
        ========================================================= */

        function openViewUserModal(
            selectedUserId
        ) {
            const user =
                findUser(
                    selectedUserId
                );


            if (!user) {

                alert(
                    "User information could not be loaded."
                );

                return;
            }


            document.getElementById(
                    "viewUserId"
                ).textContent =
                "USR-" +
                String(
                    user.user_id
                ).padStart(
                    4,
                    "0"
                );


            document.getElementById(
                    "viewUserName"
                ).textContent =
                user.display_name ||
                "—";


            document.getElementById(
                    "viewUserEmail"
                ).textContent =
                user.email ||
                "—";


            document.getElementById(
                    "viewUserMobile"
                ).textContent =
                user.mobile ||
                "—";


            document.getElementById(
                    "viewUserDepartment"
                ).textContent =
                user.department_name ||
                "Not assigned";


            document.getElementById(
                    "viewUserStatus"
                ).textContent =
                user.account_status ?
                "Active" :
                "Inactive";


            document.getElementById(
                    "viewUserCreated"
                ).textContent =
                formatDisplayDate(
                    user.created_at
                );


            document.getElementById(
                    "viewUserLastLogin"
                ).textContent =
                formatDisplayDate(
                    user.last_login
                );


            const roleElement =
                document.getElementById(
                    "viewUserRole"
                );


            roleElement.innerHTML =
                '<span class="admin-role-badge ' +
                getRoleBadgeClassJS(
                    user.role_name
                ) +
                '">' +
                escapeHtml(
                    user.role_name
                ) +
                "</span>";


            const avatar =
                document.getElementById(
                    "viewUserAvatar"
                );


            if (
                user.photo_url
            ) {

                avatar.innerHTML =
                    '<img src="' +
                    escapeHtml(
                        user.photo_url
                    ) +
                    '" alt="Profile Photo">';

            } else {

                avatar.innerHTML =
                    '<i class="fa-solid fa-user"></i>';
            }


            const extra =
                document.getElementById(
                    "viewUserExtra"
                );


            let extraHtml = "";


            if (
                user.role_name ===
                "STUDENT"
            ) {

                extraHtml +=
                    '<div class="admin-detail-section">' +

                    "<h3>Academic Information</h3>" +

                    '<div class="admin-details-grid">' +

                    detailItem(
                        "Student ID",
                        user.student_id ?
                        "STU-" +
                        String(
                            user.student_id
                        ).padStart(
                            4,
                            "0"
                        ) :
                        "—"
                    ) +

                    detailItem(
                        "PRN",
                        user.prn ||
                        "—"
                    ) +

                    detailItem(
                        "Roll Number",
                        user.roll_no ||
                        "—"
                    ) +

                    detailItem(
                        "Course",
                        user.course ||
                        "—"
                    ) +

                    detailItem(
                        "Year",
                        user.year ||
                        "—"
                    ) +

                    detailItem(
                        "Semester",
                        user.semester ||
                        "—"
                    ) +

                    detailItem(
                        "Division",
                        user.division ||
                        "—"
                    ) +

                    "</div>" +

                    detailItem(
                        "Address",
                        user.address ||
                        "—"
                    ) +

                    "</div>";

            } else {

                extraHtml +=
                    '<div class="admin-detail-section">' +

                    "<h3>Professional Information</h3>" +

                    '<div class="admin-details-grid">' +

                    detailItem(
                        "Authority ID",
                        user.authority_id ?
                        "AUT-" +
                        String(
                            user.authority_id
                        ).padStart(
                            4,
                            "0"
                        ) :
                        "—"
                    ) +

                    detailItem(
                        "Designation",
                        user.designation ||
                        "—"
                    ) +

                    "</div>" +

                    "</div>";
            }


            extra.innerHTML =
                extraHtml;


            viewUserModal.classList.add(
                "show"
            );

            viewUserModal.setAttribute(
                "aria-hidden",
                "false"
            );
        }


        /* =========================================================
           DETAIL ITEM
        ========================================================= */

        function detailItem(
            label,
            value
        ) {
            return (
                '<div class="admin-detail-item">' +

                "<span>" +
                escapeHtml(label) +
                "</span>" +

                "<strong>" +
                escapeHtml(
                    String(
                        value
                    )
                ) +
                "</strong>" +

                "</div>"
            );
        }


        /* =========================================================
           CLOSE VIEW MODAL
        ========================================================= */

        function closeViewUserModal() {
            if (!viewUserModal) {
                return;
            }


            viewUserModal.classList.remove(
                "show"
            );

            viewUserModal.setAttribute(
                "aria-hidden",
                "true"
            );
        }


        /* =========================================================
           PASSWORD TOGGLE
        ========================================================= */

        function togglePasswordField(
            fieldId,
            button
        ) {
            const field =
                document.getElementById(
                    fieldId
                );


            if (!field) {
                return;
            }


            if (
                field.type ===
                "password"
            ) {

                field.type =
                    "text";

                button.innerHTML =
                    '<i class="fa-solid fa-eye-slash"></i>';

            } else {

                field.type =
                    "password";

                button.innerHTML =
                    '<i class="fa-solid fa-eye"></i>';
            }
        }


        /* =========================================================
           CONFIRM ACTION
        ========================================================= */

        function confirmAction(
            message
        ) {
            return window.confirm(
                message ||
                "Are you sure you want to continue?"
            );
        }


        /* =========================================================
           ROLE BADGE
        ========================================================= */

        function getRoleBadgeClassJS(
            role
        ) {
            if (
                role ===
                "STUDENT"
            ) {
                return "role-student";
            }

            if (
                role ===
                "AUTHORITY"
            ) {
                return "role-authority";
            }

            if (
                role ===
                "ADMIN"
            ) {
                return "role-admin";
            }

            return "";
        }


        /* =========================================================
           DATE FORMAT
        ========================================================= */

        function formatDisplayDate(
            value
        ) {
            if (!value) {
                return "Never";
            }


            const date =
                new Date(
                    value
                );


            if (
                Number.isNaN(
                    date.getTime()
                )
            ) {
                return value;
            }


            return date.toLocaleString(
                undefined, {
                    day: "2-digit",
                    month: "short",
                    year: "numeric",
                    hour: "2-digit",
                    minute: "2-digit"
                }
            );
        }


        /* =========================================================
           HTML ESCAPE
        ========================================================= */

        function escapeHtml(
            value
        ) {
            return String(
                    value ?? ""
                )
                .replace(
                    /&/g,
                    "&amp;"
                )
                .replace(
                    /</g,
                    "&lt;"
                )
                .replace(
                    />/g,
                    "&gt;"
                )
                .replace(
                    /"/g,
                    "&quot;"
                )
                .replace(
                    /'/g,
                    "&#039;"
                );
        }


        /* =========================================================
           EVENTS
        ========================================================= */

        document.addEventListener(
            "DOMContentLoaded",
            function() {

                const addButton =
                    document.getElementById(
                        "openAddUserButton"
                    );


                if (addButton) {

                    addButton.addEventListener(
                        "click",
                        openAddUserModal
                    );
                }


                if (userRole) {

                    userRole.addEventListener(
                        "change",
                        updateRoleFields
                    );
                }


                if (userModal) {

                    userModal
                        .querySelector(
                            ".admin-modal-overlay"
                        )
                        .addEventListener(
                            "click",
                            closeUserModal
                        );
                }


                if (viewUserModal) {

                    viewUserModal
                        .querySelector(
                            ".admin-modal-overlay"
                        )
                        .addEventListener(
                            "click",
                            closeViewUserModal
                        );
                }


                if (resetPasswordModal) {

                    resetPasswordModal
                        .querySelector(
                            ".admin-modal-overlay"
                        )
                        .addEventListener(
                            "click",
                            closeResetPasswordModal
                        );
                }


                document.addEventListener(
                    "keydown",
                    function(event) {

                        if (
                            event.key ===
                            "Escape"
                        ) {

                            closeUserModal();

                            closeViewUserModal();

                            closeResetPasswordModal();
                        }

                    }
                );


                if (userForm) {

                    userForm.addEventListener(
                        "submit",
                        function(event) {

                            const role =
                                userRole.value;


                            if (
                                userAction.value ===
                                "create" &&
                                role === ""
                            ) {

                                event.preventDefault();

                                alert(
                                    "Please select a user role."
                                );

                                userRole.focus();

                                return;
                            }


                            if (
                                role ===
                                "STUDENT"
                            ) {

                                const fullName =
                                    document.getElementById(
                                        "studentFullName"
                                    ).value.trim();


                                const department =
                                    document.getElementById(
                                        "studentDepartment"
                                    ).value;


                                if (
                                    fullName === ""
                                ) {

                                    event.preventDefault();

                                    alert(
                                        "Student full name is required."
                                    );

                                    return;
                                }


                                if (
                                    department === ""
                                ) {

                                    event.preventDefault();

                                    alert(
                                        "Please select a student department."
                                    );

                                    return;
                                }

                            } else if (
                                role ===
                                "AUTHORITY" ||
                                role ===
                                "ADMIN"
                            ) {

                                const name =
                                    document.getElementById(
                                        "authorityName"
                                    ).value.trim();


                                const department =
                                    document.getElementById(
                                        "authorityDepartment"
                                    ).value;


                                if (
                                    name === ""
                                ) {

                                    event.preventDefault();

                                    alert(
                                        "Name is required."
                                    );

                                    return;
                                }


                                if (
                                    department === ""
                                ) {

                                    event.preventDefault();

                                    alert(
                                        "Please select a department."
                                    );

                                    return;
                                }
                            }


                            const password =
                                document.getElementById(
                                    "userPassword"
                                );


                            if (
                                userAction.value ===
                                "create" &&
                                password.value.length <
                                8
                            ) {

                                event.preventDefault();

                                alert(
                                    "Password must contain at least 8 characters."
                                );

                                password.focus();

                                return;
                            }


                            if (
                                userAction.value ===
                                "edit" &&
                                password.value !== "" &&
                                password.value.length <
                                8
                            ) {

                                event.preventDefault();

                                alert(
                                    "Password must contain at least 8 characters."
                                );

                                password.focus();

                                return;
                            }


                            userSubmitButton.disabled =
                                true;

                            userSubmitText.textContent =
                                "Saving...";

                        }
                    );
                }


                if (resetPasswordForm) {

                    resetPasswordForm.addEventListener(
                        "submit",
                        function(event) {

                            const newPassword =
                                resetNewPassword.value;

                            const confirmPassword =
                                resetConfirmPassword.value;

                            if (newPassword.length < 8) {
                                event.preventDefault();
                                alert("Password must contain at least 8 characters.");
                                resetNewPassword.focus();
                                return;
                            }

                            if (newPassword !== confirmPassword) {
                                event.preventDefault();
                                alert("Passwords do not match.");
                                resetConfirmPassword.focus();
                                return;
                            }

                            resetPasswordSubmitButton.disabled = true;
                            resetPasswordSubmitButton.innerHTML =
                                '<i class="fa-solid fa-spinner fa-spin"></i> Resetting...';
                        }
                    );
                }


                updateRoleFields();

            }
        );
    </script>


</body>

</html>
