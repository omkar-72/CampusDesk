<?php

session_start();

require_once "admin_auth.php";
requireAdmin();

require_once "../config/database.php";
require_once "../includes/functions.php";


/* =========================================================
   HELPER FUNCTIONS
========================================================= */

function formatCategoryId($id, $prefix)
{
    return $prefix . "-" . str_pad((int)$id, 5, "0", STR_PAD_LEFT);
}


function isActiveStatus($status)
{
    return (
        $status === true ||
        $status === "t" ||
        $status === "1" ||
        $status === 1
    );
}


/* =========================================================
   ACTIVE TAB
========================================================= */

$activeTab = isset($_GET["tab"])
    ? strtoupper(trim($_GET["tab"]))
    : "GRIEVANCE";

$allowedTabs = [
    "GRIEVANCE",
    "SUGGESTION",
    "APPLICATION"
];

if (!in_array($activeTab, $allowedTabs, true)) {
    $activeTab = "GRIEVANCE";
}


/* =========================================================
   SEARCH & FILTER
========================================================= */

$search = isset($_GET["search"])
    ? trim($_GET["search"])
    : "";

$statusFilter = isset($_GET["status"])
    ? trim($_GET["status"])
    : "";


/* =========================================================
   STATISTICS
========================================================= */

$totalGrievanceCategories = 0;
$activeGrievanceCategories = 0;

$totalSuggestionCategories = 0;
$activeSuggestionCategories = 0;

$totalApplicationTypes = 0;
$activeApplicationTypes = 0;


/* ---------------------------------------------------------
   Grievance Categories
--------------------------------------------------------- */

$result = pg_query(
    $conn,
    "
    SELECT
        COUNT(*) AS total,
        COUNT(*) FILTER (WHERE status = TRUE) AS active
    FROM grievance_categories
    "
);

if ($result) {
    $row = pg_fetch_assoc($result);

    $totalGrievanceCategories = (int)$row["total"];
    $activeGrievanceCategories = (int)$row["active"];
}


/* ---------------------------------------------------------
   Suggestion Categories
--------------------------------------------------------- */

$result = pg_query(
    $conn,
    "
    SELECT
        COUNT(*) AS total,
        COUNT(*) FILTER (WHERE status = TRUE) AS active
    FROM suggestion_categories
    "
);

if ($result) {
    $row = pg_fetch_assoc($result);

    $totalSuggestionCategories = (int)$row["total"];
    $activeSuggestionCategories = (int)$row["active"];
}


/* ---------------------------------------------------------
   Application Types
--------------------------------------------------------- */

$result = pg_query(
    $conn,
    "
    SELECT
        COUNT(*) AS total,
        COUNT(*) FILTER (WHERE status = TRUE) AS active
    FROM application_types
    "
);

if ($result) {
    $row = pg_fetch_assoc($result);

    $totalApplicationTypes = (int)$row["total"];
    $activeApplicationTypes = (int)$row["active"];
}


/* =========================================================
   FETCH CURRENT TAB DATA
========================================================= */

$items = [];

