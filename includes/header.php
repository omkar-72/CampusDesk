<?php

/* =========================================================
   COMMON HEADER
========================================================= */

$role_name = $_SESSION["role_name"] ?? "USER";

$header_user_name = "User";

/* =========================================================
   GET LOGGED-IN USER NAME
========================================================= */

if (
    isset($conn) &&
    isset($_SESSION["user_id"])
) {

    $header_user_id = (int) $_SESSION["user_id"];

    $header_query = "
        SELECT
            r.role_name,
            s.full_name AS student_name,
            a.name AS authority_name
        FROM users u

        INNER JOIN roles r
            ON u.role_id = r.role_id

        LEFT JOIN students s
            ON u.user_id = s.user_id

        LEFT JOIN authorities a
            ON u.user_id = a.user_id

        WHERE u.user_id = $1

        LIMIT 1
    ";

    $header_result = pg_query_params(
        $conn,
        $header_query,
        [$header_user_id]
    );

    if (
        $header_result &&
        pg_num_rows($header_result) > 0
    ) {

        $header_user = pg_fetch_assoc(
            $header_result
        );

        /*
         * Student Name
         */
        if (
            $header_user["role_name"] === "STUDENT" &&
            !empty($header_user["student_name"])
        ) {

            $header_user_name =
                $header_user["student_name"];

            /*
         * Authority / Admin Name
         */
        } elseif (
            (
                $header_user["role_name"] === "AUTHORITY" ||
                $header_user["role_name"] === "ADMIN"
            ) &&
            !empty($header_user["authority_name"])
        ) {

            $header_user_name =
                $header_user["authority_name"];
        }
    }
}

/* =========================================================
   PROFILE LINK
   Role is used internally only.
   It is NOT displayed in the header.
========================================================= */

$header_profile_link = "#";

switch ($role_name) {

    case "STUDENT":

        $header_profile_link =
            "../student/profile.php";

        break;

    case "AUTHORITY":

        $header_profile_link =
            "../authority/profile.php";

        break;

    case "ADMIN":

        $header_profile_link =
            "../admin/profile.php";

        break;
}

/* =========================================================
   PROFILE AVATAR
========================================================= */

$header_avatar_letter =
    strtoupper(
        substr(
            trim($header_user_name),
            0,
            1
        )
    );

if ($header_avatar_letter === "") {

    $header_avatar_letter = "U";
}

?>

<header class="top-header">

    <div class="header-left">

        <h2>
            CampusDesk
        </h2>

    </div>


    <div class="header-right">

        <a
            href="<?php echo htmlspecialchars($header_profile_link); ?>"
            class="profile-box"
            title="Open Profile">

            <div class="profile-avatar">

                <?php
                echo htmlspecialchars(
                    $header_avatar_letter
                );
                ?>

            </div>


            <div class="profile-text">

                <span>
                    <?php
                    echo htmlspecialchars(
                        $header_user_name
                    );
                    ?>
                </span>

            </div>

        </a>

    </div>

</header>
