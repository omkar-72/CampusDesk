<?php
/*
|--------------------------------------------------------------------------
| CampusDesk - Department Management
|--------------------------------------------------------------------------
*/

require_once "admin_auth.php";
requireAdmin();

require_once "../config/database.php";
require_once "../includes/functions.php";


/* =========================================================
   HELPERS
========================================================= */

function formatDepartmentId($id)
{
    return "DEP-" . str_pad(
        (string)$id,
        5,
        "0",
        STR_PAD_LEFT
    );
}

function isDepartmentActive($status)
{
    return (
        $status === true ||
        $status === "t" ||
        $status === "1" ||
        $status === 1
    );
}


/* =========================================================
   SESSION MESSAGE
========================================================= */

$message = $_SESSION["admin_departments_message"] ?? null;
unset($_SESSION["admin_departments_message"]);


/* =========================================================
   FILTERS
========================================================= */

$search = trim($_GET["search"] ?? "");
$statusFilter = $_GET["status"] ?? "ALL";

if (!in_array($statusFilter, ["ALL", "ACTIVE", "INACTIVE"], true)) {
    $statusFilter = "ALL";
}


/* =========================================================
   STATISTICS
========================================================= */

$totalDepartments = 0;
$activeDepartments = 0;
$inactiveDepartments = 0;

$result = pg_query(
    $conn,
    "SELECT
        COUNT(*) AS total,
        COUNT(*) FILTER (WHERE status = TRUE) AS active,
        COUNT(*) FILTER (WHERE status = FALSE) AS inactive
     FROM departments"
);

if ($result) {
    $row = pg_fetch_assoc($result);

    $totalDepartments = (int)($row["total"] ?? 0);
    $activeDepartments = (int)($row["active"] ?? 0);
    $inactiveDepartments = (int)($row["inactive"] ?? 0);
}


/* =========================================================
   DEPARTMENT LIST
========================================================= */

$departments = [];

$query = "
    SELECT
        department_id,
        department_name,
        status
    FROM departments
    WHERE 1 = 1
";

$params = [];
$paramIndex = 1;


/* Search */

if ($search !== "") {

    $query .= "
        AND department_name ILIKE $" . $paramIndex;

    $params[] = "%" . $search . "%";
    $paramIndex++;
}


/* Status */

if ($statusFilter === "ACTIVE") {

    $query .= " AND status = TRUE";
} elseif ($statusFilter === "INACTIVE") {

    $query .= " AND status = FALSE";
}


$query .= "
    ORDER BY department_id DESC
";


$result = pg_query_params(
    $conn,
    $query,
    $params
);

