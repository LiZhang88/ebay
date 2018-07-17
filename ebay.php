<?php
error_reporting(E_ALL);  // Turn on all errors, warnings and notices for easier debugging
// get month
$month=$_GET['month'];
// API request variables
$endpoint = 'http://svcs.ebay.com/services/search/FindingService/v1';  // URL to call
$version = '1.0.0';  // API version supported by your application
$appid = 'LIZhang43-6209-4e26-b570-6c3849756a4';  // Replace with your own AppID
$globalid = 'EBAY-US';  // Global ID of the eBay site you want to search (e.g., EBAY-DE)
$query = array();  // You may want to supply your own query
$category='15054';//category id for personal cd player
$timeFrom="2016-".$month."-01T00:00:01";
$timeTo="2016-".$month."-31T23:59:59";
//$safequery = urlencode($query);  // Make the query URL-friendly
$i = '0';  // Initialize the item filter index to 0
$items='0';
$itemNumber=array();
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
//get my query from txt file
$myfile = fopen("ebaylist.txt", "r") or die("Unable to open file!");
while(!feof($myfile)) {
  $tmp=fgets($myfile);
  if(substr($tmp,0,8)!="CATEGORY"){
    $query[]=$tmp;
  }
}
fclose($myfile);
//get categroy
function getCategroy($key){
$myfile = fopen("ebaylist.txt", "r") or die("Unable to open file!");
while(!feof($myfile)) {
  $myCate;
  $tmp=fgets($myfile);
  if(substr($tmp,0,8)=="CATEGORY"){
    $myCate=substr($tmp,9);
    //echo($myCate);
  }
  if($tmp==$key){
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
  //echo($myquery);
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
for($x=0;$x<count($query);$x++){
global $items;
global $itemNumber;
$items=0;
/*echo "<h1>eBay Search Results for ".$query[$x]."</h1>";
echo "<table>";
echo "<tr>";
echo "<td>";*/
getResult($query[$x]);
    //echo(getCategroy($query[$x]));
    $itemNumber[]=$items;
/*echo "</td>";
echo "</tr>";
echo "</table>";*/
}
$resultArray=array_combine($query, $itemNumber);
arsort($resultArray);
//create a picture
 /* CAT:Bar Chart */

 /* pChart library inclusions */
 include("pChart/class/pData.class.php");
 include("pChart/class/pDraw.class.php");
 include("pChart/class/pImage.class.php");

 /* Create and populate the pData object */
 $MyData = new pData();  
 $MyData->addPoints($itemNumber,"Numbers");
 $MyData->setAxisName(0,"Numbers");
 $MyData->addPoints($query,"Models");
 $MyData->setSerieDescription("Models","Models");
 $MyData->setAbscissa("Models");
 $MyData->setAbscissaName("Models");
 $MyData->setAxisDisplay(0,AXIS_FORMAT_METRIC,1);

 /* Create the pChart object */
 $myPicture = new pImage(500,500,$MyData);
 $myPicture->drawGradientArea(0,0,500,500,DIRECTION_VERTICAL,array("StartR"=>240,"StartG"=>240,"StartB"=>240,"EndR"=>180,"EndG"=>180,"EndB"=>180,"Alpha"=>100));
 $myPicture->drawGradientArea(0,0,500,500,DIRECTION_HORIZONTAL,array("StartR"=>240,"StartG"=>240,"StartB"=>240,"EndR"=>180,"EndG"=>180,"EndB"=>180,"Alpha"=>20));
 $myPicture->setFontProperties(array("FontName"=>"pChart/fonts/pf_arma_five.ttf","FontSize"=>6));

 /* Draw the chart scale */ 
 $myPicture->setGraphArea(100,30,480,480);
 $myPicture->drawScale(array("CycleBackground"=>TRUE,"DrawSubTicks"=>TRUE,"GridR"=>0,"GridG"=>0,"GridB"=>0,"GridAlpha"=>10,"Pos"=>SCALE_POS_TOPBOTTOM)); // 

 /* Turn on shadow computing */ 
 $myPicture->setShadow(TRUE,array("X"=>1,"Y"=>1,"R"=>0,"G"=>0,"B"=>0,"Alpha"=>10));

 /* Draw the chart */ 
 $myPicture->drawBarChart(array("DisplayPos"=>LABEL_POS_INSIDE,"DisplayValues"=>TRUE,"Rounded"=>TRUE,"Surrounding"=>30));

 /* Write the legend */ 
 $myPicture->drawLegend(570,215,array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL));

 /* Render the picture (choose the best way) */
 $myPicture->render("picture/hi.png");
 echo '<img src="picture/hi.png" align="center">';





//Summary
echo "<h1 align=\"center\">Summary 2015-".$month."</h1>";
echo "<table align=\"center\">";
echo "<tr>";
echo "<td>";
echo "Model";
echo "</td>";
echo '<td align="right">';
echo "Total Sold Number";
echo "</td>";
echo "</tr>";
foreach ($resultArray as $k => $v){
  echo "<tr>";
  echo "<td>";
  echo "<a href=\"detail.php?month=".$month."&kword=".$k."\">".$k."</a>";
  echo "</td>";
  echo '<td align="right">';
  echo $v;
  echo "</td>";
  echo "</tr>";
}
echo "</table>";

?>
</body>
</html>