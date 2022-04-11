<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Commands\Configurators;

use Hammerstone\Sidecar\Vercel\Client;
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

        $token = $this->secret('Paste your Vercel token, or press enter to skip');
        $team = $token ? $this->selectTeam($token) : '';

        $this->line(' ');
        $this->info('Done! Here are your environment variables:');
        $this->line('SIDECAR_VERCEL_TOKEN=' . $token);
        $this->line('SIDECAR_VERCEL_TEAM=' . $team);
        $this->line('SIDECAR_VERCEL_DOMAIN_SEED=' . Str::random(16));
        $this->line('SIDECAR_VERCEL_SIGNING_SECRET=' . Str::random(40));
        $this->line(' ');
        $this->info('They will work in any environment.');
    }

    protected function selectTeam($token)
    {
        $vercel = new Client([
            'base_uri' => 'https://api.vercel.com',
            'allow_redirects' => true,
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ]
        ]);

        $teams = collect($vercel->listTeams()['teams'])->mapWithKeys(function ($team) {
            return [$team['id'] => $team['name']];
        });

        if (!count($teams)) {
            return;
        }

        $teams['personal'] = 'Personal Account';

        $team = $this->choice(
            'You are a part of one or more teams. Where would you like your functions deployed?',
            $teams->values()->toArray()
        );

        $id = $teams->flip()[$team];

        if ($id === 'personal') {
            return;
        }

        return $id;
    }
}
