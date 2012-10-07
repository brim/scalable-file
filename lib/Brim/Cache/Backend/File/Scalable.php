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

class Brim_Cache_Backend_File_Scalable extends Zend_Cache_Backend_File
{
    /**
     * @var array
     */
    protected $_defaultBackendOptions = array(
        'hashed_directory_level'    => 1,
        'hashed_directory_umask'    => 0777,
        'file_name_prefix'          => 'mage',
    );

    /**
     * @var null|Brim_PageCache_Model_Backend_Database
     */
    protected $_dbBackend = null;

    /**
     * @param array $options
     */
    public function __construct($options= array()) {
        foreach($this->_defaultBackendOptions as $key => $value) {
            if (!isset($options[$key])) {
                $options[$key] = $value;
            }
        }

        if (!isset($options['cache_dir'])) {
            $options['cache_dir'] = Mage::getBaseDir('cache');
        }

        parent::__construct($options);

        $options                    = array();
        $options['adapter_callback']= array($this, 'getDbAdapter');
        $options['data_table']      = Mage::getSingleton('core/resource')->getTableName('core/cache');
        $options['tags_table']      = Mage::getSingleton('core/resource')->getTableName('core/cache_tag');

        $this->_dbBackend           = new Brim_Cache_Backend_Database($options);
    }

    /**
     * @param string $data
     * @param string $id
     * @param array $tags
     * @param bool $specificLifetime
     * @return bool|void
     */
    public function save($data, $id, $tags = array(), $specificLifetime = false) {

        parent::save($data, $id, $tags, $specificLifetime);

        // Save metadata and tags to DB for faster clean operations
        $this->_dbBackend->save(null, $id, $tags, $specificLifetime);
    }

    /**
     * @param string $id
     * @return bool|void
     */
    public function remove($id) {
        parent::remove($id);
        $this->_dbBackend->remove($id);
    }

    /**
     * @return mixed
     */
    public function getDbAdapter() {
        return Mage::getSingleton('core/resource')->getConnection('core_write');
    }

    /**
     * @param string $mode
     * @param array $tags
     * @return bool|void
     */
    public function clean($mode = Zend_Cache::CLEANING_MODE_ALL, $tags = array()) {
        parent::clean($mode, $tags);
        $this->_dbBackend->clean($mode, $tags);
    }

    /**
     * Maps to database backend methods
     *
     * @param string $dir
     * @param string $mode
     * @param array $tags
     * @return bool
     */
    protected function _clean($dir, $mode = Zend_Cache::CLEANING_MODE_ALL, $tags = array()) {

        if (!is_dir($dir)) {
            return false;
        }

        $result = true;
        $prefix = $this->_options['file_name_prefix'];

        switch ($mode) {
            case Zend_Cache::CLEANING_MODE_ALL:
                $glob = @glob($dir . $prefix . '--*');
                if ($glob === false) {
                    // On some systems it is impossible to distinguish between empty match and an error.
                    return true;
                }
                foreach ($glob as $file)  {
                    if (is_file($file)) {
                        $result = $this->_remove($file);
                    }
                    if ((is_dir($file)) and ($this->_options['hashed_directory_level']>0)) {
                        // Recursive call
                        $result = $this->_clean($file . DIRECTORY_SEPARATOR, $mode, $tags) && $result;
                        @rmdir($file);
                    }
                }

                break;
            case Zend_Cache::CLEANING_MODE_OLD:
                $matchingIds = $this->_dbBackend->getExpiredIds();
                foreach($matchingIds as $id) {
                    $result = $this->remove($id) && $result;
                }
                break;
            case Zend_Cache::CLEANING_MODE_MATCHING_TAG:
                $matchingIds = $this->_dbBackend->getIdsMatchingTags($tags);
                foreach($matchingIds as $id) {
                    $result = $this->remove($id) && $result;
                }
                break;
            case Zend_Cache::CLEANING_MODE_NOT_MATCHING_TAG:
                $matchingIds = $this->_dbBackend->getIdsNotMatchingTags($tags);
                foreach($matchingIds as $id) {
                    $result = $this->remove($id) && $result;
                }
                break;
            case Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG:
                $matchingIds = $this->_dbBackend->getIdsMatchingAnyTags($tags);
                foreach($matchingIds as $id) {
                    $result = $this->remove($id) && $result;
                }
                break;
            default:
                Zend_Cache::throwException('Invalid mode for clean() method');
                break;
        }

        return $result;
    }

    /**
     * Maps to database backend methods
     *
     * @param $dir
     * @param $mode
     * @param array $tags
     * @return array|bool
     */
    protected function _get($dir, $mode, $tags = array()) {
        $result = null;
        switch ($mode) {
            case 'ids':
                $result = $this->_dbBackend->getIds();
                break;
            case 'tags':
                $result = $this->_dbBackend->getTags();
                break;
            case 'matching':
                $result = $this->_dbBackend->getIdsMatchingTags($tags);
                break;
            case 'notMatching':
                $result = $this->_dbBackend->getIdsNotMatchingTags($tags);
                break;
            case 'matchingAny':
                $result = $this->_dbBackend->getIdsMatchingAnyTags($tags);
                break;
            default:
                Zend_Cache::throwException('Invalid mode for _get() method');
                break;
        }

        return array_unique($result);
    }
}