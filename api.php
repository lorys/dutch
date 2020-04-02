<?php
// 4 cartes/per | 2 a memo
	session_start();
	function same_card($card1, $card2) {
		$position_card1 = $card1%13;
		$position_card2 = $card2%13;
		return ($position_card1 === $position_card2);
	}

	function cartes_melange($cards_list) {

		for ($a = 0; $a < 5*13*4; $a++)
		{
			$carte_tmpa = rand(0, count($cards_list)-1);
			$carte_tmpb = rand(0, count($cards_list)-1);
			$tmp = $cards_list[$carte_tmpa];
			$cards_list[$carte_tmpa] = $cards_list[$carte_tmpb];
			$cards_list[$carte_tmpb] = $tmp;
		}
		return $cards_list;
	}

	if (!isset($_SESSION['ingame']) || !isset($_SESSION['name']) || !file_exists("data/gamers/".$_SESSION['name']))
		exit;
	$userData = unserialize(file_get_contents("data/gamers/".$_SESSION['name']));

	if (!isset($userData['echange']))
		$userData['echange'] = false;
	if ($userData['signed'] != $_SESSION['signed']){
		session_destroy();
		exit;
	}
	$userData['co'] = date("U");
	if (file_exists("data/current_game"))
		$datafile = unserialize(file_get_contents("data/current_game"));
	else {
		$datatmp['tour'] = 0;
		$datatmp['public_state_message'] = ""; // Une petite phrase pour résumer la dernière action (ex: Lorys s'est trompé de carte ! Il l'a reprends ainsi qu'une pénalité)
		$datatmp['tour_player'] = 0;
		$datatmp['players'] = array();
		$datatmp['paquet'] = array();
		$datatmp['everybody_ready'] = 0;
		for ($a = 0; $a < 13*4; $a++)
			$datatmp['paquet'][$a] = $a;
		for ($b = 0; $b < 13*4; $b++)
			$datatmp['paquet'][$a+$b] = $b;
		$datatmp['paquet'] = cartes_melange($datatmp['paquet']);
		$datatmp['hist_cartes'] = [$datatmp['paquet'][count($datatmp['paquet'])-1]];
		unset($datatmp['paquet'][count($datatmp['paquet'])-1]);
		$datatmp['running'] = false;
		file_put_contents("data/current_game", serialize($datatmp));
		$datafile = $datatmp;
	}

		if ($datafile['everybody_ready'] == count($datafile['players']) && count($datafile['players']) >= 3)
			$datafile['running'] = true;

	if (array_keys($_GET)[0] == "getdata")
	{
		if (!isset($userData['cartes']))
		{
			for ($a = 0; $a < 4; $a++)
				$userData['cartes'][] = $datafile['paquet'][count($datafile['paquet'])-1-$a];
			array_splice($datafile['paquet'], -4);
		}
		if (array_search($_SESSION['name'], $datafile['players']) === false) {
			$userData['tour_id'] = count($datafile['players']);
			array_push($datafile['players'], $_SESSION['name']);
		}
		file_put_contents("data/gamers/".$_SESSION['name'], serialize($userData));
		file_put_contents("data/current_game", serialize($datafile));
		$datafile['user'] = $userData;
		for ($a = 0; $datafile['players'][$a]; $a++)
		{
			if ($datafile['players'][$a] != $_SESSION['name'])
				$datafile['adv'][$datafile['players'][$a]] = count(unserialize(file_get_contents("data/gamers/".$datafile['players'][$a]))['cartes']);
		}
		echo json_encode($datafile);
	}

	if (array_keys($_GET)[0] == "depose" && $datafile['running'])
	{
		if (isset($datafile['hist_cartes'][count($datafile['hist_cartes'])-1]) && same_card($datafile['hist_cartes'][count($datafile['hist_cartes'])-1], $userData['cartes'][$_GET['depose']])) {
			array_push($datafile['hist_cartes'], $userData['cartes'][$_GET['depose']]);
			unset($userData['cartes'][$_GET['depose']]);
			$userData['cartes'] = array_values($userData['cartes']);
			echo json_encode('ok');
		}
		else {
			$userData['cartes'][] = $datafile['paquet'][count($datafile['paquet'])-1];
			unset($datafile['paquet'][count($datafile['paquet'])-1]);
			$datafile['public_state_message'] = $_SESSION['name']." s'est trompé(e) de carte ! Pénalité !";
			echo json_encode('wrong');
		}
		file_put_contents("data/gamers/".$_SESSION['name'], serialize($userData));
		file_put_contents("data/current_game", serialize($datafile));
	}
	else if (array_keys($_GET)[0] == "echange" && $datafile['running'])
	{
		if ($userData['tour_id'] == $datafile['tour_player'])
		{
			if (!$userData['echange']) {
				$userData['echange'] = true;
				$userData['cartes'][] = $datafile['paquet'][count($datafile['paquet'])-1];
				unset($datafile['paquet'][count($datafile['paquet'])-1]);
				$datafile['public_state_message'] = $_SESSION['name']." a pioché une carte.";
			}
			else if (isset($userData['echange']) && $userData['echange']) {
				$userData['echange'] = false;
				array_push($datafile['hist_cartes'], $userData['cartes'][$_GET['echange']]);
				unset($userData['cartes'][$_GET['echange']]);
				$userData['cartes'] = array_values($userData['cartes']);
				$datafile['tour_player'] = ($datafile['tour_player'] == count($datafile['players'])-1)?0:$datafile['tour_player']+1;
				$datafile['public_state_message'] = $_SESSION['name']." a posé une carte.";
			}
			echo json_encode("ok");
		}
		else {
			echo json_encode('denied');
		}
		file_put_contents("data/gamers/".$_SESSION['name'], serialize($userData));
		file_put_contents("data/current_game", serialize($datafile));
	}
	else if (array_keys($_GET)[0] == "cardseen")
	{
		$datafile['everybody_ready']++;
		$userData['card_seen'] = 70;
		file_put_contents("data/gamers/".$_SESSION['name'], serialize($userData));
		file_put_contents("data/current_game", serialize($datafile));
		echo json_encode("ok");
	}
?>