<?php

// Variables
$warned = array();
$jailed = array();
$badnames = array('fukyou', 'fucyou', 'fuck', 'cunt', 'fuku', 'bitch', 'b!tch', 'nigga', 'nigger', 'niga', 'shit', 'sh!t', 'penis', 'pen!s', 'fag', 'pussy', '[admin]', '[mod]','[moderator]', '[manager]', '[owner]'); 
$afkTime = 60*60; //60 minutes 
$reported = array();

// load framework files
require_once("libraries/TeamSpeak3/TeamSpeak3.php");
// connect to local server, authenticate and spawn an object for the virtual server on port 9987
try{
	$ts3_VirtualServer = TeamSpeak3::factory("serverquery://afkbot:[PASSWORD_PROTECTED]@127.0.0.1:10011/?server_port=9987&blocking=0&nickname=AGNBot");
}catch(TeamSpeak3_Adapter_ServerQuery_Exception $e){
	exit($e);
}

$ts3_VirtualServer->notifyRegister("textserver");
TeamSpeak3_Helper_Signal::getInstance()->subscribe("notifyTextmessage", "onTextMessage");
$ts3_VirtualServer->message("[COLOR=blue][B]Aggressive Gaming Network teamspeak management bot started, version [COLOR=green]'1.0.12beta' [COLOR=blue]Server messages: [COLOR=green]ENABLED [COLOR=blue]Private messages: [COLOR=red]DISABLED!");