if ($activeTab === "GRIEVANCE") {

    $query = "
        SELECT
            category_id,
            category_name,
            status
        FROM grievance_categories
    ";

    $conditions = [];
    $params = [];

    if ($search !== "") {
        $conditions[] = "category_name ILIKE $1";
        $params[] = "%" . $search . "%";
    }

    if ($statusFilter === "active") {
        $conditions[] = "status = TRUE";
    } elseif ($statusFilter === "inactive") {
        $conditions[] = "status = FALSE";
    }

    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }

    $query .= " ORDER BY category_id DESC";

    if (!empty($params)) {
        $result = pg_query_params(
            $conn,
            $query,
            $params
        );
    } else {
        $result = pg_query(
            $conn,
            $query
        );
    }

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $items[] = $row;
        }
    }
} elseif ($activeTab === "SUGGESTION") {

    $query = "
        SELECT
            category_id,
            category_name,
            status
        FROM suggestion_categories
    ";

    $conditions = [];
    $params = [];

    if ($search !== "") {
        $conditions[] = "category_name ILIKE $1";
        $params[] = "%" . $search . "%";
    }

    if ($statusFilter === "active") {
        $conditions[] = "status = TRUE";
    } elseif ($statusFilter === "inactive") {
        $conditions[] = "status = FALSE";
    }

    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }

    $query .= " ORDER BY category_id DESC";

    if (!empty($params)) {
        $result = pg_query_params(
            $conn,
            $query,
            $params
        );
    } else {
        $result = pg_query(
            $conn,
            $query
        );
    }

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $items[] = $row;
        }
    }
} elseif ($activeTab === "APPLICATION") {

    $query = "
        SELECT
            application_type_id,
            type_name,
            status
        FROM application_types
    ";

    $conditions = [];
    $params = [];

    if ($search !== "") {
        $conditions[] = "type_name ILIKE $1";
        $params[] = "%" . $search . "%";
    }

    if ($statusFilter === "active") {
        $conditions[] = "status = TRUE";
    } elseif ($statusFilter === "inactive") {
        $conditions[] = "status = FALSE";
    }

    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }

    $query .= " ORDER BY application_type_id DESC";

    if (!empty($params)) {
        $result = pg_query_params(
            $conn,
            $query,
            $params
        );
    } else {
        $result = pg_query(
            $conn,
            $query
        );
    }

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $items[] = $row;
        }
    }
}


/* =========================================================
   SESSION MESSAGE
========================================================= */

$message = null;

if (isset($_SESSION["admin_categories_message"])) {

    $message = $_SESSION["admin_categories_message"];

    unset($_SESSION["admin_categories_message"]);
}


/* =========================================================
   TAB DISPLAY INFORMATION
========================================================= */

