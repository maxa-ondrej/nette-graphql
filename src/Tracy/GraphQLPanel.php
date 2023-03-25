<?php declare(strict_types = 1);

namespace Maxa\Ondrej\Nette\GraphQL\Tracy;

use GraphQL\Type\Schema;
use Nette\Utils\Html;
use Tracy\Debugger;
use Tracy\IBarPanel;

final class GraphQLPanel implements IBarPanel {

    /**
     * @param Schema $schema The GraphQL schema
     * @param array<string, string|array<string, string>> $request GraphQL Request
     * @param array<string, array<mixed>> $response GraphQL Response
     */
    public function __construct(public Schema $schema, public array $request, public array $response) {
    }

    public function getTab(): string {
        return (new TracyPanelTab(
            'GraphQL',
            'Informace of GraphQL pozadavku a odpovedi',
            'https://upload.wikimedia.org/wikipedia/commons/1/17/GraphQL_Logo.svg',
        ))->toHtml();
    }

    public function getPanel(): string {
        $content = new TracyPanelContent('GraphQL');
        $request_div = Html::el('div', [
            'style' => 'margin-bottom: 10px;',
        ]);
        if (!empty($this->request)) {
            $request_div->addHtml('<b>Request</b>');
            $request_div->addHtml(Debugger::dump($this->request, true));
            $content->addHtml($request_div);
        }

        if (!empty($this->response)) {
            $response_div = Html::el('div');
            $response_div->addHtml('<b>Response</b>');
            $response_div->addHtml(Debugger::dump($this->response, true));
            $content->addHtml($response_div);
        }

        $schema_div = Html::el('div');
        $schema_div->addHtml('<b>Schema</b>');
        $schema_div->addHtml(Debugger::dump($this->schema, true));
        $content->addHtml($schema_div);

        return $content->toHtml();
    }

}
