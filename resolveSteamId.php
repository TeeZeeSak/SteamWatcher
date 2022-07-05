<?php
	require 'steamauth/steamauth.php';
	require "env.php";

	// source: Laravel Framework
	// https://github.com/laravel/framework/blob/8.x/src/Illuminate/Support/Str.php
    function str_starts_with($haystack, $needle) {
        return (string)$needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
	}
	
	function str_ends_with($haystack, $needle) {
		return $needle !== '' && substr($haystack, -strlen($needle)) === (string)$needle;
	}

	
	function str_contains($haystack, $needle) {
		return $needle !== '' && mb_strpos($haystack, $needle) !== false;
	}
	//
	
	$cleared_input = htmlspecialchars($_GET["input"]);

    if(strlen($cleared_input) < 1){
        echo("-1");
        die();
    }
    $data = array(
        "Steam2" => "",
        "Steam3" => "",
        "Steam64" => "",
        "Name" => "",
        "Profile" => "",
        "CommunityVisibility" => "",
        "ProfileCreated" => "",
        "RealName" => "",
        "Location" => "",
        "Online" => "",
        "AvatarMedium" => "",
		"Positive" => 0,
		"Cheater" => 0,
		"Scammer" => 0,
		"LocalVoted" => 0
    );

	if(str_ends_with($cleared_input, "/")){
        $cleared_input = substr_replace($cleared_input, "", -1);
    }
	
    $cleared_input = str_replace("https://www.", "", $cleared_input);
    $cleared_input = str_replace("http://www.", "", $cleared_input);
    $cleared_input = str_replace("https://", "", $cleared_input);
    $cleared_input = str_replace("http://", "", $cleared_input);
    

	resolve_input_type($cleared_input);
	function resolve_input_type($input){
        global $data;
		if(str_starts_with($input, "steamcommunity.com/profiles/")){
            $steamid = str_replace("steamcommunity.com/profiles/", "", $input);
            if(is_numeric($steamid)){
                get_steamid2_from_steamid64($steamid, true);
            }
		}else if(str_starts_with($input, "steamcommunity.com/id/")){
            $user = str_replace("steamcommunity.com/id/", "", $input);
            get_steamid64_from_vanityurl($user);

		}else if(str_starts_with($input, "STEAM_")){
			get_steamid64_from_steamid2($input);
		}else if(str_starts_with($input, "U:") || str_starts_with($input, "[U:") && str_ends_with($input,"]")){
            SteamID3SteamID64($input);
		}else if(is_numeric($input) && strlen($input) == 17){
            get_steamid2_from_steamid64($input, true);
        }else{
			get_steamid64_from_vanityurl($input);
		}

        if(isset($data["Steam64"])){
            $_steamid64 = $data["Steam64"];
            if(strlen($_steamid64) != 17){
                echo "-1";
                die();
            }
			global $STEAM_API_KEY;
            $jsonData = json_decode(file_get_contents("http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=$STEAM_API_KEY&steamids=$_steamid64"), true);
            
            $timecreated = $jsonData["response"]["players"][0]["timecreated"];
            $personastate =  $jsonData["response"]["players"][0]["personastate"];
            $communityvisibilitystate = $jsonData["response"]["players"][0]["communityvisibilitystate"];
            $realname = $jsonData["response"]["players"][0]["realname"];
            $countrycode = $jsonData["response"]["players"][0]["loccountrycode"];
            $avatar = $jsonData["response"]["players"][0]["avatarmedium"];

            $date_readable = "Unknown";
            $personastate_readable = "Offline";
            $communityvisibility_readable = "0";
            $realname_readable = "Not set";
            $countrycode_readable = "Not set";
            $avatar_readable = "/resources/default.jpg";

            if(isset($timecreated)){
                $date = new DateTime();
                $date->setTimestamp($timecreated);
                $date_readable = gmdate('d.m.Y', strtotime($date->format('d.m.Y')));
            }

            if(isset($personastate)){
                if($personastate != 0){
                    $personastate_readable = "Online";
                }else{
                     $personastate_readable = "Offline";
                }
            }

            if(isset($CommunityVisibility)){
                $communityvisibility_readable = $communityvisibilitystate;
            }

            if(isset($realname)){
                $realname_readable = $realname;
            }

            if(isset($countrycode)){
                $countrycode_readable = $countrycode;
            }

            if(isset($avatar)){
                $avatar_readable = $avatar;
            }

            $data["CommunityVisibility"] = $communityvisibility_readable;
            $data["ProfileCreated"] = $date_readable;
            $data["RealName"] = $realname_readable;
            $data["Location"] = $countrycode_readable;
            $data["Online"] = $personastate_readable;
            $data["AvatarMedium"] = $avatar_readable;
			get_steamid64_stats($_steamid64);
			if(isset($_SESSION['steamid'])){
				get_local_vote($_steamid64, $_SESSION['steamid']);
			}
            echo json_encode($data);
        }else{
            echo "-1";
            die();
        }
	}
	
    function get_steamid2_from_steamid64($steamid64, $continue = false){
        $highBits = ($steamid64 - (76561197960265728 + ($steamid64 % 2)))/2;
        $lowBits = $steamid64 % 2 ? 1 : 0;

        $steam2 = "STEAM_0:$lowBits:$highBits";

        if($continue){
            get_steamid64_from_steamid2($steam2);
        }else{
            return $steam2;
        }

    }

    function get_steamid64_from_steamid2($steamId2)
    {
        global $data;

        $accountId = 0;

        if (preg_match('/^STEAM_[0-9]:([0-9]):([0-9]+)$/i', $steamId2, $matches)) {
            $accountId = $matches[1] + ($matches[2] * 2);
        }
        if (preg_match('/^\[U:[0-9]:([0-9]+)\]$/i', $steamId2, $matches)) {
            $accountId = $matches[1];
        }

        $id = gmp_strval(gmp_add('76561197960265728', $accountId));
        
        $data["Steam2"] = $steamId2;
        $data["Steam64"] = $id;
        $data["Steam3"] = get_steamid3_from_steamid2($steamId2);    
        $data["Profile"] = "https://steamcommunity.com/profiles/$id";
		global $STEAM_API_KEY;
        $jsonData = json_decode(file_get_contents("http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=$STEAM_API_KEY&steamids=$id"), true);
        if(strlen($jsonData["response"]["players"][0]["personaname"]) > 1){
            $data["Name"] = $jsonData["response"]["players"][0]["personaname"];
        }
    } 

	function get_steamid64_from_vanityurl($username){
        global $data;
		global $STEAM_API_KEY;
		
		$jsonData = json_decode(file_get_contents("http://api.steampowered.com/ISteamUser/ResolveVanityURL/v0001/?key=$STEAM_API_KEY&vanityurl=$username"), true);
        
        if($jsonData["response"]["success"] == 1){
            $s64 = $jsonData["response"]["steamid"];
            $s2 = get_steamid2_from_steamid64($s64);
            $data["Steam64"] = $s64;
            $data["Steam2"] = $s2;
            $data["Steam3"] = get_steamid3_from_steamid2($s2);    
            $data["Profile"] = "https://steamcommunity.com/profiles/$s64";
			
            $persona = json_decode(file_get_contents("http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=$STEAM_API_KEY&steamids=$s64"), true);
            if(strlen($persona["response"]["players"][0]["personaname"]) > 1){
                $data["Name"] = $persona["response"]["players"][0]["personaname"];
            }
        }
	}

    function SteamID3SteamID64($steamid3) {
        global $data;
        if(!str_contains($steamid3, "]")){
            $steamid3 .= "]";
        }
        if(!str_contains($steamid3, "[")){
            "[" . $steamid3;
        }

        $args = explode(":", $steamid3);
        $accountid = substr($args[2], 0, -1);

        if (($accountid % 2) == 0) {
            $Y = 0;
            $Z = ($accountid / 2);
        } else {
            $Y = 1;
            $Z = (($accountid - 1) / 2);
        }

        $s64 = "7656119".(($Z * 2) + (7960265728 + $Y));
        get_steamid2_from_steamid64($s64, true);
    }
	
    function get_steamid3_from_steamid2($steam2){
        if(str_starts_with($steam2, "STEAM_0:1")){
            $temp = str_replace("STEAM_0:1:", "", $steam2);
            $temp = intval($temp);
            $temp *= 2;
            $temp += 1;
            $temp = "[U:1:$temp]";
            return $temp;
        }else{
            $temp = str_replace("STEAM_0:0:", "", $steam2);
            $temp = intval($temp);
            $temp *= 2;
            $temp = "[U:1:$temp]";
            return $temp;    
        }
    }
	
	function get_local_vote($steam64, $steam64_voter){
		if(isset($_SESSION['steamid'])){
			global $data;
			require("db.php");
			// Create connection
			$conn = new mysqli($servername, $username, $password, $database);
			// Check connection
			if ($conn->connect_error) {
			  die("Connection failed: " . $conn->connect_error);
			}
			
			if(!is_numeric($steam64)){
				echo "Steam64 is not numerical.";
				die();
			}
			
			$steamid_safe = mysqli_real_escape_string($conn, $steam64);
			$steamid_voter_safe = mysqli_real_escape_string($conn, $steam64_voter);

			
			
			
			$sql = "SELECT vote_type FROM stats WHERE steam64='$steamid_safe' AND steam64_voter='$steamid_voter_safe'";
			//echo $sql;
			$result = $conn->query($sql);
		

			if ($result->num_rows > 0) {
			  // output data of each row
			  while($row = $result->fetch_assoc()) {
				if($row["vote_type"] == 1)
					$data["LocalVoted"] = 1;
				else if($row["vote_type"] == 2)
					$data["LocalVoted"] = 2;
				else if($row["vote_type"] == 3)
					$data["LocalVoted"] = 3;
			  }
			}else{
				$data["LocalVoted"] = 0;
			}
			$conn->close();
		}

	}
	
	function get_steamid64_stats($steam64){
		global $data;
		require("db.php");
		// Create connection
		$conn = new mysqli($servername, $username, $password, $database);
		// Check connection
		if ($conn->connect_error) {
		  die("Connection failed: " . $conn->connect_error);
		}
		
		if(!is_numeric($steam64)){
			echo "Steam64 is not numerical.";
			die();
		}
		
		$steamid_safe = mysqli_real_escape_string($conn, $steam64);
		
		
		
		
		$sql = "SELECT vote_type FROM stats WHERE steam64='$steamid_safe'";
		//echo $sql;
		$result = $conn->query($sql);
	
		$positive = 0;
		$cheater = 0;
		$scammer = 0;

		if ($result->num_rows > 0) {
		  // output data of each row
		  while($row = $result->fetch_assoc()) {
			if($row["vote_type"] == 1)
				$positive++;
			else if($row["vote_type"] == 2)
				$cheater++;
			else if($row["vote_type"] == 3)
				$scammer++;
		  }
		}
		
		$data["Positive"] = $positive;
		$data["Cheater"] = $cheater;
		$data["Scammer"] = $scammer;
		
		$conn->close();
	
	}

	//http://api.steampowered.com/ISteamUser/ResolveVanityURL/v0001/?key=$API_KEY&vanityurl=TeeZ0

?>