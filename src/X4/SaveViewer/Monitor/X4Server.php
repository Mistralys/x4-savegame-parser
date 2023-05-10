<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Monitor;

use AppUtils\ConvertHelper_Exception;
use AppUtils\FileHelper;
use AppUtils\FileHelper\FileInfo;
use AppUtils\FileHelper_Exception;
use Mistralys\X4\SaveViewer\SaveViewer;
use Mistralys\X4\UI\UserInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Socket\SocketServer;
use Throwable;
use function AppUtils\parseThrowable;
use function AppUtils\parseURL;

class X4Server extends BaseMonitor
{
    public const ALLOWED_EXTENSIONS = array(
        'js',
        'html',
        'css',
        'md',
        'txt',
        'json',
        'map',

        // Fonts
        'otf',
        'woff',
        'woff2',
        'eot',
        'ttf',

        // Images
        'svg',
        'jpg',
        'png',
        'ico'
    );

    private UserInterface $ui;
    private SaveViewer $app;
    private int $requestCounter = 0;

    protected function setup() : void
    {
        $this->app = new SaveViewer();
        $this->ui = new UserInterface($this->app, 'http://'.X4_SERVER_HOST.':'.X4_SERVER_PORT);

        $server = new HttpServer($this->loop, array($this, 'handleRequest'));

        $socket = new SocketServer(X4_SERVER_HOST.':'.X4_SERVER_PORT, array(), $this->loop);
        $server->listen($socket);

        $this->logHeader('X4 Savegame server');
        $this->log('Listening on [%s].', str_replace('tcp:', 'http:', $socket->getAddress()));
        $this->log('');
    }

    public function handleRequest(ServerRequestInterface $request) : Response
    {
        $this->requestCounter++;

        $this->logHeader('Request %s', $this->requestCounter);

        $response =  $this->handleRequestTarget(
            $request->getRequestTarget(),
            array_merge($request->getQueryParams(), $request->getServerParams())
        );

        $this->log('Request [%s] | Sending response code [%s].', $this->requestCounter, $response->getStatusCode());
        $this->log('');

        return $response;
    }

    /**
     * Analyzes the target of the request, to determine if we should
     * serve a specific file or display the user interface.
     *
     * @param string $target
     * @param array<string,string|number|array> $requestVars
     * @return Response
     *
     * @throws ConvertHelper_Exception
     * @throws FileHelper_Exception
     */
    private function handleRequestTarget(string $target, array $requestVars) : Response
    {
        $info = parseURL($target);
        $path = $info->getPath();

        $this->log('Request [%s] | Target: [%s]', $this->requestCounter, $target);

        if($path === '/') {
            return $this->handleUIRequest($requestVars);
        }

        return $this->handlePathRequest(trim($path, '/'));
    }

    /**
     * Handle a path to a specific file.
     *
     * @param string $path The target path, e.g. <code>favicon.ico<code>, <code>path/to/file.html</code>.
     * @return Response
     * @throws FileHelper_Exception
     */
    private function handlePathRequest(string $path) : Response
    {
        $this->log('Request [%s] | Handling path | [%s]', $this->requestCounter, $path);

        $parts = explode('/', $path);
        if(empty($parts)) {
            return new Response(
                Response::STATUS_BAD_REQUEST
            );
        }

        if($parts[0] === 'vendor') {
            return $this->handleVendorAssetRequest($path);
        }

        return new Response(
            Response::STATUS_OK,
            array(
                'Content-Type' => 'text/html'
            ),
            'Target'
        );
    }

    /**
     * Handles a request to a file from the <code>vendor</code> folder.
     * Resolves the actual path on disk.
     *
     * @param string $path
     * @return Response
     * @throws FileHelper_Exception
     */
    private function handleVendorAssetRequest(string $path) : Response
    {
        return $this->responseFile(__DIR__.'/../../../../'.$path);
    }

    /**
     * Serves a file content.
     *
     * @param string $path
     * @return Response
     * @throws FileHelper_Exception
     */
    private function responseFile(string $path) : Response
    {
        $real = realpath($path);

        if($real === false)
        {
            $this->log('File not found: [%s]', basename($path));

            return $this->responseNotFound(sprintf(
                'File [%s] not found.',
                basename($path)
            ));
        }

        $file = FileInfo::factory($real);

        $ext = $file->getExtension();
        if(!in_array($ext, self::ALLOWED_EXTENSIONS, true))
        {
            $this->log('File extension [%s] not allowed.', $ext);

            $this->responseNotAllowed(sprintf(
                'File type [%s] not supported',
                $ext
            ));
        }

        $headers = array(
            'Content-Type' => FileHelper::detectMimeType($file->getName())
        );

        $date = $file->getModifiedDate();
        if($date !== null) {
            $headers['Last-Modified'] = $date->format(
                'D, d M Y H:i:s e'
            );
        }

        return new Response(
            Response::STATUS_OK,
            $headers,
            $file->getContents()
        );
    }

    private function responseNotFound(string $reason) : Response
    {
        return new Response(
            Response::STATUS_NOT_FOUND,
            array(),
            '',
            '1.1',
            $reason
        );
    }

    private function responseNotAllowed(string $reason) : Response
    {
        return new Response(
            Response::STATUS_UNAUTHORIZED,
            array(),
            '',
            '1.1',
            $reason
        );
    }

    /**
     * Handles a request to the save viewer user interface.
     *
     * @param array<string,string|number|array> $requestVars
     * @return Response
     * @throws ConvertHelper_Exception
     */
    private function handleUIRequest(array $requestVars) : Response
    {
        $this->log('Request [%s] | Handling UI rendering.', $this->requestCounter);

        // Simulate a regular request environment. Since the
        // request object uses the $_REQUEST variable exclusively,
        // there is no need to handle post variables separately.
        $_REQUEST = $requestVars;

        try
        {
            $content = $this->ui->render();
        }
        catch (Throwable $e)
        {
            $content = parseThrowable($e)->renderErrorMessage(true);
        }

        return new Response(
            200,
            array(
                'Content-Type' => 'text/html'
            ),
            $content
        );
    }

    protected function _handleTick() : void
    {
        // The UI does not process any data.
    }
}
