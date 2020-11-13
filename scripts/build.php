#!/usr/bin/php

<?php
/**
 * Copyright 2018 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
// [START sheets_quickstart]
require __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/../include/common.php';

require_once __DIR__ . '/../routes/hidvl-metadata-player.php';

if (php_sapi_name() != 'cli') {
  throw new Exception('This application must be run on the command line.');
}

/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient() {
    $client = new Google_Client();
    $client->setApplicationName('Google Sheets API PHP Quickstart');
    $client->setScopes(Google_Service_Sheets::SPREADSHEETS_READONLY);
    $client->setAuthConfig('credentials.json');
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');

    // Load previously authorized token from a file, if it exists.
    // The file token.json stores the user's access and refresh tokens, and is
    // created automatically when the authorization flow completes for the first
    // time.
    $tokenPath = 'token.json';
    if (file_exists($tokenPath)) {
        $accessToken = json_decode(file_get_contents($tokenPath), true);
        $client->setAccessToken($accessToken);
    }

    // If there is no previous token or it's expired.
    if ($client->isAccessTokenExpired()) {
        // Refresh the token if possible, else fetch a new one.
        if ($client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        } else {
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();
            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';
            $authCode = trim(fgets(STDIN));

            // Exchange authorization code for an access token.
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            $client->setAccessToken($accessToken);

            // Check to see if there was an error.
            if (array_key_exists('error', $accessToken)) {
                throw new Exception(join(', ', $accessToken));
            }
        }
        // Save the token to a file.
        if (!file_exists(dirname($tokenPath))) {
            mkdir(dirname($tokenPath), 0700, true);
        }
        file_put_contents($tokenPath, json_encode($client->getAccessToken()));
    }
    return $client;
}

function getFromGoogleServiceSheets($spreadsheetId, $destination) {
  // Get the API client and construct the service object.
  $client = getClient();
  $service = new Google_Service_Sheets($client);
  $range = 'bsn-noid';
  $response = $service->spreadsheets_values->get($spreadsheetId, $range);
  $values = $response->getValues();
  if (empty($values)) {
    echo "No data found.", PHP_EOL;
    exit(1);
  }
  // Save the token to a file.
  if (!file_exists(dirname($destination))) {
    mkdir(dirname($destination), 0755, true);
    file_put_contents($destination, json_encode($values));
  }
}

function build($datasource) {
  if (file_exists(dirname($datasource))) {
    $documents = json_decode(file_get_contents($datasource));
    $dist_dir = '../dist';
    if (!file_exists($dist_dir)) {
      mkdir($dist_dir, 0755, true);
    }
    foreach ($documents as $row) {
      $bsn = $row[0];
      $noid = $row[1];
      echo "$noid | http://hdl.handle.net/2333.1/$noid", PHP_EOL;
      $document = init(array($bsn));
      $raw_html = render($document);
      if (!file_exists("$dist_dir/$noid")) {
        mkdir("$dist_dir/$noid", 0755, true);
      }
      file_put_contents("$dist_dir/$noid/index.html", $raw_html);
    }
  }
}

// While we get things started, you can create dummy data
// e.g., $ echo '[["47d7wmjw","47d7wmjw"]]' > ../datasource/bsn-noid.json
$from_cache = true;

$spreadsheetId = '11qBXeoa8_3SkwGKst5rrGUFhGm7gYKR--J3ZvR4j4iE';

$datasource = '../datasource/bsn-noid.json';

if ($from_cache) {
  build($datasource);
} else {
  // This will not work now, these documets are not available in pnxs
  getFromGoogleServiceSheets($spreadsheetId, $datasource);
  build($datasource);
}
