<?php

namespace L5Swagger;

use Illuminate\Support\Collection;

class SecurityDefinitions
{
    protected $swaggerVersion;
    protected $securityConfig;

    public function __construct(string $swaggerVersion, array $securityConfig = [])
    {
        $this->swaggerVersion = $swaggerVersion;
        $this->securityConfig = $securityConfig;
    }

    /**
     * Reads in the l5-swagger configuration and appends security settings to documentation.
     *
     * @param string $filename The path to the generated json documentation
     */
    public function generate($filename)
    {
        $securityConfig = $this->securityConfig;

        if (is_array($securityConfig) && ! empty($securityConfig)) {
            $documentation = collect(
                json_decode(file_get_contents($filename))
            );

            $openApi3 = version_compare($this->swaggerVersion, '3.0', '>=');

            $documentation = $openApi3 ?
                $this->generateOpenApi($documentation, $securityConfig) :
                $this->generateSwaggerApi($documentation, $securityConfig);

            file_put_contents(
                $filename,
                $documentation->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            );
        }
    }

    /**
     * Inject security settings for Swagger 1 & 2.
     *
     * @param Collection $documentation The parse json
     * @param array $securityConfig The security settings from l5-swagger
     *
     * @return Collection
     */
    protected function generateSwaggerApi(Collection $documentation, array $securityConfig)
    {
        $securityDefinitions = collect();
        if ($documentation->has('securityDefinitions')) {
            $securityDefinitions = collect($documentation->get('securityDefinitions'));
        }

        foreach ($securityConfig as $key => $cfg) {
            $securityDefinitions->offsetSet($key, self::arrayToObject($cfg));
        }

        $documentation->offsetSet('securityDefinitions', $securityDefinitions);

        return $documentation;
    }

    /**
     * Inject security settings for OpenApi 3.
     *
     * @param Collection $documentation The parse json
     * @param array $securityConfig The security settings from l5-swagger
     *
     * @return Collection
     */
    protected function generateOpenApi(Collection $documentation, array $securityConfig)
    {
        $components = collect();
        if ($documentation->has('components')) {
            $components = collect($documentation->get('components'));
        }

        $securitySchemes = collect();
        if ($components->has('securitySchemes')) {
            $securitySchemes = collect($components->get('securitySchemes'));
        }

        foreach ($securityConfig as $key => $cfg) {
            $securitySchemes->offsetSet($key, self::arrayToObject($cfg));
        }

        $components->offsetSet('securitySchemes', $securitySchemes);

        $documentation->offsetSet('components', $components);

        return $documentation;
    }

    /**
     * Converts an array to an object.
     *
     * @param $array
     *
     * @return object
     */
    public static function arrayToObject($array)
    {
        return json_decode(json_encode($array));
    }
}
