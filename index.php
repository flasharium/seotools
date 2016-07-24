<?
require_once('func.inc.php');
require_once('simplehtmldom_1_5/simple_html_dom.php');
?>
<html>
<head>
  <title>Commerc</title>
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
  <script src="/js/script.js"></script>
  <script src="/bootstrap-3.0.3-dist/dist/js/bootstrap.min.js"></script>
  <style>
    body {padding-top: 50px;}
  </style>
</head>
<body>


<? $key = isset($_POST['key']) ? $_POST['key'] : ''; ?>

<div class="container">

  <? if (!$key) {/* ?>
    <div class="row">
      <div class="col-md-4 col-md-offset-8">
        <div class="panel panel-primary">
          <div class="panel-heading">
            <h3 class="panel-title">KeyCollector + FreeMind</h3>
          </div>
          <div class="panel-body">
            <form action="/tree.php" enctype="multipart/form-data">
              <div class="JS-MapDropZone alert alert-info" data-extension="mm">
                <span>Место для карты</span>
                <div class="progress progress-striped active hidden JS-ProgressBar">
                  <div class="progress-bar JS-ProgressBar-elem"  role="progressbar" aria-valuenow="45" aria-valuemin="0" aria-valuemax="100" style="width: 0%"></div>
                </div>
                <input type="hidden" name="map_file"/>
              </div>
              <div class="JS-BaseDropZone alert alert-info" data-extension="kcdb">
                <span>Место для базы</span>
                <div class="progress progress-striped active hidden JS-ProgressBar">
                  <div class="progress-bar JS-ProgressBar-elem"  role="progressbar" aria-valuenow="45" aria-valuemin="0" aria-valuemax="100" style="width: 0%"></div>
                </div>
                <input type="hidden" name="db_file"/>
              </div>
              <button class="btn btn-default" type="submit">Загрузить</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  <?*/ } ?>

  <div class="row">
    <div class="col-md-8 col-md-offset-3">
      <form method="post" class="form-inline">
        <div class="col-lg-8">
          <h4>Обработка поискового запроса <small>[Яндекс, Москва]</small></h4>
          <div class="input-group">
            <input class="form-control" size="50" type="text" name="key" value="<?=$key;?>"/>
            <span class="input-group-btn">
              <button class="btn btn-default" type="submit">Go!</button>
            </span>
          </div>
        </div>
      </form>
    </div>
  </div>

  <?

  if (!$key)
    return;

  $encoded = urlencode($key);
  $ya_url = 'http://yandex.ru/yandsearch?lr=213&text=' . $encoded;
  $serp = get_content($ya_url);
  $factor = 0;
  $html = str_get_html($serp);

  $times = $html->find('.b-place-adv b');
  if (count($times)){
    $times = $times[0];
    $times = $times->plaintext . ' показов в месяц, ';
  } else {
    $times = '';
  }


  ?>

  <div class="row">
    <div class="col-md-7 col-md-offset-3">
      <blockquote class="pull-right">
        <p class="h3"><?=$key;?></p>
        <small><a target="_blank" href="<?= $ya_url; ?>"><?=$key;?></a> [<?=$times;?>Яднекс, Москва] </small>
      </blockquote>
    </div>
  </div>

  <div class="row">
    <div class="col-md-8 col-md-offset-2">
      <ul class="list-group">
        <?

        $items = $html->find('.b-body-items');
        $lis = $html->find('.b-body-items .b-serp-item');

        foreach ($lis as $li)
        {
          if (in_array('z-market-offers', explode(' ' , $li->class)))
            continue;

          if (in_array('z-images', explode(' ' , $li->class)))
            continue;

          if (in_array('z-video-list', explode(' ' , $li->class)))
            continue;

          $is_commerc = false;

          if (count($li->find('.b-info')))
            $is_commerc = true;

          if (count($li->find('.b-address')))
            $is_commerc = true;

          if (count($li->find('.b-market__info')))
            $is_commerc = true;

          if (count($li->find('.b-market__price')))
            $is_commerc = true;

          if ($badge_score = commerc_score($li->plaintext))
            $is_commerc = true;

          $link = $li->find('h2 a');
          $link = $link[0];
          $label = $link->find('span');
          $label = $label[0];
          $label = $label->plaintext;
          $link = $link->href;

          $score = null;

          if (count($li->find('.b-serp-item__mime-icon')))
          {
            $img = $li->find('.b-serp-item__mime-icon');
            $img = $img[0];
            $img = $img->src;
            $is_commerc = false;
          }
          else if (!$is_commerc)
          {
            $score = fetch_url_commerc($link);
            $is_commerc = $score >= 10;
            $img = null;
          }
          ?>

            <li class="list-group-item">
              <?=get_link($link, $label, $score, $badge_score, $img);?>
            </li>

          <?
          if ($is_commerc) $factor++;
        }
        $factor *= 10;
        $class = 'success';

        if ($factor >= 10 && $factor <= 30)
          $class = 'info';

        if ($factor >= 30 && $factor <= 50)
          $class = 'primary';

        if ($factor > 50 && $factor <= 70)
          $class = 'warning';

        if ($factor > 70)
          $class = 'danger';
        ?>
      </ul>
    </div>
  </div>

  <div class="row">
    <div class="col-md-offset-4">
      <h3>Коммерческая выдача <span class="label label-<?=$class;?>"><?=$factor?>%</span></h3>
    </div>
  </div>

</div>
</body>
</html>
