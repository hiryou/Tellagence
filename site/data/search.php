<?php
error_reporting(0);

//Search for x number of nodes closest to some center node

//the center of our graph
$user = 1;
if (isset($_GET["id"])) {
    $id = $_GET["id"];

    if ( (int)$id == $id && $id >= 0 ) {
	$user = (int)$id;
    }
}
$user = (string)$user;

//how many nodes to find around our center
$total = 100;
if (isset($_GET["depth"])) {
    $depth = $_GET["depth"];

    if ( (int)$depth == $depth && $depth >= 0 ) {
	$total = (int)$depth;
    }
}

//how many nodes we've visited so far
//also used to map database index <-> json index
$visited[] = $user;

//list of nodes to visit
$toVisit = array();

//shortest paths from user X to all other users
$path = array();

//create the json skeleton
$json['nodes'] = array();
$json['links'] = array();

//connect to the database
$dbconn = pg_Connect("host=capstone06.cs.pdx.edu dbname=fake user=postgres password=bees");
if (!$dbconn) {
    die("Error connecting to database.");
}

//add the central node
addNode($user);
findNodes($user);

//get all the other nodes
while (count($visited) < $total && count($toVisit) > 0) {
    //get the first node to visit
    $next = array_shift($toVisit);

    addNode($next);
    findNodes($next);

    $visited[] = $next;
}

//link the nodes
foreach ($json['nodes'] as $node) {
    addLinks($node);
}

//print out the json
//print_r($json); die;
echo(json_encode($json));

//close the database connection
pg_close($dbconn);


//here be dragons^Wfunctions

function getPath($who) {
    global $dbconn, $path;

    $result = pg_Exec($dbconn, "SELECT shortestpath FROM test2 WHERE user_id = $who;");
    $row = pg_fetch_array($result, 0);//if there are multiple entries, just use the first

    //turn the string '1:2:3' into the array '1','2','3'
    $path = explode(":",$row[0]);
}

function addNode($who) {
    global $dbconn, $toVisit, $visited, $json;

    //find this nodes name
    $result = pg_Exec($dbconn, "SELECT username FROM users WHERE user_id = $who;");
    $num = pg_numrows($result);

    for ($i = 0; $i < $num; $i++) {
	$row = pg_fetch_array($result, $i);
	//$node['name'] = $row[0];
	$node['id'] = $who;
	//add this node to the json
	$json['nodes'][] = $node;
    }
}

function findNodes($who) {
    //add this nodes neighboors to toVisit
    global $dbconn, $visited, $toVisit;

    $result = pg_Exec($dbconn, "SELECT user_id2 FROM relationship WHERE user_id1 = $who;");
    $num = pg_numrows($result);

    for ($i = 0; $i < $num; $i++) {
	$row = pg_fetch_array($result, $i);
	if (!in_array($row[0],$visited)) {
	    $toVisit[] = $row[0];
	}
    }

    $result = pg_Exec($dbconn, "SELECT user_id1 FROM relationship WHERE user_id2 = $who;");
    $num = pg_numrows($result);

    for ($i = 0; $i < $num; $i++) {
	$row = pg_fetch_array($result, $i);
	if (!in_array($row[0],$visited)) {
	    $toVisit[] = $row[0];
	}
    }

}

function addLinks($here) {
    global $dbconn, $toVisit, $visited, $path, $json;
    
    //get the shortest paths from the db
    getPath($here['id']);

    //our index in the nodes array
    $source = array_search($here, $json['nodes']);

    //get the influences from the db
    $result1 = pg_Exec($dbconn, "SELECT user_id2, inf_1to2, inf_2to1 FROM relationship WHERE user_id1 = " . $here['id'] . ";");
    $num1 = pg_numrows($result1);

    $result2 = pg_Exec($dbconn, "SELECT user_id1, inf_1to2, inf_2to1 FROM relationship WHERE user_id2 = " . $here['id'] . ";");
    $num2 = pg_numrows($result2);

    //create links between us and all the other nodes
    foreach ($json['nodes'] as $target => $there) {

	//we only want to add the link once, lets use the node with more paths in the db
	if ($here['id'] <= $there['id']) {
	    //move on to the next node in the nodes array
	    continue;
	}

	//first check if we are user_id1
	for ($i = 0; $i < $num1; $i++) {
	    $row = pg_fetch_array($result1, $i);
	    if ($row[0] == $there['id']) {
		$link['source'] =  $source;
		$link['target'] =  $target;
		$link['influence'] = (int)$row[1] + (int)$row[2];
		$link['shortestpath'] = (float)$path[$there[id] - 1];
		$json['links'][] = $link;

		//move on to the next node in the nodes array
		continue 2;
	    }
	}

	//next check user_id2
	for ($i = 0; $i < $num2; $i++) {
	    $row = pg_fetch_array($result2, $i);
	    if ($row[0] == $there['id']) {
		$link['source'] =  $source;
		$link['target'] =  $target;
		$link['influence'] = (int)$row[1] + (int)$row[2];
		$link['shortestpath'] = (float)$path[$there[id] - 1];
		$json['links'][] = $link;

		//move on to the next node in the nodes array
		continue 2;
	    }
	}

	//i guess there's no direct link
	$link['source'] =  $source;
	$link['target'] =  $target;
	$link['influence'] = 0;
	$link['shortestpath'] = (float)$path[$there[id] - 1];
	$json['links'][] = $link;
    }
}

?>