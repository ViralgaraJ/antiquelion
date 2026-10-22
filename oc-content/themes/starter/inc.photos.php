<?php
if (!defined('ABS_PATH')) { exit('ABS_PATH is not loaded. Direct access is not allowed.'); }

/*
 * Listing photos (js/al-photos.js). When a seller edits a photo that is already saved
 * on a listing, the edited copy is uploaded as a new photo and the old one is only
 * deleted once the listing is saved: the form sends its id as al_replaced_res[].
 * Leaving the page without saving keeps the listing as it was.
 */
function antique_photos_delete_replaced($item) {
  $ids = Params::getParam('al_replaced_res');
  if (!is_array($ids) || empty($item['pk_i_id'])) { return; }
  $itemId = (int)$item['pk_i_id'];
  foreach (array_slice($ids, 0, 50) as $id) {
    $id = (int)$id;
    if ($id <= 0) { continue; }
    $res = ItemResource::newInstance()->findByPrimaryKey($id);
    // only photos of the listing that was just saved (the edit itself checked ownership)
    if (!$res || (int)$res['fk_i_item_id'] !== $itemId) { continue; }
    osc_deleteResource($id, false);
    ItemResource::newInstance()->delete(array('pk_i_id' => $id, 'fk_i_item_id' => $itemId));
  }
}
osc_add_hook('edited_item', 'antique_photos_delete_replaced');
