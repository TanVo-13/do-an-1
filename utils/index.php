<?php


function fetchData($url)
{
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_TIMEOUT, 5);
  $response = curl_exec($ch);
  curl_close($ch);

  return json_decode($response, true) ?? [];
}

function convertToEmbedUrl($youtubeUrl)
{
  parse_str(parse_url($youtubeUrl, PHP_URL_QUERY), $queryParams);
  return isset($queryParams['v']) ? "https://www.youtube.com/embed/" . $queryParams['v'] : null;
}
