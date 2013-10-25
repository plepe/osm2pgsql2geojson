<?
function print_results($res) {
  global $print_results;

  while ($elem = pg_fetch_assoc($res)) {
    if (isset($print_results))
      print ",\n";
    $print_results = true;

    print "    { \"type\": \"Feature\",\n";
    print "      \"id\": \"{$elem['id']}\",\n";
    print "      \"geometry\": {$elem['geo']},\n";
    print "      \"properties\": {$elem['tags']}\n";
    print "    }";
  }
}

function get_queries($qry, $types) {
  $w = array();

  foreach($types as $t)
    if(isset($qry[$t]))
      $w[] = $qry[$t];

  return implode(" or ", $w);
}
