<?php

require_once "admin_auth.php";

requireAdmin();

require_once "../config/database.php";


/*
|--------------------------------------------------------------------------
| FILTER VALUES
|--------------------------------------------------------------------------
*/

$search = trim($_GET["search"] ?? "");

$role = strtoupper(
    trim($_GET["role"] ?? "ALL")
);

$module = strtoupper(
    trim($_GET["module"] ?? "ALL")
);

$action = strtoupper(
    trim($_GET["action"] ?? "ALL")
);

$date = trim(
    $_GET["date"] ?? ""
);


/*
|--------------------------------------------------------------------------
| ALLOWED FILTER VALUES
|--------------------------------------------------------------------------
*/

$allowed_roles = [
    "ALL",
    "ADMIN",
    "AUTHORITY",
    "STUDENT"
];

$allowed_modules = [
    "ALL",
    "USER",
    "GRIEVANCE",
    "SUGGESTION",
    "APPLICATION",
    "AUTHENTICATION",
    "SYSTEM",
    "CATEGORY",
    "APPLICATION_TYPE",
    "ADMIN"
];

$allowed_actions = [
    "ALL",
    "LOGIN",
    "LOGOUT",
    "ADMIN_LOGIN",
    "CREATE",
    "CREATE_USER",
    "UPDATE",
    "UPDATE_USER",
    "DELETE",
    "SUBMIT",
    "SUBMIT_GRIEVANCE",
    "SUBMIT_SUGGESTION",
    "SUBMIT_APPLICATION",
    "APPROVE",
    "REJECT",
    "RESOLVE",
    "UPDATE_GRIEVANCE",
    "RESOLVE_GRIEVANCE",
    "REJECT_GRIEVANCE",
    "UPDATE_SUGGESTION",
    "UPDATE_APPLICATION",
    "CHANGE_PASSWORD",
    "ACTIVATE",
    "ACTIVATE_USER",
    "DEACTIVATE",
    "DEACTIVATE_USER",
    "CREATE_CATEGORY",
    "UPDATE_CATEGORY"
];


if (!in_array($role, $allowed_roles, true)) {
    $role = "ALL";
}

if (!in_array($module, $allowed_modules, true)) {
    $module = "ALL";
}

if (
    $action !== ""
    && !in_array($action, $allowed_actions, true)
) {
    $action = "ALL";
}


/*
|--------------------------------------------------------------------------
| PAGINATION
|--------------------------------------------------------------------------
*/

$records_per_page = 20;

$page = (int)(
    $_GET["page"] ?? 1
);

if ($page < 1) {
    $page = 1;
}

$offset =
    ($page - 1) *
    $records_per_page;


/*
|--------------------------------------------------------------------------
| BUILD FILTER CONDITIONS
|--------------------------------------------------------------------------
*/

$conditions = [];

$params = [];

$parameter_number = 1;


/*
 * Search by email
 */

if ($search !== "") {

    $conditions[] =
        "LOWER(u.email) LIKE LOWER($" .
        $parameter_number .
        ")";

    $params[] =
        "%" . $search . "%";

    $parameter_number++;
}


/*
 * Role
 */

if ($role !== "ALL") {

    $conditions[] =
        "r.role_name = $" .
        $parameter_number;

    $params[] = $role;

    $parameter_number++;
}


/*
 * Module
 */

if ($module !== "ALL") {

    $conditions[] =
        "al.module_type = $" .
        $parameter_number;

    $params[] = $module;

    $parameter_number++;
}


/*
 * Action
 */

if (
    $action !== ""
    && $action !== "ALL"
) {

    $conditions[] =
        "al.action = $" .
        $parameter_number;

    $params[] = $action;

    $parameter_number++;
}


/*
 * Date
 */

if ($date !== "") {

    $conditions[] =
        "DATE(al.created_at) = $" .
        $parameter_number;

    $params[] = $date;

    $parameter_number++;
}


