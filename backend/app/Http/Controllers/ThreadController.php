<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Actions\Threads\ListThreadsByCategoryAction;
use App\Actions\Threads\RecentThreadsAction;
use App\Actions\Threads\MyThreadsAction;
use App\Actions\Threads\ShowThreadAction;
use App\Actions\Threads\CreateThreadAction;
use App\Actions\Threads\UpdateThreadAction;
use App\Actions\Threads\DeleteThreadAction;
use App\Actions\Threads\SearchThreadsAction;

class ThreadController extends Controller
{
    public function __construct(
        private ListThreadsByCategoryAction $listByCategory,
        private RecentThreadsAction $recentThreads,
        private MyThreadsAction $myThreads,
        private ShowThreadAction $showThread,
        private CreateThreadAction $createThread,
        private UpdateThreadAction $updateThread,
        private DeleteThreadAction $deleteThread,
        private SearchThreadsAction $searchThreads,
    ) {}

    public function index($categorySlug)
    {
        return response()->json(
            $this->listByCategory->execute($categorySlug)
        );
    }

    public function recent(Request $request)
    {
        return response()->json(
            $this->recentThreads->execute($request->query('page', 1))
        );
    }

    public function myThreads()
    {
        return response()->json(
            $this->myThreads->execute(auth()->user())
        );
    }

    public function show($slug)
    {
        return response()->json(
            $this->showThread->execute($slug, request()->ip())
        );
    }

    public function store(Request $request)
    {
        return response()->json(
            $this->createThread->execute($request->all(), auth()->id()),
            201
        );
    }

    public function update(Request $request, $slug)
    {
        return response()->json(
            $this->updateThread->execute($slug, $request->all(), auth()->id())
        );
    }

    public function destroy($slug)
    {
        return response()->json(
            $this->deleteThread->execute($slug, auth()->id())
        );
    }

    public function search(Request $request)
    {
        return response()->json(
            $this->searchThreads->execute(
                $request->query('q'),
                (int) $request->query('page', 1),
                $request->url(),
                $request->query()
            )
        );
    }
}
