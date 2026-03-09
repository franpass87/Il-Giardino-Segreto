<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Helper;

/**
 * Badge di fiducia predefiniti per le pagine prodotto tour.
 */
final class TrustBadges
{
    /** @return array<string, array{icon: string, it: string, en: string}> */
    public static function getAll(): array
    {
        $badges = [
            'secure_payment' => [
                'icon' => '🔒',
                'it' => 'Pagamento sicuro',
                'en' => 'Secure payment',
            ],
            'flexible_cancellation' => [
                'icon' => '📅',
                'it' => 'Cancellazione flessibile',
                'en' => 'Flexible cancellation',
            ],
            'best_price' => [
                'icon' => '💰',
                'it' => 'Miglior prezzo garantito',
                'en' => 'Best price guarantee',
            ],
            'verified_reviews' => [
                'icon' => '⭐',
                'it' => 'Recensioni verificate',
                'en' => 'Verified reviews',
            ],
            'no_card_booking' => [
                'icon' => '📋',
                'it' => 'Prenota senza carta',
                'en' => 'Book without card',
            ],
            'support_24' => [
                'icon' => '🛟',
                'it' => 'Assistenza 24/7',
                'en' => '24/7 support',
            ],
        ];
        return apply_filters('igs_trust_badges', $badges);
    }

    /** @return array<string, array{icon: string, it: string, en: string}> */
    public static function getForProduct(\WC_Product $product): array
    {
        $all = self::getAll();
        $ids = get_post_meta($product->get_id(), '_igs_trust_badges', true);
        if (!is_array($ids) || empty($ids)) {
            return [];
        }
        $result = [];
        foreach ($ids as $id) {
            if (is_string($id) && isset($all[$id])) {
                $result[$id] = $all[$id];
            }
        }
        return $result;
    }
}