while(1){
	$start = microtime()+1000000;
	// walk through list of clients
	$ts3_clients = $ts3_VirtualServer->clientList();

	foreach($ts3_clients as $ts3_Client)
	{
		//var_dump($ts3_Client);
		try{
			$nickname = (string)strtolower(str_replace(' ', '', $ts3_Client));
			$uid = (string)$ts3_Client['client_unique_identifier'];
			$info = $ts3_Client->getInfo();
			$clAFK = $info['client_idle_time']/1000;
		}catch(Exception $e){}
		try{
			if(stristr((string)$info['client_servergroups'], '13') || stristr((string)$info['client_servergroups'], '14') || stristr((string)$info['client_servergroups'], '32')){
				foreach($ts3_VirtualServer->complaintList() as $complaint){
					if(isset($reported[$nickname]) && @$reported[$nickname]['a'.$complaint['timestamp']] == $uid || $ts3_Client['client_type'] == 1){
						if(!isset($reported[$uid])){
							$ts3_Client->message("[COLOR=blue][B]After you have dealt with this, can you please remove the complaint from the list as to not confuse other moderators.[B][/COLOR]");
							$reported[$uid] = TRUE;
						}
					}else{
						unset($reported[$uid]);
						echo "Reported to {$nickname}".PHP_EOL;
						$reported[$nickname]['a'.$complaint['timestamp']] = $uid;
						$ts3_Client->message("[COLOR=blue][B]Hey {$nickname}, [U]{$complaint['fname']}[/U] has complained about [U]{$complaint['tname']}[/U] for \"{$complaint['message']}\" at ".date('Y-m-d H:i:s',$complaint['timestamp'])."[B][/COLOR]");
			            echo "Reported to {$nickname}".PHP_EOL;

					}
				}
			}
		}catch(Exception $e){}

		if(stristr((string)$info['client_servergroups'], '44') || stristr((string)$info['client_servergroups'], '43')){
			try{
				$ts3_Client->move(213);
				$ts3_Client->poke("[COLOR=red][B]You have been sent to jail![/B][/COLOR]");
				$ts3_Client->poke("[COLOR=red][B]Serve your time and I will free you after 10 minutes.[/B][/COLOR]");
				$jailed[$nickname] = time()+600;
			}catch(Exception $e){}

			if($jailed[$nickname] <= time()){
				$ts3_VirtualServer->serverGroupClientDel(44,$ts3_Client["client_database_id"]);
				$ts3_Client->kick(TeamSpeak3::KICK_CHANNEL);
				$ts3_Client->poke("You have spent your jail time, you are now free to go.");
			}
		}
		// AFK MOVER---------------------------------------------------
		try{
			if($clAFK >= 60*60){
				$ts3_Client->move(216);
				$ts3_Client->message("[COLOR=green][B]You have been moved to the ~AFK~ Channel for being AFK for 1 hour.[/B][/COLOR]");
				echo "Moved {$nickname} for being afk for {$clAFK}".PHP_EOL;
			} else if($clAFK >= 60*15 && $info['client_input_hardware'] == 0){
				$ts3_Client->move(216);
				$ts3_Client->message("[COLOR=green][B]You have been moved to the ~AFK~ Channel for being on another teamspeak.[/B][/COLOR]");
				echo "Moved {$nickname} for being on another teamspeak for 15 minutes".PHP_EOL;
			} else if($clAFK >= 60*15 && $info['client_output_muted'] == 1){
				$ts3_Client->move(216);
				$ts3_Client->message("[COLOR=green][B]You have been moved to the ~AFK~ Channel for being muted for 15 minutes.[/B][/COLOR]");
				echo "Moved {$nickname} for being muted on teamspeak for 15 minutes".PHP_EOL;
			}
		}catch(Exception $e){}

		if(strposa($nickname, $badnames)){
			try{
				if(@$warned[$uid]['chances'] >= 2){
					$ts3_Client->poke("[COLOR=red][B]I gave you 3 chances, Don't waste my time![/B][/COLOR]");
					$warned[$uid]['chances'] = 0;
					$ts3_VirtualServer->clientKick($ts3_Client, TeamSpeak3::KICK_SERVER, "BOTKICK: {$nickname} is blacklisted nickname. (3 Chances)");
					break;
				}

				switch (@$warned[$uid]['warnings']) {
					default:
						$ts3_Client->poke("[COLOR=red][B]Please read your private message from me![/B][/COLOR]");
						$ts3_Client->message("[COLOR=red][B]Your nickname is blacklisted, Change your name now or you will be kicked (20 second warning)[/B][/COLOR]");
						$warned[$uid]['warnings'] = 1;
						break;
					case 1:	break;
					case 2:	break;
					case 3:	break;
					case 4: break;
					case 5:
						$ts3_Client->message("[COLOR=red][B]Your nickname is blacklisted, Change your name now or you will be kicked. (15 second warning)[/B][/COLOR]");
						break;
					case 6:	break;
					case 7:	break;
					case 8:	break;
					case 9:	break;
					case 10:
						$ts3_Client->message("[COLOR=red][B]Your nickname is blacklisted, Change your name now or you will be kicked. (10 second warning)[/B][/COLOR]");
						break;
					case 11: break;
					case 12:
						$ts3_Client->message("[COLOR=red][B]Your nickname is blacklisted, Change your name now or you will be kicked. (8 second warning)[/B][/COLOR]");
						break;
					case 13:
						break;
					case 14:
						$ts3_Client->message("[COLOR=red][B]Your nickname is blacklisted, Change your name now or you will be kicked. (6 second warning)[/B][/COLOR]");
						break;
					case 15:
						$ts3_Client->message("[COLOR=red][B]Your nickname is blacklisted, Change your name now or you will be kicked. (5 second warning)[/B][/COLOR]");
						break;
					case 16:
						$ts3_Client->message("[COLOR=red][B]Your nickname is blacklisted, Change your name now or you will be kicked. (4 second warning)[/B][/COLOR]");
						break;
					case 17:
						$ts3_Client->message("[COLOR=red][B]Your nickname is blacklisted, Change your name now or you will be kicked. (3 second warning)[/B][/COLOR]");
						break;
					case 18:
						$ts3_Client->message("[COLOR=red][B]Your nickname is blacklisted, Change your name now or you will be kicked. (2 second warning)[/B][/COLOR]");
						break;
					case 19:
						$ts3_Client->message("[COLOR=red][B]Your nickname is blacklisted, Change your name now or you will be kicked. (1 second warning)[/B][/COLOR]");
						break;
	
					case 20:
						$ts3_Client->poke("[COLOR=red][B]Fine, I will just kick you![/B][/COLOR]");
						$ts3_Client->message("[COLOR=red][B]Your nickname is blacklisted, You have been kicked from the Aggressive Gaming teamspeak3 server for using an offensive name.[/B][/COLOR]");
						$warned[$uid]['warnings'] = 0;
						$warned[$uid]['chances'] = 0;
						$ts3_VirtualServer->clientKick($ts3_Client, TeamSpeak3::KICK_SERVER, "BOTKICK: {$nickname} is blacklisted nickname. (10 Second warning)");
						break;
				}
			}catch(Exception $e){}
			$warned[$uid]['warnings'] += 1;
			echo "{$nickname} warned {$warned[$uid]['warnings']} times".PHP_EOL;
		}
		if(@$warned[$uid]['warnings'] >= 1 && !strposa($nickname, $badnames)){
			$warned[$uid]['warnings'] = 0;
			echo "Changes username".PHP_EOL;
			$warned[$uid]['chances'] = isset($warned[$uid]['chances']) ? $warned[$uid]['chances']+1 : 1;
			$ts3_Client->message("[COLOR=green]Thank you for changing your name.[/COLOR]");
		}
	}
	$ts3_VirtualServer->clientListReset();
	usleep($start - microtime());
}

function strposa($haystack, $needle, $offset=0) {
    if(!is_array($needle)) $needle = array($needle);
    foreach($needle as $query) {
        if(strpos($haystack, $query, $offset) !== false) return true; // stop on first true result
    }
    return false;
}

