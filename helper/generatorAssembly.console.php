<?php
defined('ROOT_TOP_PATH') ? '' : define("ROOT_TOP_PATH", dirname(__FILE__));
include_once (ROOT_TOP_PATH . "/../core/xutil/GeneratorAssemblyProcess.class.php");

$assemblyFileName = ROOT_TOP_PATH . "/Assembly.php";

$includepaths = array();
$includepaths[] = ROOT_TOP_PATH . "/../core";
$includepaths[] = ROOT_TOP_PATH . "/commands";

$notincludepaths = array();
$notincludepaths[] = ROOT_TOP_PATH . "/../core/util/simpletest";
$notincludepaths[] = ROOT_TOP_PATH . "/../core/tools";

$process = new GeneratorAssemblyProcess($assemblyFileName, $includepaths, $notincludepaths);
$process->dowork();