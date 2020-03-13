<?php

namespace L5Swagger;

use L5Swagger\Exceptions\L5SwaggerException;

class GeneratorFactory
{
    /**
     * Make Generator Instance.
     *
     * @throws L5SwaggerException
     *
     * @return Generator
     */
    public function make(string $documentation): Generator
    {
        $config = $this->documentationConfig($documentation);

        $annotationsDir = $config['paths']['annotations'];
        $docDir = $config['paths']['docs'];
        $docsFile = $docDir . '/' . ($config['paths']['docs_json'] ?? 'api-docs.json');
        $yamlDocsFile = $docDir . '/' . ($config['paths']['docs_yaml'] ?? 'api-docs.yaml');
        $excludedDirs = $config['paths']['excludes'];
        $basePath = $config['paths']['base'];
        $constants = $config['constants'] ?: [];
        $yamlCopyRequired = $config['generate_yaml_copy'] ?? false;
        $swaggerVersion = $config['swagger_version'];

        $security = new SecurityDefinitions($swaggerVersion, $config['security']);

        return new Generator(
            $annotationsDir,
            $docDir,
            $docsFile,
            $yamlDocsFile,
            $excludedDirs,
            $constants,
            $yamlCopyRequired,
            $basePath,
            $swaggerVersion,
            $security
        );
    }

    /**
     * Get documentation config.
     *
     * @throws L5SwaggerException
     *
     * @return array
     */
    protected function documentationConfig(string $documentation): array
    {

        if ($documentation === 'legacy') {
            return $this->legacyConfig();
        }

        $documentations = config('l5-swagger.documentations');

        if (!isset($documentations[$documentation])) {
            throw new L5SwaggerException('Documentation config not found');
        }

        return $documentations[$documentation];
    }

    /**
     * Get default config.
     *
     * @return array
     */
    protected function legacyConfig(): array
    {
        $config = config('l5-swagger');

        unset($config['documentations']);

        return $config;
    }
}
