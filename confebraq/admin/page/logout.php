<?php
session_start();
unset($_SESSION[usuario], $_SESSION[admin]);
session_destroy();
?>
<script>location.href='../index.php';</script>