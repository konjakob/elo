<?php

require_once('includes/application_top.php');
require_once('includes/SBBCodeParser.php');

$codes = array();
$ubbParser = new SBBCodeParser_Document();
foreach ( $ubbParser->list_bbcodes() as $c ) 
	$codes[] = $c;

$breadcrumb[] = array( 'text' => _('Topics'), 'href' => 'topic.php');
$twig_data['codes'] = $codes;
$twig_data['breadcrumb'] = $breadcrumb;
echo $twig->render("faq.twig", $twig_data);