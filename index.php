<?php
  define("SECOND", 1);
  define("MINUTE", 60 * SECOND);
  define("HOUR", 60 * MINUTE);
  define("DAY", 24 * HOUR);
  define("MONTH", 30 * DAY);

  if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    throw new \Exception('please run "composer require google/apiclient in "' . __DIR__ .'"');
  }
  
  require_once __DIR__ . '/vendor/autoload.php';

  $request = "";

  if (isset($_POST['q'])) {
    $DEVELOPER_KEY = 'MY_KEY';
    $client = new Google_Client();
    $client->setDeveloperKey($DEVELOPER_KEY);
    
    try {
      if (!isset($_POST['type'])) {
        $_POST['type'] = [];
        array_push($_POST['type'], "Video", "Channel", "Playlist");
      }

      $youtube = new Google_Service_YouTube($client);
      $searchResponse = $youtube->search->listSearch('id,snippet', array(
        'q' => $_POST['q'],
        'maxResults' => 25,
        'type' => $_POST["type"]
      ));

      $resultSearchFilter = [];
      $videos = "";
      $channels = "";
      $playlists = "";
      $container = "";
      $content = "";
      $typeSearchForm = $_POST["type"];

      foreach ($searchResponse['items'] as $searchResult) {
        $typeSearch = $searchResult['id']['kind'];
        
        foreach ($typeSearchForm as $type) {
          if (in_array(containsStringInsensitive($typeSearch, $type), $typeSearchForm)) {
            $container = <<<END
              <div class="container-fluid">
                <div class="row">
            END;
            switch ($typeSearch) {
              case 'youtube#video':
                $idVideo = $searchResult['id']['videoId'];
                $response = $youtube->videos->listVideos("statistics", array('id' => $idVideo));
                $googleService = current($response->items);
                $statistics = $googleService->getStatistics();
                $viewCount = $statistics->viewCount;
                
                $content .= sprintf("
                  <div class='col-xl-4 col-lg-6 col-md-6 mb-5'>
                    <p><a href='https://www.youtube.com/watch?v=%s' target='_blank'>%s</a></p>
                    <p><img src=%s /></p>
                    <p>%s · %s · %s</p>
                    <p>%s</p>
                  </div>
                  ",
                  $idVideo, $searchResult['snippet']['title'], $searchResult['snippet']['thumbnails']['medium']['url'], $searchResult['snippet']['channelTitle'], "${viewCount} views", elapsedTime($searchResult['snippet']['publishedAt']), $searchResult['snippet']['description']);
                $container .= $content;
                break;
            }
            $container .= <<<END
                </div>
              </div>
            END;
          }
        }
      }

      $request .= <<<END
        <h3>Videos</h3>
        <div>$container</div>
      END;
    }
    catch (Google_Service_Exception $e) {
      $request .= sprintf('<p>A service error occurred: <code>%s</code></p>',
        htmlspecialchars($e->getMessage()));
    }
    catch (Google_Exception $e) {
      $request .= sprintf('<p>An client error occurred: <code>%s</code></p>',
        htmlspecialchars($e->getMessage()));
    }
  }

  function containsStringInsensitive($haystack, $needle) : bool
  {
    return stripos($haystack, $needle) !== false;
  }

  function elapsedTime(string $stringDate) : string {
    $timestamp = DateTime::createFromFormat("Y-m-d\TH:i:s\Z", $stringDate, new DateTimeZone("UTC"))->getTimestamp();
    
    $delta = time() - $timestamp;

    if ($delta < 1 * MINUTE) {
      return $delta == 1 ? "en este momento" : "hace " . $delta . " segundos ";
    }
    if ($delta < 2 * MINUTE) {
      return "hace un minuto";
    }
    if ($delta < 45 * MINUTE) {
      return "hace " . floor($delta / MINUTE) . " minutos";
    }
    if ($delta < 90 * MINUTE) {
      return "hace una hora";
    }
    if ($delta < 24 * HOUR) {
      return "hace " . floor($delta / HOUR) . " horas";
    }
    if ($delta < 48 * HOUR) {
      return "ayer";
    }
    if ($delta < 30 * DAY) {
      return "hace " . floor($delta / DAY) . " dias";
    }

    if ($delta < 12 * MONTH) {
      $months = floor($delta / DAY / 30);
      return $months <= 1 ? "el mes pasado" : "hace " . $months . " meses";
    }
    else {
      $years = floor($delta / DAY / 365);
      return $years <= 1 ? "el año pasado" : "hace " . $years . " años";
    }
  }
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Youtube Search</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css"/>
    <link rel="stylesheet" href="css/main.css"/>
  </head>
  <body>
    <div class="container">
      <div class="row">
        <div class="col-lg-12">
          <form id="searchForm" class="mt-5 mb-5" method="POST">
            <div id="searchContainer" class="form-group">
              <input type="search" class="form-control" id="q" name="q" placeholder="Busca un video en Youtube" value="<?php echo $_POST['q'] ?? '' ?>">
              <button type="submit" class="btn btn-danger searchButton" name="searchButton">Buscar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <?= $request ?>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js"></script>
  </body>
</html>
