<?
function compile_selectors($qry) {
  $ret = array();

  foreach($qry as $q) {
    $c = array();

    foreach($q['condition'] as $condition) {
      $c[] = "tags @> hstore('" . pg_escape_string($condition['key']) .
             "', '" . pg_escape_string($condition['value']) . "')";
    }

    $ret[$q['type']][] = "(" . implode(" and ", $c) . ")";
  }

  foreach($ret as $type=>$c) {
    $ret[$type] = implode(" or ", $c);
  }

  return $ret;
}
