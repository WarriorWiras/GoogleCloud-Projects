<?php
session_start();
$_SESSION["dismiss_2fa_prompt"] = true; // Dismiss only lasts for one session; will encourage the user to set it up the next time he logs in
header("Location: index.php");
exit();