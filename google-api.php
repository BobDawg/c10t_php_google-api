<?php ini_set("memory_limit", "200M");
/**
 * google-api.php
 *
 * authors  - Ben Rice (dashiva@dashiva.com)
 *          - Robert LaFont, Jr (robert.lafont.jr@bobdawg.org)
 *
 * The function of this script is to render several Minecraft maps using the
 * map generator "c10t". A googlemap is then created using the tiles, and output.
 *
 * Usage:
 *   php google-api.php -w=<world> -o=<output> [options]
 *   
 *   Required Arguments:
 *     -w=<world>    - World directory path, with trailing slash.
 *                     (ex. "..\MinecraftServer\world\")
 *     -o=<output>   - Output directory path, with trailing slash.
 *                     (ex. "..\map\")
 *                     
 *   Options:
 *     -h            - Display this help text.
 *     -O=<opts>     - Extra options to pass directly to c10t, in quotes.
 * 
 * Example: 
 *   php google-api.php -w=myworld -o=gmap -O="-isometric -r 270"
 */

### Configuration Options ######

define("VERBOSE", false);

define("DAY", true);
define("NIGHT", true);
define("CAVE", true);
define("HEIGHT", true);
define("DIAMOND", true);
define("DUNGEON", true);
define("LAVA", true);

#################################

function parseArgs($argv){
    array_shift($argv); $o = array();
    foreach ($argv as $a){
        if (substr($a,0,2) == '--'){ $eq = strpos($a,'=');
            if ($eq !== false){ $o[substr($a,2,$eq-2)] = substr($a,$eq+1); }
            else { $k = substr($a,2); if (!isset($o[$k])){ $o[$k] = true; } } }
        else if (substr($a,0,1) == '-'){
            if (substr($a,2,1) == '='){ $o[substr($a,1,1)] = substr($a,3); }
            else { foreach (str_split(substr($a,1)) as $k){ if (!isset($o[$k])){ $o[$k] = true; } } } }
        else { $o[] = $a; } }
    return $o;
}

$args = parseArgs($argv);

