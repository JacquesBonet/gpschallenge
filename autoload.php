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
        self::$spots[5] = array( 44.54, -1.25, 44.61, -1.21, "Arguin");
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
    var $name = "";
    var $driverUrl = "";
    var $speed = 0;
    var $runs = null;
}

class Model {
    var $drivers = [];
}

function average($driver)
{
    $driver->speed = 0;
    if (count($driver->runs) == 5)
    {
        for( $i = 0; $i < 5; $i++)
            $driver->speed += $driver->runs[$i]->speed;
    }
    $driver->speed = $driver->speed / 5;
}

// lecture du post
$buffer = implode( $_POST);

Run::init();

// transformation en objet PHP
$xml = simplexml_load_string($buffer);

// parse de l'objet PHP pour obtenir un objet Driver
$count = 0;
$updated = 0;
$newDriver = new Driver;
$parse = explode( ":", $xml->userLogin);
$newDriver->name = (string ) $parse[0];
$newDriver->driverUrl = null;
if (count($parse) == 2)
    $newDriver->driverUrl = "http://www.windsurfing33.com/forum/memberlist.php?mode=viewprofile&u=$parse[1]";

//error_log( $buffer, 3, "./trace.log");

foreach($xml->resultItem as $resultItem) {

    if ($resultItem["value"] == "500.0" && $resultItem["type"] == "distance") {
        $run = new Run;
        $run->speed = (float) $resultItem->speed;
        $run->length = (float) $resultItem->length;
        $run->date = (string) date("d/m H:i", strtotime($resultItem->date));
        $initialPos = new Position;
        $initialPos->lat = (float) $resultItem->initialPos->position["lat"];
        $initialPos->lon = (float) $resultItem->initialPos->position["lon"];
        $finalPos = new Position;
        $finalPos->lat = (float) $resultItem->finalPos->position["lat"];
        $finalPos->lon = (float) $resultItem->finalPos->position["lon"];
        $run->initialPos = $initialPos;
        $run->finalPos = $finalPos;
        $run->findSpot();
        $run->maps = "https://www.google.com/maps/preview/dir/$initialPos->lat,$initialPos->lon/$finalPos->lat,$finalPos->lon";
        $newDriver->runs[$count++] = $run;
    }
}
average( $newDriver);

$found = false;

// lecture des données archivées sous la forme d'un objet Drivers de type tableau
$file_handle = false;
if (file_exists('./gpsChallenge.json'))
    $file_handle = fopen('./gpsChallenge.json', 'r');

if ($file_handle != false)
{
    $model = json_decode( fread($file_handle, filesize('gpsChallenge.json')));
    $drivers = $model->drivers;

    // parcours de la list des drivers enregistrés
    foreach ($drivers as &$driver)
    {
        if ($driver->name == $newDriver->name)
        {
            // on a trouvé le driver
            $found = true;

            if ($driver->driverUrl == null)
                $driver->driverUrl = $newDriver->driverUrl;

            // parcours des nouveaux runs du driver
            foreach ($newDriver->runs as &$newRun)
            {
                // parcours des précedents runs du driver
                for ($i = 0; $i < count( $driver->runs); $i++)
                {
                    // run deja rentré?
                    if ($driver->runs[$i]->speed == $newRun->speed && $driver->runs[$i]->date == $newRun->date)
                        break;
                    // le run precedent est il inferieur
                    if ($driver->runs[$i]->speed < $newRun->speed && $driver->runs[$i]->date != $newRun->date)
                    {
                        $updated++;
                        // décalage des runs moins performants, dans la limite de la taille du tableau
                        for ($j = min( 3, count( $driver->runs)); $j >= $i; $j--)
                        {
                            $driver->runs[$j + 1] = $driver->runs[$j];
                        }
                        // set du run plus performant
                        $driver->runs[$i] = $newRun;
                        break;
                    }
                }
            }
            average( $driver);
            break;
        }
    }
    fclose($file_handle);
}
if ($found == false)
{
    $drivers[] = $newDriver;
    // calculate average

}

$model = new Model();
$model->drivers = $drivers;

$file_handle = fopen('./gpsChallenge.json', 'w');
fwrite($file_handle, json_encode($model));
fclose($file_handle);
http_response_code(200);

if ($found == false)
    echo "$count new runs added for driver $newDriver->name";
else
    echo "$updated runs updated for driver $newDriver->name";
?>