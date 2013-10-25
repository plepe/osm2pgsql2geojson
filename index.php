<?
require "conf.php";

$db['conn'] = pg_connect("dbname={$db['db']} host={$db['host']} user={$db['user']} password={$db['password']}");

// Check bbox parameter
if(!isset($_REQUEST['bbox'])) {
  print "no bbox parameter, e.g. bbox=9,47,9.5,47.5";
  exit;
}
$bbox = split(",", $_REQUEST['bbox']);
if (sizeof($bbox) != 4) {
  print "illegal bbox parameter, need 4 values";
  exit(1);
}
foreach ($bbox as $v) {
  if (!preg_match("/^[+\-]?[0-9]+(\.[0-9]+)?$/", $v)) {
    print "illegal bbox parameter, element is not a number";
    exit(1);
  }
}

// Check and parse qry parameter
if(!isset($_REQUEST['qry'])) {
  print "no qry given";
  exit(1);
}
//$qry = parse_query($_REQUEST['qry']);
$qry = array("line"=>"tags @> 'highway=>primary'", "point"=>"tags @> 'amenity=>bar'");

Header("Content-type: application/json; charset=utf8");

print "{ \"type\": \"FeatureCollection\",\n";
print "  \"features\": [\n";

$first = true;
foreach($qry as $table=>$q) {
  $res = pg_query($db['conn'], 
    "select ".
    "  (CASE WHEN osm_id<0 THEN 'r' || (-osm_id) ELSE 'w' || osm_id END) as id, ".
    "  ST_AsGeoJSON(ST_Transform(way, 4326)) as geo, ".
    "  json_encode(tags) as tags ".
    "from planet_osm_{$table} ".
    "where ".
    "  way && ST_Transform(ST_SetSRID(ST_MakeBox2D(ST_Point($bbox[0], $bbox[1]), ST_Point($bbox[2], $bbox[3])), 4326), 900913) and ".
    "  $q");

  while ($elem = pg_fetch_assoc($res)) {
    if (!$first)
      print ",\n";
    $first = false;

    print "    { \"type\": \"Feature\",\n";
    print "      \"id\": \"{$elem['id']}\",\n";
    print "      \"geometry\": {$elem['geo']},\n";
    print "      \"properties\": {$elem['tags']}\n";
    print "    }";
  }
}

print "\n  ]\n}\n";
