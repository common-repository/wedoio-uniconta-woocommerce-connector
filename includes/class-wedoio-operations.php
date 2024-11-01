<?php
/**
 * Api operations for the plugin
 *
 */

// Debtors operations
/**
 * Fetch a list of debtors and generate the operations.
 * @param $state
 */
function wedoio_batch_debtor_init(&$state) {
  $api = new WedoioApi();
  $items_per_batch = 2;
  $res = $api->send("Debtor/dr");
  $body = $res['body'];
  $debtors = json_decode($body, true);

  $id = $state['id'];
  $batch = new WedoioBatchApi($id);
  $batch_items = true;
  while ($batch_items) {
    $batch_items = array_splice($debtors, 0, $items_per_batch);
    if ($batch_items) {
      $batch->addOperation("wedoio_batch_debtor_process", [$batch_items]);
    }
  }
  $batch->addOperation("wedoio_batch_debtor_finalize");
  $state['msg'] = "Initializing Debtor Master Sync";
  $state['operations'] = $batch->getState()['operations'];
}

/**
 * Process the debtor Sync
 * @param $rowId
 * @param $state
 */
function wedoio_batch_debtor_process($rowIds, &$state) {
  $uids = array();
  $state['msg'] = "Master Sync : " . intval($state['percent']) . "% | Processing Debtors [" . implode(",", $rowIds) . "]";
  foreach ($rowIds as $rowId) {
    $uids[] = Wedoio_Debtor::syncDebtorFromRowId($rowId);
  }
  return $uids;
}

/**
 * Finalize the sync and save the last sync execution
 * @param $state
 */
function wedoio_batch_debtor_finalize(&$state) {
  $process_time = time() - $state['start'];
  $state['msg'] = "Finished Synchronization in " . $process_time . " secs";
  update_option("last_master_sync_debtor", time());
}


// Invitem operations
/**
 * Fetch a list of invitems and generate the operations.
 * @param $state
 */
function wedoio_batch_invitem_init(&$state) {
  $api = new WedoioApi();
  $items_per_batch = 10;
  $res = $api->odata_call("InvItemClient");
  $body = $res['body'];
  $invitems = json_decode($body, true);

  // We need to filter the InvItems a bit
  $invitems_tmp = [];
  foreach ($invitems as $invitem) {
    $invitems_tmp[$invitem->_Item] = $invitem;
  }

  $invitems = $invitems_tmp;

  $id = $state['id'];
  $batch = new WedoioBatchApi($id);
  $batch_items = true;
  while ($batch_items) {
    $items_slice = array_splice($invitems, 0, $items_per_batch);
    $batch_items = array();
    foreach ($items_slice as $item) {
      $batch_items[] = $item['_Item'];
    }
    if ($batch_items) {
      $batch->addOperation("wedoio_batch_invitem_process", [$batch_items]);
    }
  }
  $batch->addOperation("wedoio_batch_invitem_finalize");
  $state['msg'] = "Initializing Invitem Master Sync";
  $state['operations'] = $batch->getState()['operations'];
}

/**
 * Process the invitem Sync
 * @param $rowId
 * @param $state
 */
function wedoio_batch_invitem_process($items, &$state) {
  $uids = array();
  $state['msg'] = "Master Sync : " . intval($state['percent']) . "% | Processing Invitem [" . implode(",", $items) . "]";
  foreach ($items as $item) {
    $uids[] = Wedoio_InvItem::syncInvItemFromItem($item);
  }
  return $uids;
}

/**
 * Finalize the sync and save the last sync execution
 * @param $state
 */
function wedoio_batch_invitem_finalize(&$state) {
  $process_time = time() - $state['start'];
  $state['msg'] = "Finished Synchronization in " . $process_time . " secs";
  update_option("last_master_sync_invitem", time());
}


// Batch operations (test)
/**
 * Add a random number of operations in the batch
 */
function wedoio_batch_test_init(&$state) {

  $items_nb = rand(5, 30);
  $id = $state['id'];
  $batch = new WedoioBatchApi($id);
  for ($i = 0; $i < $items_nb; $i++) {
    $batch->addOperation("wedoio_batch_test_process");
  }
  $batch->addOperation("wedoio_batch_test_finalize");
  $state['msg'] = "Initializing Test Batch";
  $state['operations'] = $batch->getState()['operations'];
}

/**
 * Test process that spend 1 to 5 seconds to execute to simulate operations
 * @param $state
 */
function wedoio_batch_test_process(&$state) {
  $execution = rand(1, 5);
  $state['msg'] = "Processing : " . intval($state['percent']) . "%";
  sleep($execution);
  return rand(0, 1);
}

/**
 * Test Process Finalization
 * @param $state
 * @return int
 */
function wedoio_batch_test_finalize(&$state) {
  $process_time = time() - $state['start'];
  $state['msg'] = "Finished Processing test in " . $process_time . " secs";
}

// Helper functions
/**
 * DRUPAL RULES !!!!
 */
function wedoio_dpm() {
  if (isset($_GET['wedoio-debug'])) {
    $_ = func_get_args();
    call_user_func_array(
      array('krumo', 'dump'), $_
    );
  }
}