if ($result) {

    while ($row = pg_fetch_assoc($result)) {
        $departments[] = $row;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <meta
        name="description"
        content="CampusDesk Department Management">

    <title>Departments - CampusDesk</title>


    <!-- Common Admin CSS -->
    <link
        rel="stylesheet"
        href="css/admin-common.css">


    <!-- Admin Navbar CSS -->
    <link
        rel="stylesheet"
        href="css/admin-navbar.css">


    <!-- Department CSS -->
    <link
        rel="stylesheet"
        href="css/admin-departments.css">


    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>


<body>


    <?php include "includes/navbar.php"; ?>


    <main class="admin-main">

        <div class="admin-container">


            <!-- =====================================================
             PAGE HEADER
        ====================================================== -->

            <div class="department-page-header">

                <div class="department-header-content">

                    <div class="department-title-row">

                        <span class="department-title-icon">
                            <i class="fa-solid fa-building"></i>
                        </span>

                        <div>

                            <h1>Departments</h1>

                            <p>
                                Manage departments used across CampusDesk.
                            </p>

                        </div>

                    </div>

                </div>


                <div class="department-header-actions">

                    <button
                        type="button"
                        class="admin-btn admin-btn-primary"
                        onclick="openDepartmentModal('add')">

                        <i class="fa-solid fa-plus"></i>

                        Add Department

                    </button>

                </div>

            </div>


            <!-- =====================================================
             MESSAGE
        ====================================================== -->

            <?php if (!empty($message)): ?>

                <div
                    class="admin-alert
                <?= ($message["type"] ?? "") === "success"
                    ? "admin-alert-success"
                    : "admin-alert-error" ?>">

                    <i
                        class="fa-solid
                    <?= ($message["type"] ?? "") === "success"
                        ? "fa-circle-check"
                        : "fa-circle-exclamation" ?>">
                    </i>

                    <span>
                        <?= htmlspecialchars($message["text"] ?? "") ?>
                    </span>

                </div>

            <?php endif; ?>


            <!-- =====================================================
             SUMMARY
        ====================================================== -->

            <div class="department-stats-grid">


                <!-- Total -->

                <div class="department-stat-card">

                    <div class="department-stat-icon total">

                        <i class="fa-solid fa-building"></i>

                    </div>

                    <div>

                        <span>Total Departments</span>

                        <strong>
                            <?= number_format($totalDepartments) ?>
                        </strong>

                    </div>

                </div>


                <!-- Active -->

                <div class="department-stat-card">

                    <div class="department-stat-icon active">

                        <i class="fa-solid fa-circle-check"></i>

                    </div>

                    <div>

                        <span>Active Departments</span>

                        <strong>
                            <?= number_format($activeDepartments) ?>
                        </strong>

                    </div>

                </div>


                <!-- Inactive -->

                <div class="department-stat-card">

                    <div class="department-stat-icon inactive">

                        <i class="fa-solid fa-circle-xmark"></i>

                    </div>

                    <div>

                        <span>Inactive Departments</span>

                        <strong>
                            <?= number_format($inactiveDepartments) ?>
                        </strong>

                    </div>

                </div>

            </div>


            <!-- =====================================================
             FILTER
        ====================================================== -->

            <section class="department-filter-card">

                <div class="department-filter-header">

                    <div>

                        <h2>
                            <i class="fa-solid fa-magnifying-glass"></i>
                            Search & Filter Departments
                        </h2>

                        <p>
                            Find departments by name or status.
                        </p>

                    </div>

                </div>


                <form
                    method="GET"
                    action="departments.php"
                    class="department-filter-form">


                    <!-- Search -->

                    <div class="department-filter-group">

                        <label for="departmentSearch">
                            Search
                        </label>

                        <div class="department-input-wrapper">

                            <i class="fa-solid fa-magnifying-glass"></i>

                            <input
                                type="text"
                                id="departmentSearch"
                                name="search"
                                value="<?= htmlspecialchars($search) ?>"
                                placeholder="Search department name...">

                        </div>

                    </div>


                    <!-- Status -->

                    <div class="department-filter-group">

                        <label for="departmentStatus">
                            Status
                        </label>

                        <select
                            id="departmentStatus"
                            name="status"
                            class="department-form-control">

                            <option
                                value="ALL"
                                <?= $statusFilter === "ALL" ? "selected" : "" ?>>
                                All Status
                            </option>

                            <option
                                value="ACTIVE"
                                <?= $statusFilter === "ACTIVE" ? "selected" : "" ?>>
                                Active
                            </option>

                            <option
                                value="INACTIVE"
                                <?= $statusFilter === "INACTIVE" ? "selected" : "" ?>>
                                Inactive
                            </option>

                        </select>

                    </div>


                    <!-- Actions -->

                    <div class="department-filter-actions">

                        <button
                            type="submit"
                            class="admin-btn admin-btn-primary">

                            <i class="fa-solid fa-filter"></i>

                            Apply

                        </button>


                        <a
                            href="departments.php"
                            class="admin-btn admin-btn-secondary">

                            <i class="fa-solid fa-xmark"></i>

                            Clear

                        </a>

                    </div>

                </form>

            </section>


            <!-- =====================================================
             TABLE
        ====================================================== -->

            <section class="department-table-card">


                <div class="department-table-header">

                    <div>

                        <h2>
                            Department List
                        </h2>

                        <p>
                            <?= number_format(count($departments)) ?>
                            department(s) found.
                        </p>

                    </div>

                    <div class="department-table-count">

                        <?= number_format(count($departments)) ?>

                    </div>

                </div>


                <?php if (!empty($departments)): ?>

                    <div class="department-table-wrapper">

                        <table class="department-table">

                            <thead>

                                <tr>

                                    <th>ID</th>

                                    <th>Department Name</th>

                                    <th>Status</th>

                                    <th>Actions</th>

                                </tr>

                            </thead>


                            <tbody>

                                <?php foreach ($departments as $department): ?>

                                    <?php
                                    $departmentId =
                                        (int)$department["department_id"];

                                    $departmentName =
                                        $department["department_name"];

                                    $active =
                                        isDepartmentActive(
                                            $department["status"]
                                        );
                                    ?>

                                    <tr>

                                        <!-- ID -->

                                        <td>

                                            <span class="department-id">
                                                <?= htmlspecialchars(
                                                    formatDepartmentId(
                                                        $departmentId
                                                    )
                                                ) ?>
                                            </span>

                                        </td>


                                        <!-- Name -->

                                        <td>

                                            <div class="department-name-cell">

                                                <span class="department-name-icon">

                                                    <i class="fa-solid fa-building"></i>

                                                </span>

                                                <strong>
                                                    <?= htmlspecialchars(
                                                        $departmentName
                                                    ) ?>
                                                </strong>

                                            </div>

                                        </td>


                                        <!-- Status -->

                                        <td>

                                            <?php if ($active): ?>

                                                <span
                                                    class="department-status-badge active">

                                                    <i class="fa-solid fa-circle"></i>

                                                    Active

                                                </span>

                                            <?php else: ?>

                                                <span
                                                    class="department-status-badge inactive">

                                                    <i class="fa-solid fa-circle"></i>

                                                    Inactive

                                                </span>

                                            <?php endif; ?>

                                        </td>


                                        <!-- Actions -->

                                        <td>

                                            <div class="department-action-group">


                                                <!-- Edit -->

                                                <button
                                                    type="button"
                                                    class="department-action-btn edit"
                                                    title="Edit Department"
                                                    onclick="openEditDepartment(
                                                <?= $departmentId ?>,
                                                '<?= htmlspecialchars(
                                                        $departmentName,
                                                        ENT_QUOTES
                                                    ) ?>',
                                                <?= $active ? "true" : "false" ?>
                                            )">

                                                    <i class="fa-solid fa-pen"></i>

                                                </button>


                                                <!-- Activate / Deactivate -->

                                                <form
                                                    method="POST"
                                                    action="../admin_actions/departments.php"
                                                    class="department-inline-form"
                                                    onsubmit="return confirmDepartmentStatus(
                                                <?= $active ? "true" : "false" ?>
                                            );">

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="<?= $active
                                                                    ? "deactivate"
                                                                    : "activate" ?>">

                                                    <input
                                                        type="hidden"
                                                        name="department_id"
                                                        value="<?= $departmentId ?>">

                                                    <button
                                                        type="submit"
                                                        class="department-action-btn
                                                    <?= $active
                                                        ? "deactivate"
                                                        : "activate" ?>"
                                                        title="<?= $active
                                                                    ? "Deactivate Department"
                                                                    : "Activate Department" ?>">

                                                        <i class="fa-solid
                                                    <?= $active
                                                        ? "fa-toggle-off"
                                                        : "fa-toggle-on" ?>">
                                                        </i>

                                                    </button>

                                                </form>

                                            </div>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php else: ?>

                    <div class="department-empty-state">

                        <div class="department-empty-icon">

                            <i class="fa-solid fa-building"></i>

                        </div>

                        <h3>
                            No Departments Found
                        </h3>

                        <p>
                            No departments match the selected search
                            and filter criteria.
                        </p>

                        <a
                            href="departments.php"
                            class="admin-btn admin-btn-secondary">

                            Clear Filters

                        </a>

                    </div>

                <?php endif; ?>

            </section>


        </div>

    </main>


    <!-- =========================================================
     ADD / EDIT MODAL
