<?php
/**
 * ContactCycleHelper.php - Fälligkeits-Ampel aus Kontaktzyklus + letzter Interaktion
 * (siehe concept.md 4.7). Reine Funktion, ohne DB-Zugriff testbar.
 */
class ContactCycleHelper
{
    private const CYCLE_DAYS = [
        'WEEKLY'        => 7,
        'BIWEEKLY'      => 14,
        'MONTHLY'       => 30,
        'QUARTERLY'     => 90,
        'SEMI_ANNUALLY' => 182,
        'ANNUALLY'      => 365,
    ];

    /**
     * @return array{color: string, label: string}
     */
    public static function getStatus(?string $lastInteractionDate, ?string $contactCycle): array
    {
        if (empty($contactCycle) || !isset(self::CYCLE_DAYS[$contactCycle])) {
            return ['color' => 'gray', 'label' => 'Kein Kontaktzyklus definiert'];
        }

        if (empty($lastInteractionDate)) {
            return ['color' => 'red', 'label' => 'Noch nie Kontakt gehabt'];
        }

        $cycleDays = self::CYCLE_DAYS[$contactCycle];
        $daysSinceContact = (int) floor((time() - strtotime($lastInteractionDate)) / 86400);

        if ($daysSinceContact <= $cycleDays) {
            return ['color' => 'green', 'label' => 'Kontakt aktuell'];
        }

        if ($daysSinceContact <= $cycleDays * 2) {
            return ['color' => 'yellow', 'label' => 'Überfällig'];
        }

        return ['color' => 'red', 'label' => 'Stark überfällig'];
    }
}
