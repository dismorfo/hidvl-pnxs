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

    $q = "q=$field,contains,$contains";

    $vid = 'DLTS';

    $inst = 'DLTS';

    $scope = 'default_scope';

    $tab = 'default_tab';

    $query = "$pnxs_service?vid=$vid&tab=$tab&scope=$scope&$q&inst=$inst";

    $request = Requests::get($query);

    if ($request->success && $request->status_code === 200) {

      $data = json_decode($request->body);

      if (empty($data->docs)) {
        throw new Exception("PNXS - No document found - Search \"$contains\" does not match any record.");
      }

      $record = $data->docs[0]->pnx->display;

      $recordId = $data->docs[0]->pnx->control->recordid[0];

      return array(
        'template' => 'player.html',
        'data' => array(
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
          'primo' => "$primo?docid=$recordId&context=L&vid=DLTS&lang=en_US",
        ),
      );
    } else {
      throw new Exception('PNXS request fail');
    }

  }
  catch (Exception $e) {
    return array(
      'template' => 'error.html',
      'data' => array(
        'body' => $e->getMessage(),
      )
    );
  }
}
