<?php
/*
MIT License

Copyright (c) 2025 Jasson44

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

*/
declare(strict_types=1);

namespace xeonch\trident;

use pocketmine\data\bedrock\item\ItemTypeNames;
use pocketmine\data\bedrock\item\SavedItemData;
use pocketmine\data\SavedDataLoadingException;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\inventory\CreativeInventory;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\AsyncTask;
use pocketmine\world\format\io\GlobalItemDataHandlers;
use pocketmine\world\World;
use xeonch\trident\entity\TridentEntity;
use xeonch\trident\helper\ExtraVanillaItems;

class Main extends PluginBase
{

    public function onEnable(): void
    {
        EntityFactory::getInstance()->register(TridentEntity::class, function (World $world, CompoundTag $compoundTag): TridentEntity {
            $itemTag = $compoundTag->getCompoundTag("Trident");
            if ($itemTag === null) {
                throw new SavedDataLoadingException("Expected \"Trident\" NBT tag not found");
            }

            $item = Item::nbtDeserialize($itemTag);
            if ($item->isNull()) {
                throw new SavedDataLoadingException("Trident Item is invalid");
            }
            return new TridentEntity(EntityDataHelper::parseLocation($compoundTag, $world), $item, null, $compoundTag);
        }, ['Trident', 'ThrownTrident', 'minecraft:trident', 'minecraft:trown_trident']);
        self::registerItems();
        $this->getServer()->getAsyncPool()->addWorkerStartHook(function (int $worker): void {
            $this->getServer()->getAsyncPool()->submitTaskToWorker(new class extends AsyncTask {
                public function onRun(): void
                {
                    Main::registerItems();
                }
            }, $worker);
        });
    }

    public static function registerItems(): void
    {
        $item = ExtraVanillaItems::TRIDENT();
        self::registerSimpleItem($item);
    }

    /**
     */
    private static function registerSimpleItem(Item $item): void
    {
        $stringToItemParserNames = ["trident"];
        GlobalItemDataHandlers::getDeserializer()->map(ItemTypeNames::TRIDENT, fn() => clone $item);
        GlobalItemDataHandlers::getSerializer()->map($item, fn() => new SavedItemData(ItemTypeNames::TRIDENT));

        if (!CreativeInventory::getInstance()->contains($item)) {
            CreativeInventory::getInstance()->add($item);
        }

        foreach ($stringToItemParserNames as $name) {
            StringToItemParser::getInstance()->register($name, fn() => clone $item);
        }
    }
}
