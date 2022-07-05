<?php
require 'steamauth/steamauth.php';

?>
<html>

	<head>
		<title>Steam Watch</title>
		<script src="https://kit.fontawesome.com/d52228e122.js" crossorigin="anonymous"></script>
		<link rel="stylesheet" type="text/css" href="/css/main.css?<?php echo rand(0,10000000); ?>">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
		<link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
		<link rel="icon" type="image/x-icon" href="/favicon.ico">


		<script defer>
		let playerData;
        document.onclick = function(){
            if(document.activeElement === document.getElementById("steamid")){
                document.getElementById("steamid").classList.remove("error");
                $('#errorText').fadeOut();
            }
        }
		
		function setCookie(cname, cvalue, exdays) {
		  const d = new Date();
		  d.setTime(d.getTime() + (exdays*24*60*60*1000));
		  let expires = "expires="+ d.toUTCString();
		  document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
		}
		
		function getCookie(cname) {
		  let name = cname + "=";
		  let decodedCookie = decodeURIComponent(document.cookie);
		  let ca = decodedCookie.split(';');
		  for(let i = 0; i <ca.length; i++) {
			let c = ca[i];
			while (c.charAt(0) == ' ') {
			  c = c.substring(1);
			}
			if (c.indexOf(name) == 0) {
			  return c.substring(name.length, c.length);
			}
		  }
		  return "";
		}
		
		function vote(vote_type) {
			const xhttp = new XMLHttpRequest();
			xhttp.onload = function() {
				
				let response = this.responseText;

				if(response == "-2"){
					window.location.href = "/index.php?login";
					return;
				}

				let votes = response.split("|");
				document.getElementById("data-positive").innerHTML = "Positive - " + votes[0] + "x";
                document.getElementById("data-cheater").innerHTML = "Cheater - " + votes[1] + "x";
                document.getElementById("data-scammer").innerHTML = "Scammer - " + votes[2] + "x";
				
				if(vote_type == 1){
					if(document.getElementById("votePositive").style.color == "lime"){
						document.getElementById("votePositive").style.color = "black";
					}else{
						document.getElementById("votePositive").style.color = "lime";
						document.getElementById("voteScammer").style.color = "black";
						document.getElementById("voteCheater").style.color = "black";
					}
				}else if(vote_type == 2){
					if(document.getElementById("voteCheater").style.color == "darkorange"){
						document.getElementById("voteCheater").style.color = "black";
					}else{
						document.getElementById("voteCheater").style.color = "darkorange";
						document.getElementById("voteScammer").style.color = "black";
						document.getElementById("votePositive").style.color = "black";
					}
				}else if(vote_type == 3){
					if(document.getElementById("voteScammer").style.color == "red"){
						document.getElementById("voteScammer").style.color = "black";
					}else{
						document.getElementById("voteScammer").style.color = "red";
						document.getElementById("voteCheater").style.color = "black";
						document.getElementById("votePositive").style.color = "black";

					}
				}
			}
			xhttp.open("GET", "vote.php?steamid64=" + document.getElementById("steamID64").innerHTML + "&type=" + vote_type, true);
			xhttp.send();
		}

		function searchID(steamid = "-1", force = false) {
			if(steamid === document.getElementById("steamID64").innerHTML && !force)
				return;
			if(!force){
				$('#contentBox').slideUp();
				document.getElementById("steamid").classList.remove("error");
				$('#errorText').fadeOut();
			}
			const xhttp = new XMLHttpRequest();
			xhttp.onload = function() {

                    
                    if(this.responseText == "-1"){
                        document.getElementById("steamid").classList.add("error");
                        $('#contentBox').hide();
                        $('#errorText').fadeIn();
                        return;
                    }
                    playerData = JSON.parse(this.responseText);

                    document.getElementById("steamID").innerHTML = playerData["Steam2"];
                    document.getElementById("steamID3").innerHTML = playerData["Steam3"];
                    document.getElementById("steamID64").innerHTML = playerData["Steam64"];
                    document.getElementById("Profile").innerHTML = playerData["Profile"];
                    document.getElementById("Profile").href = playerData["Profile"];
                    document.getElementById("ProfileCreated").innerHTML = playerData["ProfileCreated"];
                    document.getElementById("RealName").innerHTML = playerData["RealName"];
                    document.getElementById("Location").innerHTML = playerData["Location"];
                    document.getElementById("Avatar").src = playerData["AvatarMedium"];
                    document.getElementById("Username").innerHTML = playerData["Name"];
					
					document.getElementById("data-positive").innerHTML = "Positive - " + playerData["Positive"] + "x";
                    document.getElementById("data-cheater").innerHTML = "Cheater - " + playerData["Cheater"] + "x";
                    document.getElementById("data-scammer").innerHTML = "Scammer - " + playerData["Scammer"] + "x";
					
					
					if(playerData["LocalVoted"] == 1){
						document.getElementById("votePositive").style.color = "lime";
					}else if(playerData["LocalVoted"] == 2){
						document.getElementById("voteCheater").style.color = "darkorange";
					}else if(playerData["LocalVoted"] == 3){
						document.getElementById("voteScammer").style.color = "red";
					}else{
						document.getElementById("votePositive").style.color = "black";
						document.getElementById("voteCheater").style.color = "black";
						document.getElementById("voteScammer").style.color = "black";
					}
					
                    if(playerData["Online"] == "Online"){
                        document.getElementById("Avatar").style = "border: 2px solid #75b9de;";
                    }

                    $('#contentBox').slideDown();
                    
			}
          if(steamid === "-1"){
		    xhttp.open("GET", "resolveSteamId.php?input=" + document.getElementById("steamid").value, true);
          }else{
             xhttp.open("GET", "resolveSteamId.php?input=" + steamid, true);

          }
		  xhttp.send();
		}
       
		</script>
	</head>
	
	<body>
        <p class="header">FIND AND CONVERT YOUR STEAM HEX, STEAMID, STEAMID3, STEAMID64, CUSTOMURL AND COMMUNITY ID</p>
		<div class="input-box">
			<p class="heading">Enter a SteamID, SteamID3, SteamID64, custom URL or complete URL</p>
			<input id="steamid" type="text"/>
            <span id="errorText" style="color: red; margin-top: 5px; display:none;">No profile found.</span>
			<p><button id="btnSend" onclick="searchID()">Search</button>    
            <?php
            if(!isset($_SESSION['steamid'])) {
				

                ?>
				<a href="/index.php?login" class="lgnSteam">.. or Login using Steam</a></p>
				<?php

            }  else {

                include ('steamauth/userInfo.php'); //To access the $steamprofile array
                //Protected content
				$usersteamid = $steamprofile['steamid'];
				$username = $steamprofile['personaname'];
				echo "<a onclick='searchID(\"$usersteamid\")' style='user-select: none;white-space: nowrap;display: inline-block;margin-right: 30px;font-size:125%;' class='lgnSteam'>$username</a>";
				?>
				<i style="position: relative;right: -510px; cursor:hand;" id="dropDownBtn" class="fa-solid fa-angle-down dropdown"></i></p>
				<form action='' method='get'>
					<button name='logout' id="logOutBtn" class="dropDown" type='submit'>Sign out  <i class="fa-solid fa-arrow-right-from-bracket"></i></button>
				</form>
				
				<script>
					document.getElementById("dropDownBtn").onclick = function(){
						$('#logOutBtn').fadeToggle(100);
						
					}
				</script>
				<?php
            }     
            ?>
		</div>
        <div id="contentBox" class="content">
            <div class="data-splitter">
                    <img id="Avatar" src="https://avatars.cloudflare.steamstatic.com/69b25db3192888af24f7aa9e081fb8c09bd8a59b_full.jpg" class="data-avatar"><p id="Username" class="data-username">FER</p></img>
            </div>
            <div class="data-box">
                <p>SteamID: <code id="steamID">test</code></p>
                <p>SteamID3: <code id="steamID3"></code></p>
                <p>SteamID64: <code id="steamID64"></code></p>
                <p>Profile: <a href="#" id="Profile"></a></p>
                <p>Profile created: <code id="ProfileCreated"></code></p>
                <p>Real name: <code id="RealName"></code></p>
                <p>Location: <code id="Location"></code></p>
				</br>
				<a>This user has been seen as:</a>
				<span style="width:100%; overflow: hidden; white-space: nowrap; display:inline-block; text-align: center; font-size:110%;">
					<p style="text-align: center;display: flex;flex-direction: row;flex-wrap: nowrap;flex-grow: 1;justify-content: center;gap: 58px;"><span id="data-positive">0x</span> <span id="data-cheater">0x</span> <span id="data-scammer">0x</span></p>
					<p id="vote" style="text-align: center;display: flex;flex-direction: row;flex-wrap: nowrap;flex-grow: 1;justify-content: center;gap: 93px;transform:scale(1.5);"> <i onclick="vote(1)" id="votePositive" class="fa-solid fa-face-smile"></i> <i onclick="vote(2)" id="voteCheater" class="fa-solid fa-ban"></i> <i onclick="vote(3)" id="voteScammer" class="fa-solid fa-people-robbery"></i> </p>
				
				</span>
				
            </div>
        </div>
		
		<div class="footer">
			<p style="margin-bottom: 10px;">Made with <i style="color: red;" class="fa-solid fa-heart"></i> by TeeZ0</p>
		</div>
        
        <script>
         $('#steamid').keypress(function (e) {
            if (e.which == 13) {
                searchID();
                return false;
            }
        });
        </script>
	</body>

</html>