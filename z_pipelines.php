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

/**
 * Fonction Page automatique a partir de contenu/page-xx
 *
 * @param array $flux
 * @return array
 */
function Z_styliser($flux){
	$z_blocs = isset($GLOBALS['z_blocs'])?$GLOBALS['z_blocs']:array('contenu','navigation','extra','head');
	$contenu = array_shift($z_blocs); // contenu par defaut

	$squelette = $flux['data'];
	if (!$squelette // non trouve !
		AND $fond = $flux['args']['fond']
		AND $ext = $flux['args']['ext']){

		// si on est sur un ?page=XX non trouve
	  if ($flux['args']['contexte'][_SPIP_PAGE] == $fond) {
			// si c'est un objet spip, associe a une table, utiliser le fond homonyme
			if (z_scaffoldable($fond)){
				$flux['data'] = substr(find_in_path("objet.$ext"), 0, - strlen(".$ext"));
			}
			// sinon, si brancher sur contenu/page-xx si elle existe
			else {
				$base = "$contenu/page-".$fond.".".$ext;
				if ($base = find_in_path($base)){
					$flux['data'] = substr(find_in_path("page.$ext"), 0, - strlen(".$ext"));
				}
			}
		}

		// scaffolding :
		// si c'est un fond de contenu d'un objet en base
		// generer un fond automatique a la volee pour les webmestres
		elseif (strncmp($fond, "$contenu/", strlen($contenu)+1)==0
			AND include_spip('inc/autoriser')
			AND isset($GLOBALS['visiteur_session']['id_auteur']) // performance
			AND autoriser('webmestre')){
			$type = substr($fond,strlen($contenu)+1);
			if ($is = z_scaffoldable($type))
				$flux['data'] = z_scaffolding($type,$is[0],$is[1],$is[2],$ext);
		}

		// sinon, si on demande un fond non trouve dans un des autres blocs
		// et si il y a bien un contenu correspondant ou scaffoldable
		// se rabbatre sur le dist.html du bloc concerne
		else{
			if ( $dir = explode('/',$fond)
				AND $dir = reset($dir)
				AND in_array($dir,$z_blocs)){
				$type = substr($fond,strlen("$dir/"));
				if (find_in_path("$contenu/$type.$ext") OR z_scaffoldable($type))
					$flux['data'] = substr(find_in_path("$dir/dist.$ext"), 0, - strlen(".$ext"));
			}
		}
	}
	// chercher le fond correspondant a la composition
	if (isset($flux['args']['contexte']['composition'])
	  AND $fond = $flux['args']['fond']
	  AND $ext = $flux['args']['ext']
	  AND substr($flux['data'],-strlen($fond))==$fond
	  AND $f=find_in_path($fond."-".$flux['args']['contexte']['composition'].".$ext")){
		$flux['data'] = substr($f,0,-strlen(".$ext"));
	}
	return $flux;
}

/**
 * Tester si un type est scaffoldable
 * cad si il correspond bien a un objet en base
 * 
 * @staticvar array $scaffoldable
 * @param string $type
 * @return bool
 */
function z_scaffoldable($type){
	static $scaffoldable = array();
	if (isset($scaffoldable[$type]))
		return $scaffoldable[$type];
	if ($table = table_objet($type)
	  AND $type == objet_type($table)
	  AND $trouver_table = charger_fonction('trouver_table','base')
	  AND
		($desc = $trouver_table($table_sql = table_objet_sql($type))
		OR $desc = $trouver_table($table_sql = "spip_$table"))
		)
		return $scaffoldable[$type] = array($table,$table_sql,$desc);
	else
		return $scaffoldable[$type] = false;
}


/**
 * Generer a la volee un fond a partir d'une table de contenu
 *
 * @param string $type
 * @param string $table
 * @param string $table_sql
 * @param array $desc
 * @param string $ext
 * @return string
 */
