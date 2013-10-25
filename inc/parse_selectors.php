<?
function parse_selectors($str) {
  $ret = array();

  while(1) {
    $current = array();

    if(preg_match("/^\s*$/", $str))
      return $ret;

    if(!preg_match("/^\s*(point|node|area|way|line|relation|\*)/", $str, $m)) {
      print "Can't parse object type";
      return false;
    }

    $current['type'] = $m[1];
    $str = substr($str, strlen($m[0]));

    while(preg_match("/^\[/", $str)) {
      $str = substr($str, 1);

      if(preg_match("/^([A-Za-z\-:_]+)(=|!=|<|>|<=|>=|\^=|\$=|\*=|~=|=~)([^\]]+)\]/", $str, $m)) {
	$current['condition'][] = array(
	  'key' => $m[1],
	  'op'  => $m[2],
	  'value' => $m[3],
	);

	$str = substr($str, strlen($m[0]));
      }
      else {
	print "Can't parse condition";
	return false;
      }
    }

    if(preg_match("/^\s*;\s*/", $str, $m)) {
      $str = substr($str, strlen($m[0]));
    }
    elseif($str == "") {
    }
    else {
      print "Can't parse selector";
      return false;
    }

    $ret[] = $current;
  }
}
