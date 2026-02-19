<?php

namespace App\Console\Commands;

use App\Models\Invitation;
use Illuminate\Console\Command;

class ListInvitations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invitation:list
                            {--status=all : Filter by status (all, valid, used, expired)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all invitation codes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $status = $this->option('status');

        $query = Invitation::query()->orderBy('created_at', 'desc');

        switch ($status) {
            case 'valid':
                $query->valid();
                $this->info('Showing valid invitations:');
                break;
            case 'used':
                $query->used();
                $this->info('Showing used invitations:');
                break;
            case 'expired':
                $query->expired();
                $this->info('Showing expired invitations:');
                break;
            default:
                $this->info('Showing all invitations:');
                break;
        }

        $invitations = $query->get();

        if ($invitations->isEmpty()) {
            $this->warn('No invitations found');
            return 0;
        }

        $data = $invitations->map(function ($invitation) {
            $statusLabel = 'Valid';
            if ($invitation->used_at) {
                $statusLabel = '✓ Used';
            } elseif ($invitation->expires_at && $invitation->expires_at->isPast()) {
                $statusLabel = '✗ Expired';
            }

            return [
                'Code' => $invitation->code,
                'Status' => $statusLabel,
                'Expires' => $invitation->expires_at ? $invitation->expires_at->format('Y-m-d') : 'Never',
                'Used By' => $invitation->used_at ? "User #{$invitation->used_by}" : '-',
                'Created' => $invitation->created_at->format('Y-m-d'),
            ];
        })->toArray();

        $this->table(
            ['Code', 'Status', 'Expires', 'Used By', 'Created'],
            $data
        );

        $this->newLine();
        $this->info("Total: {$invitations->count()} invitation(s)");

        return 0;
    }
}
