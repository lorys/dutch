<?php
session_start();
	if (isset(array_keys($_GET)[0]))
	{
		$_SESSION['ingame'] = true;
		$_SESSION['name'] = array_keys($_GET)[0];
		header("location: game.php");
	}
	if (isset($_SESSION['ingame']))
		header("location: game.php");
	$list_members = glob("img/*.jpg");
?><html>
	<head>
		<title>DUTCH AVEC LES COPAING</title>
		<script>
			message = 0;
			function change_message() {
					document.getElementById('message').innerHTML=(message)?'WESH LES SANGS !':"CLIQUES-TOI D'SSUS MON PAUVRE !";
					message = !message;
			}
		</script>
		<style>
			@font-face {
				src: url("data/FunSized.ttf");
				font-family: "funsized";
			}
			body {
				background-color: #e67e22;
			}
			#message {
				text-align: center;
				margin-top: 5%;
				font-family: funsized;
			}
			.choose {
				width: 100%;
				text-align: center;
				margin-top: 5%;
			}
			.profile {
				width: <?= 95/count($list_members) ?>%;
				background-position: center;
				background-size: cover;
				background-repeat: no-repeat;
				height: 50%;
				margin: auto;
				display: inline-block;
				transition: 1s;
				cursor: pointer;
			}
			.profile:hover {
				transform: scale(1.3);
			}
			.online {
				height: 30px;
				background-color: rgba(46, 204, 113,1);
				position: absolute;
				width: <?= 95/count($list_members) ?>%;
				color: white;
				font-style: bold;
				font-size: 30px;
				padding-top: 10px;
				padding-bottom: 10px;
			}
		</style>
	</head>
	<body onLoad="change_message(); setInterval(() => change_message(), 5000);">
	<?php

				if (file_exists("data/current_game") && unserialize(file_get_contents("data/current_game"))['running'])
				{
					?>
						<div style="
								background-color: rgba(255,255,255,0.5); 
								position: fixed; 
								top: 0; 
								left: 0; 
								width: 100%; 
								height: 100%;">
							<h1 style="text-align: center;">Une partie est déjà en cours...</h1>
						</div>		
					<?php
				}
	
			?>
			<h1 id="message">CHOISISSEZ VOTRE CHAMPION !</h1>
			<div class="choose">
				<?php
					for ($a = 0; $list_members[$a]; $a++)
					{
						?>
							<div style="background-image: url('<?= $list_members[$a] ?>');" class="profile" onClick="self.location.href='index.php?<?= str_replace("img/", "", str_replace(".jpg", "", $list_members[$a])) ?>';">
								<?php
							if (file_exists("data/gamers/".str_replace("img/", "", str_replace(".jpg", "", $list_members[$a]))))
							{
								$data = unserialize(file_get_contents("data/gamers/".str_replace("img/", "", str_replace(".jpg", "", $list_members[$a]))))['co'];
								if ((date("U")-$data) < 20)
								{
									?>
										<div class="online">En ligne</div>
									<?php
								}
							}
								?></div>
						<?php
					}
				?>
			</div>
	</body>
</html>