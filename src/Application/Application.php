<?php declare(strict_types = 1);

namespace Maxa\Ondrej\Nette\GraphQL\Application;

use GraphQL\Error\DebugFlag;
use GraphQL\Error\Error;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use JetBrains\PhpStorm\NoReturn;
use Maxa\Ondrej\Nette\GraphQL\Tracy\GraphQLPanel;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLExceptionInterface;
use Tracy\Debugger;
use function array_key_exists;
use function array_map;
use function assert;
use function dumpe;
use function file_get_contents;
use function filter_input;
use function header;
use function http_response_code;
use function is_array;
use function is_object;
use function is_string;
use function json_encode;
use const INPUT_GET;

final class Application {

    public function __construct(private Schema $schema) {
    }

    /**
     * Dispatch a HTTP request to GraphQL
     */
    #[NoReturn]
    public function run(): void {
        $query = filter_input(INPUT_GET, 'query');
        $request = $query === null ? $this->parseJson(file_get_contents('php://input')) : [
            'query' => $query,
            'variables' => $this->parseJson(filter_input(INPUT_GET, 'variables')),
        ];

        $this->runRequest($request, is_string($query));
    }

    /**
     * @return array<mixed>
     */
    private function parseJson(string|false $json): array {
        try {
            return Json::decode(
                is_string($json) ? $json : '{}',
                Json::FORCE_ARRAY,
            );
        } catch (JsonException) {
            return [];
        }
    }

    /**
     * Dispatch a request to GraphQL
     *
     * @param array<string, string|array<string, string>> $request GraphQL Request
     */
    #[NoReturn]
    public function runRequest(array $request, bool $debug = false): void {
        try {
            $response = $this->processRequest(
                is_string($request['query']) ? (string) $request['query'] : '',
                is_array($request['parameters']) ? (array) $request['parameters'] : [],
                !$debug,
            );
            if ($debug) {
                Debugger::getBar()->addPanel(new GraphQLPanel($this->schema, $request, $response));
                dumpe($response);
            }

            $this->sendJson($response, array_key_exists('errors', $response) ? 500 : 200);
        } catch (GraphQLExceptionInterface $exception) {
            $screen = Debugger::getBlueScreen();
            $screen->addPanel(static fn () => [
                'tab' => 'Error',
                'panel' => Debugger::dump($exception, true),
            ]);
            $screen->render($exception);
        }
    }

    /**
     * Process a request by given body and return predicted result.
     * Useful for tests but also a core function of application.
     *
     * @param string $query the actual query
     * @param array<string, string> $variables variables
     * @return array<string, array<mixed>> GraphQL Response
     *
     * @throws GraphQLExceptionInterface only when rethrowing
     */
    public function processRequest(string $query, array $variables, bool $catchExceptions): array {
        return GraphQL::executeQuery($this->schema, $query, null, null, $variables)
            ->setErrorsHandler(self::getErrorsHandler())
            ->toArray($catchExceptions ? DebugFlag::NONE : DebugFlag::RETHROW_INTERNAL_EXCEPTIONS);
    }

    private static function getErrorsHandler(): callable {
        return static function (array $errors, callable $formatter): array {
            if (Debugger::$productionMode) {
                foreach ($errors as $error) {
                    assert($error instanceof Error);
                    Debugger::log(
                        is_object($error->getPrevious()) ? $error->getPrevious() : $error,
                        'GraphQLite',
                    );
                }
            }

            return array_map($formatter, $errors);
        };
    }

    /**
     * @param array<mixed> $json
     */
    #[NoReturn]
    public function sendJson(array $json, int $code): void {
        header('Content-Type: application/json');
        http_response_code($code);
        echo json_encode($json);
        exit;
    }

}
