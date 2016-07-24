<!DOCTYPE html>
<html>
<head>
  <link rel="stylesheet" type="text/css" href="//w2ui.com/src/w2ui-1.3.min.css" />

  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="/bootstrap-3.0.3-dist/dist/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="/bootstrap-3.0.3-dist/dist/css/bootstrap-theme.min.css"/>
  <link rel="fonts" href="/bootstrap-3.0.3-dist/dist/fonts/glyphicons-halflings-regular.eot"/>
  <link rel="fonts" href="/bootstrap-3.0.3-dist/dist/fonts/glyphicons-halflings-regular.svg"/>
  <link rel="fonts" href="/bootstrap-3.0.3-dist/dist/fonts/glyphicons-halflings-regular.ttf"/>
  <link rel="fonts" href="/bootstrap-3.0.3-dist/dist/fonts/glyphicons-halflings-regular.woff"/>
  <link rel="icon" href="/img/favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="/img/favicon.ico" type="image/x-icon">
  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js"></script>
  <script src="/bootstrap-3.0.3-dist/dist/js/bootstrap.min.js"></script>
  <script type="text/javascript" src="//w2ui.com/src/w2ui-1.3.min.js"></script>

</head>
<body>
<?

$db_file = dirname(__FILE__) . '/upload/softbox.kcdb';
$mm_file = dirname(__FILE__) . '/upload/softbox.mm';

require_once('func.inc.php');
$kc_data = get_key_collector_data($db_file);
$xml = simplexml_load_file($mm_file);

function recurs_xml_to_array(SimpleXMLElement $node, $key_collector)
{
  $childs = array();
  $total_profits = 0.0;

  foreach ($node->children() as $sub_node)
  {
    $arr = recurs_xml_to_array($sub_node, $key_collector);
    $childs[] = $arr;
    $total_profits += $arr['total_profits'];
  }

  $key = trim(preg_replace('/\[.*\]/', '', $node['TEXT']));
  $total_profits += get_profit($key, $key_collector);
  $data = isset($key_collector[$key]) ? $key_collector[$key] : array('sp_budget'=>0, 'titles'=>0, 'mains'=>0, 'rookee_budget'=>0);

  $node['TEXT'] = $key;
  $node['PROFIT'] = number_format(get_profit($key, $key_collector), 2, '.' , '');
  $node['SP'] = $data['sp_budget'];
  $node['RK'] = $data['rookee_budget'];
  $node['TITLE'] = $data['titles'];
  $node['MAIN'] = $data['mains'];

  $result =  array(
    'id' => rand(),
    'text' => (string)$node['TEXT'],
    'profit' => (string)$node['PROFIT'],
    'img' => 'icon-page',
    'total_profits' => number_format($total_profits, 2, '.' , ''),
  );
  $result = array_merge($result, $data);

  if ($node->children()->count())
  {
    $result['img'] = 'icon-folder';
    $result['nodes'] = $childs;
  }

  return $result;
}

$arr = recurs_xml_to_array($xml, $kc_data);
$arr = array_pop($arr);
$arr[0]['expanded'] = 'true';
$arr[0]['group'] = 'true';

?>

<div class="container">
  <div class="row">
    <div class="span12">
      <div id="layout" style="height: 800px;"></div>
    </div>
  </div>
</div>

<div class="hidden">
  <div class="container JS-Result-Container">
    <div class="row">
      <div class="col-md-12">
        <div class="col-md-2">  Ключ</div>
        <div class="col-md-7 JS-text"></div>
      </div>
    </div>
    <div class="row">
      <div class="col-md-12">
        <div class="col-md-2">  quote_point</div>
        <div class="col-md-7 JS-quote_point"></div>
      </div>
    </div>
    <div class="row">
      <div class="col-md-12">
        <div class="col-md-2">  profit</div>
        <div class="col-md-7 JS-profit"></div>
      </div>
    </div>
    <div class="row">
      <div class="col-md-12">
        <div class="col-md-2">  total_profits</div>
        <div class="col-md-7 JS-total_profits"></div>
      </div>
    </div>
  </div>
</div>

</body>

<script>
  json = <?=json_encode($arr);?>;
  $('#layout').w2layout({
    name: 'layout',
    panels: [
      { type: 'left', size: 400, resizable: true, style: 'background-color: #F5F6F7;', content: 'left' },
      { type: 'main', style: 'background-color: #F5F6F7; padding: 5px;' }
    ]
  });
  w2ui['layout'].content('left', $().w2sidebar({
    name: 'sidebar',
    img: null,
    nodes: json,
    onClick: function (event) {
      var elem = $('.JS-Result-Container').clone();

      elem.find('.JS-text').html(event.object.text);
      elem.find('.JS-quote_point').html(event.object.quote_point);
      elem.find('.JS-profit').html(event.object.profit);
      elem.find('.JS-total_profits').html(event.object.total_profits);

      w2ui['layout'].content('main', elem[0]);
    }
  }));
</script>
</html>
