<?php

/**
 * @file
 * hidvl-metadata-player.php
 */

/**
 * Player function.
 */

use ISO639\ISO639;
use Spatie\SchemaOrg\Schema;

function customSort($a, $b) {
    // Extract names from the strings
    $nameA = explode(',', $a)[0];
    $nameB = explode(',', $b)[0];

    // Compare names and return the result
    return strcmp($nameA, $nameB);
}

function player($args) {
  try {

    $videoShema = Schema::VideoObject();

    $iso = new ISO639();

    $noid = filter_var(
      $args[0],
      FILTER_UNSAFE_RAW,
      FILTER_FLAG_STRIP_LOW
    );

    $media_service = $_ENV['media_service'];

    $pnxs_query_prefix = $_ENV['pnxs_query_prefix'];

    $thumbnail = "$media_service/api/v0/noid/$noid/thumbnail";

    $player = "$media_service/api/v0/noid/$noid/embed";

    $bobcat_url = $_ENV['bobcat_url'];

    $pnxs_service = $_ENV['pnxs_service'];

    $primo = $bobcat_url . $_ENV['primo_service'];

    $field = $_ENV['pnxs_search_field'];

    $apikey = $_ENV['apikey'];

    $contains = $pnxs_query_prefix . $noid;

    $q = "$field,contains,$contains";

    $vid = '01NYU_NETWORK:DEF_UNION';

    $inst = '01NYU_NETWORK';

    $primo_context = 'L';

    $lang = 'en_US';

    $scope = 'DiscoveryNetwork';

    $tab = 'DiscoveryNetwork';

    // @TODO: Find out the new collection home Url.
    $collection_home = "https://search.library.nyu.edu/discovery/search?query=any,contains,%22Hemispheric%20Institute%20Digital%20Video%20Library%22&tab=HIDVL&search_scope=HIDVL&vid=01NYU_INST:HIDVL&offset=0";

    $current_dir = getcwd();

    $cachefile = $current_dir . '/cache/' . $noid . '.json';

    if (file_exists($cachefile) && (time() - filemtime($cachefile)) < 604800 && args('reset') !== 'true') {
      $body = json_decode(
        file_get_contents($cachefile)
      );
    }
    else {
      $query = $pnxs_service . '?' . http_build_query(
        [
          'q' => $q,
          'vid' => $vid,
          'scope' => $scope,
          'tab' => $tab,
          'inst' => $inst,
          'apikey' => $apikey,
        ]
      );
      $request = WpOrg\Requests\Requests::get($query);
      if (
        $request->success &&
        $request->status_code === 200
      ) {
        
        $body = json_decode($request->body);

        file_put_contents($cachefile, json_encode($body));
      }
    }

    if (empty($body->docs)) {
      throw new Exception("PNXS - No document found - Search \"$contains\" does not match any record.");
    }

    $record = $body->docs[0]->pnx->display;

    // PNXS record id.
    $recordId = $body->docs[0]->pnx->control->recordid[0];

    // Title.
    $title = $record->title[0];

    $authors = [];

    // Author/Creator.
    if (isset($record->contributor) && is_array($record->contributor)) {
      foreach ($record->contributor as $_contributor) {
        $contributor = preg_replace('/\$\$Q.*$/', '', $_contributor);
        $contributor = str_replace('Hemispheric Institute Digital Video Library', '', $contributor);
        if (substr($contributor, -1) === '.') {
          $contributor = substr($contributor, 0, -1);
        }
        if (!empty($contributor)) {
          $authors[] = $contributor;
        }
      }
    }

    if (isset($record->creator) && is_array($record->creator)) {
      foreach ($record->creator as $_creator) {
        $creator = preg_replace('/\$\$Q.*$/', '', $_creator);
        $creator = str_replace('Hemispheric Institute Digital Video Library', '', $creator);
        if (substr($creator, -1) === '.') {
          $creator = substr($creator, 0, -1);
        }
        if (!empty($creator)) {
          $authors[] = $creator;
        }
      }
    }

    // Credits
    // https://jira.nyu.edu/browse/HIDVL-720
    // response['docs'][0]['pnx']['display']['lds64']
    // response['docs'][0]['pnx']['display']['lds65']
    $credits = [];
    if (isset($record->lds64) && is_array($record->lds64)) {
      $credits = array_merge($credits, $record->lds64);
    }

    if (isset($record->lds65) && is_array($record->lds65)) {
      $credits = array_merge($credits, $record->lds65);
    }

    usort($credits, 'customSort');
    

    // Publication Date.
    $publicationdate = $record->creationdate[0];

    // Description.
    $description = $record->format[0];

    $handleurl = 'https://hdl.handle.net';

    $handlenamespace = '2333.1';

    // Permalink.
    $permalink = "$handleurl/$handlenamespace/$noid";

    /*
     * Restrictions/Permissions.
     * Massaging as per @link https://docs.google.com/spreadsheets/d/1IZN34mWbU84Qec3z6ZkQeP65M-1L0m7Bx83W515lsZs/edit#gid=0
     * 1) The value "Open Access." should be ignored.
     * 2) The value with prefix "Copyright holder:" should go into the
     * copyrightHolder schema.org element.
     * 3) The prefix "Copyright holder:" should be removed.
     * 4) The value with prefix "Contact information:" should go into
     * the address schema.org element.
     * 5) The prefix "Contact information:"
     * should be removed.
     */
    // Restrictions/Permissions.
    $rights = [];
    if (isset($record->lds63)) {
      $rights = $record->lds63;
    }

    $rights_remove = array_search('Open Access.', $rights);

    if ($rights_remove !== FALSE) {
      unset($rights[$rights_remove]);
    }

    // Language.
    // Note that this is a three letter ISO code, so will need to
    // be flipped to a human-readable language label.
    $languages = [];
    $lang_code = [];
    if (isset($record->language)) {
      foreach ($record->language as $lang) {
        $lang_pieces = explode(";", $lang);
        foreach ($lang_pieces as $lang_piece) {
          $lang_piece = trim($lang_piece);
          $lang_code[] = $lang_piece;
          $languages[$lang_piece] = $iso->languageByCode2t($lang_piece);
        }
      }
    }

    // Summary.
    $summary = [];
    $og_summary = [];

    foreach ($record->description as $text) {
      $text = str_replace('$$Ccredits$$V', '<strong>Credits</strong>: ', $text);
      $text = str_replace('$$Csummary$$V', '<strong>Summary</strong>: ', $text);
      $summary[] = $text;
      $text = str_replace('<strong>Credits</strong>: ', '', $text);
      $text = str_replace('<strong>Summary</strong>: ', '', $text);
      $og_summary[] = $text;
    }

    $subject = explode(';', $record->subject[0]);

    // JSON-LS VideoObject.
    $videoShema->name($title);
    $videoShema->creator($authors);
    $videoShema->dateCreated($publicationdate);
    $videoShema->inLanguage($lang_code);
    $videoShema->url($permalink);
    $videoShema->description($og_summary);
    $videoShema->holdingArchive('New York University Libraries');
    $videoShema->isPartOf('Hemispheric Institute Digital Video Library');
    $videoShema->thumbnailUrl($thumbnail);
    $videoShema->embedUrl($permalink);
    $videoShema->uploadDate(date('Y-m-d'));

    $data = [
      'id' => $noid,
      'ld' => $videoShema->toScript(),
      'recordId' => $recordId,
      'title' => $title,
      'description' => $description,
      'summary' => $summary,
      'og_summary' => $og_summary,
      'publicationdate' => $publicationdate,
      'format' => $record->format[0],
      'subject' => $subject,
      'language' => $languages,
      'lang_code' => $lang_code,
      'type' => $record->type[0],
      'contributor' => $authors,
      'playerUrl' => $player,
      'thumbnail' => $thumbnail,
      'cite' => $permalink,
      'credits' => $credits,
      'rights' => $rights,
      'collection_home' => $collection_home,
      'primo' => "https://search.library.nyu.edu/permalink/01NYU_INST/1d6v258/$recordId",
    ];

    return [
      'template' => 'player.html',
      'data' => $data,
    ];

  }
  catch (Exception $e) {
    return [
      'template' => 'error.html',
      'data' => [
        'title' => 'Error',
        'body' => $e->getMessage(),
      ],
    ];
  }
}