========================================================== -->

    <div
        id="departmentModal"
        class="department-modal"
        aria-hidden="true">

        <div
            class="department-modal-overlay"
            onclick="closeDepartmentModal()">
        </div>


        <div
            class="department-modal-dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="departmentModalTitle">


            <div class="department-modal-header">

                <div>

                    <h2 id="departmentModalTitle">
                        Add Department
                    </h2>

                    <p id="departmentModalSubtitle">
                        Create a new department.
                    </p>

                </div>

                <button
                    type="button"
                    class="department-modal-close"
                    onclick="closeDepartmentModal()"
                    aria-label="Close">

                    <i class="fa-solid fa-xmark"></i>

                </button>

            </div>


            <form
                method="POST"
                action="../admin_actions/departments.php"
                id="departmentForm">


                <div class="department-modal-body">

                    <input
                        type="hidden"
                        name="action"
                        id="departmentAction"
                        value="create">

                    <input
                        type="hidden"
                        name="department_id"
                        id="departmentId"
                        value="">


                    <div class="department-form-group">

                        <label for="departmentName">

                            Department Name

                            <span>*</span>

                        </label>

                        <input
                            type="text"
                            id="departmentName"
                            name="department_name"
                            class="department-form-control"
                            maxlength="100"
                            placeholder="Enter department name"
                            required>

                        <small>
                            Department name must be unique.
                        </small>

                    </div>


                    <div class="department-form-group">

                        <label for="departmentModalStatus">
                            Status
                        </label>

                        <select
                            id="departmentModalStatus"
                            name="status"
                            class="department-form-control">

                            <option value="1">
                                Active
                            </option>

                            <option value="0">
                                Inactive
                            </option>

                        </select>

                    </div>

                </div>


                <div class="department-modal-footer">

                    <button
                        type="button"
                        class="admin-btn admin-btn-secondary"
                        onclick="closeDepartmentModal()">

                        Cancel

                    </button>

                    <button
                        type="submit"
                        class="admin-btn admin-btn-primary"
                        id="departmentSubmitButton">

                        <i class="fa-solid fa-plus"></i>

                        <span id="departmentSubmitText">
                            Add Department
                        </span>

                    </button>

                </div>

            </form>

        </div>

    </div>


    <script>
        /* =========================================================
   MODAL
========================================================= */

        function openDepartmentModal(mode) {

            const modal =
                document.getElementById("departmentModal");

            const action =
                document.getElementById("departmentAction");

            const id =
                document.getElementById("departmentId");

            const name =
                document.getElementById("departmentName");

            const status =
                document.getElementById("departmentModalStatus");

            const title =
                document.getElementById("departmentModalTitle");

            const subtitle =
                document.getElementById("departmentModalSubtitle");

            const submitText =
                document.getElementById("departmentSubmitText");

            const submitButton =
                document.getElementById("departmentSubmitButton");


            if (mode === "add") {

                action.value = "create";
                id.value = "";
                name.value = "";
                status.value = "1";

                title.textContent =
                    "Add Department";

                subtitle.textContent =
                    "Create a new department.";

                submitText.textContent =
                    "Add Department";

                submitButton.innerHTML =
                    '<i class="fa-solid fa-plus"></i>' +
                    '<span id="departmentSubmitText">' +
                    'Add Department' +
                    '</span>';

            }


            modal.classList.add("open");
            modal.setAttribute("aria-hidden", "false");

            setTimeout(function() {
                name.focus();
            }, 100);

        }


        function openEditDepartment(
            departmentId,
            departmentName,
            isActive
        ) {

            const modal =
                document.getElementById("departmentModal");

            const action =
                document.getElementById("departmentAction");

            const id =
                document.getElementById("departmentId");

            const name =
                document.getElementById("departmentName");

            const status =
                document.getElementById("departmentModalStatus");

            const title =
                document.getElementById("departmentModalTitle");

            const subtitle =
                document.getElementById("departmentModalSubtitle");

            const submitButton =
                document.getElementById("departmentSubmitButton");


            action.value = "update";
            id.value = departmentId;
            name.value = departmentName;
            status.value = isActive ? "1" : "0";

            title.textContent =
                "Edit Department";

            subtitle.textContent =
                "Update department information.";

            submitButton.innerHTML =
                '<i class="fa-solid fa-save"></i>' +
                '<span id="departmentSubmitText">' +
                'Save Changes' +
                '</span>';


            modal.classList.add("open");
            modal.setAttribute("aria-hidden", "false");

            setTimeout(function() {
                name.focus();
            }, 100);

        }


        function closeDepartmentModal() {

            const modal =
                document.getElementById("departmentModal");

            modal.classList.remove("open");

            modal.setAttribute("aria-hidden", "true");

        }


        /* =========================================================
           STATUS CONFIRMATION
        ========================================================= */

        function confirmDepartmentStatus(isActive) {

            if (isActive) {

                return confirm(
                    "Are you sure you want to deactivate this department?"
                );

            }

            return confirm(
                "Are you sure you want to activate this department?"
            );
        }


        /* =========================================================
           FORM VALIDATION
        ========================================================= */

        document
            .getElementById("departmentForm")
            .addEventListener("submit", function(event) {

                const name =
                    document
                    .getElementById("departmentName")
                    .value
                    .trim();

                if (name.length === 0) {

                    event.preventDefault();

                    alert(
                        "Please enter a department name."
                    );

                    return;
                }

                if (name.length > 100) {

                    event.preventDefault();

                    alert(
                        "Department name cannot exceed 100 characters."
                    );

                    return;
                }

            });


        /* =========================================================
           ESCAPE KEY
        ========================================================= */

        document.addEventListener(
            "keydown",
            function(event) {

                if (event.key === "Escape") {
                    closeDepartmentModal();
                }

            }
        );


        /* =========================================================
           ALERT AUTO HIDE
        ========================================================= */

        setTimeout(function() {

            const alert =
                document.querySelector(".admin-alert");

            if (!alert) {
                return;
            }

            alert.style.transition =
                "opacity 0.3s ease";

            alert.style.opacity = "0";

            setTimeout(function() {

                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }

            }, 300);

        }, 5000);
    </script>


</body>

</html>
