<?php

namespace L5Swagger\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use L5Swagger\Exceptions\L5SwaggerException;
use L5Swagger\GeneratorFactory;

class SwaggerController extends BaseController
{
    /**
     * @var L5Swagger\GeneratorFactory
     */
    protected $generatorFactory;

    public function __construct(GeneratorFactory $generatorFactory)
    {
        $this->generatorFactory = $generatorFactory;
    }

    /**
     * Dump api-docs content endpoint. Supports dumping a json, or yaml file.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $file
     *
     * @return \Response
     */
    public function docs(\Illuminate\Http\Request $request, string $file = null)
    {
        $documentation = $request->offsetGet('documentation');
        $config = $request->offsetGet('config');

        $extension = 'json';
        $targetFile = $config['paths']['docs_json'] ?? 'api-docs.json';

        if (! is_null($file)) {
            $targetFile = $file;
            $extension = explode('.', $file)[1];
        }

        $filePath = $config['paths']['docs'].'/'.$targetFile;

        if ($config['generate_always'] || ! File::exists($filePath)) {
            $generator = $this->generatorFactory->make($documentation);

            try {
                $generator->generateDocs();
            } catch (\Exception $e) {
                Log::error($e);

                abort(
                    404,
                    sprintf(
                        'Unable to generate documentation file to: "%s". Please make sure directory is writable. Error: %s',
                        $filePath,
                        $e->getMessage()
                    )
                );
            }
        }

        $content = File::get($filePath);

        if ($extension === 'yaml') {
            return Response::make($content, 200, [
                'Content-Type' => 'application/yaml',
                'Content-Disposition' => 'inline',
            ]);
        }

        return Response::make($content, 200, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Display Swagger API page.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function api(\Illuminate\Http\Request $request)
    {
        $documentation = $request->offsetGet('documentation');
        $config = $request->offsetGet('config');

        if ($proxy = $config['proxy']) {
            if (! is_array($proxy)) {
                $proxy = [$proxy];
            }
            \Illuminate\Http\Request::setTrustedProxies($proxy, \Illuminate\Http\Request::HEADER_X_FORWARDED_ALL);
        }

        // Need the / at the end to avoid CORS errors on Homestead systems.
        $response = Response::make(
            view('l5-swagger::index', [
                'documentation' => $documentation,
                'secure' => Request::secure(),
                'urlToDocs' => route('l5-swagger.'.$documentation.'.docs', $config['paths']['docs_json'] ?? 'api-docs.json'),
                'operationsSorter' => $config['operations_sort'],
                'configUrl' => $config['additional_config_url'],
                'validatorUrl' => $config['validator_url'],
            ]),
            200
        );

        return $response;
    }

    /**
     * Display Oauth2 callback pages.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return string
     * @throws L5SwaggerException
     */
    public function oauth2Callback(\Illuminate\Http\Request $request)
    {
        $documentation = $request->offsetGet('documentation');

        return File::get(swagger_ui_dist_path($documentation, 'oauth2-redirect.html'));
    }
}
