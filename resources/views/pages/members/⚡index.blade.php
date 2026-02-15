<?php

use App\Models\User;
use Livewire\Component;

new class extends Component
{
    public function memberCount (): int
    {
        return User::count();
    }

    public function render()
    {
        return $this->view()
            ->title(__('members/title.title'));
    }
};
?>

<div>
    <header class="page__header">
        <x-path />
        <h1>@lang('members/title.title')</h1>
    </header>
    <div class="panel panel--padded">
        <div class="flex flex-col flex-gap-l">
            <h2>Wordt aan gewerkt</h2>
            <div class="formatted">
                <p>
                    Keiforum is nog volop in ontwikkeling en ik vind het geweldig dat je nu al komt kijken!
                    Op deze pagina vind je straks een handig, doorzoekbaar overzicht van alle Amersfoorters die zich hier hebben aangemeld.
                    Op dit moment kan ik je alleen vertellen dat het er <b>{{ $this->memberCount() }}</b> zijn en daar ben ik al heel trots op 😌
                </p>
                <p>
                    Help vooral mee om meer Amersfoorters op Keiforum te krijgen!
                </p>
                <p>
                    — <a href="{{  route('user.show', 'keiforum') }}">Alexander</a>
                </p>
            </div>
        </div>
    </div>
</div>