if ($activeTab === "GRIEVANCE") {

    $pageSectionTitle = "Grievance Categories";
    $pageSectionDescription =
        "Categories available when students submit grievances.";
    $addButtonText = "Add Category";
    $searchPlaceholder = "Search category...";
    $nameLabel = "Category Name";
    $typeDisplay = "Grievance Category";
    $idPrefix = "GRC";
} elseif ($activeTab === "SUGGESTION") {

    $pageSectionTitle = "Suggestion Categories";
    $pageSectionDescription =
        "Categories available when students submit suggestions.";
    $addButtonText = "Add Category";
    $searchPlaceholder = "Search category...";
    $nameLabel = "Category Name";
    $typeDisplay = "Suggestion Category";
    $idPrefix = "SGC";
} else {

    $pageSectionTitle = "Application Types";
    $pageSectionDescription =
        "Types available when students submit applications.";
    $addButtonText = "Add Application Type";
    $searchPlaceholder = "Search application type...";
    $nameLabel = "Application Type";
    $typeDisplay = "Application Type";
    $idPrefix = "APT";
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
        Categories - CampusDesk Administration
    </title>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link
        rel="stylesheet"
        href="css/admin-common.css">

    <link
        rel="stylesheet"
        href="css/admin-navbar.css">

    <link
        rel="stylesheet"
        href="css/admin-categories.css">

</head>

<body>

    <?php include "includes/navbar.php"; ?>


    <main class="admin-main">

        <div class="admin-container">


            <!-- =====================================================
             PAGE HEADER
        ====================================================== -->

            <div class="page-header">

                <div class="page-header-content">

                    <h1>
                        <i class="fa-solid fa-layer-group"></i>
                        Categories
                    </h1>

                    <p>
                        Manage categories and application types used by CampusDesk services.
                    </p>

                </div>


                <div class="page-header-actions">

                    <button
                        type="button"
                        class="btn btn-primary"
                        onclick="openAddCategoryModal()">

                        <i class="fa-solid fa-plus"></i>

                        <?= htmlspecialchars($addButtonText) ?>

                    </button>

                </div>

            </div>


            <!-- =====================================================
             MESSAGE
        ====================================================== -->

            <?php if ($message !== null): ?>

                <div
                    class="alert alert-<?= htmlspecialchars($message["type"]) ?>">

                    <?php if ($message["type"] === "success"): ?>

                        <i class="fa-solid fa-circle-check"></i>

                    <?php else: ?>

                        <i class="fa-solid fa-circle-exclamation"></i>

                    <?php endif; ?>


                    <span>
                        <?= htmlspecialchars($message["message"]) ?>
                    </span>


                    <button
                        type="button"
                        class="alert-close"
                        onclick="this.parentElement.remove()">

                        <i class="fa-solid fa-xmark"></i>

                    </button>

                </div>

            <?php endif; ?>


            <!-- =====================================================
             SUMMARY CARDS
        ====================================================== -->

            <section class="category-summary-grid">


                <!-- Grievance -->

                <div class="category-summary-card">

                    <div class="category-summary-icon">

                        <i class="fa-solid fa-triangle-exclamation"></i>

                    </div>

                    <div class="category-summary-content">

                        <span>
                            Grievance Categories
                        </span>

                        <strong>
                            <?= $totalGrievanceCategories ?>
                        </strong>

                        <small>
                            <?= $activeGrievanceCategories ?> active
                        </small>

                    </div>

                </div>


                <!-- Suggestion -->

                <div class="category-summary-card">

                    <div class="category-summary-icon">

                        <i class="fa-solid fa-lightbulb"></i>

                    </div>

                    <div class="category-summary-content">

                        <span>
                            Suggestion Categories
                        </span>

                        <strong>
                            <?= $totalSuggestionCategories ?>
                        </strong>

                        <small>
                            <?= $activeSuggestionCategories ?> active
                        </small>

                    </div>

                </div>


                <!-- Application -->

                <div class="category-summary-card">

                    <div class="category-summary-icon">

                        <i class="fa-solid fa-file-lines"></i>

                    </div>

                    <div class="category-summary-content">

                        <span>
                            Application Types
                        </span>

                        <strong>
                            <?= $totalApplicationTypes ?>
                        </strong>

                        <small>
                            <?= $activeApplicationTypes ?> active
                        </small>

                    </div>

                </div>

            </section>


            <!-- =====================================================
             TABS
        ====================================================== -->

            <section class="category-tabs">


                <a
                    href="categories.php?tab=GRIEVANCE"
                    class="category-tab <?= $activeTab === "GRIEVANCE" ? "active" : "" ?>">

                    <span class="tab-icon">

                        <i class="fa-solid fa-triangle-exclamation"></i>

                    </span>

                    <span class="tab-text">

                        <strong>
                            Grievance Categories
                        </strong>

                        <small>
                            <?= $totalGrievanceCategories ?> categories
                        </small>

                    </span>

                </a>


                <a
                    href="categories.php?tab=SUGGESTION"
                    class="category-tab <?= $activeTab === "SUGGESTION" ? "active" : "" ?>">

                    <span class="tab-icon">

                        <i class="fa-solid fa-lightbulb"></i>

                    </span>

                    <span class="tab-text">

                        <strong>
                            Suggestion Categories
                        </strong>

                        <small>
                            <?= $totalSuggestionCategories ?> categories
                        </small>

                    </span>

                </a>


                <a
                    href="categories.php?tab=APPLICATION"
                    class="category-tab <?= $activeTab === "APPLICATION" ? "active" : "" ?>">

                    <span class="tab-icon">

                        <i class="fa-solid fa-file-lines"></i>

                    </span>

                    <span class="tab-text">

                        <strong>
                            Application Types
                        </strong>

                        <small>
                            <?= $totalApplicationTypes ?> types
                        </small>

                    </span>

                </a>

            </section>


            <!-- =====================================================
             CONTENT PANEL
        ====================================================== -->

            <section class="content-panel">


                <!-- Panel Header -->

                <div class="panel-header">

                    <div>

                        <h2>
                            <?= htmlspecialchars($pageSectionTitle) ?>
                        </h2>

                        <p>
                            <?= htmlspecialchars($pageSectionDescription) ?>
                        </p>

                    </div>


                    <div class="panel-header-count">

                        <strong>
                            <?= count($items) ?>
                        </strong>

                        <span>
                            <?= $activeTab === "APPLICATION"
                                ? "type(s)"
                                : "category(ies)" ?>
                        </span>

                    </div>

                </div>


                <!-- =================================================
                 FILTER
            ================================================== -->

                <div class="category-filter-bar">

                    <form
                        method="GET"
                        action="categories.php"
                        class="category-filter-form">

                        <input
                            type="hidden"
                            name="tab"
                            value="<?= htmlspecialchars($activeTab) ?>">


                        <div class="form-group category-search">

                            <label for="categorySearch">
                                Search
                            </label>

                            <div class="input-icon-wrapper">

                                <i class="fa-solid fa-magnifying-glass"></i>

                                <input
                                    type="text"
                                    id="categorySearch"
                                    name="search"
                                    class="form-control"
                                    placeholder="<?= htmlspecialchars($searchPlaceholder) ?>"
                                    value="<?= htmlspecialchars($search) ?>">

                            </div>

                        </div>


                        <div class="form-group">

                            <label for="categoryStatus">
                                Status
                            </label>

                            <select
                                id="categoryStatus"
                                name="status"
                                class="form-control">

                                <option value="">
                                    All Status
                                </option>

                                <option
                                    value="active"
                                    <?= $statusFilter === "active" ? "selected" : "" ?>>
                                    Active
                                </option>

                                <option
                                    value="inactive"
                                    <?= $statusFilter === "inactive" ? "selected" : "" ?>>
                                    Inactive
                                </option>

                            </select>

                        </div>


                        <div class="category-filter-actions">

                            <button
                                type="submit"
                                class="btn btn-primary">

                                <i class="fa-solid fa-filter"></i>

                                Apply

                            </button>


                            <a
                                href="categories.php?tab=<?= urlencode($activeTab) ?>"
                                class="btn btn-secondary">

                                <i class="fa-solid fa-xmark"></i>

                                Clear

                            </a>

                        </div>

                    </form>

                </div>


                <!-- =================================================
                 RESULT SUMMARY
            ================================================== -->

                <div class="result-summary">

                    <span>

                        <strong>
                            <?= count($items) ?>
                        </strong>

                        <?= $activeTab === "APPLICATION"
                            ? "application type(s)"
                            : "category(ies)" ?>

                        found

                    </span>


                    <?php if ($search !== "" || $statusFilter !== ""): ?>

                        <span class="filter-summary">

                            <i class="fa-solid fa-filter"></i>

                            Filters applied

                        </span>

                    <?php endif; ?>

                </div>


                <!-- =================================================
                 TABLE
            ================================================== -->

                <div class="table-card">

                    <div class="table-responsive">

                        <table class="admin-table category-table">

                            <thead>

                                <tr>

                                    <th>
                                        ID
                                    </th>

                                    <th>
                                        <?= htmlspecialchars($nameLabel) ?>
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Actions
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                <?php if (empty($items)): ?>

                                    <tr>

                                        <td
                                            colspan="4"
                                            class="empty-table-cell">

                                            <div class="empty-state">

                                                <div class="empty-state-icon">

                                                    <i class="fa-solid fa-layer-group"></i>

                                                </div>

                                                <h3>
                                                    No Records Found
                                                </h3>

                                                <p>

                                                    <?php if (
                                                        $search !== "" ||
                                                        $statusFilter !== ""
                                                    ): ?>

                                                        No records match the selected filters.

                                                    <?php else: ?>

                                                        No
                                                        <?= $activeTab === "APPLICATION"
                                                            ? "application types"
                                                            : "categories" ?>
                                                        have been added yet.

                                                    <?php endif; ?>

                                                </p>

                                            </div>

                                        </td>

                                    </tr>

                                <?php else: ?>

                                    <?php foreach ($items as $item): ?>

                                        <?php

                                        if ($activeTab === "APPLICATION") {

                                            $itemId =
                                                (int)$item["application_type_id"];

                                            $itemName =
                                                $item["type_name"];
                                        } else {

                                            $itemId =
                                                (int)$item["category_id"];

                                            $itemName =
                                                $item["category_name"];
                                        }

                                        $itemActive =
                                            isActiveStatus($item["status"]);

                                        ?>

                                        <tr>

                                            <!-- ID -->

                                            <td>

                                                <span class="category-id">

                                                    <?= htmlspecialchars(
                                                        formatCategoryId(
                                                            $itemId,
                                                            $idPrefix
                                                        )
                                                    ) ?>

                                                </span>

                                            </td>


                                            <!-- NAME -->

                                            <td>

                                                <div class="category-name-cell">

                                                    <div class="category-name-icon">

                                                        <?php if ($activeTab === "GRIEVANCE"): ?>

                                                            <i class="fa-solid fa-triangle-exclamation"></i>

                                                        <?php elseif ($activeTab === "SUGGESTION"): ?>

                                                            <i class="fa-solid fa-lightbulb"></i>

                                                        <?php else: ?>

                                                            <i class="fa-solid fa-file-lines"></i>

                                                        <?php endif; ?>

                                                    </div>


                                                    <div>

                                                        <strong>
                                                            <?= htmlspecialchars($itemName) ?>
                                                        </strong>

                                                    </div>

                                                </div>

                                            </td>


                                            <!-- STATUS -->

                                            <td>

                                                <?php if ($itemActive): ?>

                                                    <span class="status-badge status-active">

                                                        <i class="fa-solid fa-circle"></i>

                                                        Active

                                                    </span>

                                                <?php else: ?>

                                                    <span class="status-badge status-inactive">

                                                        <i class="fa-solid fa-circle"></i>

                                                        Inactive

                                                    </span>

                                                <?php endif; ?>

                                            </td>


                                            <!-- ACTIONS -->

                                            <td>

                                                <div class="action-group">


                                                    <!-- EDIT -->

                                                    <button
                                                        type="button"
                                                        class="action-btn edit-btn"
                                                        title="Edit"
                                                        data-id="<?= $itemId ?>"
                                                        data-name="<?= htmlspecialchars(
                                                                        $itemName,
                                                                        ENT_QUOTES,
                                                                        "UTF-8"
                                                                    ) ?>"
                                                        data-status="<?= $itemActive ? "1" : "0" ?>"
                                                        data-type="<?= htmlspecialchars($activeTab) ?>"
                                                        onclick="editCategoryFromButton(this)">

                                                        <i class="fa-solid fa-pen"></i>

                                                    </button>


                                                    <!-- ACTIVATE / DEACTIVATE -->

                                                    <form
                                                        method="POST"
                                                        action="../admin_actions/categories.php"
                                                        class="inline-action-form">

                                                        <input
                                                            type="hidden"
                                                            name="action"
                                                            value="<?= $itemActive
                                                                        ? "deactivate"
                                                                        : "activate" ?>">

                                                        <input
                                                            type="hidden"
                                                            name="type"
                                                            value="<?= htmlspecialchars($activeTab) ?>">

                                                        <input
                                                            type="hidden"
                                                            name="id"
                                                            value="<?= $itemId ?>">

                                                        <button
                                                            type="submit"
                                                            class="action-btn <?= $itemActive
                                                                                    ? "deactivate-btn"
                                                                                    : "activate-btn" ?>"
                                                            title="<?= $itemActive
                                                                        ? "Deactivate"
                                                                        : "Activate" ?>"
                                                            onclick="return confirmCategoryStatus('<?= $itemActive ? "deactivate" : "activate" ?>')">

                                                            <i class="fa-solid <?= $itemActive
                                                                                    ? "fa-toggle-off"
                                                                                    : "fa-toggle-on" ?>">
                                                            </i>

                                                        </button>

                                                    </form>

                                                </div>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            </section>

        </div>

    </main>


    <!-- =====================================================
     ADD / EDIT MODAL
