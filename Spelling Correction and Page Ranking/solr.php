<?php
// make sure browsers see this page as utf-8 encoded HTML
header('Content-Type: text/html; charset=utf-8');

$limit = 10;
$query = isset($_REQUEST['q']) ? $_REQUEST['q'] : false;
$results = false;
$row = 1;
$data1 =array();
$hid = isset($_REQUEST['hid']) ? $_REQUEST['hid'] : false;

ini_set('memory_limit', '-1');
set_time_limit(2000);
$rows = array_map('str_getcsv', file('USAMap.csv')); 	//rows will contain key:value pair of URL:name mapping
$header = array_shift($rows);




if ($query)
{
require_once('Apache/Solr/Service.php');
$solr = new Apache_Solr_Service('localhost', 8983, '/solr/usatoday2/');  
//check if the query is corrected or not

$correct = true;
    if ($hid == "no") {
        include 'SpellCorrector.php';
        $queries = explode(" ", $query);

        $spelled = "";
        foreach ($queries as $q) {
            $spell = strtoLower(SpellCorrector::correct($q)); 
            if (strcasecmp($q, $spell) != 0) {
                $correct = false;
                $spelled = $spelled . ' ' . $spell;
            } else {
                $spelled = $spelled . ' ' . $q;
            }
        }
        $temp_query = $query;
        $query      = trim($spelled);
    }
/*require_once('SpellCorrector.php');

?> 
<p>q is <?php echo $query; ?> </p>
<p>Result is: <?php echo SpellCorrector::correct($query); ?> </p>
<?php
*/


// if magic quotes is enabled then stripslashes will be needed
if (get_magic_quotes_gpc() == 1) {
$query = stripslashes($query); }
try
{
  $algorithm = isset($_GET['algo']) ? $_GET['algo'] : false;  
  if ($algorithm == "Default Algorithm") {    
	$results = $solr->search($query, 0, $limit);
	}
  else if($algorithm =="PageRank Algorithm"){
	$results = $solr->search($query, 0, $limit,$arrayName = array('sort' => 'pageRankFile desc')); //check
	}
}
catch (Exception $e) {
  die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>");
} 
}
?> 

<script>

    $(function() {

     function split( val ) {
      return val.split( " " );
    }
    function extractLast( term ) {
      var res = split( term ).pop();
      if(res != ""){
      return res;
    }
      else{
        return term;
      }
    }
function myfunc(query){
      document.getElementById("q").value = query;
      document.getElementById("hid").value = "yes";
      document.getElementById("form").submit();
      }
      
function lastWord(str){
        if(str.indexOf(' ') === -1){
            return str;
        }
      return str.substring(str.lastIndexOf(" ") + 1, str.length);
    }
    function dropValue(str){
        if(str.indexOf(' ') === -1){
            return "";
        }
      return str.substring(0, str.lastIndexOf(" ")) + " ";
    }


 var URL_PREFIX = "http://localhost:8983/solr/usatoday2/suggest?q=";
 var URL_SUFFIX = "&wt=json&indent=true";
 $( "#q" )
      // don't navigate away from the field on tab when selecting an item
      .bind( "keydown", function( event ) {
        if ( event.keyCode === $.ui.keyCode.TAB &&
            $( this ).autocomplete( "instance" ).menu.active ) {
          event.preventDefault();
        }
      })
      .autocomplete({
        minLength: 0,
 source : function(request, response) {
 var URL = URL_PREFIX + extractLast( request.term.toLowerCase() ).trim()  + URL_SUFFIX;
 $.ajax({
 url : URL,
 success : function(data) {
 var docs = JSON.stringify(data.suggest.suggest);
 var jsonData = JSON.parse(docs);
 var unique_result = [];
 var i = 0;
 response($.map(jsonData[extractLast( request.term.toLowerCase() )].suggestions,function(value, key) {
 return {
 label : dropValue($("#q").val()).toLowerCase() + value.term.replace(/[^a-zA-Z0-9/./_ ]/g, "") }  
}));
 },
 });
 },
        focus: function() {
          // prevent value inserted on focus
          return false;
        },
        select: function( event, ui ) {
          // add placeholder to get the comma-and-space at the end
          this.value = ui.item.value;
      dfunc(this.value);
          return false;
        }
 })
 });

