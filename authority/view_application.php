<?php
session_start();

if (!isset($_SESSION["role_name"])) {
    $_SESSION["role_name"] = "AUTHORITY";
}

require_once "../config/database.php";

if (!isset($_GET["id"])) {
    die("Application not found.");
}

$id = $_GET["id"];

$query = "
SELECT
    a.application_id,
    st.full_name,
    at.type_name,
    a.subject,
    a.description,
    a.submission_date,
    a.review_date,
    a.remarks,
    s.status_name

FROM applications a

LEFT JOIN students st
ON a.student_id = st.student_id

LEFT JOIN application_types at
ON a.application_type_id = at.application_type_id

LEFT JOIN statuses s
ON a.status_id = s.status_id

WHERE a.application_id = $1
LIMIT 1;
";

$result = pg_query_params($conn, $query, [$id]);

if (!$result || pg_num_rows($result) == 0) {
    die("Application not found.");
}

$row = pg_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>CampusDesk | View Application</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<link rel="stylesheet" href="../css/global.css">
<link rel="stylesheet" href="../css/header.css">
<link rel="stylesheet" href="../css/navbar.css">
<link rel="stylesheet" href="../css/authority-dashboard.css">

<style>

.view-container{
    max-width:1000px;
    margin:0 auto;
}

.back-btn{
    display:inline-flex;
    align-items:center;
    gap:8px;
    background:#2563EB;
    color:white;
    padding:10px 18px;
    border-radius:10px;
    text-decoration:none;
    font-weight:500;
    margin-bottom:20px;
    transition:.3s;
}

.back-btn:hover{
    background:#1D4ED8;
}

.detail-card{
    background:white;
    border-radius:22px;
    overflow:hidden;
    box-shadow:0 12px 35px rgba(15,23,42,.08);
}

.detail-header{
    background:linear-gradient(135deg,#2563EB,#3B82F6);
    color:white;
    padding:32px;
}

.detail-header h2{
    margin:0;
    font-size:30px;
}

.detail-header p{
    margin-top:6px;
    opacity:.9;
}

.status-badge{
    display:inline-block;
    margin-top:15px;
    padding:8px 16px;
    border-radius:20px;
    background:rgba(255,255,255,.2);
    font-size:14px;
    font-weight:600;
}

.detail-body{
    padding:30px;
}

.info-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:18px;
    margin-bottom:28px;
}

.info-box{
    background:#F8FAFC;
    border:1px solid #E2E8F0;
    border-radius:16px;
    padding:18px;
}

.info-box span{
    display:block;
    color:#64748B;
    font-size:13px;
    margin-bottom:6px;
}

.info-box strong{
    color:#0F172A;
    font-size:16px;
}

.section{
    margin-top:24px;
}

.section h3{
    font-size:18px;
    margin-bottom:12px;
    color:#0F172A;
}

.content-box{
    background:#F8FAFC;
    border:1px solid #E2E8F0;
    border-radius:16px;
    padding:18px;
    line-height:1.7;
    color:#334155;
}

@media(max-width:768px){

.detail-header{
    padding:22px;
}

.detail-body{
    padding:20px;
}

.info-grid{
    grid-template-columns:1fr;
}

}

</style>

</head>

<body>

<?php include "../includes/navbar.php"; ?>
<?php include "../includes/header.php"; ?>

<div class="dashboard-layout">

<main class="dashboard-content">

<div class="view-container">

<a href="applications.php" class="back-btn">
<i class="fa-solid fa-arrow-left"></i>
Back to Applications
</a>

<div class="detail-card">

<div class="detail-header">

<h2>Application #APP<?= str_pad($row['application_id'],3,'0',STR_PAD_LEFT) ?></h2>

<p><?= htmlspecialchars($row['subject']) ?></p>

<span class="status-badge">
<?= htmlspecialchars($row['status_name']) ?>
</span>

</div>

<div class="detail-body">

<div class="info-grid">

<div class="info-box">
<span>Student</span>
<strong><?= htmlspecialchars($row['full_name']) ?></strong>
</div>

<div class="info-box">
<span>Application Type</span>
<strong><?= htmlspecialchars($row['type_name']) ?></strong>
</div>

<div class="info-box">
<span>Submitted</span>
<strong><?= date('d M Y',strtotime($row['submission_date'])) ?></strong>
</div>

<?php if($row['review_date']): ?>
<div class="info-box">
<span>Review Date</span>
<strong><?= date('d M Y',strtotime($row['review_date'])) ?></strong>
</div>
<?php endif; ?>

</div>

<div class="section">

<h3>Description</h3>

<div class="content-box">
<?= nl2br(htmlspecialchars($row['description'])) ?>
</div>

</div>

<?php if(!empty($row['remarks'])): ?>

<div class="section">

<h3>Authority Remarks</h3>

<div class="content-box">
<?= nl2br(htmlspecialchars($row['remarks'])) ?>
</div>

</div>

<?php endif; ?>

</div>

</div>

</div>

</main>

</div>

<?php include "../includes/footer.php"; ?>

</body>

</html>