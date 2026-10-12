<?php
/**
 * Organic-gate conform Skal: conventioneel zaad is TOEGESTAAN wanneer
 * biologisch niet beschikbaar, maar vereist expliciete bevestiging en
 * een auditbaar "conventioneel"-event. Geen stilzwijgende blokkade.
 *
 * @return array ['allowed'=>bool,'conventional'=>bool,'reason'=>string]
 */
function organicGateEvaluate(array $seed, bool $confirmedConventional): array {
    $certified = ($seed['organic_status'] === 'CERTIFIED');
    $validCert = empty($seed['certificate_valid_until'])
                 || $seed['certificate_valid_until'] >= date('Y-m-d');

    if ($certified && $validCert) {
        return ['allowed'=>true, 'conventional'=>false, 'reason'=>''];
    }

    // Niet biologisch geldig -> conventioneel spoor, mits bevestigd.
    $why = !$certified
        ? "status '{$seed['organic_status']}'"
        : "certificaat verlopen ({$seed['certificate_valid_until']})";

    if (!$confirmedConventional) {
        return [
            'allowed'=>false, 'conventional'=>true,
            'reason'=>"Zaad is niet biologisch geldig ($why). Bevestig expliciet om conventioneel zaad te gebruiken.",
        ];
    }
    return [
        'allowed'=>true, 'conventional'=>true,
        'reason'=>"Conventioneel zaad gebruikt ($why), bevestigd door gebruiker.",
    ];
}
