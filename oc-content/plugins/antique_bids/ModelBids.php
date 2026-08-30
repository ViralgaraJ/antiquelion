<?php
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
        $this->dao->limit(1);
        $result = $this->dao->get();

        if ($result && $result->numRows() > 0) {
            return $result->row();
        }
        return false;
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
        $this->dao->limit($limit);
        $result = $this->dao->get();

        if ($result && $result->numRows() > 0) {
            return $result->result();
        }
        return array();
    }

    /**
     * Places a bid safely with transactional locking (SELECT FOR UPDATE)
     * to prevent race conditions during high concurrency.
     */
    public function placeBid($itemId, $userId, $amount, $ipAddress) {
        $this->dao->connId->autocommit(false);

        try {
            $prefix = DB_TABLE_PREFIX;

            // 1. Lock the auction settings row using FOR UPDATE
            $sql_auction = "SELECT d_starting_price, d_min_increment, dt_end_date, b_active 
                            FROM `{$prefix}t_item_auctions` 
                            WHERE fk_i_item_id = " . (int)$itemId . " LIMIT 1 FOR UPDATE";
            
            $auction_res = $this->dao->query($sql_auction);
            if (!$auction_res || $auction_res->numRows() == 0) {
                throw new Exception("This listing is not configured for bidding.");
            }
            $auction = $auction_res->row();

            if ($auction['b_active'] == 0 || strtotime($auction['dt_end_date']) < time()) {
                throw new Exception("Bidding for this item has closed.");
            }

            // 2. Lock the current highest bid using FOR UPDATE
            $sql_highest = "SELECT MAX(d_amount) as current_highest 
                            FROM `{$prefix}t_item_bids` 
                            WHERE fk_i_item_id = " . (int)$itemId . " AND s_status = 'active' FOR UPDATE";
            
            $highest_res = $this->dao->query($sql_highest);
            $highestBidRow = $highest_res ? $highest_res->row() : null;
            
            $currentHighest = ($highestBidRow && $highestBidRow['current_highest'] !== null) 
                              ? $highestBidRow['current_highest'] 
                              : $auction['d_starting_price'];
            
            $minNextBid = $currentHighest + $auction['d_min_increment'];

            // 3. Validate user bid amount
            if ($amount < $minNextBid) {
                throw new Exception("Bid must be at least LKR " . number_format($minNextBid, 2));
            }

            // 4. Record new bid in DB
            $inserted = $this->dao->insert($prefix . 't_item_bids', array(
                'fk_i_item_id' => (int)$itemId,
                'fk_i_user_id' => (int)$userId,
                'd_amount'     => (float)$amount,
                's_ip_address' => $ipAddress
            ));

            if (!$inserted) {
                throw new Exception("Could not insert bid record.");
            }

            $this->dao->connId->commit();
            $this->dao->connId->autocommit(true);
            return true;

        } catch (Exception $e) {
            $this->dao->connId->rollback();
            $this->dao->connId->autocommit(true);
            return $e->getMessage();
        }
    }

    /**
     * Inserts or updates the auction parameters for a listing.
     */
    public function saveAuction($itemId, $startingPrice, $minIncrement, $durationDays, $active) {
        $prefix = DB_TABLE_PREFIX;
        $endDate = date('Y-m-d H:i:s', time() + ((int)$durationDays * 24 * 60 * 60));
        
        $exists = $this->getAuction($itemId);
        if ($exists) {
            return $this->dao->update(
                $prefix . 't_item_auctions',
                array(
                    'd_starting_price' => (float)$startingPrice,
                    'd_min_increment'  => (float)$minIncrement,
                    'dt_end_date'      => $endDate,
                    'b_active'         => $active ? 1 : 0,
                    'b_processed'      => 0
                ),
                array('fk_i_item_id' => (int)$itemId)
            );
        } else {
            return $this->dao->insert(
                $prefix . 't_item_auctions',
                array(
                    'fk_i_item_id'     => (int)$itemId,
                    'd_starting_price' => (float)$startingPrice,
                    'd_min_increment'  => (float)$minIncrement,
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
     * Mark auction as processed
     */
    public function markAuctionProcessed($itemId) {
        return $this->dao->update(
            DB_TABLE_PREFIX . 't_item_auctions',
            array('b_processed' => 1),
            array('fk_i_item_id' => (int)$itemId)
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
        $this->dao->delete($prefix . 't_item_bids', array('fk_i_item_id' => (int)$itemId));
        return $this->dao->delete($prefix . 't_item_auctions', array('fk_i_item_id' => (int)$itemId));
    }
}
?>
