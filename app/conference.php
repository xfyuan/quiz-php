<?php
namespace App;

class Conference {

  /**
   * Days of whole conference
   *
   * @var number
   **/
  public $days            = null;

  /**
   * whole talks
   *
   * @var array
   **/
  public $talks           = [];

  /**
   * whole talks whick are grouped by it's length
   *
   * @var array
   **/
  public $groupedTalks    = [];

  /**
   * whole tracks
   *
   * @var array
   **/
  public $tracks          = [];

  /**
   * whole tracks filled with planned talks
   *
   * @var array
   **/
  public $scheduledTracks = [];

  /**
   * Create a new Conference.
   *
   * @param string $data
   * @return void
   **/
  public function __construct($data) {
    $this->readSource($data);
    $this->refreshDays();
    $this->refreshTracks();
  }

  /**
   * readSource
   *
   * @param string $data
   * @return void
   **/
  public function readSource($data) {
    if($talks = preg_split("/".PHP_EOL."/", $data)) {
      foreach($talks as $talk) {
        $this->talks[] = new Talk($talk);
      }
    }
  }

  /**
   * refreshDays
   *
   * @return void
   **/
  public function refreshDays() {
    $minutes = array_reduce($this->talks, function($memo, $talk){
      return $memo += $talk->length;
    });
    $this->days = (int) ceil($minutes / (new Track())->totalLength);
  }

  /**
   * refreshTracks
   *
   * @return void
   **/
  public function refreshTracks() {
    for ($i=0; $i < $this->days; $i++) {
      $this->tracks[] = new Track();
    }
  }

  /**
   * groupedTalks
   *
   * @return void
   **/
  public function groupedTalks() {
    $this->groupedTalks = array_reduce($this->talks, function($memo, $talk) {
      $key = $talk->length . preg_replace('/ /', '-', strtolower($talk->title));
      $memo[$key] = $talk;
      return $memo;
    }, []);
    krsort($this->groupedTalks, SORT_NUMERIC);
  }

  /**
   * scheduleTracksWithTalks
   *
   * @return void
   **/
  public function scheduleTracksWithTalks() {
    $this->scheduledTracks = array_reduce($this->tracks, function($memo, $track) {
      $track->talks = $this->fillTalksIntoCurrentTrack($track);
      $track->planTalks();
      $memo[] = $track;
      return $memo;
    }, []);
  }

  private function fillTalksIntoCurrentTrack($track) {
    $totalTrackLength = $track->totalLength;
    return array_reduce($this->groupedTalks, function($memo, $talk) use (&$totalTrackLength) {
      if (!$talk->marked && $totalTrackLength >= $talk->length) {
        $memo[] = $talk;
        $talk->marked = true;
        $totalTrackLength -= $talk->length;
      }
      return $memo;
    }, []);
  }

  /**
   * outputScheduledTracks
   *
   * @return void
   **/
  public function outputScheduledTracks() {
    foreach($this->scheduledTracks as $i => $track) {
      echo "Track" . ($i+1) . PHP_EOL;
      foreach($track->plannedTalks as $markedTime => $talk) {
        echo "{$markedTime} {$talk->output()}" . PHP_EOL;
      }
      echo PHP_EOL . PHP_EOL;
    }
  }

}
