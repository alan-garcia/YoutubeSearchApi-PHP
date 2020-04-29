<?php
  if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    throw new \Exception('please run "composer require google/apiclient in "' . __DIR__ .'"');
  }
  
  require_once __DIR__ . '/vendor/autoload.php';

  $htmlBody = <<<END
  <form id="searchForm" method="GET">
    <div>
      <label for='search'>Buscar:</label>
      <input type="search" id="q" name="q" placeholder="Busca un video">
    </div>
    <div>
      <label for='maxResults'>Cantidad de resultados:</label>
      <input type="number" id="maxResults" name="maxResults" min="1" max="50" step="1" value="25">
    </div>
    <input type="submit" name="searchSubmit" value="Buscar">
  </form>
  END;

  // This code will execute if the user entered a search query in the form
  // and submitted the form. Otherwise, the page displays the form above.
  if (isset($_GET['q']) && isset($_GET['maxResults'])) {
    /*
    * Set $DEVELOPER_KEY to the "API key" value from the "Access" tab of the
    * Google API Console <https://console.developers.google.com/>
    * Please ensure that you have enabled the YouTube Data API for your project.
    */
    $DEVELOPER_KEY = 'AIzaSyC2x7UoxPlP4dGtHjBQHZqdaP9RgutX_lA';

    $client = new Google_Client();
    $client->setDeveloperKey($DEVELOPER_KEY);

    // Define an object that will be used to make all API requests.
    $youtube = new Google_Service_YouTube($client);

    $htmlBody = '';
    try {

      // Call the search.list method to retrieve results matching the specified
      // query term.
      $searchResponse = $youtube->search->listSearch('id,snippet', array(
        'q' => $_GET['q'],
        'maxResults' => $_GET['maxResults'],
      ));

      $videos = '';
      $channels = '';
      $playlists = '';

      // Add each result to the appropriate list, and then display the lists of
      // matching videos, channels, and playlists.
      foreach ($searchResponse['items'] as $searchResult) {
        switch ($searchResult['id']['kind']) {
          case 'youtube#video':
            $videos .= sprintf('<li>%s (%s)</li>',
                $searchResult['snippet']['title'], $searchResult['id']['videoId']);
            break;
          case 'youtube#channel':
            $channels .= sprintf('<li>%s (%s)</li>',
                $searchResult['snippet']['title'], $searchResult['id']['channelId']);
            break;
          case 'youtube#playlist':
            $playlists .= sprintf('<li>%s (%s)</li>',
                $searchResult['snippet']['title'], $searchResult['id']['playlistId']);
            break;
        }
      }

      $htmlBody .= <<<END
      <h3>Videos</h3>
      <ul>$videos</ul>
      <h3>Channels</h3>
      <ul>$channels</ul>
      <h3>Playlists</h3>
      <ul>$playlists</ul>
      END;
    } catch (Google_Service_Exception $e) {
      $htmlBody .= sprintf('<p>A service error occurred: <code>%s</code></p>',
        htmlspecialchars($e->getMessage()));
    } catch (Google_Exception $e) {
      $htmlBody .= sprintf('<p>An client error occurred: <code>%s</code></p>',
        htmlspecialchars($e->getMessage()));
    }
  }
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Patent Search</title>
  </head>
  <body>
    <?= $htmlBody ?>
  </body>
</html>