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

function player($args) {
  try {

    $videoShema = Schema::VideoObject();

    $iso = new ISO639();

    $noid = filter_var(
      $args[0],
      FILTER_SANITIZE_STRING,
      FILTER_FLAG_STRIP_LOW
    );

    $media_service = $_ENV['media_service'];

    $pnxs_query_prefix = $_ENV['pnxs_query_prefix'];

    $resource = "clip/$noid";

    $thumbnail = "$media_service/api/v0/noid/$noid/thumbnail";

    $player = "$media_service/api/v0/noid/$noid/embed";

    $bobcat_url = $_ENV['bobcat_url'];

    $pnxs_service = $bobcat_url . $_ENV['pnxs_service'];

    $primo = $bobcat_url . $_ENV['primo_service'];

    $field = $_ENV['pnxs_search_field'];

    $contains = $pnxs_query_prefix . $noid;

    $q = "$field,contains,$contains";

    $vid = 'DLTS';

    $inst = 'DLTS';

    $primo_context = 'L';

    $lang = 'en_US';

    $scope = 'default_scope';

    $tab = 'default_tab';

    $collection_home = "$bobcat_url/primo-explore/search?query=any,contains,%22Hemispheric%20Institute%20Digital%20Video%20Library%22&tab=default_tab&search_scope=default_scope&vid=DLTS&lang=en_US&offset=0";

    $query = $pnxs_service . '?' . http_build_query(
      [
        'q' => $q,
        'vid' => $vid,
        'tab' => $tab,
        'scope' => $scope,
        'inst' => $inst,
      ]
    );

    $request = Requests::get($query);

    if (
      $request->success &&
      $request->status_code === 200
    ) {

      $body = json_decode($request->body);

      if (empty($body->docs)) {
        throw new Exception("PNXS - No document found - Search \"$contains\" does not match any record.");
      }

      $record = $body->docs[0]->pnx->display;

      // PNXS record id.
      $recordId = $body->docs[0]->pnx->control->recordid[0];

      // Title.
      $title = $record->title[0];

      $author_creator = [];

      // Author/Creator.
      if (isset($record->contributor)) {
        $contributor = $record->contributor;
        if ($contributor[0] && !empty($contributor[0])) {
          // As per @link https://docs.google.com/spreadsheets/d/1IZN34mWbU84Qec3z6ZkQeP65M-1L0m7Bx83W515lsZs/edit#gid=0
          $contributor = str_replace('; Hemispheric Institute Digital Video Library.', '', $contributor[0]);
          $author_creator = array_merge($author_creator, explode(';', $contributor));
        }
      }

      if (isset($record->creator)) {
        if (isset($creator[0]) && !empty($creator[0])) {
          $creator = $record->creator;
          $author_creator = array_merge($author_creator, explode(';', $creator[0]));
        }
      }

      // Publication Date.
      $publicationdate = $record->creationdate[0];

      // Description.
      $description = $record->format[0];

      // Permalink.
      $permalink = "https://hdl.handle.net/2333.1/$noid";

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
      $rights = [];
      if (isset($record->rights)) {
        $rights = $record->rights;
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
      if (isset($record->language[0])) {
        $langs = explode(';', $record->language[0]);
        foreach ($langs as $lang) {
          $lang_code[] = $lang;
          $languages[$lang] = $iso->languageByCode2t($lang);
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
      $videoShema->creator($author_creator);
      $videoShema->dateCreated($publicationdate);
      $videoShema->inLanguage($lang_code);
      $videoShema->url($permalink);
      $videoShema->description($og_summary);
      $videoShema->holdingArchive('New York University Libraries');
      $videoShema->isPartOf('Hemispheric Institute Digital Video Library');
      $videoShema->thumbnailUrl($thumbnail);
      $videoShema->embedUrl($permalink);

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
        'contributor' => $author_creator,
        'playerUrl' => $player,
        'thumbnail' => $thumbnail,
        'cite' => $permalink,
        'rights' => $rights,
        'collection_home' => $collection_home,
        'primo' => "$primo?docid=$recordId&context=$primo_context&vid=$vid&lang=$lang",
      ];
      return [
        'template' => 'player.html',
        'data' => $data,
      ];
    }
    else {
      throw new Exception('PNXS request fail.');
    }
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
