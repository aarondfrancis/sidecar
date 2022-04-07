<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Commands;

use Illuminate\Support\Str;

trait ConfigureVercel
{

    public function configureVercel()
    {
        $this->line(str_repeat('-', $this->width));
        $this->text('This interactive command will set up your Sidecar credentials for Vercel.');
        $this->line('');
        $this->text("The first thing you'll need is a Vercel token, which you can generate here:");
        $this->text('https://vercel.com/account/tokens');
        $this->line(str_repeat('-', $this->width));
        $this->line('');

        $token = $this->ask('Paste your Vercel token, or press enter to skip');

        $this->line(' ');
        $this->info('Done! Here are your environment variables:');
        $this->line('SIDECAR_VERCEL_TOKEN=' . $token);
        $this->line('SIDECAR_VERCEL_DOMAIN_SEED=' . Str::random(16));
        $this->line('SIDECAR_VERCEL_SIGNING_SECRET=' . Str::random(40));
        $this->line(' ');
        $this->info('They will work in any environment.');
    }


}