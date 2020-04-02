<?php
	
		// Ici nous mettons les commandes que nous devons envoyer au backend avant que la page ne se charge

	session_start();
		if ($_GET['destroy'] == "game")	// Pour detruire le jeu courrant
		{
			if (file_exists("chat"))
				unlink("chat");
			if (file_exists("data/current_game"))
				unlink("data/current_game");
			$list = glob("data/gamers/*");
			for  ($i = 0; $list[$i]; $i++)
				unlink($list[$i]);
			session_destroy();
			header("location: index.php");
			exit;
		}

	if (!isset($_SESSION['name']) || !isset($_SESSION['ingame'])) // Si la personne n'est pas connectée on la renvoie à la page d'accueil
		header("location: index.php");
	if (isset($_GET['deco'])) { // Permet la deconnexion
		session_destroy(); 		// detruit la session du visiteur
		header("location: index.php");
	}
	$time = date("U"); // Date en secondes depuis le 1 janvier 1970 (appelé timestamp ou unixtimestamp)
	$userdata = (file_exists("data/gamers/".$_SESSION['name']))?unserialize(file_get_contents("data/gamers/".$_SESSION['name'])):array();
		$userdata['co'] = $time; // on sauvegarde l'heure à laquelle le visiteur a donné un dernier signe de vie.
		if (!isset($userdata['signed'])) { // On signe l'échange entre le serveur et la session utilisateur pour etre sur qu'on parle avec la bonne personne
			$userdata['card_seen'] = false;
			$userdata['echange'] = false;
			$userdata['signed'] = $time;
			$_SESSION['signed'] = $time;
		}
		file_put_contents("data/gamers/".$_SESSION['name'], serialize($userdata)); // on enregistre tout ça dans le fichier utilisateur sur le serveur
?>

