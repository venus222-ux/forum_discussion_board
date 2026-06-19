<?php

namespace App\Services\Comment;

use App\Models\User;

class CommentTreeService
{
    public function build($comments): array
    {
        $map = [];
        $tree = [];

        foreach ($comments as $comment) {
            $arr = $comment->toArray();
            $arr['_id'] = (string)$comment->_id;
            $arr['children'] = [];
            $map[$arr['_id']] = $arr;
        }

        foreach ($map as $id => &$comment) {
            if (!empty($comment['parentId']) && isset($map[$comment['parentId']])) {
                $map[$comment['parentId']]['children'][] = &$comment;
            } else {
                $tree[] = &$comment;
            }
        }

        return $tree;
    }

    public function markBest(array $tree, ?string $bestId): array
    {
        if (!$bestId) return $tree;

        foreach ($tree as &$node) {
            $node['isBest'] = ((string)$node['_id'] === (string)$bestId);

            if (!empty($node['children'])) {
                $node['children'] = $this->markBest($node['children'], $bestId);
            }
        }

        return $tree;
    }

    public function attachUsers(array $tree): array
    {
        $userIds = $this->collectUserIds($tree);
        $users = User::whereIn('id', $userIds)->get()->keyBy('id');

        return $this->mapUsers($tree, $users);
    }

    private function collectUserIds(array $tree): array
    {
        $ids = [];

        foreach ($tree as $node) {
            if (!empty($node['authorId'])) {
                $ids[] = $node['authorId'];
            }

            if (!empty($node['children'])) {
                $ids = array_merge($ids, $this->collectUserIds($node['children']));
            }
        }

        return array_unique($ids);
    }

    private function mapUsers(array $tree, $users): array
    {
        foreach ($tree as &$node) {
            $user = $users[$node['authorId']] ?? null;

            $node['user'] = [
                'id' => $node['authorId'],
                'name' => $user->name ?? 'Anonymous'
            ];

            if (!empty($node['children'])) {
                $node['children'] = $this->mapUsers($node['children'], $users);
            }
        }

        return $tree;
    }

    public function sort(array $tree): array
    {
        usort($tree, function ($a, $b) {
            if (($a['isBest'] ?? false) && !($b['isBest'] ?? false)) return -1;
            if (!($a['isBest'] ?? false) && ($b['isBest'] ?? false)) return 1;

            return strtotime($b['createdAt'] ?? 'now')
                - strtotime($a['createdAt'] ?? 'now');
        });

        return $tree;
    }
}
