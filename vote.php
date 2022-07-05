<?php
	
	require 'steamauth/steamauth.php';

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
	
	$steamid = htmlspecialchars($_GET["steamid64"]);
	$report_type = htmlspecialchars($_GET["type"]);
	$steamid_reporter = "";
	
    if(strlen($steamid) != 17){
        echo("-1");
        die();
    }
	
	if(!is_numeric($report_type)){
		echo "-1";
		die();
	}
	
	if($report_type > 3 || $report_type < 1){
		echo "-1";
		die();
	}
	
	if(!isset($_SESSION['steamid'])){
		echo "-2";
		die();
	}else{
		$steamid_reporter = $_SESSION['steamid'];
	}
	
	$userVoted = get_user_vote($steamid_reporter, $steamid);

	
	if($userVoted == 0){ //User has not voted yet
		add_user_vote($steamid_reporter, $steamid, $report_type);
	}else{ // User has voted already
		if($userVoted == $report_type){
			subtract_user_vote($steamid_reporter, $steamid, $userVoted);
		}else{
			change_user_vote($steamid_reporter, $steamid, $report_type);	
		}
	}	
	
	$data = get_steamid64_stats($steamid);
	
	echo $data["Positive"];
	echo "|";
	echo $data["Cheater"];
	echo "|";
	echo $data["Scammer"];

	
	
	function change_user_vote($steamid_voter, $steamid, $vote_type){
		require("db.php");
		// Create connection
		$conn = new mysqli($servername, $username, $password, $database);
		// Check connection
		if ($conn->connect_error) {
		  die("Connection failed: " . $conn->connect_error);
		}
	
		$steamid_safe = mysqli_real_escape_string($conn, $steamid);
		$steamid_reporter_safe = mysqli_real_escape_string($conn, $steamid_voter);
		
		
		$sql = "UPDATE stats SET vote_type=$vote_type WHERE steam64='$steamid_safe' AND steam64_voter='$steamid_reporter_safe'";
		
		$conn->query($sql);
		
		
		
	}
	
	function get_steamid64_stats($steam64){
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
		
		$data = array(
			"Positive" => 0,
			"Cheater" => 0,
			"Scammer" => 0
		);

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
		
		return $data;
	
	}
	
	function add_user_vote($steamid_voter, $steamid, $vote_type){ // User hasnt voted yet, so add a vote
		require("db.php");
		// Create connection
		$conn = new mysqli($servername, $username, $password, $database);
		// Check connection
		if ($conn->connect_error) {
		  die("Connection failed: " . $conn->connect_error);
		}
	
		$steamid_safe = mysqli_real_escape_string($conn, $steamid);
		$steamid_reporter_safe = mysqli_real_escape_string($conn, $steamid_voter);
		
		$sql = "INSERT INTO stats (steam64, steam64_voter, vote_type) VALUES ('$steamid_safe', '$steamid_reporter_safe', $vote_type)";
		
		$conn->query($sql);
		
		
		
		$conn->close();
	}
	
	function subtract_user_vote($steamid_voter, $steamid, $vote_type){ // Basically "cancel" users vote
		require("db.php");
		// Create connection
		$conn = new mysqli($servername, $username, $password, $database);
		// Check connection
		if ($conn->connect_error) {
		  die("Connection failed: " . $conn->connect_error);
		}
	
		$steamid_safe = mysqli_real_escape_string($conn, $steamid);
		$steamid_reporter_safe = mysqli_real_escape_string($conn, $steamid_voter);
		
		
		$sql = "DELETE FROM stats WHERE steam64_voter = '$steamid_reporter_safe' AND steam64 = '$steamid_safe'";
		
		$conn->query($sql);
		
		
		
		$conn->close();
	}
	
	function get_user_vote($steamid_voter, $steamid){
		require("db.php");
		// Create connection
		$conn = new mysqli($servername, $username, $password, $database);
		// Check connection
		if ($conn->connect_error) {
		  die("Connection failed: " . $conn->connect_error);
		}
	
		$steamid_safe = mysqli_real_escape_string($conn, $steamid);
		$steamid_reporter_safe = mysqli_real_escape_string($conn, $steamid_voter);
		
		
		
		$sql = "SELECT vote_type FROM stats WHERE steam64='$steamid_safe' AND steam64_voter='$steamid_reporter_safe'";
		
		$result = $conn->query($sql);

		if ($result->num_rows > 0) {
		  // output data of each row
		  while($row = $result->fetch_assoc()) {
			if($row["vote_type"] == 1)
				return 1;
			else if($row["vote_type"] == 2)
				return 2;
			else if($row["vote_type"] == 3)
				return 3;
			else
				return 0;
		  }
		}else{
			return 0;
		}
		
		
		
		$conn->close();
	}
	
	
	
	
	
	
?>