<html>
	<head>
		<title> -- DUTCH --</title>
		<style>
			#game {
				position: fixed;
				top: 0px;
				left: 0px;
			}

			#chat {
				position: fixed;
				right: 0;
				top: 0;
				width: 25%;
				height: 100%;
				background-color: #e67e22;
			}
			#chat #send_msg {
				position: fixed;
				bottom: 0;
				width: 25%;
				height: 30px;
			}
			#msg {
				font-size: 20px;
				height: 100%;
				width: 80%;
				border: none;
			}
			#chat #send_msg #send {
				width: 20%;
				height: 100%;
				display: inline-block;
				background-color: #2ecc71;
				background-image: url("data/send.png");
				background-position: center;
				background-repeat: no-repeat;
				background-size: auto 90%;
				bottom: 0;
				position: absolute;
				cursor: pointer;
			}

			#messages {
				margin: 30px;
				scroll-behavior: smooth;
				overflow: hidden;
			}

			#messages .message h5 {
				background-color: white;
				padding: 5px;
				margin: 0;
				font-size: 20px;
			}
			#messages .message p {
				margin: 0;
				background-color: rgba(255,255,255,0.4);
				padding: 5px;
				font-size: 20px;
			}
		</style>
		<script>

			// Ici nous allons sauvegarder les informations essentielles
			windowInnerHeight = window.innerHeight;
			windowInnerWidth = window.innerWidth*0.75;
			elements = [];
			help = 0;
			card_selected = 0;
			card_seen = 3;
			show_card = [-1, -1];
			nb_cards = -1;
			public_state_message = "";
			cestaqui = "";
			log = "";
			marginTop_adv = windowInnerHeight*0.05;
			echange = false;
			last_card_id = -1;
			running = 0;
			loading_request = false;


			function action(command, response) { // Cette fonction va permettre le rendu des images
				try {
					response = JSON.parse(response); // on verifie si la réponse est lisible
				}
				catch (e) {
					log += "\n"+e+" = "+response;
					return;
				}
				if (command == 'getdata')						// si la commande est 'getdata' nous allons ajouter les éléments qui devront s'afficher a l'ecran 
				{
					windowInnerHeight = window.innerHeight;
					windowInnerWidth = window.innerWidth*0.75;
					// a partir d'ici on va remplir les variables locale grace aux données du serveur
					running = response['running']; // Pour savoir si la partie a commencé
					elements = new Array();			// on remet à zero les éléments à l'écran et on rempli tout
					public_state_message = response['public_state_message'];
					echange = response['user']['echange'];						// est ce qu'un échange est en cours ?
					cestaqui = response['players'][response['tour_player']];	// c'est a qui ?
					elements.push("Text|Au tour de "+cestaqui+"|30px Arial|"+(windowInnerWidth-300)+"|"+(windowInnerHeight-30)); // On met à jour la phrase "au tour de..."
					players_shown_top = 0; // Nombre de joueurs dans la partie
					for (i = 0; response['players'][i]; i++) { // Cette boucle affiche tout les joueurs en ligne
						if (response['players'][i] == "<?= $_SESSION['name'] ?>") { // Si c'est le joueur connecté, on l'affiche en bas a gauche
							elements.push("Image|"+response['players'][i]+"|0|"+(windowInnerHeight-(windowInnerHeight*0.3))+"|"+(windowInnerHeight*0.3)+"|"+(windowInnerHeight*0.3));
						}
						else { 														// Sinon on affiche en haut
							image_tmp_x = ((players_shown_top*(windowInnerWidth/response['players'].length))+windowInnerWidth*0.02);
							elements.push("Image|"+response['players'][i]+"|"+image_tmp_x+"|"+marginTop_adv+"|"+(windowInnerWidth*0.08)+"|"+(windowInnerWidth*0.08));
							for (p = 0; p < response['adv'][response['players'][i]]; p++)
								elements.push("Carte|54|"+(image_tmp_x+(windowInnerWidth*0.1)+(p*40))+"|"+marginTop_adv+"|30|60");
							players_shown_top++;
						}
					}
					for (i = 0; i < response['user']['cartes'].length; i++)	// On affiche les cartes du joueur connecté
					{
						espace_tmp = (windowInnerHeight*0.15);
						cart_tmp = 54;
						if (show_card[0] == i || show_card[1] == i && !response['user']['echange'])	// Si il n'a pas encore vu les deux cartes
							cart_tmp = response['user']['cartes'][i];
						if (response['user']['echange'] && !response['user']['cartes'][i+1]) {		// Si il a pioché une carte
							cart_tmp = response['user']['cartes'][i];
						}
						elements.push("CarteJoueur|"+cart_tmp+"|"+(((windowInnerHeight*0.3)+(i*espace_tmp)))+"|"+(windowInnerHeight-(espace_tmp*2))+"|"+espace_tmp+"|"+(espace_tmp*2));
					}
					for (i = 0; i < response['paquet'].length; i++)		// Affichage de la pioche 
						elements.push("Carte|54|"+((windowInnerWidth*0.52)+(i*0.1))+"|"+((windowInnerHeight*0.4)+((i*0.1)))+"|"+(windowInnerWidth*0.06)+"|"+(windowInnerWidth*0.06*2));	// on montre les cartes de dos
					for (i = 0; response['hist_cartes'][i]; i++){ // Affichage des dernieres cartes posées
						elements.push("Carte|"+response['hist_cartes'][i]+"|"+(windowInnerWidth*0.45+(i*0.1))+"|"+(windowInnerHeight*0.4+(i*0.1))+"|"+(windowInnerWidth*0.06)+"|"+((windowInnerWidth*0.06)*2));
					}
					if (card_seen == 3)
						card_seen = response['user']['card_seen']; // on demande au serveur si le joueur a deja vu ses cartes
					nb_cards = response['user']['cartes'].length; // on enregistre combien de carte il a (juste au cas ou)
				}
				else if (command == 'depose') {					// Quand tu veux deposer une carte
					if (response != "denied" && response != "wrong") {	// Si la commande passe
						card_selected = (card_selected > nb_cards)?card_selected-1:card_selected; // on selectionne la carte juste avant pour eviter de selectionner une carte qui n'existe pas
					}
				}
			}

			function api(command) {		// fonction permettant de faire des appels au serveur
				co = new XMLHttpRequest();
				co.open("GET", "/api.php?"+command, true);
				co.onload = function () {
					if (co.responseText == "")
						return;
					action(command, co.responseText);
				};
				co.send();
			}

			function getCard(which, x, y, width, height) {	// La fonction qui va découper img/cartes.jpg
				if (which < 0 || which == null)
					return;
				canvas = document.getElementById('game').getContext("2d");
				line_nbr = 0;
				cartes = document.getElementById('cartes');
				a = 0;
				for (i = 0; i < 13*4+3; i++)
				{
					if (a == 13){
						line_nbr++;
						a = 0;
					}
					if (i == which) {
						canvas.drawImage(cartes, a*167.3, line_nbr*243, 170, 245, x, y, width, height);
						break;
					}
					a++;
				}
			}

			function send_chat() {		// Fonction permettant d'envoyer un msg
				chatco_send = new XMLHttpRequest();
				chatco_send.open("GET", "/chat.php?name=<?= $_SESSION['name'] ?>&msg="+document.getElementById('msg').value, true);
				chatco_send.onload= () => {
					document.getElementById('msg').value = "";
				};
				chatco_send.send();
			}

			function formatDate(unix) {	// Fonction pour donner une heure lisible a partir du timestamp
				diff = ((new Date().getTime()/1000) - (unix)); // in seconds
				if (diff > 3600*24) {
					return (new Date(unix*1000).toLocaleTimeString("fr-FR"));
				}
				else if (diff > 3600)
				{
					return ("Il y a "+Math.round(diff/3600)+" heures");
				}
				else if (diff > 60)
				{
					return ("Il y a "+Math.round(diff/60)+" minutes");
				}
				else {
					return ("Il y a "+Math.round(diff)+" secondes");
				}
			}

			function chat() {		// Recuperation des messages 
				document.getElementById('messages').style.height=window.innerHeight-120+"px";
				chatco = new XMLHttpRequest();
				chatco.open("GET", "/chat", true);
				chatco.onload = () => {
					if (chatco.response == "")
						return;
					data = JSON.parse(chatco.response);
					document.getElementById("messages").innerHTML="";
					for (n = 0; data[n]; n++)
					{
						document.getElementById("messages").innerHTML+="<div class='message'><h5>"+data[n]['name']+" - "+formatDate(data[n]['date'])+"</h5><p>"+data[n]['msg']+"</p></div>";
					}
				};
				chatco.send();
				document.getElementById('messages').scrollTo(0, document.getElementById('messages').scrollHeight);
			}

			function fps(frames_per_seconds) {	// Rendu de l'image (Avec les elements donné par action())
				canvas = document.getElementById('game').getContext("2d");
				background = document.getElementById('background');
				canvas.clearRect(0,0,windowInnerWidth, windowInnerHeight);	// on reinitialise l'image
				canvas.drawImage(background, 0, 0, 1254, 836, 0, 0, windowInnerWidth, windowInnerHeight); // on met le fond
				carte_nb = 0;
				for (z = 0; elements.length > z ; z++) // on affiche tout les elements un a un
				{
					datatmp = elements[z].split("|");
					if (datatmp[0] == "Image") { // drawImage format : type|imageID|x|y|width|height
												 // Text format : type|text|font|x|y
							canvas.drawImage(document.getElementById(datatmp[1]), 0, 0, document.getElementById(datatmp[1]).width, document.getElementById(datatmp[1]).height, datatmp[2], datatmp[3], datatmp[4], datatmp[5]);
					}
					else if (datatmp[0] == "Carte") {
						getCard(parseInt(datatmp[1]), parseInt(datatmp[2]), parseInt(datatmp[3]), parseInt(datatmp[4]), parseInt(datatmp[5]));
					}
					else if (datatmp[0] == "CarteJoueur") {
						getCard(parseInt(datatmp[1]), parseInt(datatmp[2]), parseInt(datatmp[3])-((carte_nb == card_selected)?50:0), parseInt(datatmp[4]), parseInt(datatmp[5]));
						carte_nb++;
					}
					else if (datatmp[0] == "Text") {
						canvas.font= datatmp[2];
						canvas.fillText(datatmp[1], datatmp[3], datatmp[4]);
					}
					else {
						console.log("not handled : " + datatmp[0]);
					}
				}
				if (help)
					canvas.drawImage(document.getElementById("help"), 0, 0, document.getElementById("help").width, document.getElementById("help").height, windowInnerWidth*0.45, windowInnerHeight*0.15, windowInnerWidth*0.2, windowInnerHeight*0.6);
				canvas.font= ((windowInnerWidth > windowInnerHeight) ? windowInnerHeight*0.02 : windowInnerWidth*0.02)+"px Arial";
				canvas.fillText("ESC pour se déconnecter", 10, 25);
				if (card_seen < 2)	// Si on a pas vu les deux cartes de debut de partie on affiche les instructions
				{
					canvas.font= ((windowInnerWidth > windowInnerHeight) ? windowInnerHeight*0.04 : windowInnerWidth*0.04)+"px Arial";
					canvas.fillText("Utilisez la touche ↑ ", 50, windowInnerHeight*0.4);
					canvas.fillText("pour voir la carte souhaitée", 50, windowInnerHeight*0.4+35);
					canvas.font= ((windowInnerWidth > windowInnerHeight) ? windowInnerHeight*0.02 : windowInnerWidth*0.02)+"px Arial";
					canvas.fillText("(Reste "+(2-card_seen)+" à voir)", 50, windowInnerHeight*0.4+60);
				}
				canvas.font= (windowInnerHeight*0.03)+"px Arial";
				canvas.fillText(public_state_message, windowInnerWidth*0.1, windowInnerHeight*0.3);
				if (card_selected >= nb_cards)
					card_selected--;
				if (card_selected < 0)
					card_selected++;

			}

			function load() { // on charge tout le jeu
				document.getElementById('loading').style.display='none';
				document.getElementById('game').width=windowInnerWidth;
				document.getElementById('game').height=windowInnerHeight;
				canvas = document.getElementById('game').getContext("2d");
				fps();
				setInterval(() => fps(), 80); // un rendu d'image toutes les 80 millisecondes
				api("getdata");
				setInterval(() => api("getdata"), 500+Math.round(Math.random() * 500)); // un appel au serveur toutes les 500/1000 millisecondes. Pour éviter que tout le monde appel le serveur en meme temps le delai est aléatoire
				setInterval(() => chat(), 1000);		// Un appel toutes les secondes pour savoir les messages envoyé
			}


			function command(code) {	// Gestion des touches appuyées 
				if (code == 'ArrowRight')
					card_selected = (card_selected < nb_cards-1)?card_selected+1:card_selected;
				else if (code == 'ArrowLeft')
					card_selected = (card_selected > 0)?card_selected-1:card_selected;
				else if (code == 'ArrowUp')
				{
					if (card_seen < 2)
					{
						show_card[(show_card[0] != -1)?1:0] = card_selected;
						card_seen++;
						if (card_seen == 2) {
							api("cardseen");
							setTimeout(function () {
								show_card[0] = -1;
								show_card[1] = -1;
							}, 4000);
						}
					}
					else if (nb_cards > 0) {
						if (!echange)
							api("depose="+card_selected);
						else
							api("echange="+card_selected);
					}
				}
				else if (code == 'ArrowDown' && !echange)
					api("echange");
				else if (code == 'Escape')
					self.location.href='/game.php?deco';
				else {
					help = 1;
					console.log(code);
				}
			}
		</script>
	</head>
	<body onLoad="load();" onresize="document.getElementById('game').width=windowInnerWidth; document.getElementById('game').height=windowInnerHeight;" onKeyDown="command(event.key);" onKeyUp="help = 0;">
		<div id="loading" style="z-index: 999; background-color: white; font-size: 40px; text-align: center; padding-top: 10%">CALMEZ VOUS ÇA CHARGE<br><img src="data/arrow_keys.jpg" style="width: 200px;"/><br>
			<b>C'est tout ce dont vous avez besoin !</b>
		</div>
		<div style="display: none;">
			<img src="data/cartes.png" id="cartes"/>
			<img src="data/tuto.jpg" id="help"/>
			<img src="data/background.png" id="background"/>
			<?php
			$list_members = glob("img/*.jpg");
				for ($a = 0; $list_members[$a]; $a++)
				{
					?><img src="<?= $list_members[$a] ?>" id="<?= str_replace("img/", "", str_replace(".jpg", "", $list_members[$a])); ?>"/><?php
				}
			?>
		</div>
		<canvas id="game"></canvas>
		<?php
			if ($_SESSION['name'] == "Lorys")
			{
				?>
					<button onClick="self.location.href='?destroy=game';" style="position: fixed; top: 0px; left: 0px;">Destroy game</button>
				<?php
			}
		?>
		<div id="chat">
			<div id="messages"></div>
			<div id="send_msg">
				<input type="text" id="msg" placeholder="Écrivez quelque chose...." /><div id="send" onClick="send_chat();"></div>
			</div>

		</div>
	</body>
</html>