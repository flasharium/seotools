<?php

require_once('simplehtmldom_1_5/simple_html_dom.php');

function get_content($url) {
  $file_name = urldecode($url);
  $file_name = str_replace(array(':', '/', '?', '&', '=', '+', ';'), array('_', '_', '_', '_', '_', '_', '_'), $file_name);
  $file_path = dirname(__FILE__) . '/cache/' . $file_name;

  if (file_exists($file_path))
    return file_get_contents($file_path);

  $cookie = tmpfile();
  $userAgent = 'Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.31 (KHTML, like Gecko) Chrome/26.0.1410.64 Safari/537.31' ;

  $ch = curl_init($url);

  $options = array(
    CURLOPT_CONNECTTIMEOUT => 20 ,
    CURLOPT_USERAGENT => $userAgent,
    CURLOPT_AUTOREFERER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEFILE => $cookie,
    CURLOPT_COOKIEJAR => $cookie ,
    CURLOPT_SSL_VERIFYPEER => 0 ,
    CURLOPT_SSL_VERIFYHOST => 0
  );

  curl_setopt_array($ch, $options);
  $content = curl_exec($ch);
  curl_close($ch);

  file_put_contents($file_path, $content);

  return $content;
}

function commerc_score($text){
  $vocabulary = array(
    'акция',
    'в корзину',
    'вакансии',
    'где купить',
    'гипермаркет',
    'доставка',
    'доставки',
    'заказ',
    'заказать',
    'каталог',
    'контакты',
    'корзина',
    'купить',
    'куплю',
    'магазин',
    'менеджер',
    'о компании',
    'оао',
    'обратный звонок',
    'ооо',
    'оплата',
    'опт ',
    'оформить',
    'партнерам',
    'поставка',
    'прайс',
    'продажа',
    'продукция',
    'розница',
    'руб.',
    'свяжитесь с нами',
    'скидка',
    'скидки',
    'тариф',
    'улица',
    'услуги',
    'цена',
    'цены',
    '((8|\+7)[\- ]?)(\(?\d{3}\)?[\- ]?)[\d\- ]{7,10}',
  );

  $score = 0;

  foreach($vocabulary as $word){
    $score += preg_match_all('/' . $word . '/iu', $text, $matches);
  }

  return $score;
}

function get_link($link, $label, $score = null, $badge_score = null, $img = null)
{
  $inf = ((is_null($score)|| $score < 10) && !$badge_score) ? '<span title="Документ не обладает коммерческими признаками" class="label label-success">info</span>' : '';

  $img = $img ? "<img src='{$img}'/>" : '';

  if (!is_null($score)){
    $class = 'info';

    if ($score >= 5 && $score <= 10)
      $class = 'primary';

    if ($score > 10 && $score <= 15)
      $class = 'warning';

    if ($score > 15)
      $class = 'danger';

    $score = " <span title='Количество коммерческих маркеров на странице сайта' class='pull-right label label-{$class}'>$score</span> ";
  }

  $badge_score = $badge_score ? "<span title='Количество коммерческих маркеров в поисковом сниппете' class=\"badge\">{$badge_score}</span>" : '';
  $readable_link = substr($link, 0, 70);

  if (strlen($link) > 70)
    $readable_link .= '...';

  $html = "{$label}<br/><a href=\"{$link}\" target='_blank'>{$readable_link}</a> {$inf} {$img} {$score} {$badge_score}";

  return $html;
}

function fetch_url_commerc($link)
{
  $content = get_content($link);
  $html = str_get_html($content);
  $text = $html->plaintext;

  $images = $html->find('img');
  foreach ($images as $img) {
    if ($img->hasAttribute('alt'))
      $text .= (' ' . $img->alt);
  }

  $score = commerc_score($text);

  return $score;
}




function get_key_collector_data($key_collector_db_file)
{
  $key_collector_data = array();
  if (!file_exists($key_collector_db_file))
    die('No KC file!');

  $db = new SQLite3($key_collector_db_file);
  $sql = 'SELECT
          KeyText as key,
          YandexWordstatBaseFreq as base,
          YandexWordstatQuoteFreq as quote,
          YandexWordstatQuotePointFreq as quote_point,
          YandexDirect_CPC as yd_cpc,
          KEI_YandexMainPagesCount as mains,
          KEI_YandexTitlesCount as titles,
          SeoPult_Budget as sp_budget,
          Rookee_Budget as rookee_budget
        FROM KeyCollector_Keys';
  $result = $db->query($sql);

  if (!$result)
    die('bad db!');

  while ($res = $result->fetchArray(SQLITE3_ASSOC)) {
    $key_collector_data[$res['key']] = $res;
  }

  return $key_collector_data;
}

function recurs(SimpleXMLElement $node, $key_collector)
{
  $total_profits =  0.0;

  /** @var $sub_node SimpleXMLElement */
  foreach ($node->children() as $sub_node)
  {
    $total_profits += recurs($sub_node, $key_collector);
  }

  $key = trim(preg_replace('/\[.*\]/', '', $node['TEXT']));

  $total_profits += get_profit($key, $key_collector);

  $readable_total_profits = number_format($total_profits, 2, '.' , '');
  $data = isset($key_collector[$key]) ? $key_collector[$key] : array('sp_budget'=>0, 'titles'=>0, 'mains'=>0, 'rookee_budget'=>0);
  $attributes = ("SP: $data[sp_budget], RK:$data[rookee_budget], T:$data[titles], M:$data[mains]");

  $node['TEXT'] = $key;
  $node['PROFIT'] = $readable_total_profits;
  $node['SP'] = $data['sp_budget'];
  $node['RK'] = $data['rookee_budget'];
  $node['TITLE'] = $data['titles'];
  $node['MAIN'] = $data['mains'];

  return $total_profits;
}


function get_profit($key, $key_collector)
{
  if (!$data = isset ($key_collector[$key]) ? $key_collector[$key] : null)
    $key_profit = 0;
  else
    $key_profit = ((($data['quote_point'] / 10) * 0.03) * $data['yd_cpc']) / 4;

  return $key_profit;
}
