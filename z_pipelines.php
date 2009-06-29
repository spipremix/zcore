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

	$squelette = $flux['data'];
	if (!$squelette // non trouve !
		AND $fond = $flux['args']['fond']
		AND $ext = $flux['args']['ext']
	  AND $flux['args']['contexte'][_SPIP_PAGE] == $fond) {
		$base = "contenu/page-".$fond.".".$ext;
		if ($base = find_in_path($base)){
			$flux['data'] = substr(find_in_path("page.$ext"), 0, - strlen(".$ext"));
		}
	}

	return $flux;

}

?>