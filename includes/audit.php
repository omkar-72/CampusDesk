<?php

/*
|--------------------------------------------------------------------------
| CampusDesk - Audit Log Helper
|--------------------------------------------------------------------------
| Purpose:
|   Provides a common function for recording important user actions
|   performed by Student, Authority, or Admin.
|
| Audit Log Table:
|   audit_logs
|
| Columns:
|   audit_id
|   user_id
|   module_type
|   reference_id
|   action
|   ip_address
|   created_at
|--------------------------------------------------------------------------
*/


/**
 * Get the client's IP address.
 *
 * @return string|null
 */
function getClientIpAddress()
{
    if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
        $parts = explode(",", $_SERVER["HTTP_X_FORWARDED_FOR"]);

        return trim($parts[0]);
    }

    if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
        return trim($_SERVER["HTTP_CLIENT_IP"]);
    }

    if (!empty($_SERVER["REMOTE_ADDR"])) {
        return trim($_SERVER["REMOTE_ADDR"]);
    }

    return null;
}


/**
 * Add an entry to the audit_logs table.
 *
 * @param mixed  $conn
 * @param int    $user_id
 * @param string $module_type
 * @param int    $reference_id
 * @param string $action
 *
 * @return bool
 */
function addAuditLog(
    $conn,
    $user_id,
    $module_type,
    $reference_id,
    $action
) {
    if (!$conn) {
        return false;
    }

    /*
     * Validate user ID.
     */
    if (!is_numeric($user_id)) {
        return false;
    }

    /*
     * Validate reference ID.
     *
     * audit_logs.reference_id is NOT NULL.
     */
    if (!is_numeric($reference_id)) {
        return false;
    }

    $user_id = (int) $user_id;
    $reference_id = (int) $reference_id;

    /*
     * Normalize text values.
     */
    $module_type = strtoupper(trim((string) $module_type));
    $action = strtoupper(trim((string) $action));

    if ($module_type === "" || $action === "") {
        return false;
    }

    /*
     * Get client IP address.
     */
    $ip_address = getClientIpAddress();

    /*
     * Validate IP address.
     *
     * Invalid IP addresses are stored as NULL.
     */
    if (
        $ip_address !== null &&
        filter_var($ip_address, FILTER_VALIDATE_IP) === false
    ) {
        $ip_address = null;
    }

    /*
     * Insert audit record.
     *
     * pg_query_params() safely passes values
     * to PostgreSQL.
     */
    $query = "
        INSERT INTO audit_logs
        (
            user_id,
            module_type,
            reference_id,
            action,
            ip_address
        )
        VALUES
        (
            $1,
            $2,
            $3,
            $4,
            $5
        )
    ";

    $result = pg_query_params(
        $conn,
        $query,
        [
            $user_id,
            $module_type,
            $reference_id,
            $action,
            $ip_address
        ]
    );

    return $result !== false;
}
