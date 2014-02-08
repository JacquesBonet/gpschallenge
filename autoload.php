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
    var $lat = "";
    var $lon = "";
}
class Run {
    var $speed = "";
    var $length = "";
    var $date = "";
    var $finalPos = null;
    var $initialPos = null;
    var $spot = "";
}
class Driver {
    var $name = "";
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

function location($driver)
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
error_log( $buffer, 3, "./trace.log");

// transformation en objet PHP
$xml = simplexml_load_string($buffer);

// parse de l'objet PHP pour obtenir un objet Driver
$count = 0;
$updated = 0;
$newDriver = new Driver;
$newDriver->name = (string ) $xml->userLogin;

// error_log( $xml->userLogin, 3, "./trace.log");

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
        $run->spot = "https://www.google.com/maps/preview/dir/$initialPos->lat,$initialPos->lon/$finalPos->lat,$finalPos->lon";
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

            // parcours des nouveaux runs du driver
            foreach ($newDriver->runs as &$newRun)
            {
                // parcours des précedents runs du driver
                for ($i = 0; $i < count( $driver->run); $i++)
                {
                    // le run precedent est il inferieur
                    if ($driver->run[$i]->speed < $newRun->speed)
                    {
                        $updated++;
                        // décalage des runs moins performants, dans la limite de la taille du tableau
                        for ($j = min( 4, count( $driver->run)); $j >= $i; $j--)
                        {
                            $driver->run[$j + 1] = $driver->run[$j];
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