<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Purchase;
use App\Enum\PayPalStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class PurchaseFixtures extends Fixture
{
    public const string PURCHASE_1 = 'purchase-1';

    public function load(ObjectManager $manager): void
    {
        $orderId = uniqid('ORDER', true);
        $purchase = new Purchase()
            ->setOrderId($orderId)
            ->setStatus(PayPalStatus::CREATED);

        $manager->persist($purchase);
        $manager->flush();

        $this->addReference(self::PURCHASE_1, $purchase);
    }
}
