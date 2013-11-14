<?php
/**
 * Brim LLC
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to support@brimllc.com so we can send you a copy immediately.
 *
 *
 * @category   Brim
 * @package    Brim_Cache
 * @copyright  Copyright (c) 2012 Brim LLC
 * @license    http://ecommerce.brimllc.com/license-osl
 */

class Brim_Cache_Backend_Database extends Varien_Cache_Backend_Database {

    /**
     * Returns ids of expired cached items.
     *
     * @return array
     */
    public function getExpiredIds () {

        $select = $this->_getAdapter()->select()
            ->from($this->_getDataTable(), 'id')
            ->where('expire_time > ?', 0)
            ->where('expire_time <= ?', time());

        return $this->_getAdapter()->fetchCol($select);
    }

    /**
     * Removes orphaned tags from the tags database table when cleaning old objects.
     *
     * @param string $mode
     * @param array $tags
     * @return bool
     */
    public function clean($mode = Zend_Cache::CLEANING_MODE_ALL, $tags = array())
    {
        $result = parent::clean();

        if ($mode ==  Zend_Cache::CLEANING_MODE_OLD) {
            $adapter = $this->_getAdapter();
            $adapter->delete($this->_getTagsTable(), "cache_id NOT IN (SELECT id FROM {$this->_getDataTable()})");
        }

        return $result;
    }
}
