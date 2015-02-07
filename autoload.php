<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 1/19/14
 * Time: 10:55 PM
 */

/**
 * Class JsonXMLElement
 */

class Position {
    var $lat = 0.0;
    var $lon = 0.0;
}
class Run {
    var $speed = "";
    var $length = "";
    var $date = "";
    var $finalPos = null;
    var $initialPos = null;
    var $spot = "";
    var $maps = "";
    public static $spots;
    static function init()
    {
        self::$spots = array();
        self::$spots[0] = array( 44.43, -1.21, 44.53, -1.05, "Sangui");
        self::$spots[1] = array( 45.05, -1.15, 45.22, -1.06, "Hourt");
        self::$spots[2] = array( 44.92, -1.16, 45.12, -1.08, "Lacan");
        self::$spots[3] = array( 44.30, -1.22, 44.39, -1.08, "Bisca");
        self::$spots[4] = array( 44.62, -1.25, 44.79, -0.97, "Arcach");
        self::$spots[5] = array( 44.45, -1.26, 44.61, -1.21, "Arguin");
    }
    function findSpot()
    {
        $minLat = min( $this->initialPos->lat, $this->finalPos->lat);
        $maxLat = max( $this->initialPos->lat, $this->finalPos->lat);
        $minLong = min( $this->initialPos->lon, $this->finalPos->lon);
        $maxLong = max( $this->initialPos->lon, $this->finalPos->lon);
        foreach( Run::$spots as $value)
        {
 //           error_log( "$minLat $value[0]     $maxLat $value[2]    $minLong  $value[1]     $maxLong  $value[3] \n", 3, "./trace.log");
            if ($value[0] < $minLat && $maxLat < $value[2] && $value[1] < $minLong  && $maxLong < $value[3])
            {
                $this->spot = $value[4];
                return;
            }
        }
        $this->spot = "Lien";
    }
}
class Driver {
    const MAX_RUNS = 5;
    var $name = "";
    var $driverUrl = "";
    var $speed = 0;
    var $runs = [];
    var $updated = 0;
}

function insertRun( &$driver, &$newRun)
{
    $inserted = 0;
    $i = 0;

    // parcours des précedents runs du driver
    for ($i = 0; $i < count( $driver->runs); $i++)
    {
        // run deja rentré?
        if ($driver->runs[$i]->speed == $newRun->speed && $driver->runs[$i]->date == $newRun->date)
            return;
        // le nouveau run est il superieur
        if ($newRun->speed > $driver->runs[$i]->speed)
        {
            $driver->updated++;
            // décalage des runs moins performants, dans la limite de la taille du tableau
            for ($j = min( count( $driver->runs) - 1, Driver::MAX_RUNS - 2); $j >= $i; $j--)
            {
                $driver->runs[$j + 1] = $driver->runs[$j];
            }
            // set du run plus performant
            $driver->runs[$i] = $newRun;
            return;
        }
    }

    // si run inf mais pas 5 results on rajoute
    if ($i ==  count( $driver->runs) && $i < Driver::MAX_RUNS)
    {
        $driver->runs[$i] = $newRun;
        $driver->updated++;
    }
}

class Model {
    var $challenge = [];
}

class ModelDrivers {
    var $drivers = [];
}

function average(&$driver)
{
    $driver->speed = 0;
    if (count($driver->runs) == Driver::MAX_RUNS)
    {
        for( $i = 0; $i < Driver::MAX_RUNS; $i++)
            $driver->speed += $driver->runs[$i]->speed;
    }
    $driver->speed = $driver->speed / Driver::MAX_RUNS;
}



// lecture du post
$buffer = implode( $_POST);

Run::init();

// transformation en objet PHP
$xml = simplexml_load_string($buffer);

// parse de l'objet PHP pour obtenir un objet Driver
$newDriver = new Driver;
$parse = explode( ":", $xml->userLogin);
$newDriver->name = (string ) $parse[0];
$newDriver->driverUrl = "";
if (count($parse) == 2)
    $newDriver->driverUrl = "http://www.windsurfing33.com/forum/memberlist.php?mode=viewprofile&u=$parse[1]";

//error_log( $buffer, 3, "./trace.log");

foreach($xml->resultItem as $resultItem) {

    if ($resultItem["value"] == "500.0" && $resultItem["type"] == "distance") {
        error_log( "valuee = 500", 3, "./trace.log");
        $newRun = new Run;
        $newRun->speed = (float) $resultItem->speed;
        $newRun->length = (float) $resultItem->length;
        $newRun->date = (string) date("m/d H:i", strtotime($resultItem->date));
        $initialPos = new Position;
        $initialPos->lat = (float) $resultItem->initialPos->position["lat"];
        $initialPos->lon = (float) $resultItem->initialPos->position["lon"];
        $finalPos = new Position;
        $finalPos->lat = (float) $resultItem->finalPos->position["lat"];
        $finalPos->lon = (float) $resultItem->finalPos->position["lon"];
        $newRun->initialPos = $initialPos;
        $newRun->finalPos = $finalPos;
        $newRun->findSpot();
        $newRun->maps = "https://www.google.com/maps/preview/dir/$initialPos->lat,$initialPos->lon/$finalPos->lat,$finalPos->lon";
        // insert on good position
        insertRun( $newDriver, $newRun);
    }
}
average( $newDriver);

$found = false;
$dataFile = "gpsChallenge_2015.json";
$dataFileDrivers = "users.json";

// lecture des données archivées sous la forme d'un objet Drivers de type tableau

if (($file_handle = fopen( $dataFile, "r")) != false)
{

    $model = json_decode( fread($file_handle, filesize($dataFile)));
    $drivers = $model->challenge;

    // parcours de la list des drivers enregistrés
    foreach ($drivers as &$driver)
    {
        if ($driver->name == $newDriver->name)
        {
            // on a trouvé le driver
            $found = true;
            $driver->updated = 0;

            if ($newDriver->driverUrl != null && $newDriver->driverUrl != "")
                $driver->driverUrl = $newDriver->driverUrl;

            // parcours des nouveaux runs du driver
            foreach ($newDriver->runs as &$newRun)
            {
                insertRun( $driver, $newRun);
            }
            average( $driver);
            $newDriver = $driver;
            break;
        }
    }
    fclose($file_handle);
}
if ($found == false)
{
    if (($file_handle = fopen($dataFileDrivers, "r")) != false)
    {

        $modelDrivers = json_decode( fread($file_handle, filesize($dataFileDrivers)));
        $driversList = $modelDrivers->drivers;

        // parcours de la list des drivers enregistrés
        foreach ($drivers as &$driver)
        {
            if ($driverList->name == $newDriver->name)
            {
               if ($newDriver->driverUrl == null || $newDriver->driverUrl == "")
                    $newDriver->driverUrl = $driverList->driverUrl;
                break;
            }
        }
        fclose($file_handle);
    }
    $drivers[] = $newDriver;
    // calculate average
}

$model = new Model();
$model->challenge = $drivers;

if (($file_handle = fopen( $dataFile, "w")) != false)
{
	fwrite($file_handle, json_encode($model));
	fclose($file_handle);

	if ($found == false)
    	echo "$newDriver->updated new runs added for driver $newDriver->name";
	else
    	echo "$newDriver->updated runs updated for driver $newDriver->name";
}
else
	echo "Ouverture fichier $dataFile impossible";

http_response_code(200);
?>