====================================================== -->

    <div
        id="categoryModal"
        class="admin-modal">

        <div
            class="modal-overlay"
            onclick="closeCategoryModal()"></div>


        <div class="modal-container category-modal">


            <!-- MODAL HEADER -->

            <div class="modal-header">

                <div>

                    <h2 id="categoryModalTitle">

                        <i class="fa-solid fa-plus"></i>

                        Add <?= htmlspecialchars($typeDisplay) ?>

                    </h2>

                    <p id="categoryModalSubtitle">

                        Create a new
                        <?= strtolower(htmlspecialchars($typeDisplay)) ?>.

                    </p>

                </div>


                <button
                    type="button"
                    class="modal-close"
                    onclick="closeCategoryModal()">

                    <i class="fa-solid fa-xmark"></i>

                </button>

            </div>


            <!-- FORM -->

            <form
                method="POST"
                action="../admin_actions/categories.php"
                id="categoryForm">

                <input
                    type="hidden"
                    name="action"
                    id="categoryAction"
                    value="add">

                <input
                    type="hidden"
                    name="type"
                    id="categoryType"
                    value="<?= htmlspecialchars($activeTab) ?>">

                <input
                    type="hidden"
                    name="id"
                    id="categoryId"
                    value="">


                <div class="modal-body">


                    <!-- TYPE -->

                    <div class="form-group">

                        <label for="categoryTypeDisplay">
                            Type
                        </label>

                        <input
                            type="text"
                            id="categoryTypeDisplay"
                            class="form-control"
                            value="<?= htmlspecialchars($typeDisplay) ?>"
                            readonly>

                    </div>


                    <!-- NAME -->

                    <div class="form-group">

                        <label for="categoryName">

                            <span id="categoryNameLabel">
                                <?= htmlspecialchars($nameLabel) ?>
                            </span>

                            <span class="required">
                                *
                            </span>

                        </label>

                        <input
                            type="text"
                            id="categoryName"
                            name="name"
                            class="form-control"
                            maxlength="100"
                            required
                            placeholder="Enter name">

                    </div>


                    <!-- STATUS -->

                    <div class="form-group">

                        <label for="categoryStatusInput">
                            Status
                        </label>

                        <select
                            id="categoryStatusInput"
                            name="status"
                            class="form-control">

                            <option value="1">
                                Active
                            </option>

                            <option value="0">
                                Inactive
                            </option>

                        </select>

                    </div>


                    <!-- INFO -->

                    <div class="form-info-box">

                        <i class="fa-solid fa-circle-info"></i>

                        <div>

                            <strong>
                                Category Management
                            </strong>

                            <p>
                                Inactive categories or application types remain
                                in the database but are not available for new
                                student submissions.
                            </p>

                        </div>

                    </div>

                </div>


                <!-- MODAL FOOTER -->

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        onclick="closeCategoryModal()">

                        <i class="fa-solid fa-xmark"></i>

                        Cancel

                    </button>


                    <button
                        type="submit"
                        class="btn btn-primary"
                        id="categorySubmitButton">

                        <i class="fa-solid fa-plus"></i>

                        <span id="categorySubmitText">
                            Add
                        </span>

                    </button>

                </div>

            </form>

        </div>

    </div>


    <!-- =====================================================
     JAVASCRIPT
