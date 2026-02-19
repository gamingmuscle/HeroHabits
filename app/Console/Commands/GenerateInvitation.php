<?php

namespace App\Console\Commands;

use App\Models\Invitation;
use Illuminate\Console\Command;

class GenerateInvitation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invitation:generate
                            {--count=1 : Number of invitations to generate}
                            {--days=30 : Days until expiration (0 = never)}
                            {--email= : Email of who created this invitation (optional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate invitation code(s) for parent registration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = (int) $this->option('count');
        $days = (int) $this->option('days');
        $email = $this->option('email');

        if ($count < 1 || $count > 100) {
            $this->error('Count must be between 1 and 100');
            return 1;
        }

        $this->info("Generating {$count} invitation code(s)...");
        $this->newLine();

        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $invitation = Invitation::generate($days);

            if ($email) {
                $invitation->update(['created_by_email' => $email]);
            }

            $codes[] = [
                'Code' => $invitation->code,
                'Expires' => $invitation->expires_at ? $invitation->expires_at->format('Y-m-d H:i') : 'Never',
            ];
        }

        $this->table(['Code', 'Expires'], $codes);

        $this->newLine();
        $this->info("✅ Generated {$count} invitation code(s) successfully!");

        if ($days > 0) {
            $this->comment("Codes will expire in {$days} days");
        } else {
            $this->comment("Codes will never expire");
        }

        return 0;
    }
}
