<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2009                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

function Z_styliser($flux){

	/*
	// les pages non implementees mais qui ont un contenu connu, passent par page.html
	@define('_SPIP_PAGE','page');
	if (isset($_GET[_SPIP_PAGE])
	 AND $page = $_GET[_SPIP_PAGE]
	 AND !find_in_path("$page.html")
	 AND find_in_path("contenu/page-$page.html")) {
		$_GET['presentation'] = $page;
		$_GET[_SPIP_PAGE] = 'page';
	}


	// pipeline styliser
	$squelette = pipeline('styliser', array(
		'args' => array(
			'id_rubrique' => $id_rubrique,
			'ext' => $ext,
			'fond' => $fond,
			'lang' => $lang,
			'connect' => $connect
		),
		'data' => $squelette,
	));*/

	return $flux;

}

?>