function onTextMessage(TeamSpeak3_Adapter_ServerQuery_Event $event, TeamSpeak3_Node_Host $host)
{
	echo "[{$event["invokername"]}]: {$event["msg"]}".PHP_EOL;
	$serv = $host->serverGetByPort(9987);
	if(stristr(strtolower((string)$event["msg"]),'hello') && strtolower($event["invokername"]) != "agnbot"){
		$serv->message("Howdy {$event["invokername"]}, isn't it a nice day today.");
	}

	if(stristr(strtolower((string)$event["msg"]),'!meetingmove') && strtolower($event["invokername"]) != "agnbot"){
		foreach($serv->clientList() as $ts3_Client)
		{	
			$nickname = (string)strtolower($ts3_Client);
			if($nickname != "musicbot"){
				try{
					$ts3_Client->move(30);
				}catch(Exception $e){}
			}
		}
	}

	if(stristr(strtolower((string)$event["msg"]),'!meetingtime') && strtolower($event["invokername"]) != "agnbot"){
		$time = file_get_contents('meeting.txt');
		foreach($serv->clientList() as $ts3_Client)
		{
			try{
				$ts3_Client->message("[COLOR=blue][B]{$event["invokername"]} wanted to remind you the next meeting will be: {$time}[/B][/COLOR]");
			}catch(Exception $e){}
		}
	}

	if(stristr(strtolower((string)$event["msg"]),'!donate') && strtolower($event["invokername"]) != "agnbot"){
		$donate = file_get_contents('donate.txt');
		foreach($serv->clientList() as $ts3_Client)
		{
			$nickname = (string)strtolower($ts3_Client);
			try{
				$ts3_Client->message("[COLOR=blue][B]Hi {$nickname}, {$donate}[/B][/COLOR]");
			}catch(Exception $e){}
		}
	}

	if(stristr(strtolower((string)$event["msg"]),'!time') && strtolower($event["invokername"]) != "agnbot"){
		$serv->message("The current server time is ".date("F j, Y, g:i a"));
	}

	if(stristr(strtolower((string)$event["msg"]),'!info') && strtolower($event["invokername"]) != "agnbot"){
		$name = strtolower(str_replace("!info", '',str_replace("!info ", '',(string)$event["msg"])));
		try{
			$info = $serv->clientInfoDb($serv->clientFindDb($name));

			foreach($serv->clientList() as $ts3_Client)
			{
				$nickname = (string)strtolower($ts3_Client);
				if($nickname == (string)strtolower($event["invokername"])){
						$ts3_Client->message("[COLOR=blue][B]{$name}: Database ID {$info["client_database_id"]}[/COLOR]");
						$ts3_Client->message("[COLOR=blue][B]{$name}: Unique ID {$info["client_unique_identifier"]}[/COLOR]");
						$ts3_Client->message("[COLOR=blue][B]{$name}: Joined ".date("F j, Y, g:i a",$info["client_created"])."[/COLOR]");
						$ts3_Client->message("[COLOR=blue][B]{$name}: Last connection ". date("F j, Y, g:i a",$info["client_lastconnected"])."[/COLOR]");
						$ts3_Client->message("[COLOR=blue][B]{$name}: Total connections {$info["client_totalconnections"]}[/COLOR]");
						$ts3_Client->message("[COLOR=blue][B]{$name}: Client description {$info["client_description"]}[/COLOR]");
						$ts3_Client->message("[COLOR=blue][B]{$name}: Last IP {$info["client_lastip"]}[/COLOR]");
						break;
				}
			}
		}catch(Exception $e){}

	}

	if(stristr(strtolower((string)$event["msg"]),'!troll') || stristr(strtolower((string)$event["msg"]),'!jail') && strtolower($event["invokername"]) != "agnbot"){
		$matches = '';
		$name = strtolower(str_replace("!troll ", '', str_replace("!jail ", '', (string)$event["msg"])));
		try{
			foreach($serv->clientList() as $ts3_Client)
			{
				$nickname = (string)strtolower($ts3_Client);

				if($nickname == $name){
					$serv->serverGroupClientAdd(44,$ts3_Client["client_database_id"]);
					break;
				} else if(stristr($nickname,$name)){
					$matches .= $nickname." ";
					$serv->serverGroupClientAdd(44,$ts3_Client["client_database_id"]);
				}
			}
			foreach($serv->clientList() as $ts3_Client)
			{
				$nickname = (string)strtolower($ts3_Client);
				if($nickname == 'moddedtibby' && $matches != ''){
					$ts3_Client->message("[COLOR=blue][B]{$event["invokername"]} has sent users matching {$name} to jail, users: {$matches} at ".date("F j, Y, g:i a",time())."[/B][/COLOR]");
					echo "{$event["invokername"]} has sent {$matches} to jail at ".date("F j, Y, g:i a",time()).PHP_EOL;
					break;
				} else if($nickname == 'moddedtibby'){
					$ts3_Client->message("[COLOR=blue][B]{$event["invokername"]} has sent {$name} to jail at ".date("F j, Y, g:i a",time())."[/B][/COLOR]");
					echo "{$event["invokername"]} has sent {$name} to jail at ".date("F j, Y, g:i a",time()).PHP_EOL;
					break;
				}
			}
			if(isset($matches) && $matches != ''){
				$serv->message("Found matches for {$name}: {$matches} - Sent to jail.");
			}
		}catch(Exception $e){}
	}
}
