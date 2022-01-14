<?php declare(strict_types = 1);

namespace Maxa\Ondrej\Nette\GraphQL\Tracy;

use Nette\Utils\Html;
use function is_string;

class TracyPanelTab {

    private Html $main;

    public function __construct(string $name, string $title, ?string $imageUrl = null) {
        $this->main = Html::el('span', [
            'title' => $title,
        ]);
        $title = Html::el('span', [
            'class' => 'tracy-label',
        ]);
        $title->setText($name);
        if (is_string($imageUrl)) {
            $this->main->addHtml(Html::el('img', [
                'src' => $imageUrl,
                'alt' => '',
                'width' => 15,
                'height' => 15,
            ]));
        }

        $this->main->addHtml($title);
    }

    final public function toHtml(): string {
        return $this->main->toHtml();
    }

}
