<?php

namespace spicyweb\embeddedassets\actions;

use Craft;
use craft\base\ElementAction;
use craft\elements\Asset;
use craft\elements\db\ElementQueryInterface;
use spicyweb\embeddedassets\Plugin as EmbeddedAssets;
use spicyweb\embeddedassets\errors\RefreshException;

/**
 * Refresh element action.
 *
 * @package spicyweb\embeddedassets\actions
 * @author Spicy Web <plugins@spicyweb.com.au>
 * @since 4.2.0
 */
class Refresh extends ElementAction
{
    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('embeddedassets', 'Refresh');
    }

    /**
     * @inheritdoc
     */
    public function getTriggerHtml(): ?string
    {
        Craft::$app->getView()->registerJsWithVars(fn($type, $assetClassName) => <<<JS
(() => {
    new Craft.ElementActionTrigger({
        type: $type,
        bulk: true,
        validateSelection: \$selectedItems => \$selectedItems.find('.element[data-type=$assetClassName]').get().every(selectedItem => selectedItem.hasAttribute('data-embedded-asset')),
    });
})();
JS, [static::class, str_replace('\\', '\\\\', Asset::class)]);

        return Craft::$app->getView()->renderTemplate('embeddedassets/_components/elementactions/refresh/trigger.twig');
    }

    /**
     * @inheritdoc
     */
    public function performAction(ElementQueryInterface $query): bool
    {
        $anySuccess = false;
        $anyFail = false;

        foreach ($query->all() as $asset) {
            try {
                EmbeddedAssets::$plugin->methods->refreshEmbeddedAsset($asset);
                $anySuccess = true;
            } catch (RefreshException $e) {
                $anyFail = true;
            }
        }

        if (!$anySuccess && $anyFail) {
            $this->setMessage(Craft::t('embeddedassets', 'Embedded assets not refreshed.'));
            return false;
        }

        if ($anyFail) {
            $this->setMessage(Craft::t('embeddedassets', 'Some embedded assets refreshed.'));
        } else {
            $this->setMessage(Craft::t('embeddedassets', 'Embedded assets refreshed.'));
        }

        return true;
    }
}
