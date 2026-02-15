<?php

use Livewire\Component;

new class extends Component
{
    public function render()
    {
        return $this->view()
            ->title(__('agenda/title.title'));
    }
};
?>

<div>
    <header class="page__header">
        <x-path />
        <h1>@lang('agenda/title.title')</h1>
    </header>
    <div class="panel panel--padded">
        <div class="flex flex-col flex-gap-l">
            <h2>Wordt aan gewerkt</h2>
            <div class="formatted">
                <p>
                    Keiforum is nog volop in ontwikkeling en ik vind het geweldig dat je nu al komt kijken!
                    Op deze pagina vind je straks een overzicht van evenementen en activiteiten in Amersfoort.
                    Van festivals tot vlooienmarkten; bandjes tot bierproeverijen.
                    De Amersfoortse horeca en cultuursector kunnen hier zelf hun evenementen toevoegen.
                </p>
                <p>
                    Wil je hier alvast meer over weten? <a href="mailto:mail@keiforum.nl">Neem even contact op.</a>
                </p>
                <p>
                    — <a href="{{  route('user.show', 'keiforum') }}">Alexander</a>
                </p>
            </div>
        </div>
    </div>
</div>