function z_scaffolding($type,$table,$table_sql,$desc,$ext){
	include_spip('public/interfaces');
	$primary = id_table_objet($type);
	if (!$primary AND isset($desc['key']["PRIMARY KEY"])){
		$primary = $desc['key']["PRIMARY KEY"];
	}

	// reperer un titre
	$titre = 'titre';
	if (isset($GLOBALS['table_titre'][$table])){
		$titre = explode(' ',$GLOBALS['table_titre'][$table]);
		$titre = explode(',',reset($titre));
		$titre = reset($titre);
	}
	if (isset($desc['field'][$titre])){
		unset($desc['field'][$titre]);
		$titre="<h1 class='h1 #EDIT{titre}'>#".strtoupper($titre)."</h1>";
	}
	else $titre="";

	// reperer une date
	$date = "date";
	if (isset($GLOBALS['table_date'][$table]))
		$date = $GLOBALS['table_date'][$table];
	if (isset($desc['field'][$date])){
		unset($desc['field'][$date]);
		$date = strtoupper($date);
		$date="<p class='info-publi'>[(#$date|nom_jour) ][(#$date|affdate)][, <span class='auteurs'><:par_auteur:> (#LESAUTEURS)</span>]</p>";
	}
	else $date = "";

	$content = array();
	foreach($desc['field'] as $champ=>$z){
		if (!in_array($champ,array('maj','statut','idx',$primary))){
			$content[] = "[<div class='#EDIT{".$champ."} $champ'>(#".strtoupper($champ)."|image_reduire{500,0})</div>]";
		}
	}
	$content = implode("\n\t",$content);

	$scaffold = "#CACHE{0}
<BOUCLE_contenu($table_sql){".$primary."}>
[(#REM) Fil d'Ariane ]
<p id='hierarchie'><a href='#URL_SITE_SPIP/'><:accueil_site:></a>[ &gt; <strong class='on'>(#TITRE|couper{80})</strong>]</p>

<div class='contenu-principal'>
	<div class='cartouche'>
		$titre
		$date
	</div>

	$content

</div>

[<div class='notes surlignable'><h2 class='h2 pas_surlignable'><:info_notes:></h2>(#NOTES)</div>]
</BOUCLE_contenu>";

	$dir = sous_repertoire(_DIR_CACHE,"scaffold",false);
	$dir = sous_repertoire($dir,"contenu",false);
	$f = $dir."$type";
	ecrire_fichier("$f.$ext",$scaffold);
	return $f;
}



/**
 * Surcharger les intertires avant que le core ne les utilise
 * pour y mettre la class h3
 * une seule fois suffit !
 *
 * @param string $flux
 * @return string
 */
function Z_pre_propre($flux){
	static $init = false;
	if (!$init){
		$intertitre = $GLOBALS['debut_intertitre'];
		$class = extraire_attribut($GLOBALS['debut_intertitre'],'class');
		$class = ($class ? " $class":"");
		$GLOBALS['debut_intertitre'] = inserer_attribut($GLOBALS['debut_intertitre'], 'class', "h3$class");
		foreach($GLOBALS['spip_raccourcis_typo'] as $k=>$v){
			$GLOBALS['spip_raccourcis_typo'][$k] = str_replace($intertitre,$GLOBALS['debut_intertitre'],$GLOBALS['spip_raccourcis_typo'][$k]);
		}
		$init = true;
	}
	return $flux;
}

/**
 * Ajouter le inc-insert-head du theme si il existe
 *
 * @param string $flux
 * @return string
 */
function Z_insert_head($flux){
	if (find_in_path('inc-insert-head.html')){
		$flux .= recuperer_fond('inc-insert-head',array());
	}
	return $flux;
}

//
// fonction standard de calcul de la balise #INTRODUCTION
// mais retourne toujours dans un <p> comme propre
//
// http://doc.spip.org/@filtre_introduction_dist
function filtre_introduction($descriptif, $texte, $longueur, $connect) {
	// Si un descriptif est envoye, on l'utilise directement
	if (strlen($descriptif))
		return propre($descriptif,$connect);

	// Prendre un extrait dans la bonne langue
	$texte = extraire_multi($texte);

	// De preference ce qui est marque <intro>...</intro>
	$intro = '';
	$texte = preg_replace(",(</?)intro>,i", "\\1intro>", $texte); // minuscules
	while ($fin = strpos($texte, "</intro>")) {
		$zone = substr($texte, 0, $fin);
		$texte = substr($texte, $fin + strlen("</intro>"));
		if ($deb = strpos($zone, "<intro>") OR substr($zone, 0, 7) == "<intro>")
			$zone = substr($zone, $deb + 7);
		$intro .= $zone;
	}
	$texte = $intro ? $intro : $texte;

	// On ne *PEUT* pas couper simplement ici car c'est du texte brut, qui inclus raccourcis et modeles
	// un simple <articlexx> peut etre ensuite transforme en 1000 lignes ...
	// par ailleurs le nettoyage des raccourcis ne tient pas compte des surcharges
	// et enrichissement de propre
	// couper doit se faire apres propre
	//$texte = nettoyer_raccourcis_typo($intro ? $intro : $texte, $connect);

	// ne pas tenir compte des notes ;
	// bug introduit en http://trac.rezo.net/trac/spip/changeset/12025
	$mem = array($GLOBALS['les_notes'], $GLOBALS['compt_note'], $GLOBALS['marqueur_notes'], $GLOBALS['notes_vues']);
	// memoriser l'etat de la pile unique
	$mem_unique = unique('','_spip_raz_');


	$texte = propre($texte,$connect);


	// restituer les notes comme elles etaient avant d'appeler propre()
	list($GLOBALS['les_notes'], $GLOBALS['compt_note'], $GLOBALS['marqueur_notes'], $GLOBALS['notes_vues']) = $mem;
	// restituer l'etat de la pile unique
	unique($mem_unique,'_spip_set_');


	@define('_INTRODUCTION_SUITE', '&nbsp;(...)');
	$texte = couper($texte, $longueur, _INTRODUCTION_SUITE);

	// Fermer les paragraphes ; mais ne pas en creer si un seul
	$texte = paragrapher($texte, $GLOBALS['toujours_paragrapher']);


	return $texte;
}

/**
 * Tester la presence sur une page
 * @param <type> $p
 * @return <type>
 */
function balise_SI_PAGE_dist($p) {
	$_page = interprete_argument_balise(1,$p);
	$p->code = "(((\$Pile[0][_SPIP_PAGE]==(\$zp=$_page)) OR (\$Pile[0]['composition']==\$zp AND \$Pile[0]['type']=='page'))?' ':'')";
	$p->interdire_scripts = false;
	return $p;
}

?>