<?php

/**
 * @file
 * Tests for build_player_data() in routes/hidvl-metadata-player.php.
 *
 * These tests exercise the metadata-parsing logic directly against a
 * fixture PNXS API response, without making any real HTTP requests.
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../routes/hidvl-metadata-player.php';

class BuildPlayerDataTest extends TestCase {

  private function loadFixture(): object {
    $json = file_get_contents(__DIR__ . '/fixtures/pnxs-response.json');
    return json_decode($json);
  }

  private function env(): array {
    return [
      'media_service' => 'https://media.example.edu',
    ];
  }

  public function testBasicFieldsAreExtracted(): void {
    $body = $this->loadFixture();
    $data = build_player_data($body, '47d7wmjw', $this->env());

    $this->assertSame('47d7wmjw', $data['id']);
    $this->assertSame(
      'Inversión de escena (scrolling of performance synopsis : English version)',
      $data['title']
    );
    $this->assertSame('Alternate title example', $data['other_title']);
    $this->assertSame('991234567890', $data['recordId']);
    $this->assertSame('1979', $data['publicationdate']);
    $this->assertSame('video', $data['type']);
    $this->assertSame('https://hdl.handle.net/2333.1/47d7wmjw', $data['cite']);
  }

  public function testThumbnailAndPlayerUrlsUseMediaService(): void {
    $body = $this->loadFixture();
    $data = build_player_data($body, '47d7wmjw', $this->env());

    $this->assertSame(
      'https://media.example.edu/api/v0/noid/47d7wmjw/thumbnail',
      $data['thumbnail']
    );
    $this->assertSame(
      'https://media.example.edu/api/v0/noid/47d7wmjw/embed',
      $data['playerUrl']
    );
  }

  public function testAuthorsCombineCreatorAndContributorWithCleanup(): void {
    $body = $this->loadFixture();
    $data = build_player_data($body, '47d7wmjw', $this->env());

    // The literal "Hemispheric Institute Digital Video Library." contributor
    // entry should be filtered out, and the "$$Q..." suffix and trailing
    // periods should be stripped from both creator and contributor entries.
    $this->assertSame(
      ['Castillo, Juan', 'Rosenfeld, Lotty'],
      $data['contributor']
    );
  }

  public function testOpenAccessRightsAreDropped(): void {
    $body = $this->loadFixture();
    $data = build_player_data($body, '47d7wmjw', $this->env());

    $this->assertNotContains('Open Access.', $data['rights']);
    $this->assertContains('Copyright holder: Hemispheric Institute', $data['rights']);
  }

  public function testCreditsAndPerformerNoteAreSortedByLastName(): void {
    $body = $this->loadFixture();
    $data = build_player_data($body, '47d7wmjw', $this->env());

    $this->assertSame(
      ['Eltit, Diamela, writer', 'Zurita, Raul, writer'],
      array_values($data['credits'])
    );
    $this->assertSame(
      ['Castillo, Juan, performer', 'Rosenfeld, Lotty, performer'],
      array_values($data['performer_note'])
    );
  }

  public function testTimePlaceOfEventAndLocationComeFromLds73(): void {
    $body = $this->loadFixture();
    $data = build_player_data($body, '47d7wmjw', $this->env());

    $this->assertSame(['Santiago, Chile'], $data['time_place_of_event']);
    $this->assertSame('Santiago, Chile', $data['location']);
  }

  public function testDescriptionLabelsAreConvertedToHtmlInSummaryOnly(): void {
    $body = $this->loadFixture();
    $data = build_player_data($body, '47d7wmjw', $this->env());

    $this->assertSame(
      '<strong>Summary</strong>: Video documentation of a public art intervention.',
      $data['summary'][0]
    );
    $this->assertSame(
      '<strong>Credits</strong>: Filmed by the Hemispheric Institute.',
      $data['summary'][1]
    );
    // og_summary is the plain-text version used for meta tags, without
    // the injected HTML labels.
    $this->assertSame(
      'Video documentation of a public art intervention.',
      $data['og_summary'][0]
    );
    $this->assertSame(
      'Filmed by the Hemispheric Institute.',
      $data['og_summary'][1]
    );
  }

  public function testMaliciousDescriptionTextIsEscapedInSummary(): void {
    $body = $this->loadFixture();
    $body->docs[0]->pnx->display->description = [
      '$$Csummary$$V<script>alert(1)</script> & "quoted" <img src=x onerror=alert(2)>',
    ];

    $data = build_player_data($body, '47d7wmjw', $this->env());

    // The trusted <strong> label is still rendered as real HTML...
    $this->assertStringStartsWith('<strong>Summary</strong>: ', $data['summary'][0]);
    // ...but the untrusted PNXS text is HTML-escaped, so no executable
    // markup survives even though the template renders this with `|raw`.
    $this->assertStringNotContainsString('<script>', $data['summary'][0]);
    $this->assertStringNotContainsString('<img', $data['summary'][0]);
    $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $data['summary'][0]);
    $this->assertStringContainsString('&amp; &quot;quoted&quot;', $data['summary'][0]);
  }

}
