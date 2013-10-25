<?
require "conf.php";
require "inc/parse_selectors.php";
require "inc/compile_selectors.php";
require "inc/functions.php";

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
$qry = parse_selectors($_REQUEST['qry']);
$qry = compile_selectors($qry);

Header("Content-type: application/json; charset=utf8");

print "{ \"type\": \"FeatureCollection\",\n";
print "  \"features\": [\n";

$where_bbox = "way && ST_Transform(ST_SetSRID(ST_MakeBox2D(ST_Point($bbox[0], $bbox[1]), ST_Point($bbox[2], $bbox[3])), 4326), 900913)";

// planet_osm_point
$next_qry = get_queries($qry, array("*", "node", "point"));
if(sizeof($next_qry)) {
  $res = pg_query($db['conn'],
    "select ".
    "  'n' || osm_id as id, ".
    "  ST_AsGeoJSON(ST_Transform(way, 4326)) as geo, ".
    "  json_encode(tags) as tags ".
    "from planet_osm_point ".
    "where $where_bbox and $next_qry");

  print_results($res);
}

// planet_osm_line - ways
$next_qry = get_queries($qry, array("*", "line", "way"));
if(sizeof($next_qry)) {
  $res = pg_query($db['conn'],
    "select ".
    "  'w' || osm_id as id, ".
    "  ST_AsGeoJSON(ST_Transform(way, 4326)) as geo, ".
    "  json_encode(tags) as tags ".
    "from planet_osm_line ".
    "where osm_id > 0 and $where_bbox and $next_qry");

  print_results($res);
}

// planet_osm_line - relations
$next_qry = get_queries($qry, array("*", "line", "relation"));
if(sizeof($next_qry)) {
  $res = pg_query($db['conn'],
    "select ".
    "  'r' || (-osm_id) as id, ".
    "  ST_AsGeoJSON(ST_Transform(way, 4326)) as geo, ".
    "  json_encode(tags) as tags ".
    "from planet_osm_line ".
    "where osm_id < 0 and $where_bbox and $next_qry");

  print_results($res);
}

// planet_osm_polygon - ways
$next_qry = get_queries($qry, array("*", "area", "way"));
if(sizeof($next_qry)) {
  $res = pg_query($db['conn'],
    "select ".
    "  'w' || osm_id as id, ".
    "  ST_AsGeoJSON(ST_Transform(way, 4326)) as geo, ".
    "  json_encode(tags) as tags ".
    "from planet_osm_polygon ".
    "where osm_id > 0 and $where_bbox and $next_qry");

  print_results($res);
}

// planet_osm_polygon - relations
$next_qry = get_queries($qry, array("*", "area", "relation"));
if(sizeof($next_qry)) {
  $res = pg_query($db['conn'],
    "select ".
    "  'r' || (-osm_id) as id, ".
    "  ST_AsGeoJSON(ST_Transform(way, 4326)) as geo, ".
    "  json_encode(tags) as tags ".
    "from planet_osm_polygon ".
    "where osm_id < 0 and $where_bbox and $next_qry");

  print_results($res);
}

print "\n  ]\n}\n";
