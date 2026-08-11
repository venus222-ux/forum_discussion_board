<?php

namespace App\Jobs;

use App\Events\CommentFlagged;
use App\Events\CommentModerated;
use App\Models\Comment;
use App\Models\CommentFlag;
use App\Models\Thread;
use App\Services\AiModerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAiModerationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $commentId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $commentId)
    {
        $this->commentId = $commentId;
    }

    /**
     * Execute the job.
     */
    public function handle(AiModerationService $ai): void
    {
        $comment = Comment::find($this->commentId);
        if (! $comment) {
            return;
        }

        // Developer blacklist check
        $blacklist = ['fuck', 'shit', 'bitch']; // Configurable in future
        $containsBlacklisted = preg_match('/\b('.implode('|', $blacklist).')\b/i', $comment->content);

        // Run AI analysis
        $toxicity = $ai->analyze($comment->content);
        $hate = $ai->detectHateSpeech($comment->content);

        $updateData = [
            'ai_score' => $toxicity['toxicity_score'],
            'ai_label' => $toxicity['toxicity_label'],
            'ai_reason' => $toxicity['toxicity_reason'],
            'ai_reviewed' => true,
            'ai_hate_score' => $hate['hate_score'],
            'ai_hate_label' => $hate['hate_label'],
            'ai_hate_reason' => $hate['hate_reason'],
            'ai_hate_reviewed' => true,
        ];

        $autoHide = false;
        $autoFlag = false;
        $moderationReason = '';

        if ($containsBlacklisted) {
            $autoHide = true;
            $moderationReason = 'Blocked word detected';
        }

        if ($toxicity['toxicity_label'] === 'severe' || $hate['hate_label'] === 'hate') {
            $autoHide = true;
            $moderationReason = $moderationReason ? $moderationReason.' and AI detected severe toxicity or hate speech' : 'AI detected severe toxicity or hate speech';
        } elseif ($toxicity['toxicity_label'] === 'toxic' || $hate['hate_label'] === 'offensive') {
            $autoFlag = true;
            $moderationReason = 'AI detected potential toxic or offensive content';
        }

        $wasHidden = $comment->is_hidden;

        if ($autoHide && ! $comment->is_hidden) {
            $updateData['is_hidden'] = true;
            $updateData['status'] = 'hidden';
            $updateData['ai_auto_hidden'] = true;
            $updateData['moderation_reason'] = $moderationReason;
        }

        $comment->update($updateData);

        $thread = Thread::where('uuid', $comment->threadId)->first();

        if ($autoHide && ! $wasHidden) {
            broadcast(new CommentModerated(
                $this->commentId,
                true,
                $moderationReason,
                $thread->slug
            ))->toOthers();
        }

        if ($autoFlag && ! $comment->is_hidden) {
            // Prevent duplicate system flag
            if (! CommentFlag::where('comment_id', $this->commentId)->whereNull('user_id')->exists()) {
                CommentFlag::create([
                    'comment_id' => $this->commentId,
                    'user_id' => null,
                    'reason' => $moderationReason.': '.$toxicity['toxicity_reason'].' / '.$hate['hate_reason'],
                    'status' => 'pending',
                ]);

                // Check for auto-hide after system flag
                $count = CommentFlag::where('comment_id', $this->commentId)
                    ->where('status', 'pending')
                    ->count();

                $wasHidden = $comment->is_hidden;
                $hideUpdate = [];

                if ($comment->ai_hate_label === 'offensive' && $count >= 3) {
                    $hideUpdate = [
                        'is_hidden' => true,
                        'status' => 'hidden',
                        'moderation_reason' => 'AI hate detection + flags (including system)',
                    ];
                } elseif ($comment->ai_label === 'toxic' && $count >= 3) {
                    $hideUpdate = [
                        'is_hidden' => true,
                        'status' => 'hidden',
                        'moderation_reason' => 'AI + flags (including system)',
                    ];
                } elseif ($count >= 5) {
                    $hideUpdate = [
                        'is_hidden' => true,
                        'status' => 'hidden',
                        'moderation_reason' => 'Auto-hidden due to multiple flags (including system)',
                    ];
                }

                if (! empty($hideUpdate)) {
                    $comment->update($hideUpdate);
                    if (! $wasHidden) {
                        broadcast(new CommentModerated(
                            $this->commentId,
                            true,
                            $hideUpdate['moderation_reason'],
                            $thread->slug
                        ))->toOthers();
                    }
                }

                // Broadcast flag event
                broadcast(new CommentFlagged(
                    $this->commentId,
                    $count,
                    1,
                    null,  // System flag
                    $thread->slug
                ))->toOthers();
            }
        }
    }
}
