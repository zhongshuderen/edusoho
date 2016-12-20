<?php

namespace Biz\Thread\Dao\Impl;

use Biz\Thread\Dao\ThreadPostDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class ThreadPostDaoImpl extends GeneralDaoImpl implements ThreadPostDao
{
    protected $table = 'thread_post';

    public function getPostPostionInArticle($articleId, $postId)
    {
        $sql = "SELECT COUNT(1) FROM {$this->table} WHERE targetType = 'article' AND targetId = ? AND parentId = 0 AND id >= ? ORDER BY createdTime DESC";
        return $this->getConnection()->fetchColumn($sql, array($articleId, $postId));
    }

    public function deletePostsByThreadId($threadId)
    {
        return $this->db()->delete($this->table, array('threadId' => $threadId));
    }

    public function deletePostsByParentId($parentId)
    {
        return $this->db()->delete($this->table, array('parentId' => $parentId));
    }

    public function declares()
    {
        $declares['orderbys'] = array(
            'createdTime',
            'updatedTime'
        );

        $declares['conditions'] = array(
            'userId = :userId',
            'userId NOT IN (:notUserIds)',
            'userId IN (:userIds)',
            'id < :id',
            'id < :lessThanId',
            'ups >= :ups_GT',
            'id NOT IN (:excludeIds)',
            'createdTime >= :GTEcreatedTime',
            'parentId = :parentId',
            'threadId = :threadId',
            'targetId = :targetId',
            'targetId IN (:targetIds)',
            'targetType = :targetType',
            'adopted = :adopted'
        );

        $declares['serializes'] = array(
            'ats' => 'json'
        );

        return $declares;
    }
}
