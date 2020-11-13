<?php

function init($args) {
  try {

    $noid = filter_var(
      $args[0],
      FILTER_SANITIZE_STRING,
      FILTER_FLAG_STRIP_LOW
    );

    $media_service = $_ENV['media_service'];

    $pnxs_query_prefix = $_ENV['pnxs_query_prefix'];

    $resource = "clip/$noid";

    $player = "$media_service/$resource/mode/embed";

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

    $collection_home = "$bobcat_url/primo-explore/search?query=creator,contains,%22Hemispheric%20Institute%20Digital%20Video%20Library%22,AND&pfilter=pfilter,exact,video,AND&tab=all&sortby=rank&vid=$vid&lang=$lang&mode=advanced&offset=0";

    $query = $pnxs_service . '?' . http_build_query(
      array(
        'q' => $q,
        'vid' => $vid,
        'tab' => $tab,
        'scope' => $scope,
        'inst' => $inst,
      )
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

      $recordId = $body->docs[0]->pnx->control->recordid[0];

      $data = array(
        'id' => $noid,
        'recordId' => $recordId,
        'title' => $record->title[0],
        'description' => $record->title[0],
        'creationdate' => $record->creationdate[0],
        'format' => $record->format[0],
        'subject' => $record->subject[0],
        'language' => $record->language[0],
        'type' => $record->type[0],
        'contributor' => $record->contributor[0],
        'playerUrl' => $player,
        'cite' => "https://hdl.handle.net/2333.1/$noid",
        'collection_home' => $collection_home,
        'primo' => "$primo?docid=$recordId&context=$primo_context&vid=$vid&lang=$lang",
      );

      return array(
        'template' => 'player.html',
        'data' => $data,
      );
    } else {
      throw new Exception('PNXS request fail.');
    }

  }
  catch (Exception $e) {
    return array(
      'template' => 'error.html',
      'data' => array(
        'title' => 'Error',
        'body' => $e->getMessage(),
      ),
    );
  }
}
