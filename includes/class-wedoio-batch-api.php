<?php
/**
 * An helper to define a batch process that could be reused for a lot of batches with visual queues
 */

class WedoioBatchApi {
  private $state;
  private $cron;

  public function __construct($id, $cron = false) {
    // We check in the session if there is a batch already running and we take that state
    if ($id) {
      if (!$cron) {
        $state = isset($_SESSION['wedoio-batches'][$id]) ? $_SESSION['wedoio-batches'][$id] : $this->newState($id);
      } else {
        $batches = get_option("wedoio-batches-cron", []);
        $state = isset($batches[$id]) ? $batches[$id] : $this->newState($id);
      }

    } else {
      $state = false;
    }

    $this->cron = $cron;
    $this->setState($state);
  }

  /**
   * Return the current batch status
   */
  public function status() {
    return $this->state['status'];
  }

  /**
   * return the current State
   * @return mixed
   */
  public function getState() {
    return $this->state;
  }

  /**
   * Return an empty new state
   */
  public function newState($id) {
    return [
      "id" => $id,
      "start" => time(),
      "last" => time(),
      "operations" => [],
      "results" => [],
      "status" => "new",
      "current" => 0,
      "msg" => "",
      "error" => false,
      "percent" => 0,
      "cron" => false,
      "retries" => 0
    ];
  }

  /**
   * Set State
   * @param $state
   */
  public function setState($state) {
    $cron = $this->cron;

    if ($cron) {
      $batches = get_option("wedoio-batches-cron", []);
      $id = $state['id'];
      if ($id) {
        $batches[$id] = $state;
        update_option("wedoio-batches-cron", $batches);
      }
    }

    $this->state = $state;
    if ($state['id']) {
      $_SESSION['wedoio-batches'][$state['id']] = $state;
//            session_write_close();
    }
  }

  /**
   * Process the current operation and return a new batch state
   */
  public function process() {
    set_time_limit(0);
    // The percent is calculated automatically
    $state = $this->state;
    $last = isset($state['last']) ? $state['last'] : time();

    if (time() - $last >= 500) {
      // If the last time that has been a long time
      $current = $state['current'];
      if ($current < count($state['operation'])) {
        $state['current'] += 1;
      }
    }

    $state['last'] = time();

    try {
      switch ($state['status']) {
        case "new":
        case "next":
          if (!$state) throw new Exception("No State found");
          $current = $state['current'];
          $operations = $state['operations'];
          if ($operations) {
            $percent = ($current / count($operations)) * 100;
            $state['percent'] = $percent;
            if ($current < count($operations)) {
              $r = $this->processOperation($state);
              array_push($state["results"], $r);
              $state['status'] = "next";
              $state['current'] += 1;
            } else {
              $state['status'] = "finished";
            }
          } else {
            throw new Exception("No operations defined");
          }
          break;
        case "pause":
          $state['msg'] = "batch paused";
          return $state;
        case "processing":
          $retries = $state['retry'];
          if ($retries > 10) {
            $state['retries'] = 0;
            $state['status'] = "next";
          } else {
            $state['retries'] += 1;
          }

        case "finished":
          $state['trash'] = true;
          $state['msg'] = "Cleaning Batch";
          $state['status'] = "reseted";
          $this->setState($state);
          $this->garbageCollector();
          return $state;
      }
    } catch (Exception $e) {
      $state['status'] = "finished";
      $state['trash'] = true;
      $state['error'] = true;
      $state['msg'] = $e->getMessage();
    }

    $this->setState($state);
    return $state;
  }

  /**
   * Process an operation on the batch. it needs to be a static function so we don't have problems with the classes
   */
  public function processOperation(&$state) {
    if (!$state) {
      $state = $this->state;
    }

    $r = false;

    try {
      // We set it as processing so it's not called all the time
      $state['status'] = "processing";
      $this->setState($state);

      // We add the state in the last part of the arguments always
      $current = $state['current'];
      $operation = $state['operations'][$current];
      $function = $operation[0];
      $args = $operation[1];
      $args[] = &$state;
      $r = call_user_func_array($function, $args);
    } catch (Exception $e) {
      throw new Exception($e);
    }

    return $r;
  }

  /**
   * Add an operation to the batch
   * @param $function
   * @param $args
   */
  public function addOperation($function, $args = []) {
    $this->state['operations'][] = array($function, $args);
  }

  /**
   * Set the operations on the batch
   * @param $operations
   */
  public function setOperations($operations) {
    $this->state['operations'] = $operations();
  }

  /**
   * Set Action on a batch
   * @param $state
   */
  public function setAction($action, $state = null) {
//        $state['action'] = "action";
    if (!$state) $state = $this->state;

    switch ($action) {
      case "stop":
        $state['status'] = "finished";
        break;
      case "pause":
        $state['status'] = "pause";
        break;
      case "resume":
        if ($state['status'] == "pause") {
          $state['status'] = "next";
        }
        break;
    }

    $this->setState($state);
  }

  /**
   * Process the batches in cron
   */
  public static function processCron() {
    $batches = get_option("wedoio-batches-cron", []);
    foreach ($batches as $id => $b) {
      $batch = new WedoioBatchApi($id, true);
      $batch->process();
    }
  }