# Required args
if((empty($args['w']) || empty($args['o'])) || isset($args['h']) ) {
	echo("google-api.php - Minecraft map parsing script
version: 1.7a, built on Mar 12 2011
by: Robert LaFont, Jr <robert.lafont.jr@bobdawg.org> et al.

Usage:
  php ".$argv[0]." -w=<world> -o=<output> [options]
  
  Required Arguments:
    -w=<world>    - World directory path, with trailing slash.
                    (ex. \"..\\MinecraftServer\\world\\\")
    -o=<output>   - Output directory path, with trailing slash.
                    (ex. \"..\\map\\\")
                    
  Options:
    -h            - Display this help text.
    -O=<opts>     - Extra options to pass directly to c10t, in quotes.
");
	exit();
  //Original by Ben Rice <dashiva@dashiva.com>
}

$outHTML = "index.html";
$inPath = $args['w'];		  # World files directory
$outDir = $args['o'];		  # Tile directory
if( isset($args['O']) ) {	# c10t args
	$c10tArgs = $args['O']; //"--isometric";
}

$zoom = 6;
$factor = 5;
$splits = "128 256 512 1024 2048 4096";
$base = 256;
                   
# Verify world directory is valid
if( !is_dir($inPath) || !file_exists($inPath."level.dat") ) {
	echo("Invalid world directory: ".$inPath."\r\n");
	exit();
}
else
	echo("World directory...OK\r\n");

# Verify the executable
exec("c10t", $output);
if( empty($output) ) {
	echo("Cannot find c10t.exe!\r\n");
	exit();
}
else
	echo("c10t.exe...OK\r\n");

# check / create output dir for images
if( !is_dir($outDir) ) {
	mkdir($outDir);
	echo("Creating folder ".$outDir."\r\n");
}
else
	echo("Folder ".$outDir." already exists.\r\n");
  
# check / create output dir for images
if( !is_dir($outDir."tiles\\") ) {
  mkdir($outDir."tiles\\");
  echo("Creating folder ".$outDir."tiles\\\r\n");
}
else
  echo("Folder ".$outDir."tiles\\ already exists.\r\n");


/**
 * function generate
 *
 * This will execute c10t with the proper parameters.
 */
function generate($arg1, $name, $pixelsplit) {
	$start = time();
	global $inPath;
	global $outDir;
  global $base;
	global $c10tArgs;
	echo("Generating: ".$name."...");
	
	# generate a set of split files
  $outputPattern = $outDir."tiles\\".$name.".%d.%d.%d.png";
  # Output Pattern Details:                   ^  ^  ^
  #                             Zoom Level ---|  |  |--- Y Coord of Tile
  #                        X Coord of Tile ------|
	$run = "c10t ".$c10tArgs." ".$arg1." --split=\"".$pixelsplit."\" --split-base=".$base." -w ".$inPath." -o ".$outputPattern." --write-json ".$outDir.$name.".json";
	
	# Uncomment for details
	if(VERBOSE) {
		echo("\r\n".$run."\r\n");
	}
	
  # Execute c10t to render the tiles.
  exec($run, $output);
	
  if(VERBOSE) {
		var_dump($output);
  }
	$end = time();
	$elapsed = $end - $start;
	echo("Done in ".getTimeStr($elapsed)."\r\n");
	
}

/**
 * function read
 *
 * This will open a file and return it's contents.
 */
function read($file) {
	if(file_exists($file)) {
		$in = fopen ($file, "r");
		if (!$in)
			return false;
		$raw = "";
		if(filesize($file) <= 0)
			return false;
		else 
			$raw = fread($in, filesize($file));
		fclose($in);
		
		return $raw;
	}
	return false; 
}

/**
 * function write
 *
 * This will write contents to a file.
 */
function write($file, $content) {
	if( empty($file) || empty($content) )
		return false;
	
	$fl = fopen($file, "w+"); 
	fputs($fl, $content);
	fclose($fl);
	return true;
}

/**
 * function getTimeStr
 *
 * Description: Returns human readable time representing the number of 
 *              seconds passed to this function.
 */
function getTimeStr($durationInSeconds)
{
  $week = floor($durationInSeconds / 86400 / 7);
  $day = $durationInSeconds / 86400 % 7;
  $hour = $durationInSeconds / 3600 % 24;
  $min = $durationInSeconds / 60 % 60;
  $sec = $durationInSeconds % 60;

  if($week != 0)
  {
    $time  = $week . isPlural($week, " week");
    if($day != 0)
      $time .= ", " . $day  . isPlural($day, " day");
  }
  else if($day != 0)
  {
    $time  = $day  . isPlural($day, " day");
    if($hour != 0)
      $time .= ", " . $hour . isPlural($hour, " hour");
  }
  else if($hour != 0)
  {
    $time  = $hour . isPlural($hour, " hour");
    if($min != 0)
      $time .= ", " . $min  . isPlural($min, " minute");
  }
  else if($min != 0)
  {
    $time  = $min  . isPlural($min, " minute");
    if($sec != 0)
      $time .= ", " . $sec  . isPlural($sec, " second");
  }
  else if($sec != 0)
  {
    $time  = $sec  . isPlural($sec, " second");
  }
  
  return $time;
}

/**
 * function isPlural
 *
 * Description: Return pluralized text.
 */
function isPlural($num, $word){
  if($num > 1)
    return $word."s";
  else
    return $word;
}


# Start the processing logic
# store the start time for calculating the duration of this script.
$_start = time();


if(DAY)
  generate("", "day", $splits);
if(NIGHT)
  generate("-n", "ngt", $splits);
if(CAVE)
  generate("-c", "cve", $splits);	# Cavemode
if(HEIGHT)
  generate("-H", "hgt", $splits);	# Heightmap
if(DIAMOND)
  generate("--hide-all --include 56 -B DiamondOre=191,255,0,255", "dmd", $splits);
if(DUNGEON)
  generate("--hide-all --include 4 --include 48", "dng", $splits);
if(LAVA)
  generate("--hide-all --include 10 --include 11", "lav", $splits);


$output = '<!DOCTYPE html>
<html>
  <head>
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
    <title>Latest Overhead Map - BobDawg\'s Minecraft Server</title>
    <meta name="description" content="The latest c10t overhead map of BobDawg\'s Minecraft Server" />
    <meta name="keywords" content="BobDawg\'s, Minecraft, Overhead, Latest, c10t, Map, Server" />
    <link rel="shortcut icon" href="favicon.ico" />
    <link rel="icon" href="favicon.ico" type="image/vnd.microsoft.icon" sizes="16x16" />
    <link rel="icon" href="favicon_large.ico" type="image/vnd.microsoft.icon" sizes="16x16 24x24 32x32 48x48 64x64 128x128 150x150" />
    <link rel="icon" href="favicon.png" type="image/png" sizes="16x16" />
    <link rel="apple-touch-icon" type="image/png" href="apple-touch-icon.png" sizes="114x114" />
    <link rel="apple-touch-icon" type="image/png" href="apple-touch-icon-57x57.png" sizes="57x57" />
    <link rel="apple-touch-icon" type="image/png" href="apple-touch-icon-72x72.png" sizes="72x72" />
    <script type="text/javascript">
      var _gaq = _gaq || [];
      _gaq.push([\'_setAccount\', \'UA-284445-2\']);
      _gaq.push([\'_setDomainName\', \'none\']);
      _gaq.push([\'_setAllowLinker\', true]);
      _gaq.push([\'_trackPageview\']);

      (function() {
        var ga = document.createElement(\'script\'); ga.type = \'text/javascript\'; ga.async = true;
        ga.src = (\'https:\' == document.location.protocol ? \'https://ssl\' : \'http://www\') + \'.google-analytics.com/ga.js\';
        var s = document.getElementsByTagName(\'script\')[0]; s.parentNode.insertBefore(ga, s);
      })();
    </script>
    <link href="//ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/ui-darkness/jquery-ui.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.5.2/jquery.min.js"></script>
    <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jqueryui/1.8.11/jquery-ui.min.js"></script>
    <script type="text/javascript" src="//maps.google.com/maps/api/js?sensor=false"></script>
    <script type="text/javascript" src="libc10t.google.js"></script>
    <script type="text/javascript" src="options.js"></script>
    <!-- Make the document body take up the full screen -->
    <style type="text/css">
        v\:* {behavior:url(#default#VML);}
        html, body {width: 100%; height: 100%; background-color: black;}
        body {margin-top: 0px; margin-right: 0px; margin-left: 0px; margin-bottom: 0px}
        .marker_header {font-family: "Trebuchet MS",Helvetica,Jamrul,sans-serif;}
        .marker_body {text-align:center; font-family: Consolas, "Lucida Console", "Courier New", monospace;}
        .text-overlay {border:1px solid #77C; padding: 0px 2px; height: 16px; background-color: black;}
        .text-overlay, #link {color: #77C; font-size: 9pt; font-family: "Trebuchet MS",Helvetica,Jamrul,sans-serif;}
        #link {background-color: transparent;}
        #coord {position:absolute; bottom: 20px; right:1px; z-index: 1000;}
        #updated {position:absolute; bottom:1px; right:1px; z-index: 1000;}
    </style>
  </head>
  <body onload="initialize(\'map_canvas\', options, modes)">
    <div id="map_canvas" style="width: 100%; height: 100%;"></div>
    <span id="coord" class="text-overlay">Link: <a href="?ll=0,0&z=1&t=day" id="link">?ll=0,0&z=1&t=day</a></span>
    <span id="updated" class="text-overlay">Updated: '. date("Y-m-d g:iA") .'</span>
    <!-- Start of AWStats Code 
      <script type="text/javascript" src="http://bobdawg.org/awstats_misc_tracker.php"></script>
      <noscript><div class="awstats"><img src="http://bobdawg.org/awstats_misc_tracker.php?nojs=y" alt="statistics" height="0" width="0" style="display: none; border: none; position:absolute; top:10px; left:5000px;" /></div></noscript>
         End of AWStats Code -->
  </body>
</html>';

$outputJS = '// Read a page\'s GET URL variables and return them as an associative array.
function getUrlVars() {
    var vars = [], hash;
    var hashes = window.location.href.slice(window.location.href.indexOf(\'?\') + 1).split(\'&\');

    for(var i = 0; i < hashes.length; i++)
    {
        hash = hashes[i].split(\'=\');
        vars.push(hash[0]);
        vars[hash[0]] = hash[1];
    }

    return vars;
}

jQuery(window).load(function() {
  // Hide mobile browser\'s address bar when page is done loading.
  setTimeout(function() { window.scrollTo(0, 1); }, 1);
  jQuery.getJSON("lastUpdate.json", { rand: Math.random()*10 }, function(jsonObj) {
      window.timestamp = jsonObj.lastUpdate.timestamp;
  });
});

jQuery(document).ready(function() {
  updateDialog = jQuery("<div></div>")
    .html("There is a more recent render available. Would you like to refresh the page to see it?")
    .dialog({
      autoOpen: false,
      title: "Refresh Map",
      modal: false,
      resizable: false,
      beforeClose: function(event, ui) {
          //clearInterval(window.checkUpdateID);
          window.checkUpdateIntervalID = setInterval("checkUpdate()", 3600000);
      },
      buttons: {
          "Yes": function() { location.replace(document.getElementById("link").href); },
          "Later": function() { jQuery(this).dialog("close"); }
      }
    });
  jQuery("#checkupdate-link").click(function() {
    checkUpdate();
    return false;
  });
});

window.checkUpdateIntervalID = setInterval("checkUpdate()", 600000);
    
function checkUpdate() {
  // alert("checkUpdate() triggered."); // Debugging
  jQuery.getJSON("lastUpdate.json", { rand: Math.random()*10 }, function(jsonObj) {
    //alert("getJSON(\'lastUpdate.json\') triggered."); // Debugging
    var latestTimestamp = jsonObj.lastUpdate.timestamp;
    if(window.timestamp != latestTimestamp) {
      clearInterval(window.checkUpdateIntervalID);
      updateDialog.dialog("open");
    }
  });
}

function isset(varname){
  return(typeof(window[varname])!=\'undefined\');
}

function extend(t , o) {
  for (k in o) { if (o[k] != null) { t[k] = o[k]; } }
  return t;
}

function keys(o) {
  var a = [];
  for (m in modes) { a[a.length] = m; };
  return a;
}

// The maximum width/height of the grid in regions (must be a power of two)
var GRID_WIDTH_IN_REGIONS = 4096;
// Map from a GRID_WIDTH_IN_REGIONS x GRID_WIDTH_IN_REGIONS square to Lat/Long (0, 0),(-90, 90)
var SCALE_FACTOR = 90.0 / GRID_WIDTH_IN_REGIONS;

// Override the default Mercator projection with Euclidean projection
// (insert oblig. Flatland reference here)
function EuclideanProjection() {};

EuclideanProjection.prototype.fromLatLngToPoint = function(latLng, opt_point) {
  var point = opt_point || new google.maps.Point(0, 0);
  point.x = latLng.lng() / SCALE_FACTOR;
  point.y = latLng.lat() / SCALE_FACTOR;
  return point;
};

EuclideanProjection.prototype.fromPointToLatLng = function(point) {
  var lng = point.x * SCALE_FACTOR;
  var lat = point.y * SCALE_FACTOR;
  return new google.maps.LatLng(lat, lng, true);
};

function new_map_type(m, o, ob) {
  var world = ob.data.world;
  return extend(
    {
      getTileUrl: function(c, z) {
        var img = o.host + "tiles/" + m + "." + (world.split - z) + "." + c.x + "." + c.y + ".png";
        return img;
      },
      isPng: true,
      name : "none",
      alt : "none",
      minZoom: 1, maxZoom: world.split,
      tileSize: new google.maps.Size(256, 256)
    },
    ob
  );
}

function initialize(id, opt, modes) {
  var element = document.getElementById(id);
  
  opt = extend(opt, {
    mapTypeControlOptions: {
      mapTypeIds: keys(modes),
      style: google.maps.MapTypeControlStyle.DROPDOWN_MENU}});
  
  var map = new google.maps.Map(element, opt);

  var firstMode = null;
  
  for (m in modes) {
    var imt  = new google.maps.ImageMapType(new_map_type(m, opt, modes[m]));
    imt.projection = new EuclideanProjection();
    
    // Now attach the grid map type to the maps registry
    map.mapTypes.set(m, imt);
    if (firstMode == null) firstMode = m;
  }
  
  map.setMapTypeId(firstMode);

  var globaldata = modes[firstMode].data;
  var world = globaldata.world;
  var factor = Math.pow(2, opt.factor);
  
  {
    // Changed "center-x" and "center-y" to "cx" and "cy" to match
    // updates to the c10t JSON output format.
    var center = new google.maps.Point(world.cx / factor, world.cy / factor);
    var latlng = EuclideanProjection.prototype.fromPointToLatLng(center);
    window.spawn = {
      "latlng": latlng,
         "lat": latlng.lat(),
         "lng": latlng.lng()
    };
    map.setCenter(latlng);
    map.setZoom(1);
    makeLink(map);
  }
  
  for (var i = 0; i < globaldata.markers.length; i++)
  {
    var m = globaldata.markers[i];
    var point = new google.maps.Point(m.x / factor, m.y / factor);
    var latlng = EuclideanProjection.prototype.fromPointToLatLng(point)

    if(m.type == "player") {
      var markerIcon = "//maps.gstatic.com/mapfiles/marker.png";
    } else if(m.type == "sign") {
      var markerIcon = "//maps.gstatic.com/mapfiles/marker_green.png";
    } else {
      var markerIcon = "//maps.gstatic.com/mapfiles/marker_yellow_blue.png";
    }
    var markerType = m.type;
    var shadowIcon = new google.maps.MarkerImage("//maps.gstatic.com/mapfiles/shadow50.png",
        // The shadow image is larger in the horizontal dimension
        // while the position and offset are the same as for the main image.
        new google.maps.Size(37, 34),
        new google.maps.Point(0,0),
        new google.maps.Point(10, 34));

    var marker = new google.maps.Marker({
        position: latlng, 
        map: map, 
        title: m.text,
        icon: markerIcon,
        shadow: shadowIcon
    });
    var contentString = "<b class=\"marker_header\">"+ markerType.replace(markerType.charAt(0),markerType.charAt(0).toUpperCase()) +":</b><br><pre class=\"marker_body\">"+ m.text +"</pre>";
    attachInfoWindow(map, marker, contentString);
  }
  
  if (window.attachEvent) {
    window.attachEvent("onresize", function() {this.map.onResize()} );
  } else {
    window.addEventListener("resize", function() {this.map.onResize()} , false);
  }
  
  google.maps.event.addListener(map, "zoom_changed", function() {
    makeLink(map);
  });
  google.maps.event.addListener(map, "center_changed", function() {
    makeLink(map);
  });
  google.maps.event.addListener(map, "maptypeid_changed", function() {
    makeLink(map);
  });
  
  var UrlVars = getUrlVars();
  if(UrlVars[\'ll\']) {
    var latilong = UrlVars[\'ll\'].split(\',\');
    //map.setCenter(new google.maps.LatLng(latilong[0] + spawn.lat, latilong[1] + spawn.lng, true));
    map.setCenter(new google.maps.LatLng(latilong[0], latilong[1], true));
  }
  if(UrlVars[\'z\']) {
    map.setZoom(parseInt(UrlVars[\'z\']));
  }
  if(UrlVars[\'t\']) {
    map.setMapTypeId(UrlVars[\'t\']);
  }
}


function makeLink(map) {
  //var spawnRelCoord = new google.maps.LatLng(map.getCenter().lat() - spawn.lat, map.getCenter().lng() - spawn.lng, true);
  //var text = "?ll=" + spawnRelCoord.toUrlValue(3)
  var text = "?ll=" + map.getCenter().toUrlValue(3)
           + "&z=" + map.getZoom()
           + "&t=" + map.getMapTypeId();
  var addr = location.href.substring(0,location.href.lastIndexOf(location.search)) + text;
           //+ "?ll=" + spawnRelCoord.toUrlValue(3)
           //+ "&z="  + map.getZoom()
           //+ "&t="  + map.getMapTypeId();
  document.getElementById("link").href      = addr;
  document.getElementById("link").innerHTML = text;
}

function attachInfoWindow(map, marker, text) {
  var infowindow = new google.maps.InfoWindow({
        content: text,
        size: new google.maps.Size(25,20)
  });
  google.maps.event.addListener(marker, "click", function() {
    //if(!isset(lastinfowindow)) {
    //  lastinfowindow = infowindow;
    //}
    //else {
    //  lastinfowindow.close();
    //}
    infowindow.open(map,marker); 
  });
}';

echo("Writing ".$outHTML."...");
if( !write($outDir.$outHTML, $output) ) {
	echo("Error writing ".$outHTML);
	exit();
}
echo("Done.\r\n");

echo("Writing libc10t.google.js...");
if( !write($outDir."libc10t.google.js", $outputJS) ) {
	echo("Error writing libc10t.google.js...");
	exit();
}
echo("Done.\r\n");

# Googlemap options JS
if( DAY && ($jsonDay = read($outDir."day.json")) ) {
	//echo("Error opening day.json");
	$jsonDay = '\'day\': { name: "Day", alt: "Day Mode", data: '.$jsonDay.'},';
}
if( NIGHT && ($jsonNgt = read($outDir."ngt.json")) ) {
	//echo("Error opening ngt.json");
	$jsonNgt = '\'ngt\': { name: "Night", alt: "Night Mode", data: '.$jsonNgt.'},';
}
if( CAVE && ($jsonCve = read($outDir."cve.json")) ) {
	//echo("Error opening cve.json");
	$jsonCve = '\'cve\': { name: "Cave", alt: "Cave Mode", data: '.$jsonCve.'},';
}
if( HEIGHT && ($jsonHgt = read($outDir."hgt.json")) ) {
	//echo("Error opening hgt.json");
	$jsonHgt = '\'hgt\': { name: "Height", alt: "Height Mode", data: '.$jsonHgt.'},';
}
if( DIAMOND && ($jsonDmd = read($outDir."dmd.json")) ) {
	//echo("Error opening dmd.json");
	$jsonDmd = '\'dmd\': { name: "Diamond", alt: "Diamond Mode", data: '.$jsonDmd.'},';
}
if( DUNGEON && ($jsonDng = read($outDir."dng.json")) ) {
	//echo("Error opening dng.json");
	$jsonDng = '\'dng\': { name: "Dungeons", alt: "Dungeon Mode", data: '.$jsonDng.'},';
}
if( LAVA && ($jsonLav = read($outDir."lav.json")) ) {
	//echo("Error opening lav.json");
	$jsonLav = '\'lav\': { name: "Lava", alt: "Lava Mode", data: '.$jsonLav.'},';
}

#"'.substr($outDir, 0, -1).'/",
$js = 'var options = {
  factor: '.$factor.',
  host: "",			
  scaleControl: false,
  navigationControl: true,
  streetViewControl: false,
  noClear: false,
  backgroundColor: "#000000",
  isPng: true,
}

var modes = {
  '.$jsonDay.'
  '.$jsonNgt.'
  '.$jsonCve.'
  '.$jsonHgt.'
  '.$jsonDmd.'
  '.$jsonDng.'
  '.$jsonLav.'
}';


echo("Writing options.js...");
if( !write($outDir."options.js", $js) ) {
	echo("Error writing options.js");
	exit();
}
echo("Done.\r\n");

echo("Writing lastUpdate.json...");
$curTimestamp = '{"lastUpdate": {"timestamp": '. time() ."}}\r\n";
if( !write($outDir."lastUpdate.json", $curTimestamp) ) {
  echo("Error writing lastUpdate.json");
  exit();
}
echo("Done.\r\n");

$_end = time();

echo("Complete in ".getTimeStr($_end - $_start) );

?>
