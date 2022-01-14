<?php declare(strict_types = 1);

namespace Maxa\Ondrej\Nette\GraphQL\Tracy;

use Nette\HtmlStringable;
use Nette\Utils\Html;

class TracyPanelContent {

    private Html $main;

    private Html $container;

    public function __construct(string $title) {
        $this->main = Html::el('div');
        $titleHtml = Html::el('h1');
        $titleHtml->setText($title);
        $this->main->addHtml($titleHtml);
        $this->container = Html::el('div', [
            'class' => 'tracy-inner-container',
        ]);
        $inner = Html::el('div', [
            'class' => 'tracy-inner',
        ]);
        $inner->addHtml($this->container);
        $this->main->addHtml($inner);
    }

    final public function addHtml(HtmlStringable|string $child): TracyPanelContent {
        $this->container->addHtml($child);

        return $this;
    }

    final public function toHtml(): string {
        return $this->main->toHtml();
    }

}