  /**
   * Check the batches and clean them
   */
  public static function garbageCollector() {
    // We clean the old batches ?
    foreach ($_SESSION['wedoio-batches'] as $id => $b) {
      if (time() - $b['start'] > 60 * 60 * 24) {
        unset($_SESSION['wedoio-batches'][$id]);// No batch should last more than 24 hour lol
      }
    }

    // We clean the batches destined to be removed
    foreach ($_SESSION['wedoio-batches'] as $id => $b) {
      if (isset($b['trash'])) {
        unset($_SESSION['wedoio-batches'][$id]);
      }
    }

    // For the cron batches
    $batches = get_option("wedoio-batches-cron", []);
    $change = false;

    foreach ($batches as $id => $b) {
      if (isset($b['trash'])) {
        unset($batches[$id]);
        $change = true;
      }
    }

    if ($change) {
      update_option("wedoio-batches-cron", $batches);
    }
  }

  /**
   * Return a button with the corresponding javascript
   */
  public static function generateButton($id, $action, $label = "Start Batch", $cron = false) {

    $batch = false;
    $state = false;
    if ($cron) {
      // We get the batch
      $batch = new WedoioBatchApi($id, $cron);
      $state = $batch->getState();
    }

    ?>

    <div id="batch-<?php print $id ?>">
      <a class="batch-start-btn button button-primary" data-action="<?php print $action ?>"
         style="<?php print (($state !== false && $state['status'] !== "new" && $state['status'] !== "finished") ? "display:none;" : ""); ?>"><?php print $label ?></a>

      <div class="batch-state">
        <pre></pre>
        <div class="time"></div>
        <div class="msg">
          <?php if (isset($state['msg'])) : ?>
            <?php print $state['msg'] ?>
          <?php endif ?></div>
      </div>

      <div class="batch-meter">
        <div class="progress">
          <div class="progress-bar"
               style="width: <?php print isset($state['percent']) ? $state['percent'] : 0 ?>%;"></div>
        </div>
      </div>

      <?php if ($batch && $batch->status() && $batch->status() != "new") : ?>
        <div class="batch-cron" style="display:none;">
          <div class="info">

            <?php if (isset($state['status'])) : ?>
              <div>Current Status : <?php print $state['status'] ?> </div>
            <?php endif ?>

            <?php if (isset($state['last'])) : ?>
              <div>Last processed <?php print date("d/m/Y H:i:s", $state['last']) ?> </div>
            <?php endif ?>

          </div>
        </div>
      <?php endif; ?>

      <?php if ($cron && $state !== false && $state['status'] !== "new" && $state['status'] !== "finished"): ?>
        <div class="batch-actions" style="margin-top: 30px;">
          <a class="batch-stop-btn button button-primary">Stop</a>
          <!--                    <a class="batch-pause-btn button button-primary">Pause</a>-->
          <!--                    <a class="batch-reset-btn button button-primary">Reset</a>-->
        </div>
      <?php endif; ?>

    </div>
    <script type="text/javascript">
      (function ($) {
        $(document).ready(function () {
          var ajaxurl = window.ajaxurl;
          $("#batch-<?php print $id ?> .batch-start-btn").click(function () {
            $("#batch-<?php print $id ?> .batch-meter").addClass("active");
            $('#batch-<?php print $id ?>').attr("data-stop", false);
            $(this).hide();
            process_batch_<?php print $id ?>();

            function process_batch_<?php print $id ?>() {
              $.get(
                ajaxurl,
                {
                  'action': 'wedoio_batchapi_process',
                  'batch_action': "<?php print $action ?>",
                  'batch_cron': "<?php print $cron ?>",
                  'id': "<?php print $id; ?>"
                },
                function (response) {
                  var batch = JSON.parse(response);
                  console.log("batch response", batch);
                  $("#batch-<?php print $id ?> .batch-state pre").html(response);
                  $("#batch-<?php print $id ?> .batch-state .time").html(new Date().toUTCString());
                  $("#batch-<?php print $id ?> .batch-state .msg").html(batch.msg);
                  $("#batch-<?php print $id ?> .progress-bar").width(batch.percent + "%");
                  var stopped = $('#batch-<?php print $id ?>').attr("data-stop");
                  if (stopped === "true") batch.status = "finished";
                  if (batch.status !== "finished") {
                    process_batch_<?php print $id ?>();
                  } else {
                    $("#batch-<?php print $id ?> .batch-meter").removeClass("active");
                    $("#batch-<?php print $id ?> .batch-start-btn").show();
                  }
                }
              ).fail(function (response) {
                console.log("Batch error", response);
                process_batch_<?php print $id ?>();
              });
            }
          });

          <?php if(($state !== false && $state['status'] !== "new" && $state['status'] !== "finished")) : ?>
          $("#batch-<?php print $id ?> .batch-start-btn").click();
          <?php endif; ?>

          $("#batch-<?php print $id ?> .batch-stop-btn").click(function () {
            $('#batch-<?php print $id ?>').attr("data-stop", true);
            $.get(
              ajaxurl,
              {
                'action': 'wedoio_batchapi_process',
                'batch_action': "stop",
                'batch_cron': "<?php print $cron ?>",
                'id': "<?php print $id; ?>"
              },
              function (response) {
                var batch = JSON.parse(response);
                console.log("batch stop response", batch);
              });
          });
        });
      })
      (jQuery);
    </script>

    <?php
  }
}
