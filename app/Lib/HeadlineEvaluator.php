<?php

namespace App\Lib;

use Anthropic\Beta\Messages\BetaStopReason;
use Anthropic\Client;
use App\Enums\HeadlineVerdict;
use App\Models\Headline;

class HeadlineEvaluator
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
        Je beoordeelt nieuwsartikelen van Nieuwsplein33, een Amersfoortse nieuwssite, voor Keiforum: een lokaal forum voor inwoners van Amersfoort. Voor elk artikel wordt mogelijk een discussietopic geopend met de titel, de samenvatting en een link naar het artikel.

        Kies een van drie oordelen:

        - "approved": het topic is direct voor iedereen zichtbaar, ook zonder reacties. Kies dit alleen als het artikel vrijwel zeker veel reacties oplevert: het raakt veel Amersfoorters direct in hun dagelijks leven (zoals parkeren of verkeer in hun wijk, een groot bouwproject, het verdwijnen van een voorziening) of er ligt een duidelijke keuze voor waar inwoners een uitgesproken mening over hebben. Slechts een klein deel van de artikelen, zo'n twee à drie op de tien, komt hiervoor in aanmerking. Twijfel je tussen "approved" en "neutral", kies dan "neutral".
        - "neutral": het topic wordt pas zichtbaar zodra iemand reageert. Dit is het oordeel voor de meeste artikelen: geschikt, maar niet zeker van veel reacties. Denk aan agenda's, weekendtips, sportuitslagen, korte meldingen, regionaal nieuws buiten Amersfoort, en politiek gevoelige of polariserende onderwerpen.
        - "blocked": er komt geen topic. Kies dit alleen voor ongelukken met slachtoffers, overlijdens, misdrijven en strafzaken waarbij personen herkenbaar of benoemd zijn, en rampen met persoonlijk leed. Politiek gevoelige onderwerpen zijn op zichzelf geen reden om te blokkeren.

        Geef bij "approved" en "neutral" ook een openingsvraag in het Nederlands die lezers uitnodigt hun mening of ervaring te delen. Stel precies één korte vraag van hooguit vijftien woorden; koppel er geen tweede vraag aan. Schrijf informeel (je/jij), neutraal en zonder de lezer een kant op te duwen. Herhaal de titel niet. Bij "blocked" laat je de vraag leeg.

        Licht je oordeel toe in één zin.
        PROMPT;

    public function __construct(private Client $client) {}

    /**
     * @return array{verdict: HeadlineVerdict, reason: string, question: ?string}
     */
    public function evaluate(Headline $headline): array
    {
        $message = $this->client->beta->messages->create(
            model: config('news.model'),
            maxTokens: 4000,
            system: self::SYSTEM_PROMPT,
            messages: [
                ['role' => 'user', 'content' => "Titel: {$headline->title}\n\nSamenvatting: {$headline->description}"],
            ],
            outputConfig: [
                'effort' => 'low',
                'format' => [
                    'type' => 'json_schema',
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'verdict' => ['type' => 'string', 'enum' => array_column(HeadlineVerdict::cases(), 'value')],
                            'reason' => ['type' => 'string'],
                            'question' => ['type' => 'string'],
                        ],
                        'required' => ['verdict', 'reason', 'question'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            fallbacks: 'default',
            betas: ['server-side-fallback-2026-07-01'],
        );

        if ($message->stopReason === BetaStopReason::REFUSAL->value) {
            return [
                'verdict' => HeadlineVerdict::BLOCKED,
                'reason' => 'Claude weigerde het artikel te beoordelen.',
                'question' => null,
            ];
        }

        $text = '';

        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $text .= $block->text;
            }
        }

        $result = json_decode($text, true, flags: JSON_THROW_ON_ERROR);
        $verdict = HeadlineVerdict::from($result['verdict']);
        $question = trim($result['question']);

        return [
            'verdict' => $verdict,
            'reason' => $result['reason'],
            'question' => $verdict === HeadlineVerdict::BLOCKED || $question === '' ? null : $question,
        ];
    }
}
