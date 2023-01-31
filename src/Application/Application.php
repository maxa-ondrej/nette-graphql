<?php declare(strict_types = 1);

namespace Maxa\Ondrej\Nette\GraphQL\Application;

use GraphQL\Error\ClientAware;
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
    private function parseJson(string|false|null $json): array {
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
        if ($debug && Debugger::$productionMode) {
            echo '<h1>Do not debug this application in production mode!</h1>';
            return;
        }
        try {
            $response = $this->processRequest(
                is_string(@$request['query']) ? (string) $request['query'] : '',
                is_array(@$request['variables']) ? (array) $request['variables'] : [],
                !$debug,
            );
            if ($debug) {
                Debugger::getBar()->addPanel(new GraphQLPanel($this->schema, $request, $response));
                dumpe($response);
            }

            header('Content-Type: application/json');
            echo json_encode($response);
        } catch (ClientAware $exception) {
            Debugger::getBlueScreen()
                ->addPanel(fn () => [
                    'tab' => 'Error',
                    'panel' => Debugger::dump($exception, true),
                ])
                ->addPanel(fn () => [
                    'tab' => 'Schema',
                    'panel' => Debugger::dump($this->schema, true),
                ])
                ->render($exception);
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
        $debugResponse = DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE;

        try {
            return GraphQL::executeQuery($this->schema, $query, null, null, $variables)
            ->setErrorsHandler(
                static fn (array $errors, callable $formatter) => self::handleErrors($errors, $formatter)
            )
            ->toArray(
                $catchExceptions ? (Debugger::$productionMode ? DebugFlag::NONE : $debugResponse) : DebugFlag::RETHROW_INTERNAL_EXCEPTIONS,
            );
        } catch (Throwable $exception) {
            if ($catchExceptions) {
                return [
                    'errors' => [
                        [
                            'message' => $exception->getMessage(),
                            'extensions' => [
                                'category' => 'graphql',
                            ],
                        ],
                    ],
                ];
            }

            throw $exception;
        }
    }

    /**
     * @param array<Error> $errors
     * @param callable $formatter
     * @return array
     */
    private static function handleErrors(array $errors, callable $formatter): array {
        foreach ($errors as $error) {
            assert($error instanceof Error);
            Debugger::log(
                is_object($error->getPrevious()) ? $error->getPrevious() : $error,
                'GraphQLite',
            );
        }

        $error = $errors[0]->getPrevious() ?? $errors[0];
        http_response_code($error instanceof ClientAware ? ($error->getCode() ?: 400) : 500);
        return array_map($formatter, $errors);
    }

}
