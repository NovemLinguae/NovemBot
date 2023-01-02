<?php

class HardCodedSocks {
	static function add($data) {
		// Wikimedia Foundation board of trustees: https://meta.wikimedia.org/wiki/Wikimedia_Foundation_Board_of_Trustees
		$data['boardOfTrustees']['Jimbo Wales'] = 1;
		$data['boardOfTrustees']['Esh77'] = 1; // Shani (WMF)
		$data['boardOfTrustees']['Antanana'] = 1; // NTymkiv (WMF)
		$data['boardOfTrustees']['Pundit'] = 1;
		$data['boardOfTrustees']['Rosiestep'] = 1;
		$data['boardOfTrustees']['Victoria'] = 1;
		$data['boardOfTrustees']['Laurentius'] = 1;
		$data['boardOfTrustees']['Mike Peel'] = 1;

		// Wikimedia Endowment board of directors: https://meta.wikimedia.org/wiki/Wikimedia_Endowment#Wikimedia_Endowment_Advisory_Board_Members
		$data['boardOfTrustees']['Phoebe'] = 1;
		$data['boardOfTrustees']['Patricio.lorente'] = 1;

		// WMF staff's personal accounts
		$data['staff']['Cscott'] = 1; // SAnanian (WMF)
		$data['staff']['The wub'] = 1; // Pcoombe (WMF)
		$data['staff']['Matma Rex'] = 1; // Bartosz Dziewoński (WMF)
		$data['staff']['Aaron Schulz'] = 1; // Aaron Schulz (WMF)
		$data['staff']['Brion VIBBER'] = 1; // Brion Vibber (WMF)
		$data['staff']['Catrope'] = 1; // Roan Kattouw (WMF)
		$data['staff']['Hashar'] = 1; // Amusso (WMF)
		$data['staff']['Seddon'] = 1; // Seddon (WMF)
		$data['staff']['Krinkle'] = 1; // Timo Tijhof (WMF)
		$data['staff']['Ladsgroup'] = 1; // ASarabadani (WMF)
		$data['staff']['Lucas Werkmeister'] = 1; // Lucas Werkmeister (WMDE)
		$data['staff']['Reedy'] = 1; // Reedy (WMF)
		$data['staff']['Tim Starling'] = 1; // Tim Starling (WMF)
		$data['staff']['Addshore'] = 1; // Adam Shorland (WMDE)
		$data['staff']['Xeno'] = 1; // Xeno (WMF)
		$data['staff']['Whatamidoing'] = 1; // Whatamidoing (WMF)
		$data['staff']['Deskana'] = 1; // Deskana (WMF)
		$data['staff']['Jdforrester'] = 1; // Jdforrester (WMF)

		// On the list of former admins, but not highlighted by the two former admin queries
		// TODO: link these to their renames instead
		$data['formeradmin']['168...'] = 1;
		$data['formeradmin']['172'] = 1;
		$data['formeradmin']['1Angela'] = 1;
		$data['formeradmin']['Ævar Arnfjörð Bjarmason'] = 1;
		$data['formeradmin']['Andre Engels'] = 1;
		$data['formeradmin']['Ark30inf'] = 1;
		$data['formeradmin']['Aussie Article Writer'] = 1;
		$data['formeradmin']['Baldhur'] = 1;
		$data['formeradmin']['Blankfaze'] = 1;
		$data['formeradmin']['Cedar-Guardian'] = 1;
		$data['formeradmin']['Chuck Smith'] = 1;
		$data['formeradmin']['Fire'] = 1;
		$data['formeradmin']['Isis~enwiki'] = 1;
		$data['formeradmin']['Jeronim'] = 1;
		$data['formeradmin']['Kate'] = 1;
		$data['formeradmin']['Kils'] = 1;
		$data['formeradmin']['KimvdLinde'] = 1;
		$data['formeradmin']['Koyaanis Qatsi'] = 1;
		$data['formeradmin']['KRS'] = 1;
		$data['formeradmin']['Kyle Barbour'] = 1;
		$data['formeradmin']['Looxix'] = 1;
		$data['formeradmin']['Mentoz86'] = 1;
		$data['formeradmin']['TheCustomOfLife'] = 1;
		$data['formeradmin']['Muriel Gottrop'] = 1;
		$data['formeradmin']['Paul Benjamin Austin'] = 1;
		$data['formeradmin']['Pcb22'] = 1;
		$data['formeradmin']['Rootology'] = 1;
		$data['formeradmin']['SalopianJames'] = 1;
		$data['formeradmin']['Fys'] = 1;
		$data['formeradmin']['Secret (renamed)'] = 1;
		$data['formeradmin']['Sewing'] = 1;
		$data['formeradmin']['Stephen Gilbert'] = 1;
		$data['formeradmin']['StringTheory11'] = 1;
		$data['formeradmin']['Testuser2'] = 1;
		$data['formeradmin']['Vanished user'] = 1;
		$data['formeradmin']['Viridian Bovary'] = 1;
		$data['formeradmin']['User2004'] = 1;
		$data['formeradmin']['Muriel Gottrop~enwiki'] = 1;


		/*
		// https://gerrit.wikimedia.org/r/admin/groups/4cdcb3a1ef2e19d73bc9a97f1d0f109d2e0209cd,members
		$data['mediawikiPlusTwo']['Aaron Schulz'] = 1;
		$data['mediawikiPlusTwo']['Addshore'] = 1;
		$data['mediawikiPlusTwo']['Ammarpad'] = 1;
		$data['mediawikiPlusTwo']['Anomie'] = 1;
		$data['mediawikiPlusTwo']['Aude'] = 1;
		$data['mediawikiPlusTwo']['Awjrichards'] = 1;
		$data['mediawikiPlusTwo']['Matma Rex'] = 1; // Bartosz Dziewoński
		$data['mediawikiPlusTwo']['Brion VIBBER'] = 1;
		$data['mediawikiPlusTwo']['Catrope'] = 1; // Roan Kattouw
		$data['mediawikiPlusTwo']['Daimona Eaytoy'] = 1;
		$data['mediawikiPlusTwo']['DannyS712'] = 1;
		$data['mediawikiPlusTwo']['Glaisher'] = 1;
		$data['mediawikiPlusTwo']['Hashar'] = 1; // Antoine Musso
		$data['mediawikiPlusTwo']['Hoo man'] = 1; // also a steward
		$data['mediawikiPlusTwo']['Huji'] = 1;
		$data['mediawikiPlusTwo']['Jack Phoenix'] = 1;
		$data['mediawikiPlusTwo']['Jackmcbarn'] = 1;
		$data['mediawikiPlusTwo']['JanZerebecki'] = 1;
		$data['mediawikiPlusTwo']['Kaldari'] = 1;
		$data['mediawikiPlusTwo']['Krinkle'] = 1;
		$data['mediawikiPlusTwo']['Ladsgroup'] = 1;
		$data['mediawikiPlusTwo']['Legoktm'] = 1;
		$data['mediawikiPlusTwo']['Lucas Werkmeister'] = 1;
		$data['mediawikiPlusTwo']['Lucas Werkmeister (WMDE)'] = 1;
		$data['mediawikiPlusTwo']['Taavi'] = 1; // Majavah
		$data['mediawikiPlusTwo']['MarkAHershberger'] = 1;
		$data['mediawikiPlusTwo']['Matěj Suchánek'] = 1;
		$data['mediawikiPlusTwo']['MaxSem'] = 1;
		$data['mediawikiPlusTwo']['Mglaser'] = 1;
		$data['mediawikiPlusTwo']['Mvolz'] = 1;
		$data['mediawikiPlusTwo']['Parent5446'] = 1;
		$data['mediawikiPlusTwo']['Platonides'] = 1;
		$data['mediawikiPlusTwo']['PleaseStand'] = 1;
		$data['mediawikiPlusTwo']['Reedy'] = 1;
		$data['mediawikiPlusTwo']['SPQRobin'] = 1;
		$data['mediawikiPlusTwo']['Siebrand'] = 1;
		$data['mediawikiPlusTwo']['TheDJ'] = 1;
		$data['mediawikiPlusTwo']['Thiemo Kreuz (WMDE)'] = 1;
		$data['mediawikiPlusTwo']['Tim Starling'] = 1;
		$data['mediawikiPlusTwo']['Trevor Parscal'] = 1;
		$data['mediawikiPlusTwo']['Umherirrender'] = 1;
		$data['mediawikiPlusTwo']['Martin Urbanec'] = 1;
		$data['mediawikiPlusTwo']['Christoph Jauera (WMDE)'] = 1; //WMDE-Fisch
		$data['mediawikiPlusTwo']['Leszek Manicki (WMDE)'] = 1;
		*/

		return $data;
	}
}