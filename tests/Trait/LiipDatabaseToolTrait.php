<?php

declare(strict_types=1);

namespace App\Tests\Trait;

use App\DataFixtures\ProductFixtures;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;

trait LiipDatabaseToolTrait
{
    private AbstractDatabaseTool $databaseTool;

    protected function initDatabaseTool(): void
    {
        /** @var DatabaseToolCollection $databaseToolCollection */
        $databaseToolCollection = self::getContainer()->get(DatabaseToolCollection::class);
        $databaseTool = $databaseToolCollection->get();
        $this->databaseTool = $databaseTool;
        $this->databaseTool->loadFixtures([ProductFixtures::class]);
    }
}
