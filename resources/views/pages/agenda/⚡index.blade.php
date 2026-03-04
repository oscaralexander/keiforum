<?php

use Livewire\Component;

new class extends Component
{
    public function render()
    {
        return $this->view()
            ->title(__('agenda/index.title'));
    }
};
?>

<div>
    <x-header hide-path :title="__('agenda/index.title')" />
    <div class="panel panel--padded">
        <div class="flex flex-col flex-gap-l">
            <div class="formatted">
                <p>
                    Keiforum is nog volop in ontwikkeling en ook dit onderdeel is nog lang niet klaar,
                    maar leuk dat je al even komt kijken!
                </p>
                <p>
                    Hier vind je straks een overzicht van evenementen en activiteiten in Amersfoort.
                    Van festivals tot vlooienmarkten; bandjes tot bierproeverijen.
                    De Amersfoortse horeca en cultuursector kunnen hier zelf hun evenementen toevoegen.
                </p>
                <p>
                    Wil je hier alvast meer over weten? <a href="mailto:mail@keiforum.nl">Neem even contact op.</a>
                </p>
            </div>
        </div>
    </div>
</div>