/*
|--------------------------------------------------------------------------
| WHERE CLAUSE
|--------------------------------------------------------------------------
*/

$where_clause = "";

if (!empty($conditions)) {

    $where_clause =
        " WHERE " .
        implode(
            " AND ",
            $conditions
        );
}


/*
|--------------------------------------------------------------------------
| TOTAL RECORD COUNT
|--------------------------------------------------------------------------
*/

$count_query = "
    SELECT COUNT(*) AS total
    FROM audit_logs al

    LEFT JOIN users u
        ON al.user_id = u.user_id

    LEFT JOIN roles r
        ON u.role_id = r.role_id

    " . $where_clause;


$count_result = pg_query_params(
    $conn,
    $count_query,
    $params
);


$total_records = 0;

if ($count_result) {

    $count_row =
        pg_fetch_assoc(
            $count_result
        );

    $total_records =
        (int)(
            $count_row["total"] ?? 0
        );
}


$total_pages = max(
    1,
    (int)ceil(
        $total_records /
            $records_per_page
    )
);


if ($page > $total_pages) {

    $page = $total_pages;

    $offset =
        ($page - 1) *
        $records_per_page;
}


/*
|--------------------------------------------------------------------------
| GET AUDIT LOGS
|--------------------------------------------------------------------------
*/

$log_query = "
    SELECT
        al.audit_id,
        al.user_id,
        al.module_type,
        al.reference_id,
        al.action,
        al.ip_address,
        al.created_at,

        u.email,

        r.role_name

    FROM audit_logs al

    LEFT JOIN users u
        ON al.user_id = u.user_id

    LEFT JOIN roles r
        ON u.role_id = r.role_id

    " . $where_clause . "

    ORDER BY
        al.created_at DESC

    LIMIT $" .
    $parameter_number .

    " OFFSET $" .
    ($parameter_number + 1);


$log_params = $params;

$log_params[] =
    $records_per_page;

$log_params[] =
    $offset;


$log_result = pg_query_params(
    $conn,
    $log_query,
    $log_params
);


$audit_logs = [];

if ($log_result) {

    while (
        $row = pg_fetch_assoc(
            $log_result
        )
    ) {

        $audit_logs[] = $row;
    }
}


/*
|--------------------------------------------------------------------------
| PAGINATION URL
|--------------------------------------------------------------------------
*/

