<?php

$user = $_POST["name"];
$ldap_pass = $_POST["pass"];

sanitize($user);
sanitize($ldap_pass);

$ldap_adr = "ldap://test-vm";
$ldap_usr = "uid=$user,ou=People,dc=nodomain";

//connect to ldap
$ldap_con = ldap_connect($ldap_adr);
if (!$ldap_con) {
    redirect("login.html","LDAP connection failed");
}

ldap_set_option($ldap_con, LDAP_OPT_PROTOCOL_VERSION, 3); //we need this for some reason. otherwise LDAP_OPT_PROTOCOL_VERSION returns 17 for whatever reason

//bind user/pass to ldap server
$ldap_bind = ldap_bind($ldap_con, $ldap_usr, $ldap_pass);
if ($ldap_bind) {
    session_start();
    $_SESSION["user"] = $user;
    audit_log($_SESSION["user"] . " logIN");
    header("Location: main"); // this is one of those PHP things. this is a strict syntax we need for apache to properly redirect our program
} else {
    ldap_close($ldap_con);
    audit_log("Attempt to log into $user");
    redirect("login","Invalid username and/or password");
}

function sanitize(&$data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
}

function redirect($site, $error)
{
    $error_msg = urlencode($error);
    header("Location: $site?error=$error_msg");
}

function audit_log($message) {
    $file = '/var/www/log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = $timestamp . ' - ' . $message . PHP_EOL;

    file_put_contents($file, $logMessage, FILE_APPEND | LOCK_EX);
}
?>