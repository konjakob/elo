<?php

require('includes/application_top.php');
require_once('SBBCodeParser.php');

$codes = array();
$ubbParser = new SBBCodeParser_Document();
foreach ( $ubbParser->list_bbcodes() as $c ) 
	$codes[] = $c;

$breadcrumb[] = array( 'text' => 'Topics', 'href' => 'topic.php');
$twig_data['codes'] = $codes;
$twig_data['breadcrumb'] = $breadcrumb;
echo $twig->render("faq.twig", $twig_data);