function auditPageUrl(
    $page_number,
    $search,
    $role,
    $module,
    $action,
    $date
) {

    $params = [
        "page" => $page_number
    ];


    if ($search !== "") {
        $params["search"] = $search;
    }

    if ($role !== "ALL") {
        $params["role"] = $role;
    }

    if ($module !== "ALL") {
        $params["module"] = $module;
    }

    if (
        $action !== ""
        && $action !== "ALL"
    ) {
        $params["action"] = $action;
    }

    if ($date !== "") {
        $params["date"] = $date;
    }


    return
        "audit_logs.php?" .
        http_build_query($params);
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Audit Logs - Admin - CampusDesk
    </title>


    <!-- Font Awesome -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">


    <!-- Admin Common CSS -->

    <link
        rel="stylesheet"
        href="css/admin-common.css">


    <!-- Admin Navbar CSS -->

    <link
        rel="stylesheet"
        href="css/admin-navbar.css">


    <!-- Audit Log CSS -->

    <link
        rel="stylesheet"
        href="css/admin-audit-log.css">

</head>


<body>


    <div class="admin-layout">


        <!-- =====================================================
         ADMIN NAVBAR
    ====================================================== -->

        <?php
        include "includes/navbar.php";
        ?>


        <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

        <main class="admin-main">


            <!-- =================================================
             PAGE HEADER
        ================================================== -->

            <div class="audit-page-header">

                <div>

                    <h1>
                        Audit Logs
                    </h1>

                    <p>
                        Monitor activities performed
                        across CampusDesk.
                    </p>

                </div>


                <div class="audit-header-icon">

                    <i class="fa-solid fa-clock-rotate-left"></i>

                </div>

            </div>



            <!-- =================================================
             SUMMARY
        ================================================== -->

            <div class="audit-summary-grid">


                <div class="audit-summary-card">

                    <div class="audit-summary-icon">

                        <i class="fa-solid fa-list"></i>

                    </div>

                    <div>

                        <span>
                            Total Records
                        </span>

                        <strong>
                            <?php
                            echo number_format(
                                $total_records
                            );
                            ?>
                        </strong>

                    </div>

                </div>


                <div class="audit-summary-card">

                    <div class="audit-summary-icon">

                        <i class="fa-solid fa-user-shield"></i>

                    </div>

                    <div>

                        <span>
                            Admin Activity
                        </span>

                        <strong>

                            <?php

                            $admin_count_query = "
                            SELECT COUNT(*)
                            FROM audit_logs al

                            INNER JOIN users u
                                ON al.user_id = u.user_id

                            INNER JOIN roles r
                                ON u.role_id = r.role_id

                            WHERE r.role_name = 'ADMIN'
                        ";

                            $admin_count_result =
                                pg_query(
                                    $conn,
                                    $admin_count_query
                                );

                            $admin_count = 0;

                            if ($admin_count_result) {

                                $admin_count =
                                    (int)pg_fetch_result(
                                        $admin_count_result,
                                        0,
                                        0
                                    );
                            }

                            echo number_format(
                                $admin_count
                            );

                            ?>

                        </strong>

                    </div>

                </div>


                <div class="audit-summary-card">

                    <div class="audit-summary-icon">

                        <i class="fa-solid fa-user-tie"></i>

                    </div>

                    <div>

                        <span>
                            Authority Activity
                        </span>

                        <strong>

                            <?php

                            $authority_count_query = "
                            SELECT COUNT(*)
                            FROM audit_logs al

                            INNER JOIN users u
                                ON al.user_id = u.user_id

                            INNER JOIN roles r
                                ON u.role_id = r.role_id

                            WHERE r.role_name = 'AUTHORITY'
                        ";

                            $authority_count_result =
                                pg_query(
                                    $conn,
                                    $authority_count_query
                                );

                            $authority_count = 0;

                            if (
                                $authority_count_result
                            ) {

                                $authority_count =
                                    (int)pg_fetch_result(
                                        $authority_count_result,
                                        0,
                                        0
                                    );
                            }

                            echo number_format(
                                $authority_count
                            );

                            ?>

                        </strong>

                    </div>

                </div>


                <div class="audit-summary-card">

                    <div class="audit-summary-icon">

                        <i class="fa-solid fa-user-graduate"></i>

                    </div>

                    <div>

                        <span>
                            Student Activity
                        </span>

                        <strong>

                            <?php

                            $student_count_query = "
                            SELECT COUNT(*)
                            FROM audit_logs al

                            INNER JOIN users u
                                ON al.user_id = u.user_id

                            INNER JOIN roles r
                                ON u.role_id = r.role_id

                            WHERE r.role_name = 'STUDENT'
                        ";

                            $student_count_result =
                                pg_query(
                                    $conn,
                                    $student_count_query
                                );

                            $student_count = 0;

                            if (
                                $student_count_result
                            ) {

                                $student_count =
                                    (int)pg_fetch_result(
                                        $student_count_result,
                                        0,
                                        0
                                    );
                            }

                            echo number_format(
                                $student_count
                            );

                            ?>

                        </strong>

                    </div>

                </div>

            </div>



            <!-- =================================================
             FILTER CARD
        ================================================== -->

            <section class="audit-filter-card">


                <div class="audit-section-header">

                    <div>

                        <h2>
                            Filter Audit Logs
                        </h2>

                        <p>
                            Search and filter system activity.
                        </p>

                    </div>
                </div>



                <form
                    method="GET"
                    action="audit_logs.php"
                    class="audit-filter-form">


                    <!-- Search -->

                    <div class="audit-filter-group">

                        <label for="search">

                            <i class="fa-solid fa-magnifying-glass"></i>

                            User

                        </label>

                        <input
                            type="text"
                            id="search"
                            name="search"
                            value="<?php
                                    echo htmlspecialchars(
                                        $search
                                    );
                                    ?>"
                            placeholder="Search by email">

                    </div>



                    <!-- Role -->

                    <div class="audit-filter-group">

                        <label for="role">

                            <i class="fa-solid fa-user-tag"></i>

                            Role

                        </label>

                        <select
                            id="role"
                            name="role">

                            <option
                                value="ALL"
                                <?php
                                echo $role === "ALL"
                                    ? "selected"
                                    : "";
                                ?>>
                                All Roles
                            </option>

                            <option
                                value="ADMIN"
                                <?php
                                echo $role === "ADMIN"
                                    ? "selected"
                                    : "";
                                ?>>
                                Admin
                            </option>

                            <option
                                value="AUTHORITY"
                                <?php
                                echo $role === "AUTHORITY"
                                    ? "selected"
                                    : "";
                                ?>>
                                Authority
                            </option>

                            <option
                                value="STUDENT"
                                <?php
                                echo $role === "STUDENT"
                                    ? "selected"
                                    : "";
                                ?>>
                                Student
                            </option>

                        </select>

                    </div>



                    <!-- Module -->

                    <div class="audit-filter-group">

                        <label for="module">

                            <i class="fa-solid fa-layer-group"></i>

                            Module

                        </label>

                        <select
                            id="module"
                            name="module">

                            <?php
                            foreach (
                                $allowed_modules
                                as $module_option
                            ):

                                if (
                                    $module_option === "ALL"
                                ) {

                                    $module_label =
                                        "All Modules";
                                } else {

                                    $module_label =
                                        ucwords(
                                            strtolower(
                                                str_replace(
                                                    "_",
                                                    " ",
                                                    $module_option
                                                )
                                            )
                                        );
                                }
                            ?>

                                <option
                                    value="<?php
                                            echo htmlspecialchars(
                                                $module_option
                                            );
                                            ?>"
                                    <?php
                                    echo $module ===
                                        $module_option
                                        ? "selected"
                                        : "";
                                    ?>>

                                    <?php
                                    echo htmlspecialchars(
                                        $module_label
                                    );
                                    ?>

                                </option>

                            <?php
                            endforeach;
                            ?>

                        </select>

                    </div>



                    <!-- Action -->

                    <div class="audit-filter-group">

                        <label for="action">

                            <i class="fa-solid fa-bolt"></i>

                            Action

                        </label>

                        <select
                            id="action"
                            name="action">

                            <?php
                            foreach (
                                $allowed_actions
                                as $action_option
                            ):

                                if (
                                    $action_option === "ALL"
                                ) {

                                    $action_label =
                                        "All Actions";
                                } else {

                                    $action_label =
                                        ucwords(
                                            strtolower(
                                                str_replace(
                                                    "_",
                                                    " ",
                                                    $action_option
                                                )
                                            )
                                        );
                                }
                            ?>

                                <option
                                    value="<?php
                                            echo htmlspecialchars(
                                                $action_option
                                            );
                                            ?>"
                                    <?php
                                    echo $action ===
                                        $action_option
                                        ? "selected"
                                        : "";
                                    ?>>

                                    <?php
                                    echo htmlspecialchars(
                                        $action_label
                                    );
                                    ?>

                                </option>

                            <?php
                            endforeach;
                            ?>

                        </select>

                    </div>



                    <!-- Date -->

                    <div class="audit-filter-group">

                        <label for="date">

                            <i class="fa-regular fa-calendar"></i>

                            Date

                        </label>

                        <input
                            type="date"
                            id="date"
                            name="date"
                            value="<?php
                                    echo htmlspecialchars(
                                        $date
                                    );
                                    ?>">

                    </div>



                    <!-- Buttons -->

                    <div class="audit-filter-actions">

                        <button
                            type="submit"
                            class="admin-btn admin-btn-primary">

                            <i class="fa-solid fa-filter"></i>

                            Apply Filters

                        </button>


                        <a
                            href="audit_logs.php"
                            class="admin-btn admin-btn-secondary">

                            <i class="fa-solid fa-rotate-left"></i>

                            Clear

                        </a>

                    </div>


                </form>

            </section>



            <!-- =================================================
             AUDIT TABLE
        ================================================== -->

            <section class="audit-table-card">


                <div class="audit-section-header">

                    <div>

                        <h2>
                            Audit Activity
                        </h2>

                        <p>

                            <?php

                            if ($total_records > 0) {

                                echo "Showing ";

                                echo number_format(
                                    $offset + 1
                                );

                                echo " - ";

                                echo number_format(
                                    min(
                                        $offset +
                                            $records_per_page,
                                        $total_records
                                    )
                                );

                                echo " of ";

                                echo number_format(
                                    $total_records
                                );

                                echo " records.";
                            } else {

                                echo
                                "No audit records found.";
                            }

                            ?>

                        </p>

                    </div>

                </div>



                <div class="audit-table-wrapper">

                    <table class="audit-table">

                        <thead>

                            <tr>

                                <th>
                                    Audit ID
                                </th>

                                <th>
                                    Date & Time
                                </th>

                                <th>
                                    User
                                </th>

                                <th>
                                    Role
                                </th>

                                <th>
                                    Action
                                </th>

                                <th>
                                    Module
                                </th>

                                <th>
                                    Reference
                                </th>

                                <th>
                                    IP Address
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php if (
                                empty($audit_logs)
                            ): ?>

                                <tr>

                                    <td
                                        colspan="8"
                                        class="audit-empty-state">

                                        <div>

                                            <i
                                                class="fa-solid fa-clock-rotate-left"></i>

                                            <strong>
                                                No audit logs found
                                            </strong>

                                            <span>
                                                Try changing your
                                                search or filters.
                                            </span>

                                        </div>

                                    </td>

                                </tr>


                            <?php else: ?>


                                <?php
                                foreach (
                                    $audit_logs
                                    as $log
                                ):
                                ?>

                                    <tr>


                                        <!-- Audit ID -->

                                        <td>

                                            <span
                                                class="audit-id">

                                                AUD-<?php
                                                    echo str_pad(
                                                        $log["audit_id"],
                                                        5,
                                                        "0",
                                                        STR_PAD_LEFT
                                                    );
                                                    ?>

                                            </span>

                                        </td>



                                        <!-- Date -->

                                        <td>

                                            <div
                                                class="audit-date">

                                                <strong>

                                                    <?php
                                                    echo htmlspecialchars(
                                                        date(
                                                            "d M Y",
                                                            strtotime(
                                                                $log["created_at"]
                                                            )
                                                        )
                                                    );
                                                    ?>

                                                </strong>

                                                <span>

                                                    <?php
                                                    echo htmlspecialchars(
                                                        date(
                                                            "h:i A",
                                                            strtotime(
                                                                $log["created_at"]
                                                            )
                                                        )
                                                    );
                                                    ?>

                                                </span>

                                            </div>

                                        </td>



                                        <!-- User -->

                                        <td>

                                            <div
                                                class="audit-user">

                                                <div
                                                    class="audit-user-icon">

                                                    <i
                                                        class="fa-solid fa-user"></i>

                                                </div>

                                                <div>

                                                    <strong>

                                                        <?php

                                                        echo htmlspecialchars(
                                                            $log["email"]
                                                                ?? "Unknown User"
                                                        );

                                                        ?>

                                                    </strong>

                                                    <span>

                                                        User ID:
                                                        <?php
                                                        echo htmlspecialchars(
                                                            $log["user_id"]
                                                        );
                                                        ?>

                                                    </span>

                                                </div>

                                            </div>

                                        </td>



                                        <!-- Role -->

                                        <td>

                                            <?php

                                            $role_name =
                                                $log["role_name"]
                                                ?? "N/A";

                                            $role_class =
                                                strtolower(
                                                    $role_name
                                                );

                                            ?>

                                            <span
                                                class="
                                            role-badge
                                            role-<?php
                                                    echo htmlspecialchars(
                                                        $role_class
                                                    );
                                                    ?>
                                        ">

                                                <?php
                                                echo htmlspecialchars(
                                                    $role_name
                                                );
                                                ?>

                                            </span>

                                        </td>



                                        <!-- Action -->

                                        <td>

                                            <span
                                                class="action-badge">

                                                <?php
                                                echo htmlspecialchars(
                                                    $log["action"]
                                                );
                                                ?>

                                            </span>

                                        </td>



                                        <!-- Module -->

                                        <td>

                                            <span
                                                class="module-badge">

                                                <?php
                                                echo htmlspecialchars(
                                                    $log["module_type"]
                                                );
                                                ?>

                                            </span>

                                        </td>



                                        <!-- Reference -->

                                        <td>

                                            <span
                                                class="reference-id">

                                                <?php
                                                echo htmlspecialchars(
                                                    $log["reference_id"]
                                                );
                                                ?>

                                            </span>

                                        </td>



                                        <!-- IP -->

                                        <td>

                                            <span
                                                class="ip-address">

                                                <?php

                                                echo htmlspecialchars(
                                                    $log["ip_address"]
                                                        ?? "-"
                                                );

                                                ?>

                                            </span>

                                        </td>


                                    </tr>

                                <?php
                                endforeach;
                                ?>


                            <?php endif; ?>


                        </tbody>

                    </table>

                </div>

            </section>



            <!-- =================================================
             PAGINATION
        ================================================== -->

            <?php
            if ($total_pages > 1):
            ?>

                <div
                    class="audit-pagination">


                    <!-- Previous -->

                    <?php
                    if ($page > 1):
                    ?>

                        <a
                            href="<?php
                                    echo htmlspecialchars(
                                        auditPageUrl(
                                            $page - 1,
                                            $search,
                                            $role,
                                            $module,
                                            $action,
                                            $date
                                        )
                                    );
                                    ?>"
                            class="pagination-button">

                            <i
                                class="fa-solid fa-chevron-left"></i>

                            Previous

                        </a>

                    <?php
                    endif;
                    ?>



                    <!-- Page Numbers -->

                    <div
                        class="pagination-pages">

                        <?php

                        $start_page =
                            max(
                                1,
                                $page - 2
                            );

                        $end_page =
                            min(
                                $total_pages,
                                $page + 2
                            );

                        for (
                            $i = $start_page;
                            $i <= $end_page;
                            $i++
                        ):

                        ?>

                            <a
                                href="<?php
                                        echo htmlspecialchars(
                                            auditPageUrl(
                                                $i,
                                                $search,
                                                $role,
                                                $module,
                                                $action,
                                                $date
                                            )
                                        );
                                        ?>"
                                class="
                                pagination-button
                                <?php
                                echo $i === $page
                                    ? "active"
                                    : "";
                                ?>
                            ">

                                <?php
                                echo $i;
                                ?>

                            </a>

                        <?php
                        endfor;
                        ?>

                    </div>



                    <!-- Next -->

                    <?php
                    if ($page < $total_pages):
                    ?>

                        <a
                            href="<?php
                                    echo htmlspecialchars(
                                        auditPageUrl(
                                            $page + 1,
                                            $search,
                                            $role,
                                            $module,
                                            $action,
                                            $date
                                        )
                                    );
                                    ?>"
                            class="pagination-button">

                            Next

                            <i
                                class="fa-solid fa-chevron-right"></i>

                        </a>

                    <?php
                    endif;
                    ?>


                </div>

            <?php
            endif;
            ?>


        </main>


    </div>


    <!-- Admin JavaScript -->

    <script
        src="js/admin-script.js"></script>


</body>

</html>
