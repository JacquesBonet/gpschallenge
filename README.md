gpschallenge
============

Web program for managing GPS speed challenge. Use gpsActionReplay for importing result, Kendoui for display.


FUNCTIONS
___________________________________________________________________
Import GPX/SBN file 
Display result on a grid
Select only runs of 500 meters
Calculate average on 5 runs


INSTALL
____________________________________________________________________

Download the zip file
Unzip under a directory (root possible) of your web server


HOW TO IMPORT
____________________________________________________________________

Copy the files on your PHP web server

Install gpsActionReplay software.

Open a gpx ou sbn file.
Next go to Speed Result menu
And select button "Send current to server"
A dialog box open. 
- user entry field : enter the name of the racer. You can also specify a user id permitting to calculate the url user profile which will be displayed on the grid.
  Syntaxe : \<user name\>\[\:\<user id\>\].
- pass entry field : no need 
- server entry field: enter the url to access the autoload.php file located on your web server

The program process only runs of 500.0 meters

The import will generate a json file gpschallenge.json on your web server.




DISPLAY RESULTS
___________________________________________________________________

To display the result, enter the url where is located the index.html file.


CUSTOMIZATION
___________________________________________________________________

Change the host in autoload.php file permitting to access the user profile.

