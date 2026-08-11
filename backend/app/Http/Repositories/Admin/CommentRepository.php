<?php

namespace App\Http\Repositories\Admin;

use App\Models\Comment;
use MongoDB\BSON\UTCDateTime;

class CommentRepository
{
    public function count(): int
    {
        return Comment::count();
    }

    public function topComments($startDate): array
    {
        $startMongo = new UTCDateTime($startDate->timestamp * 1000);

        $results = Comment::raw(function ($collection) use ($startMongo) {
            return $collection->aggregate([
                ['$match' => ['createdAt' => ['$gte' => $startMongo]]],
                ['$sort' => ['upvotes' => -1]],
                ['$limit' => 10],
            ]);
        });

        return array_map(fn ($c) => [
            'id' => (string) ($c->_id ?? ''),
            'content' => $c->content ?? '',
            'upvotes' => $c->upvotes ?? 0,
            'downvotes' => $c->downvotes ?? 0,
            'author' => $c->authorId ?? 'Unknown',
        ], iterator_to_array($results));
    }
}
