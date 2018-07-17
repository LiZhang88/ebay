<?php
error_reporting(E_ALL);  // Turn on all errors, warnings and notices for easier debugging
// get month
$month=$_GET['month'];
$query=$_GET['kword'];  // You may want to supply your own query
// API request variables
$endpoint = 'http://svcs.ebay.com/services/search/FindingService/v1';  // URL to call
$version = '1.0.0';  // API version supported by your application
$appid = 'LIZhang43-6209-4e26-b570-6c3849756a4';  // Replace with your own AppID
$globalid = 'EBAY-US';  // Global ID of the eBay site you want to search (e.g., EBAY-DE)
$category='15054';//category id for personal cd player
$timeFrom="2015-".$month."-01T00:00:01";
$timeTo="2015-".$month."-31T23:59:59";
//$safequery = urlencode($query);  // Make the query URL-friendly
$i = '0';  // Initialize the item filter index to 0
$items='0';
// Create a PHP array of the item filters you want to use in your request
if($month=='00'){
$filterarray =
  array(
    array(
    'name' => 'SoldItemsOnly',//sold item only
    'value' => 'true',
    'paramName' => '',
    'paramValue' => ''),
  );
}
else{
$filterarray =
  array(
    array(
    'name' => 'EndTimeFrom',
    'value' => $timeFrom,
    'paramName' => '',
    'paramValue' => ''),
    array(
    'name' => 'SoldItemsOnly',//sold item only
    'value' => 'true',
    'paramName' => '',
    'paramValue' => ''),
    array(
    'name' => 'EndTimeTo',
    'value' => $timeTo,
    'paramName' => '',
    'paramValue' => ''),
  );
}

function getCategroy($key){

$myfile = fopen("ebaylist.txt", "r") or die("Unable to open file!");
while(!feof($myfile)) {
  $myCate;
  $tmp=fgets($myfile);
  //echo $key;
  //echo trim($tmp);
  if(substr($tmp,0,8)=="CATEGORY"){
    $myCate=substr($tmp,9);
    //echo($myCate);
  }
  if(trim($tmp)==$key){
    return "$myCate";
  }

}
  return "$myCate";
}

function buildURLArray ($filterarray) {
  global $urlfilter;
  global $i;
  // Iterate through each filter in the array
  foreach($filterarray as $itemfilter) {
    // Iterate through each key in the filter
    foreach ($itemfilter as $key =>$value) {
      if(is_array($value)) {
        foreach($value as $j => $content) { // Index the key for each value
          $urlfilter .= "&itemFilter($i).$key($j)=$content";
        }
      }
      else {
        if($value != "") {
          $urlfilter .= "&itemFilter($i).$key=$value";
        }
      }
    }
    $i++;
  }
  return "$urlfilter";
} // End of buildURLArray function

// Build the indexed item filter URL snippet
buildURLArray($filterarray);
// Construct the  HTTP GET call
function apicall($myquery){
  global $version;
  global $appid;
  global $globalid;
  global $category;
  global $urlfilter;
  global $endpoint;
  $category=trim(getCategroy($myquery));
  //echo($category);
  $myquery .=" -repair -service";
  $safequery=urlencode($myquery);
  $apicall = "$endpoint?";
  $apicall .= "OPERATION-NAME=findCompletedItems";
  $apicall .= "&SERVICE-VERSION=$version";
  $apicall .= "&SECURITY-APPNAME=$appid";
  $apicall .= "&GLOBAL-ID=$globalid";
  $apicall .= "&keywords=$safequery";
  $apicall .= "&categoryId=$category";
  $apicall .= "&paginationInput.entriesPerPage=100";
  $apicall .= "$urlfilter";
  return "$apicall";
}
// Load the call and capture the document returned by eBay API
function getResult($query){

  $myapicall=apicall($query);
  $resp = simplexml_load_file($myapicall);

// Check to see if the request was successful, else print an error
  if ($resp->ack == "Success") {
    $results = '';
  // If the response was loaded, parse it and build links
    foreach($resp->searchResult->item as $item) {
      global $items;
      $pic   = $item->galleryURL;
      $link  = $item->viewItemURL;
      $title = $item->title;
      $items = $items+1;
      
    // For each SearchResultItem node, build a link and append it to $results
      $results .= "<tr><td><img src=\"$pic\"></td><td><a href=\"$link\">$title</a></td></tr>";
    }
  }
// If the response does not indicate 'Success,' print an error
  else {
    $results  = "<h3>Oops! The request was not successful. Make sure you are using a valid ";
    $results .= "AppID for the Production environment.</h3>";
  }
  return "$results";
}
?>
<!-- Build the HTML page with values from the call response -->
<html>
<head>
<title>eBay Search Results</title>
<style type="text/css">body { font-family: arial,sans-serif;} </style>
</head>
<body>
<?php
echo "<h1 align=\"center\">Detail for ".$query." 2015-".$month."</h1>";
echo "<table align=\"center\">";
echo "<tr>";
echo "<td>";
echo getResult($query);
echo "</td>";
echo "</tr>";
echo "</table>";
?>
</body>
</html>