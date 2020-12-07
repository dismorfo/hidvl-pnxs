<?php

/**
 * @file
 * home.php
 */

/**
 * Home function.
 */
function home() {
  try {
    $raw_page = isset($_GET['page']) ? $_GET['page'] : 1;

    $currentPage = filter_var(
      $raw_page,
      FILTER_SANITIZE_STRING,
      FILTER_FLAG_STRIP_LOW
    );

    $bobcat_url = $_ENV['bobcat_url'];

    $pnxs_service = "$bobcat_url/primo_library/libweb/webservices/rest/primo-explore/v1/pnxs";

    $vid = 'DLTS';

    $inst = 'DLTS';

    $lang = 'en_US';

    $scope = 'default_scope';

    $tab = 'default_tab';

    $contains = 'hidvl*';

    $field = 'lsr12';

    $inst = 'DLTS';

    $q = "$field,contains,$contains";

    $limit = 10;

    $offset = 0;

    $query = $pnxs_service . '?' . http_build_query(
      [
        'q' => $q,
        'vid' => $vid,
        'tab' => $tab,
        'scope' => $scope,
        'inst' => $inst,
        'limit' => $limit,
        'offset' => (($currentPage * $limit) - $limit),
      ]
    );

    $request = Requests::get($query);

    if (
      $request->success &&
      $request->status_code === 200
    ) {
      $body = json_decode($request->body);
      $maxPage = $body->info->total / $limit;
      $items = [];
      foreach ($body->docs as $doc) {
        $handle = $doc->delivery->GetIt1[0]->links[0]->link;
        $noid = str_replace('hidvl', '', $doc->pnx->search->lsr12[0]);
        $items[] = [
          'handle' => $handle,
          'noid' => $noid,
          'title' => $doc->pnx->display->title[0],
          'contributor' => $doc->pnx->display->contributor,
          'subject' => $doc->pnx->display->subject[0],
          'tags' => $doc->pnx->search->subject,
          'type' => $doc->pnx->display->type[0],
          'lds13' => $doc->pnx->display->lds13,
        ];
      }
    }
    return [
      'template' => 'home.html',
      'data' => [
        'title' => 'Home',
        'items' => $items,
        'pageLimit' => $limit,
        'currentPage' => $currentPage,
        'maxPage' => $maxPage,
        'pageRange' => 1,
      ],
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