====================================================== -->

    <script>
        const currentCategoryType =
            <?= json_encode($activeTab) ?>;


        /* ---------------------------------------------------------
           Open Add Modal
        --------------------------------------------------------- */

        function openAddCategoryModal() {
            const form =
                document.getElementById("categoryForm");

            form.reset();

            document.getElementById("categoryAction").value =
                "add";

            document.getElementById("categoryId").value =
                "";

            document.getElementById("categoryType").value =
                currentCategoryType;

            document.getElementById("categoryStatusInput").value =
                "1";

            updateCategoryModalText(
                currentCategoryType,
                false
            );

            document.getElementById("categoryModal")
                .classList.add("show");

            document.body.classList.add("modal-open");
        }


        /* ---------------------------------------------------------
           Edit Category
        --------------------------------------------------------- */

        function editCategoryFromButton(button) {
            const id =
                button.getAttribute("data-id");

            const name =
                button.getAttribute("data-name");

            const status =
                button.getAttribute("data-status");

            const type =
                button.getAttribute("data-type");


            document.getElementById("categoryAction").value =
                "edit";

            document.getElementById("categoryId").value =
                id;

            document.getElementById("categoryType").value =
                type;

            document.getElementById("categoryName").value =
                name;

            document.getElementById("categoryStatusInput").value =
                status;


            updateCategoryModalText(
                type,
                true
            );


            document.getElementById("categoryModal")
                .classList.add("show");

            document.body.classList.add("modal-open");
        }


        /* ---------------------------------------------------------
           Modal Text
        --------------------------------------------------------- */

        function updateCategoryModalText(type, isEdit) {
            const title =
                document.getElementById("categoryModalTitle");

            const subtitle =
                document.getElementById("categoryModalSubtitle");

            const typeDisplay =
                document.getElementById("categoryTypeDisplay");

            const nameLabel =
                document.getElementById("categoryNameLabel");

            const submitButton =
                document.getElementById("categorySubmitButton");


            let typeName = "";
            let fieldName = "";


            if (type === "GRIEVANCE") {

                typeName = "Grievance Category";
                fieldName = "Category Name";

            } else if (type === "SUGGESTION") {

                typeName = "Suggestion Category";
                fieldName = "Category Name";

            } else {

                typeName = "Application Type";
                fieldName = "Application Type";
            }


            typeDisplay.value =
                typeName;

            nameLabel.textContent =
                fieldName;


            if (isEdit) {

                title.innerHTML =
                    '<i class="fa-solid fa-pen"></i> Edit ' +
                    escapeHtmlJS(typeName);

                subtitle.textContent =
                    "Update the selected " +
                    typeName.toLowerCase() +
                    ".";

                submitButton.innerHTML =
                    '<i class="fa-solid fa-floppy-disk"></i> ' +
                    '<span id="categorySubmitText">Save Changes</span>';

            } else {

                title.innerHTML =
                    '<i class="fa-solid fa-plus"></i> Add ' +
                    escapeHtmlJS(typeName);

                subtitle.textContent =
                    "Create a new " +
                    typeName.toLowerCase() +
                    ".";

                submitButton.innerHTML =
                    '<i class="fa-solid fa-plus"></i> ' +
                    '<span id="categorySubmitText">Add</span>';
            }
        }


        /* ---------------------------------------------------------
           Escape Text for innerHTML
        --------------------------------------------------------- */

        function escapeHtmlJS(value) {
            return String(value)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }


        /* ---------------------------------------------------------
           Close Modal
        --------------------------------------------------------- */

        function closeCategoryModal() {
            const modal =
                document.getElementById("categoryModal");

            modal.classList.remove("show");

            document.body.classList.remove("modal-open");
        }


        /* ---------------------------------------------------------
           Status Confirmation
        --------------------------------------------------------- */

        function confirmCategoryStatus(action) {
            if (action === "activate") {

                return confirm(
                    "Are you sure you want to activate this record?"
                );

            }

            return confirm(
                "Are you sure you want to deactivate this record?"
            );
        }


        /* ---------------------------------------------------------
           Form Validation
        --------------------------------------------------------- */

        document.getElementById("categoryForm")
            .addEventListener(
                "submit",
                function(event) {

                    const name =
                        document.getElementById("categoryName")
                        .value
                        .trim();


                    if (name === "") {

                        event.preventDefault();

                        alert(
                            "Please enter a name."
                        );

                        return;
                    }


                    if (name.length > 100) {

                        event.preventDefault();

                        alert(
                            "Name must not exceed 100 characters."
                        );

                        return;
                    }
                }
            );


        /* ---------------------------------------------------------
           ESC Key
        --------------------------------------------------------- */

        document.addEventListener(
            "keydown",
            function(event) {

                if (event.key === "Escape") {

                    closeCategoryModal();

                }

            }
        );


        /* ---------------------------------------------------------
           Auto Hide Message
        --------------------------------------------------------- */

        document.addEventListener(
            "DOMContentLoaded",
            function() {

                const alertBox =
                    document.querySelector(".alert");

                if (alertBox) {

                    setTimeout(
                        function() {

                            if (alertBox.parentElement) {
                                alertBox.remove();
                            }

                        },
                        5000
                    );
                }

            }
        );
    </script>


    <script src="js/admin-script.js"></script>

</body>

</html>