function dfunc(query){
    document.getElementById("q").value = query;
    document.getElementById("form1").submit();
}
</script>
<html>
<head>
<title>PHP Solr Client Example</title>
</head> 
<body>
<form id="form1" accept-charset="utf-8" method="get">
<div align = "center">
<label for="q">Search:</label>
<input id="q" name="q" type="text" autocomplete="off" value="<?php if(!$correct) { echo htmlspecialchars($temp_query, ENT_QUOTES, 'utf-8');} else { echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); } ?>"/>
<br/>

<input type="radio" name="algo" value = "PageRank Algorithm" <?php if(!isset($_GET['algo']) || (isset($_GET['algo']) && $_GET['algo'] =="PageRank Algorithm")) echo 'checked="checked"';?>  id="pagerank"> PageRank Algorithm
    
    
<input type="radio" name="algo" value ="Default Algorithm" <?php if(!isset($_GET['algo']) || (isset($_GET['algo']) && $_GET['algo'] =="Default Algorithm")) echo 'checked="checked"';?>  id="default"> Default Algorithm

<input id="hid" type="hidden" name="hid" value="no">
    
<br/>
<br/>
<input type="submit"/>
</div>
</form> 
<?php
// display results
if ($results) {
$total = (int) $results->response->numFound;
$start = min(1, $total);
$end = min($limit, $total); 
if(!$correct) {
?> Showing results for: <?php echo $query; ?>  </br>
Search instead for: <a href="http://localhost/ui.php?q=<?php echo $temp_query; ?>&algo=Default+Algorithm&hid=yes"><?php echo $temp_query ?></a>
<?php
}
?>
</br><div>Results <?php echo $start; ?> - <?php echo $end;?> of <?php echo $total; ?>:</div>
<ol> 
<?php
// iterate result documents
foreach ($results->response->docs as $doc)
{ ?>
<?php
// iterate document fields / values
$title = $doc -> title;
$date = $doc -> date;
$author = $doc -> meta_author;
$id = $doc->id;
$title = $doc->title;
$url = $doc->og_url;
?>
<li>




<p><a href = "<?php echo $url;?>"> <?php echo htmlspecialchars($title, ENT_QUOTES, 'utf-8'); ?></a></br>

URL :<a href = "<?php echo $url; ?>"> <?php echo $url;  ?> </a> </br>

Description : <?php echo substr($doc -> description,0,200); ?> </br>

ID : <?php echo $id_formatted ?> </br>

Snippet : <?php
if(start_index != false) {
    
$var = file_get_contents($id);
   $doc = new DOMDocument();
   $snippet = "null";
   $search = $query;
   while(($term = $doc->getElementsByTagName("script")) && $term->length){
        $term->item(0)->parentNode->removeChild($term->item(0));
   }
   libxml_use_internal_errors(true);
   $doc->loadHTML($var);
   $content = array();
    foreach($doc->getElementsByTagName('body') as $head){
        foreach($head->childNodes as $cell){
                $content[] = $cell->nodeValue;
 }
}

   $regex_html = '/[^\\>"\/#]{70,100}('.$query.')[^\\>"\/<#]{70,160}/i';
   for($i=0; $i<sizeof($content); $i++){
        if(preg_match($regex_html, $content[$i], $html) == 1 ){


                $snippet = html_entity_decode($html[0], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        else{
                if(strpos($search, ' ') >=0){
                        $parts = preg_split("/[\s]+/", $search);
                        foreach($parts as $str){
                                $search = $str;


                                $regex_html = '/[^\\>"\/#]{70,100}('.$query.')[^\\>"\/<#]{70,160}/i';
                                if(preg_match($regex_html, $content[$i], $html)==1){
                                        $snippet = html_entity_decode($html[0], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                                        break;
                                }
                        }
                }
        }
        if($snippet != "null"){
                break;
        }
    }

echo $snippet;
}

 }
 ?> </br>
</p>


</li> <?php
} ?>
</ol>
<?php }
?>
</body> 
</html>
