<?php

session_start();

require_once "../admin/admin_auth.php";

requireAdmin();


/* =========================================================
   ALLOWED MODULES
========================================================= */

$allowedModules = [
    "GRIEVANCE",
    "SUGGESTION",
    "APPLICATION"
];


/* =========================================================
   REQUEST METHOD
========================================================= */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: ../admin/reports.php?module=GRIEVANCE");
    exit();
}


/* =========================================================
   GET INPUT
========================================================= */

$module = isset($_POST["module"])
    ? strtoupper(trim($_POST["module"]))
    : "GRIEVANCE";

$dateFrom = isset($_POST["date_from"])
    ? trim($_POST["date_from"])
    : "";

$dateTo = isset($_POST["date_to"])
    ? trim($_POST["date_to"])
    : "";


/* =========================================================
   VALIDATE MODULE
========================================================= */

if (!in_array($module, $allowedModules, true)) {

    $module = "GRIEVANCE";
}


/* =========================================================
   VALIDATE DATE FORMAT
========================================================= */

function isValidReportDate($date)
{
    if ($date === "") {
        return true;
    }

    $dateObject = DateTime::createFromFormat(
        "Y-m-d",
        $date
    );

    return (
        $dateObject !== false &&
        $dateObject->format("Y-m-d") === $date
    );
}


/* =========================================================
   DATE VALIDATION
========================================================= */

if (!isValidReportDate($dateFrom)) {

    $_SESSION["admin_reports_message"] = [
        "type" => "error",
        "message" => "Invalid start date."
    ];

    header(
        "Location: ../admin/reports.php?module=" .
            urlencode($module)
    );

    exit();
}


if (!isValidReportDate($dateTo)) {

    $_SESSION["admin_reports_message"] = [
        "type" => "error",
        "message" => "Invalid end date."
    ];

    header(
        "Location: ../admin/reports.php?module=" .
            urlencode($module)
    );

    exit();
}


/* =========================================================
   CHECK DATE RANGE
========================================================= */

if (
    $dateFrom !== "" &&
    $dateTo !== ""
) {

    $fromObject =
        DateTime::createFromFormat(
            "Y-m-d",
            $dateFrom
        );

    $toObject =
        DateTime::createFromFormat(
            "Y-m-d",
            $dateTo
        );


    if ($fromObject > $toObject) {

        $_SESSION["admin_reports_message"] = [
            "type" => "error",
            "message" =>
            "The start date cannot be later than the end date."
        ];

        header(
            "Location: ../admin/reports.php?module=" .
                urlencode($module)
        );

        exit();
    }
}


/* =========================================================
   BUILD REPORT URL
========================================================= */

$queryParameters = [
    "module" => $module
];


if ($dateFrom !== "") {

    $queryParameters["date_from"] =
        $dateFrom;
}


if ($dateTo !== "") {

    $queryParameters["date_to"] =
        $dateTo;
}


/* =========================================================
   REDIRECT TO REPORTS
========================================================= */

$redirectUrl =
    "../admin/reports.php?" .
    http_build_query($queryParameters);


header(
    "Location: " . $redirectUrl
);

exit();
