<?php
require_once "src/lib/teng.php";

# Data pro šablony
$data["fragments"] = array();
$data["fragments"]["companies"] = array(
	array("name" => "Elza & Co."),
	array("name" => "Microsoft"),
	array("name" => "Apple")
);

# Inicializace Tengu
$teng = new Teng(
	$data,
	array(
		"templPath" => "./templ/",
		"file" => "example.html",
		"content_type" => "text/html",
		"encoding" => "utf-8",
		"dict" => "teng-cz.dict",
		"config" => "teng.conf"
	)
);
?>
