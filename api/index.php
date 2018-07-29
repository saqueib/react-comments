<?php
/* 
 * API for to store and list all comments
 * PHP and SQLite using PDO
 */
$_POST = isset($_POST['name']) ? : json_decode(file_get_contents("php://input"), true);
// Simple CORS
header("Access-Control-Allow-Origin: *");

// Set default timezone
date_default_timezone_set('UTC');

/**************************************
 * SQLite DB connection using PDO      *
 **************************************/
$dsn = 'sqlite:comm.sqlite';

try {
	$dbh = new PDO($dsn);
	// Set error mode to exceptions
	$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	// Initialize db table
	initialize_db($dbh);

} catch (PDOException $e) {
	json_response(['error' => 'Unable to open db connection '], 500);
}

// Uncomment to empty the databsae
// empty_comments($dbh);

/**************************************
 * Handle Post comment                 *
 **************************************/
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

	// Inputs
	$name = @$_POST['name'];
	$comment = @$_POST['message'];

	// Validate the input
	if (strlen($name) < 3) {
		json_response(['error' => 'Name is required!'], 422);
	}

	if (strlen($comment) < 5) {
		json_response(['error' => 'Comments is required!'], 422);
	}

	$data = ['name' => $name, 'message' => $comment];

	save_comment($data, $dbh);

	json_response(transform($data + ['id' => $dbh->lastInsertId(), 'time' => time()]), 201);
}

/**************************************
 * Return list of Comments             *
 **************************************/
$result = $dbh->query('SELECT * FROM comments ORDER BY id DESC', PDO::FETCH_ASSOC);

$comments = $result ? $result->fetchAll() : [];

// Transform result
$comments = array_map('transform', $comments);

json_response($comments);


/************************************** Helper functions *************************************/

/*
 * Save a comment in db
 * 
 * @param $data
 * @param $dbh
 * @return boolean
 */
function save_comment($data, $dbh)
{
	// Prepare statement 
	$statement = $dbh->prepare(
		"INSERT INTO comments 
			(name, message, time) 
		VALUES
			(:name, :message, :time)"
	);

	// Bind and execute
	return $statement->execute(array(
		"name" => strip_tags($data['name']),
		"message" => strip_tags($data['message']),
		"time" => time()
	));
}

/**
 * Die a valid json response
 * 
 * @param $data
 * @param int $status_code
 */
function json_response($data, $status_code = 200)
{
	http_response_code($status_code);
	header('Content-Type: application/json');
	global $dbh;
	$dbh = null;
	die(json_encode($data));
}

/**
 * Initialize db with table
 * 
 * @param $dbh
 */
function initialize_db($dbh)
{
	// Create comments table if not exists
	$dbh->exec("CREATE TABLE IF NOT EXISTS comments (
        id INTEGER PRIMARY KEY, 
        name VARCHAR,
        message TEXT,
        time INTEGER)");
}

/*
 * Transform record from db
 * 
 * @param $comm
 * @return array
 */
function transform($comm)
{
	return [
		'id' => (int)$comm['id'],
		'name' => $comm['name'],
		'message' => $comm['message'],
		'time' => time_elapsed_string($comm['time']),
	];
}

/**
 * Empty all comments 
 * 
 * @param $dbh
 */
function empty_comments($dbh)
{
	$dbh->query('DELETE FROM comments');
	$dbh->query('VACUUM');
}

function time_elapsed_string($datetime, $full = false)
{
	$now = new DateTime;
	$ago = new DateTime('@' . $datetime);
	$diff = $now->diff($ago);

	$diff->w = floor($diff->d / 7);
	$diff->d -= $diff->w * 7;

	$string = array(
		'y' => 'year',
		'm' => 'month',
		'w' => 'week',
		'd' => 'day',
		'h' => 'hour',
		'i' => 'minute',
		's' => 'second',
	);
	foreach ($string as $k => &$v) {
		if ($diff->$k) {
			$v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
		} else {
			unset($string[$k]);
		}
	}

	if (!$full) $string = array_slice($string, 0, 1);
	return $string ? implode(', ', $string) . ' ago' : 'just now';
}