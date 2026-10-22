<?php
// Upper limit for a single bid (LKR) - blocks absurd bids that would hijack an auction
define('ANTIQUE_BIDS_MAX_BID', 100000000);
// A bid placed this close to the end pushes the end back by the same amount (anti-sniping)
define('ANTIQUE_BIDS_EXTEND_SECONDS', 120);
// Bump when the plugin's tables change; ModelBids::ensureSchema() upgrades once
define('ANTIQUE_BIDS_SCHEMA', '2');

class ModelBids extends DAO {
    private static $instance;

    public static function newInstance() {
        if (!self::$instance instanceof self) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    public function __construct() {
        parent::__construct();
    }

    /**
     * Get auction details for an item
     */
    public function getAuction($itemId) {
        $this->dao->select('*');
        $this->dao->from(DB_TABLE_PREFIX . 't_item_auctions');
        $this->dao->where('fk_i_item_id', (int)$itemId);
        $result = $this->dao->get();
        
        if ($result && $result->numRows() > 0) {
            return $result->row();
        }
        return false;
    }

    /**
     * Get active highest bid for an item
     */
    public function getHighestBid($itemId) {
        $this->dao->select('*');
        $this->dao->from(DB_TABLE_PREFIX . 't_item_bids');
        $this->dao->where('fk_i_item_id', (int)$itemId);
        $this->dao->where('s_status', 'active');
        $this->dao->orderBy('d_amount', 'DESC');
        $this->dao->orderBy('pk_i_id', 'DESC');
        $this->dao->limit(1);
        $result = $this->dao->get();

        if ($result && $result->numRows() > 0) {
            return $result->row();
        }
        return false;
    }

    /**
     * Whether the current run of an auction has bids (active or won; bids from an
     * earlier run that ended unsold are 'withdrawn' and do not count)
     */
    public function hasBids($itemId) {
        $this->dao->select('pk_i_id');
        $this->dao->from(DB_TABLE_PREFIX . 't_item_bids');
        $this->dao->where('fk_i_item_id', (int)$itemId);
        $this->dao->whereIn('s_status', array('active', 'won'));
        $this->dao->limit(1);
        $result = $this->dao->get();
        return ($result && $result->numRows() > 0);
    }

    /**
     * Get active bid history list for an item
     */
    public function getBids($itemId, $limit = 10) {
        $this->dao->select('b.*, u.s_name as user_name');
        $this->dao->from(DB_TABLE_PREFIX . 't_item_bids b');
        $this->dao->join(DB_TABLE_PREFIX . 't_user u', 'b.fk_i_user_id = u.pk_i_id', 'INNER');
        $this->dao->where('b.fk_i_item_id', (int)$itemId);
        $this->dao->where('b.s_status', 'active');
        $this->dao->orderBy('b.d_amount', 'DESC');
        $this->dao->orderBy('b.pk_i_id', 'DESC');
        $this->dao->limit($limit);
        $result = $this->dao->get();

        if ($result && $result->numRows() > 0) {
            return $result->result();
        }
        return array();
    }

    /**
     * Public bid history: active and winning bids (withdrawn winners hidden)
     */
    public function getBidHistory($itemId, $limit = 10) {
        $this->dao->select('b.*, u.s_name as user_name');
        $this->dao->from(DB_TABLE_PREFIX . 't_item_bids b');
        $this->dao->join(DB_TABLE_PREFIX . 't_user u', 'b.fk_i_user_id = u.pk_i_id', 'INNER');
        $this->dao->where('b.fk_i_item_id', (int)$itemId);
        $this->dao->whereIn('b.s_status', array('active', 'won'));
        $this->dao->orderBy('b.d_amount', 'DESC');
        $this->dao->orderBy('b.pk_i_id', 'DESC');
        $this->dao->limit($limit);
        $result = $this->dao->get();

        if ($result && $result->numRows() > 0) {
            return $result->result();
        }
        return array();
    }

    /**
     * Creates the proxy-bid table on sites installed before proxy bidding existed.
     * Runs once per schema version (stored as a preference).
     */
    public function ensureSchema() {
        if (osc_get_preference('schema_version', 'antique_bids') == ANTIQUE_BIDS_SCHEMA) {
            return;
        }
        $prefix = DB_TABLE_PREFIX;
        $ok = $this->dao->query("CREATE TABLE IF NOT EXISTS `{$prefix}t_item_bid_proxies` (
            `fk_i_item_id` int(10) unsigned NOT NULL,
            `fk_i_user_id` int(10) unsigned NOT NULL,
            `d_max_amount` decimal(15,4) NOT NULL,
            `dt_updated` datetime NOT NULL,
            PRIMARY KEY (`fk_i_item_id`, `fk_i_user_id`),
            CONSTRAINT `{$prefix}t_item_bid_proxies_ibfk_1` FOREIGN KEY (`fk_i_item_id`) REFERENCES `{$prefix}t_item_auctions` (`fk_i_item_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        if (!$ok) {
            // older databases can refuse the foreign key; the table works without it
            // (deleteAuction() removes the rows itself)
            $ok = $this->dao->query("CREATE TABLE IF NOT EXISTS `{$prefix}t_item_bid_proxies` (
                `fk_i_item_id` int(10) unsigned NOT NULL,
                `fk_i_user_id` int(10) unsigned NOT NULL,
                `d_max_amount` decimal(15,4) NOT NULL,
                `dt_updated` datetime NOT NULL,
                PRIMARY KEY (`fk_i_item_id`, `fk_i_user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        }
        if ($ok) {
            osc_set_preference('schema_version', ANTIQUE_BIDS_SCHEMA, 'antique_bids');
        }
    }

    /**
     * A bidder's maximum (proxy) bid on an item, or 0.
     */
    public function getProxyMax($itemId, $userId) {
        $this->dao->select('d_max_amount');
        $this->dao->from(DB_TABLE_PREFIX . 't_item_bid_proxies');
        $this->dao->where('fk_i_item_id', (int)$itemId);
        $this->dao->where('fk_i_user_id', (int)$userId);
        $result = $this->dao->get();
        return ($result && $result->numRows() > 0) ? (float)$result->row()['d_max_amount'] : 0;
    }

    /**
     * Number of public bids (active + won) on an item.
     */
    public function countBids($itemId) {
        $this->dao->select('COUNT(*) AS n');
        $this->dao->from(DB_TABLE_PREFIX . 't_item_bids');
        $this->dao->where('fk_i_item_id', (int)$itemId);
        $this->dao->whereIn('s_status', array('active', 'won'));
        $result = $this->dao->get();
        return $result ? (int)$result->row()['n'] : 0;
    }

    /**
     * Distinct user IDs with a live or winning bid in the current run of an auction.
     */
    public function getBidderIds($itemId) {
        $result = $this->dao->query("SELECT DISTINCT fk_i_user_id FROM `" . DB_TABLE_PREFIX . "t_item_bids` WHERE fk_i_item_id = " . (int)$itemId . " AND s_status IN ('active', 'won')");
        $ids = array();
        if ($result) {
            foreach ($result->result() as $r) {
                $ids[] = (int)$r['fk_i_user_id'];
            }
        }
        return $ids;
    }

    /**
     * Places a maximum bid with proxy bidding (as on eBay): the site bids for each
     * bidder only as much as needed to keep them ahead, up to their maximum.
     * The auction row is locked (SELECT ... FOR UPDATE) so concurrent bids resolve
     * one at a time.
     *
     * Returns an array on success:
     *   leader      user ID now in the lead
     *   price       the visible current bid
     *   outbid      user ID who just lost the lead (0 if none)
     *   end         auction end (Y-m-d H:i:s), possibly extended
     *   extended    true when a late bid extended the auction
     *   reserve_met true when there is no reserve or the current bid reaches it
     * or an error message string.
     */
    public function placeBid($itemId, $userId, $maxAmount, $ipAddress, $currency = '') {
        $itemId = (int)$itemId;
        $userId = (int)$userId;
        $maxAmount = round((float)$maxAmount, 2);
        $prefix = DB_TABLE_PREFIX;
        $this->dao->connId->autocommit(false);

        try {
            $res = $this->dao->query("SELECT * FROM `{$prefix}t_item_auctions` WHERE fk_i_item_id = {$itemId} LIMIT 1 FOR UPDATE");
            if (!$res || $res->numRows() == 0) {
                throw new Exception('This listing is not configured for bidding.');
            }
            $auction = $res->row();
            $end = strtotime($auction['dt_end_date']);
            if ($auction['b_active'] == 0 || $end < time()) {
                throw new Exception('Bidding for this item has closed.');
            }
            if (!is_finite($maxAmount) || $maxAmount <= 0) {
                throw new Exception('Please enter a valid bid.');
            }
            if ($maxAmount > ANTIQUE_BIDS_MAX_BID) {
                throw new Exception('Bid cannot exceed ' . antique_bids_money(ANTIQUE_BIDS_MAX_BID, $currency) . '.');
            }

            $start = (float)$auction['d_starting_price'];
            $inc = max(1, (float)$auction['d_min_increment']);
            $reserve = (float)$auction['d_reserve_price'];

            // current visible leader
            $res = $this->dao->query("SELECT pk_i_id, fk_i_user_id, d_amount FROM `{$prefix}t_item_bids`
                WHERE fk_i_item_id = {$itemId} AND s_status = 'active' ORDER BY d_amount DESC, pk_i_id DESC LIMIT 1 FOR UPDATE");
            $top = ($res && $res->numRows() > 0) ? $res->row() : null;
            $current = $top ? (float)$top['d_amount'] : 0;
            $leaderBefore = $top ? (int)$top['fk_i_user_id'] : 0;

            // everyone's maximum; a leader from before proxy bidding counts at their bid
            $proxies = array();
            $res = $this->dao->query("SELECT fk_i_user_id, d_max_amount, dt_updated FROM `{$prefix}t_item_bid_proxies` WHERE fk_i_item_id = {$itemId} FOR UPDATE");
            if ($res) {
                foreach ($res->result() as $r) {
                    $proxies[(int)$r['fk_i_user_id']] = array('max' => (float)$r['d_max_amount'], 'at' => $r['dt_updated']);
                }
            }
            if ($top && (!isset($proxies[$leaderBefore]) || $proxies[$leaderBefore]['max'] < $current)) {
                $proxies[$leaderBefore] = array('max' => $current, 'at' => '1970-01-01 00:00:00');
            }

            if ($userId == $leaderBefore) {
                $mine = $proxies[$userId]['max'];
                if ($maxAmount <= $mine) {
                    throw new Exception('You are already the highest bidder with a maximum of ' . antique_bids_money($mine, $currency) . '. Enter a higher amount to raise it.');
                }
            } else {
                $minNext = $top ? $current + $inc : $start;
                if ($maxAmount < $minNext) {
                    throw new Exception('Your bid must be at least ' . antique_bids_money($minNext, $currency) . '.');
                }
            }

            $now = date('Y-m-d H:i:s');
            $this->dao->query("INSERT INTO `{$prefix}t_item_bid_proxies` (fk_i_item_id, fk_i_user_id, d_max_amount, dt_updated)
                VALUES ({$itemId}, {$userId}, " . sprintf('%.2F', $maxAmount) . ", '{$now}')
                ON DUPLICATE KEY UPDATE d_max_amount = VALUES(d_max_amount), dt_updated = VALUES(dt_updated)");
            $proxies[$userId] = array('max' => $maxAmount, 'at' => $now);

            // highest maximum leads; on a tie the earlier maximum wins
            uasort($proxies, function ($a, $b) {
                if ($a['max'] != $b['max']) { return $a['max'] < $b['max'] ? 1 : -1; }
                return strcmp($a['at'], $b['at']);
            });
            $ids = array_keys($proxies);
            $leader = $ids[0];
            $max1 = $proxies[$leader]['max'];
            $second = isset($ids[1]) ? $ids[1] : 0;
            $max2 = $second ? $proxies[$second]['max'] : 0;

            if ($second) {
                $price = min($max1, $max2 + $inc);
            } else {
                $price = $top ? $current : $start;
            }
            if ($top && $leader != $leaderBefore) {
                $price = max($price, min($max1, $current + $inc));
            }
            $price = max($price, $top ? $current : $start);
            if ($reserve > 0 && $max1 >= $reserve && $price < $reserve) {
                $price = $reserve;   // a maximum at or above the reserve meets it straight away
            }
            $price = min($price, $max1);

            $bids = array();
            if ($second && $max2 > $current && !($second == $leaderBefore && $max2 == $current)) {
                $bids[] = array($second, $max2);          // the runner-up's maximum, so the history shows the contest
            }
            if (!$top || $leader != $leaderBefore || $price > $current) {
                $bids[] = array($leader, $price);
            }
            foreach ($bids as $b) {
                $ok = $this->dao->insert($prefix . 't_item_bids', array(
                    'fk_i_item_id' => $itemId,
                    'fk_i_user_id' => (int)$b[0],
                    'd_amount'     => (float)$b[1],
                    's_ip_address' => ($b[0] == $userId) ? $ipAddress : 'auto'
                ));
                if (!$ok) {
                    throw new Exception('Could not save your bid. Please try again.');
                }
            }

            // a bid in the final minutes extends the auction, so nobody wins by sniping
            $extended = false;
            if ($end - time() < ANTIQUE_BIDS_EXTEND_SECONDS) {
                $end = time() + ANTIQUE_BIDS_EXTEND_SECONDS;
                $this->dao->update($prefix . 't_item_auctions', array('dt_end_date' => date('Y-m-d H:i:s', $end)), array('fk_i_item_id' => $itemId));
                $extended = true;
            }

            $this->dao->connId->commit();
            $this->dao->connId->autocommit(true);

            return array(
                'leader'      => $leader,
                'price'       => $price,
                'outbid'      => ($leaderBefore && $leaderBefore != $leader) ? $leaderBefore : 0,
                'end'         => date('Y-m-d H:i:s', $end),
                'extended'    => $extended,
                'reserve_met' => ($reserve <= 0 || $price >= $reserve)
            );

        } catch (Exception $e) {
            $this->dao->connId->rollback();
            $this->dao->connId->autocommit(true);
            return $e->getMessage();
        }
    }

    /**
     * True when an auction has ended, its result has been declared and nobody won
     * (no bids, reserve not met, or the winner was cancelled with no one to offer it to).
     * Such an auction can be run again.
     */
    public function isFinishedUnsold($auction) {
        if (!$auction || strtotime($auction['dt_end_date']) > time() || (int)$auction['b_processed'] !== 1) {
            return false;
        }
        $result = $this->dao->query("SELECT 1 FROM `" . DB_TABLE_PREFIX . "t_item_bids` WHERE fk_i_item_id = " . (int)$auction['fk_i_item_id'] . " AND s_status = 'won' LIMIT 1");
        return !($result && $result->numRows() > 0);
    }

    /**
     * Inserts or updates the auction parameters for a listing.
     * $durationDays = 0 keeps the end date of a running auction (editing the
     * listing must not restart its timer).
     */
    public function saveAuction($itemId, $startingPrice, $minIncrement, $durationDays, $active, $reservePrice = 0) {
        $prefix = DB_TABLE_PREFIX;
        $keepEnd = ((int)$durationDays === 0);
        if (!in_array((int)$durationDays, array(3, 5, 7, 10, 14, 30))) {
            $durationDays = 7;
        }
        $startingPrice = min(max(0, (float)$startingPrice), ANTIQUE_BIDS_MAX_BID);
        $minIncrement = min(max(1, (float)$minIncrement), ANTIQUE_BIDS_MAX_BID);
        // a reserve at or below the starting bid means nothing
        $reservePrice = min(max(0, (float)$reservePrice), ANTIQUE_BIDS_MAX_BID);
        if ($reservePrice <= $startingPrice) {
            $reservePrice = 0;
        }
        $endDate = date('Y-m-d H:i:s', time() + ((int)$durationDays * 24 * 60 * 60));

        $exists = $this->getAuction($itemId);
        $live = $exists && strtotime($exists['dt_end_date']) > time();
        $unsold = $exists && $this->isFinishedUnsold($exists);

        if ($exists && !$active) {
            if ($live && $this->hasBids($itemId)) {
                return true;   // bidders are committed: a running auction with bids cannot be withdrawn
            }
            return $this->dao->update($prefix . 't_item_auctions', array('b_active' => 0), array('fk_i_item_id' => (int)$itemId));
        }

        if ($unsold) {
            // running it again: the earlier bids and maximums belong to the old run
            // (kept as 'withdrawn' for the record; s_status may be an ENUM, so no new value)
            $this->dao->delete($prefix . 't_item_bid_proxies', array('fk_i_item_id' => (int)$itemId));
            $this->dao->query("UPDATE `{$prefix}t_item_bids` SET s_status = 'withdrawn' WHERE fk_i_item_id = " . (int)$itemId . " AND s_status = 'active'");
        } elseif ($exists && $this->hasBids($itemId)) {
            // Once bidding has started the terms are locked: editing the listing
            // must not restart the timer or re-open a finished auction.
            return $this->dao->update(
                $prefix . 't_item_auctions',
                array('b_active' => 1),
                array('fk_i_item_id' => (int)$itemId)
            );
        }

        if ($exists) {
            $data = array(
                'd_starting_price' => (float)$startingPrice,
                'd_reserve_price'  => (float)$reservePrice,
                'd_min_increment'  => (float)$minIncrement,
                'b_active'         => 1
            );
            if (!($keepEnd && $live && (int)$exists['b_active'] === 1)) {
                $data['dt_start_date'] = date('Y-m-d H:i:s');
                $data['dt_end_date'] = $endDate;
                $data['b_processed'] = 0;
            }
            return $this->dao->update($prefix . 't_item_auctions', $data, array('fk_i_item_id' => (int)$itemId));
        } else {
            return $this->dao->insert(
                $prefix . 't_item_auctions',
                array(
                    'fk_i_item_id'     => (int)$itemId,
                    'd_starting_price' => (float)$startingPrice,
                    'd_reserve_price'  => (float)$reservePrice,
                    'd_min_increment'  => (float)$minIncrement,
                    'dt_start_date'    => date('Y-m-d H:i:s'),
                    'dt_end_date'      => $endDate,
                    'b_active'         => $active ? 1 : 0,
                    'b_processed'      => 0
                )
            );
        }
    }

    /**
     * Fetch active auctions that have expired but have not been processed yet
     */
    public function getExpiredUnprocessedAuctions() {
        $this->dao->select('*');
        $this->dao->from(DB_TABLE_PREFIX . 't_item_auctions');
        $this->dao->where('dt_end_date < NOW()');
        $this->dao->where('b_processed', 0);
        $this->dao->where('b_active', 1);
        $result = $this->dao->get();
        if ($result && $result->numRows() > 0) {
            return $result->result();
        }
        return array();
    }

    /**
     * Mark auction as processed. Returns 1 only for the request that claimed it.
     */
    public function markAuctionProcessed($itemId) {
        return $this->dao->update(
            DB_TABLE_PREFIX . 't_item_auctions',
            array('b_processed' => 1),
            array('fk_i_item_id' => (int)$itemId, 'b_processed' => 0)
        );
    }

    /**
     * Set the status of a specific bid (won, withdrawn, active)
     */
    public function setBidStatus($bidId, $status) {
        return $this->dao->update(
            DB_TABLE_PREFIX . 't_item_bids',
            array('s_status' => $status),
            array('pk_i_id' => (int)$bidId)
        );
    }

    /**
     * Fetch detailed bid row by primary key
     */
    public function getBid($bidId) {
        $this->dao->select('*');
        $this->dao->from(DB_TABLE_PREFIX . 't_item_bids');
        $this->dao->where('pk_i_id', (int)$bidId);
        $result = $this->dao->get();
        if ($result && $result->numRows() > 0) {
            return $result->row();
        }
        return false;
    }

    /**
     * Deletes auction records and bids when an item is deleted.
     */
    public function deleteAuction($itemId) {
        $prefix = DB_TABLE_PREFIX;
        $this->dao->delete($prefix . 't_item_bid_proxies', array('fk_i_item_id' => (int)$itemId));
        $this->dao->delete($prefix . 't_item_bids', array('fk_i_item_id' => (int)$itemId));
        return $this->dao->delete($prefix . 't_item_auctions', array('fk_i_item_id' => (int)$itemId));
    }
}
?>
